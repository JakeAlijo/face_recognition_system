<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>UPSA Face Detection Attendance System</title>

    <!-- Favicons -->
    <link href="assets/img/wise.png" rel="icon">
    <link href="assets/img/wise.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Nunito|Poppins" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<style>
    .capture {
        border: 2px dotted red;
        border-radius: 20px;
        padding: 10px;
        width: 100%;
        min-height: 300px;
        position: relative;
        text-align: center;
    }

    video {
        width: 100%;
        height: auto;
        border-radius: 10px;
    }

    .logButt {
        position: absolute;
        bottom: 10px;
        right: 5px;
    }
</style>

<body style="background-image:url('assets/img/j.jpg'); background-repeat: no-repeat; background-size: cover; opacity: 1;">
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 align-items-center justify-content-center">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h2><img src="assets/img/wise.png" style='width: 150px; height:80px;' /></h2>
                                        <a href="login.php" class="btn btn-primary">Admin</a>
                                    </div>
                                    <hr>
                                    <div class="text-center">
                                        <h5 class="card-title pb-0 fs-4">Face Recognition System</h5>
                                        <p class="small">Register or mark attendance by capturing your face.</p>
                                    </div>

                                    <!-- Registration Form -->
                                    <div class="mb-3">
                                        <input type="text" id="studentName" class="form-control" placeholder="Enter your name for registration">
                                    </div>

                                    <div class="mb-3">
                                        <input type="text" id="studentIndex" class="form-control" placeholder="Enter your index number">
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" id="studentCourse" class="form-control" placeholder="Enter your course or programme">
                                    </div>
                                    <div class="mb-3">
                                        <select class="form-control" id="studentLevel" name="studentLevel" required>
                                            <option value="">Select Level</option>
                                            <option value="100">Level 100</option>
                                            <option value="200">Level 200</option>
                                            <option value="300">Level 300</option>
                                            <option value="400">Level 400</option>
                                        </select>
                                    </div>                    


                                    <div class="capture">
                                        <video id="video" autoplay></video>
                                        <canvas id="canvas" style="display:none;"></canvas>
                                        <button id="registerBtn" class="btn btn-success mt-3">Register</button>
                                        <button id="attendanceBtn" class="btn btn-primary mt-3">Mark Attendance</button>
                                        <p id="responseMessage" class="mt-3"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="credits text-center" style="color:white">
                                <a href="user_manual.php" >User Manual</a>
                                <a > | </a>
                                Designed by <a href="https://oric.network" style="color:white">Oric Network Limited</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- JavaScript -->
    <script>

    const video = document.getElementById("video");
    const canvas = document.getElementById("canvas");
    const registerBtn = document.getElementById("registerBtn");
    const attendanceBtn = document.getElementById("attendanceBtn");
    const responseMessage = document.getElementById("responseMessage");
    const toggleCameraBtn = document.createElement("button");
    let stream = null;
    let cameraOn = false;

    // Create a "Toggle Camera" button
    toggleCameraBtn.textContent = "Turn Camera On";
    toggleCameraBtn.className = "btn btn-warning mt-3";
    video.parentNode.appendChild(toggleCameraBtn);

    // Toggle Camera
    toggleCameraBtn.addEventListener("click", () => {
        if (cameraOn) {
            stopCamera();
        } else {
            startCamera();
        }
    });

    function startCamera() {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(s => {
                stream = s;
                video.srcObject = stream;
                cameraOn = true;
                toggleCameraBtn.textContent = "Turn Camera Off";
            })
            .catch(err => {
                console.error("Error accessing webcam:", err);
            });
    }

    function stopCamera() {
        if (stream) {
            const tracks = stream.getTracks();
            tracks.forEach(track => track.stop());
            video.srcObject = null;
            cameraOn = false;
            toggleCameraBtn.textContent = "Turn Camera On";
        }
    }

    // Capture image and send to Python API
    
    function captureAndSend(apiEndpoint, name = null) {
        if (!cameraOn) {
            responseMessage.innerHTML = `<strong style="color: red;">Camera is off. Please turn it on!</strong>`;
            return;
        }

        const context = canvas.getContext("2d");
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        const imageData = canvas.toDataURL("image/jpeg").split(',')[1];
        const payload = {
            image: imageData,
            name,
            index_number: document.getElementById("studentIndex").value,
            course: document.getElementById("studentCourse").value,
            level: document.getElementById("studentLevel").value,
        };

        fetch(`http://127.0.0.1:5000/${apiEndpoint}`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                responseMessage.innerHTML = `<strong style="color: green;">${data.message}</strong>`;
            } else {
                responseMessage.innerHTML = `<strong style="color: red;">${data.error}</strong>`;
            }
        })
        .catch(error => {
            console.error("Error:", error);
            responseMessage.innerHTML = `<strong style="color: red;">Error connecting to server</strong>`;
        });
    }

    



    // Register Student
    registerBtn.addEventListener("click", () => {
        const studentName = document.getElementById("studentName").value;
        if (!studentName.trim()) {
            responseMessage.innerHTML = `<strong style="color: red;">Please enter your name!</strong>`;
            return;
        }
        captureAndSend("register", studentName);
    });

    // Mark Attendance
    attendanceBtn.addEventListener("click", () => {
        captureAndSend("recognise");
    });

    // Turn off the camera when leaving the page
    window.addEventListener("beforeunload", stopCamera);
</script>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
