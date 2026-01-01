<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "face_recognition_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle student update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateStudent'])) {
    $studentIndexNumber = $conn->real_escape_string($_POST['studentIndexNumber']);
    $newName = $conn->real_escape_string($_POST['newName']);

    // Update name
    $conn->query("UPDATE student SET name = '$newName' WHERE index_number = '$studentIndexNumber'");

    // Face re-registration if image is provided (via base64)
    if (!empty($_POST['newFaceBase64'])) {
        $imageData = $_POST['newFaceBase64'];
        $apiUrl = "http://localhost:5000/register";
        $postData = json_encode([
            'id' => $studentIndexNumber,
            'name' => $newName,
            'image' => $imageData
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    header("Location: attendance.php");
    exit;
}

// Fetch attendance from Flask API
$apiUrl = "http://localhost:5000/attendance";
$attendanceData = @json_decode(file_get_contents($apiUrl), true) ?: [];

function getStudentDetails($studentIndexNumber) {
    global $conn;
    $res = $conn->query("SELECT name, course, level FROM student WHERE index_number = '$studentIndexNumber'");
    if ($res && $row = $res->fetch_assoc()) {
        return [
            'name' => $row['name'],
            'course' => $row['course'],
            'level' => $row['level']
        ];
    }
    return [
        'name' => 'Unknown',
        'course' => 'Unknown',
        'level' => 'Unknown'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UPSA Attendance Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet">
    <link href="assets/img/wise.png" rel="icon" type="image/png">
    <link href="assets/img/wise.png" rel="apple-touch-icon">
    <style>
        .dataTables_paginate .paginate_button {
            background: #007bff;
            color: white !important;
            border: 1px solid #007bff;
            border-radius: 5px;
            padding: 5px 10px;
            margin: 0 2px;
        }
        .dataTables_paginate .paginate_button:hover {
            background: #0056b3;
        }
        .dataTables_paginate .paginate_button.current {
            background: #28a745;
            border: 1px solid #28a745;
        }
    </style>
</head>
<body style="background-image: url('assets/img/j.jpg'); background-repeat: no-repeat; background-size: cover;">
    <div class="container mt-5">
        <h2 class="text-center mb-4 text-light">UPSA Attendance Records</h2>
        <div class="d-flex justify-content-between mb-3">
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
            <a href="attendance.php?logout" class="btn btn-danger">Logout</a>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label text-light" for="dateFilter">Filter by Date:</label>
                <input type="date" id="dateFilter" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label text-light" for="nameFilter">Search by Student Name:</label>
                <input type="text" id="nameFilter" class="form-control" placeholder="Enter student name">
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button id="exportCsv" class="btn btn-success btn-sm me-2">Export CSV</button>
            <button id="exportExcel" class="btn btn-warning btn-sm me-2">Export Excel</button>
            <button id="printTable" class="btn btn-info btn-sm">Print</button>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="attendanceTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Level</th>
                            <th>Timestamp</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendanceData)): ?>
                            <?php foreach ($attendanceData as $index => $record): ?>
                                <?php $details = getStudentDetails($record['index_number']); ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($record['index_number']) ?></td>
                                    <td><?= htmlspecialchars($details['name']) ?></td>
                                    <td><?= htmlspecialchars($details['course']) ?></td>
                                    <td><?= htmlspecialchars($details['level']) ?></td>
                                    <td><?= htmlspecialchars($record['timestamp']) ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="editStudent('<?= htmlspecialchars($record['index_number']) ?>', '<?= htmlspecialchars($details['name']) ?>')">Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteStudent('<?= htmlspecialchars($record['index_number']) ?>')">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No attendance records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hidden form for POSTing update -->
    <form id="updateForm" method="POST" enctype="multipart/form-data" style="display:none;">
        <input type="hidden" name="updateStudent" value="1">
        <input type="hidden" name="studentIndexNumber" id="formStudentIndex">
        <input type="hidden" name="newName" id="formNewName">
        <input type="hidden" name="newFaceBase64" id="formNewFaceBase64">
    </form>

    <!-- JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function () {
            const table = $('#attendanceTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'csv', text: 'Export CSV', className: 'btn btn-success btn-sm' },
                    { extend: 'excel', text: 'Export Excel', className: 'btn btn-warning btn-sm' },
                    { extend: 'print', text: 'Print', className: 'btn btn-info btn-sm' }
                ]
            });

            $('#exportCsv').click(() => table.button('.buttons-csv').trigger());
            $('#exportExcel').click(() => table.button('.buttons-excel').trigger());
            $('#printTable').click(() => table.button('.buttons-print').trigger());

            $('#dateFilter').on('change', function () {
                table.column(5).search($(this).val()).draw();
            });

            $('#nameFilter').on('keyup', function () {
                table.column(2).search($(this).val()).draw();
            });
        });

        function deleteStudent(studentId) {
            if (confirm("Are you sure you want to delete this student?")) {
                fetch(`http://localhost:5000/delete_student/${studentId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    location.reload();
                })
                .catch(error => {
                    console.error("Delete error:", error);
                    alert("Failed to delete student.");
                });
            }
        }

        function editStudent(indexNumber, name) {
            const newName = prompt("Enter new name for student:", name);
            if (newName === null || newName.trim() === "") return;

            const upload = confirm("Do you want to re-capture or upload a new face image?");
            if (upload) {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.onchange = function (event) {
                    const file = event.target.files[0];
                    const reader = new FileReader();
                    reader.onload = function () {
                        document.getElementById('formStudentIndex').value = indexNumber;
                        document.getElementById('formNewName').value = newName;
                        document.getElementById('formNewFaceBase64').value = reader.result.split(',')[1];
                        document.getElementById('updateForm').submit();
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            } else {
                document.getElementById('formStudentIndex').value = indexNumber;
                document.getElementById('formNewName').value = newName;
                document.getElementById('updateForm').submit();
            }
        }
    </script>
</body>
</html>
