<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'superuser') {
    header("Location: attendance.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "face_recognition_db");

// Handle adding new admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addAdmin'])) {
    $newUsername = $conn->real_escape_string($_POST['newUsername']);
    $newPassword = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);

    $conn->query("INSERT INTO users (username, password, role) VALUES ('$newUsername', '$newPassword', 'admin')");
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resetPassword'])) {
    $userId = $conn->real_escape_string($_POST['userId']);
    $newPassword = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);

    $conn->query("UPDATE users SET password = '$newPassword' WHERE id = '$userId'");
}

// Handle delete admin
if (isset($_GET['delete'])) {
    $userId = $conn->real_escape_string($_GET['delete']);

    // Check if trying to delete the superuser
    $checkUser = $conn->query("SELECT role FROM users WHERE id = '$userId'")->fetch_assoc();
    if ($checkUser['role'] !== 'superuser') {
        $conn->query("DELETE FROM users WHERE id = '$userId'");
    }
    header("Location: user_management.php");
}

// Fetch all users
$users = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <title>User Management</title>
</head>
<body class="container mt-5">
    <h2>User Management</h2>
    <a href="attendance.php" class="btn btn-secondary mb-3">Back to Attendance</a>

    <!-- Add New Admin Form -->
    <form method="POST" class="mb-4">
        <h4>Add New Admin</h4>
        <input type="text" name="newUsername" placeholder="Username" class="form-control mb-2" required>
        <input type="password" name="newPassword" placeholder="Password" class="form-control mb-2" required>
        <button type="submit" name="addAdmin" class="btn btn-success">Add Admin</button>
    </form>

    <!-- User List -->
    <h4>Current Admins</h4>
    <table class="table">
        <thead>
            <tr><th>Username</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while ($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <?php if ($user['role'] !== 'superuser'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="userId" value="<?= $user['id'] ?>">
                                <input type="password" name="newPassword" placeholder="New Password" required>
                                <button type="submit" name="resetPassword" class="btn btn-warning btn-sm">Reset Password</button>
                            </form>
                            <a href="?delete=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                        <?php else: ?>
                            <span class="text-muted">Superuser - No Actions</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
