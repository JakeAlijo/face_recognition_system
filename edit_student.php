<?php
session_start();
$conn = new mysqli("localhost", "root", "", "face_recognition_db");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: attendance.php");
    exit;
}

$studentId = $_GET['id'] ?? '';
$student = $conn->query("SELECT * FROM student WHERE id = $studentId")->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = $conn->real_escape_string($_POST['student_name']);
    $conn->query("UPDATE student SET name = '$newName' WHERE id = $studentId");

    // Send new face to Python backend
    if (!empty($_POST['captured_image'])) {
        $imageData = $_POST['captured_image'];
        $apiUrl = "http://localhost:5000/add_face";
        $postData = json_encode(['student_id' => $studentId, 'image' => $imageData]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
    }
    header("Location: attendance.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <title>Edit Student</title>
</head>
<body class="container mt-5">
    <h2>Edit Student</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Student Name:</label>
            <input type="text" name="student_name" value="<?= htmlspecialchars($student['name']) ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Capture New Face (optional):</label>
            <div>
                <video id="camera" width="300" height="300" autoplay></video>
                <canvas id="snapshot" style="display: none;"></canvas>
            </div>
            <button type="button" id="captureBtn" class="btn btn-info mt-2">Capture</button>
            <input type="hidden" name="captured_image" id="captured_image">
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="attendance.php" class="btn btn-secondary">Cancel</a>
    </form>

    <script>
        const video = document.getElementById('camera');
        const canvas = document.getElementById('snapshot');
        const captureBtn = document.getElementById('captureBtn');
        const capturedImageInput = document.getElementById('captured_image');

        // Access the camera
        navigator.mediaDevices.getUserMedia({ video: true }).then((stream) => {
            video.srcObject = stream;
        });

        captureBtn.addEventListener('click', () => {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = canvas.toDataURL('image/jpeg');
            capturedImageInput.value = imageData;
            alert('Face Captured');
        });
    </script>
</body>
</html>
