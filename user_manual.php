<?php
// user_manual.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="User Manual for UPSA Face Detection Attendance System">
    <meta name="author" content="Oric Network Limited">
    <link rel="icon" href="assets/img/wise.png">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Nunito|Poppins" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>UPSA Face Detection Attendance System - User Manual</title>
</head>
<body style="background-image:url('assets/img/j.jpg'); background-repeat: no-repeat; background-size: cover; opacity: 1; color: white;">

<main>
    <div class="container">
        <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center">
                                    <h2 class="mt-3">
                                        <img src="assets/img/wise.png" style="width: 150px; height:80px;" alt="UPSA Logo">
                                    </h2>
                                    <h3 class="card-title">UPSA Face Detection Attendance System</h3>
                                    <p class="small">User Manual</p>
                                    <hr>
                                </div>
                                <div>
                                    <h4>1. Introduction</h4>
                                    <p>Welcome to the UPSA Face Detection Attendance System. This manual will guide you through using the system for student registration and attendance tracking.</p>
                                    
                                    <h4>2. System Requirements</h4>
                                    <ul>
                                        <li>A laptop or desktop with a working webcam.</li>
                                        <li>Internet connection for API requests.</li>
                                        <li>Google Chrome or Mozilla Firefox for best performance.</li>
                                    </ul>
                                    
                                    <h4>3. How to Register</h4>
                                    <ol>
                                        <li>Click "Turn Camera On" to activate the webcam.</li>
                                        <li>Enter your full name in the "Name" field.</li>
                                        <li>Click the "Register" button to capture your face and register.</li>
                                        <li>If successful, you will see a message confirming registration.</li>
                                    </ol>

                                    <h4>4. Mark Attendance</h4>
                                    <ol>
                                        <li>Click "Turn Camera On" to activate the webcam.</li>
                                        <li>Click "Mark Attendance" to scan your face.</li>
                                        <li>If your face is recognised, your attendance will be logged automatically.</li>
                                    </ol>

                                    <h4>5. Troubleshooting</h4>
                                    <ul>
                                        <li>If the camera does not turn on, ensure that your browser has permission to access the webcam.</li>
                                        <li>If no face is detected, make sure your face is well-lit and fully visible to the camera.</li>
                                    </ul>

                                    <h4>6. Additional Tips</h4>
                                    <ul>
                                        <li>For accurate recognition, maintain a consistent facial expression during registration and attendance.</li>
                                        <li>Avoid wearing masks, sunglasses, or hats during recognition.</li>
                                    </ul>

                                    <h4>7. Acknowledgements</h4>
                                    <p>Designed by <a href="https://oric.network" style="color:blue">Oric Network Limited</a>.</p>
                                </div>

                                <div class="text-center mt-4">
                                    <a href="index.php" class="btn btn-primary">
                                        <i class="bi bi-arrow-left"></i> Back to System
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="credits text-center mt-3">
                            Designed by <a href="https://oric.network" style="color:white">Oric Network Limited</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
