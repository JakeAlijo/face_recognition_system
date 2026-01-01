from flask import Flask, request, jsonify
from flask_sqlalchemy import SQLAlchemy
from flask_cors import CORS
import cv2
import numpy as np
import base64
import face_recognition
from datetime import datetime
from PIL import Image
from io import BytesIO
import json
import logging

# Initialize logging
logging.basicConfig(level=logging.INFO)

# Initialize Flask app and database
app = Flask(__name__)
CORS(app)

# Configure MySQL connection
app.config['SQLALCHEMY_DATABASE_URI'] = 'mysql+pymysql://root:@localhost/face_recognition_db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
db = SQLAlchemy(app)

# Models
class Student(db.Model):
    index_number = db.Column(db.String(50), primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    course = db.Column(db.String(100))
    level = db.Column(db.String(20))
    face_encoding = db.Column(db.Text, nullable=False)  # Base64 encoded face encoding

class Attendance(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    student_id = db.Column(db.String(50), db.ForeignKey('student.index_number'), nullable=False)
    timestamp = db.Column(db.DateTime, default=datetime.utcnow)

# Fix base64 padding
def fix_base64_padding(b64_string):
    return b64_string + '=' * (-len(b64_string) % 4)

# Helper to convert image to encoding
def extract_face_encoding(image_data):
    try:
        image_data = fix_base64_padding(image_data)
        image_bytes = base64.b64decode(image_data)
        image_array = np.frombuffer(image_bytes, dtype=np.uint8)
        image = cv2.imdecode(image_array, cv2.IMREAD_COLOR)

        face_locations = face_recognition.face_locations(image)
        if not face_locations:
            logging.info("No face detected.")
            return None

        face_encoding = face_recognition.face_encodings(image, known_face_locations=face_locations)[0]
        return face_encoding

    except Exception as e:
        logging.error(f"Failed to extract face encoding: {str(e)}")
        return None

# Register a Student
@app.route("/register", methods=["POST"])
def register_student():
    data = request.json
    name = data.get("name")
    index_number = data.get("index_number")
    course = data.get("course")
    level = data.get("level")
    image_data = data.get("image")

    if not all([name, index_number, image_data]):
        return jsonify({"error": "Missing required fields"}), 400

    face_encoding = extract_face_encoding(image_data)
    if face_encoding is None:
        return jsonify({"error": "No face detected"}), 400

    encoded_face = base64.b64encode(face_encoding.tobytes()).decode("utf-8")

    new_student = Student(
        index_number=index_number,
        name=name,
        course=course,
        level=level,
        face_encoding=encoded_face
    )

    db.session.add(new_student)
    db.session.commit()

    return jsonify({"message": "Student registered successfully", "student_id": new_student.index_number}), 201

# Recognise Student and Mark Attendance
@app.route("/recognise", methods=["POST"])
def recognise_student():
    data = request.json
    image_data = data.get("image")

    if not image_data:
        return jsonify({"error": "Missing image"}), 400

    face_encoding = extract_face_encoding(image_data)
    if face_encoding is None:
        return jsonify({"error": "No face detected"}), 400

    students = Student.query.all()
    known_encodings = []
    student_info = []

    for student in students:
        if student is None or not student.face_encoding:
            continue
        try:
            stored_encoding = np.frombuffer(base64.b64decode(fix_base64_padding(student.face_encoding)), dtype=np.float64)
            known_encodings.append(stored_encoding)
            student_info.append({
                "id": student.index_number,
                "name": student.name,
                "index_number": student.index_number,
                "course": student.course,
                "level": student.level
            })
        except Exception as e:
            logging.error(f"Failed to decode encoding for student {student.index_number}: {e}")

    matches = face_recognition.compare_faces(known_encodings, face_encoding, tolerance=0.45)

    if True in matches:
        matched_index = matches.index(True)
        matched_student = student_info[matched_index]

        new_attendance = Attendance(student_id=matched_student["id"])
        db.session.add(new_attendance)
        db.session.commit()

        return jsonify({
            "message": f"Welcome, {matched_student['name']}!",
            "student_id": matched_student["id"],
            "name": matched_student["name"],
            "index_number": matched_student["index_number"],
            "course": matched_student["course"],
            "level": matched_student["level"]
        }), 200

    return jsonify({"error": "Unknown user"}), 404

# Get Attendance Records
@app.route("/attendance", methods=["GET"])
def get_attendance():
    try:
        records = Attendance.query.all()
        data = [{
            "id": r.id,
            "index_number": r.student_id,
            "timestamp": r.timestamp.strftime("%Y-%m-%d %H:%M:%S")
        } for r in records]
        return jsonify(data), 200
    except Exception as e:
        return jsonify({"error": f"Failed to fetch attendance: {str(e)}"}), 500

# Get All Students
@app.route("/students", methods=["GET"])
def get_students():
    try:
        students = Student.query.all()
        data = [{"index_number": s.index_number, "name": s.name} for s in students]
        return jsonify(data), 200
    except Exception as e:
        return jsonify({"error": f"Failed to fetch students: {str(e)}"}), 500

# Reset Attendance Records
@app.route("/attendance", methods=["DELETE"])
def reset_attendance():
    try:
        Attendance.query.delete()
        db.session.commit()
        return jsonify({"message": "Attendance records reset!"}), 200
    except Exception as e:
        return jsonify({"error": f"Failed to reset attendance: {str(e)}"}), 500

# Reset Students
@app.route("/students", methods=["DELETE"])
def reset_students():
    try:
        Attendance.query.delete()
        Student.query.delete()
        db.session.commit()
        return jsonify({"message": "All students and attendance records deleted!"}), 200
    except Exception as e:
        return jsonify({"error": f"Failed to reset students: {str(e)}"}), 500

# Add a new face for a student
@app.route('/add_face', methods=['POST'])
def add_face():
    data = request.json
    student_id = data.get('student_id')
    image_data = data.get('image')

    if not student_id or not image_data:
        return jsonify({"error": "Missing student ID or image"}), 400

    try:
        image = Image.open(BytesIO(base64.b64decode(image_data.split(',')[1])))
        rgb_image = np.array(image)

        encodings = face_recognition.face_encodings(rgb_image)
        if not encodings:
            return jsonify({"error": "No face detected"}), 400

        new_encoding = encodings[0]

        student = Student.query.get(student_id)
        if not student:
            return jsonify({"error": "Student not found"}), 404

        current_encoding = base64.b64decode(fix_base64_padding(student.face_encoding))
        encoding_array = np.frombuffer(current_encoding, dtype=np.float64)
        combined = np.vstack((encoding_array, new_encoding))

        student.face_encoding = base64.b64encode(combined[0].tobytes()).decode("utf-8")  # Only storing latest for now
        db.session.commit()

        return jsonify({"message": "Face added successfully!"}), 200

    except Exception as e:
        logging.error(f"Error in add_face: {e}")
        return jsonify({"error": f"Failed to add face: {str(e)}"}), 500

# Delete a Student
@app.route("/delete_student/<student_id>", methods=["DELETE"])
def delete_student(student_id):
    try:
        Attendance.query.filter_by(student_id=student_id).delete()
        student = Student.query.get(student_id)
        if student:
            db.session.delete(student)
            db.session.commit()
            return jsonify({"message": "Student deleted successfully"}), 200
        else:
            return jsonify({"error": "Student not found"}), 404
    except Exception as e:
        return jsonify({"error": f"Failed to delete student: {str(e)}"}), 500

# Run the app
if __name__ == "__main__":
    with app.app_context():
       # db.drop_all()
        db.create_all()
    app.run(debug=True)
