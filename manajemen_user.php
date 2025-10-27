<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user_role = $_SESSION['user']['role'] ?? '';
$user_username = $_SESSION['user']['username'] ?? '';
if ($user_role !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
include 'db.php';

// Handle add user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $user_role);
    if ($stmt->execute()) {
        $success = "User berhasil ditambahkan.";
    } else {
        $error = "Gagal menambahkan user.";
    }
}

// Handle edit user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $user_role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $password, $user_role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
        $stmt->bind_param("ssi", $username, $user_role, $id);
    }

    if ($stmt->execute()) {
        $success = "User berhasil diupdate.";
    } else {
        $error = "Gagal mengupdate user.";
    }
}

// Handle delete user
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "User berhasil dihapus.";
    } else {
        $error = "Gagal menghapus user.";
    }
}

// Get all users
$result = $conn->query("SELECT * FROM users ORDER BY id");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manajemen User</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .sidebar.open {
            display: flex !important;
        }

        .sidebar a.active,
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            transform: translateX(5px);
        }

        .card {
            margin-bottom: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #45a049 0%, #4CAF50 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background: none;
            border: none;
            color: #333;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 120px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            right: 0;
            top: 100%;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown.show .dropdown-content {
            display: block;
        }

        .user-info {
            display: flex;
            align-items: center;
        }
    </style>
</head>

<body>
    <!-- Top Header -->
    <div class="top-header">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <div class="logo-title">
            <img src="assets/img/logo.png" alt="Logo">
            Production Management System
        </div>
        <div class="user-info">
            <i class="fas fa-user"></i>
            <span><?php echo htmlspecialchars($user_username); ?> (<?php echo htmlspecialchars(ucfirst($user_role)); ?>)</span>
            <div class="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()"><i class="fas fa-caret-down"></i></button>
                <div id="dropdownMenu" class="dropdown-content">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <div class="logo">
            <img src="assets/img/logo_scanary.png" alt="Logo">
            <h4>Scanary</h4>
        </div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="generate_qr.php"><i class="fas fa-qrcode"></i> Generate QR</a>
        <a href="scan.php"><i class="fas fa-barcode"></i> Scan QR</a>
        <a href="history.php"><i class="fas fa-history"></i> History</a>
        <a href="monitoring.php"><i class="fas fa-desktop"></i> Monitoring</a>
        <a href="laporan.php"><i class="fas fa-file-export"></i> Laporan </a>
        <a href="labels.php"><i class="fas fa-tags"></i> Labels Produksi</a>
        <a href="manajemen_user.php" class="active"><i class="fas fa-users"></i> Manajemen User</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <style>
        body {
            background: #e3f2fd;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            width: 250px;
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            background: white;
            color: black;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 20px 0;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .sidebar a.active,
        .sidebar a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(5px);
        }

        .sidebar.open {
            display: flex !important;
        }

        .content {
            margin-left: 0;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background: #fff;
            border-radius: 15px;
            margin-right: 0;
            margin-top: 0;
            margin-bottom: 20px;
        }

        @media (max-width: 767px) {
            .sidebar {
                display: none;
            }

            .sidebar.open {
                display: flex !important;
            }
        }
    </style>

    <div class="content">
        <div class="header">
            <h4><i class="fas fa-users"></i> Manajemen User</h4>
            <p>Kelola pengguna sistem.</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add User Button -->
        <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addUserModal">
            <i class="fas fa-plus"></i> Tambah User
        </button>

        <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlentities($row['username']); ?></td>
                                <td><?php echo htmlentities($row['role']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#editUserModal"
                                        onclick="editUser(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>', '<?php echo $row['role']; ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Yakin ingin menghapus user ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" class="form-control" required>
                                <option value="operator">Operator</option>
                                <option value="admin">Admin</option>
                                <option value="supervisor">Supervisor</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Password (kosongkan jika tidak diubah)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" id="edit_role" class="form-control" required>
                                <option value="operator">Operator</option>
                                <option value="admin">Admin</option>
                                <option value="supervisor">Supervisor</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(id, username, role) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
        }

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target) && window.innerWidth <= 767) {
                sidebar.classList.remove('open');
            }
        });

        function toggleDropdown() {
            const dropdown = document.querySelector('.dropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.dropdown');
            const dropbtn = document.querySelector('.dropbtn');
            if (!dropdown.contains(event.target) && !dropbtn.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>
</body>

</html>