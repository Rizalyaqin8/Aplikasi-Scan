<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$user_role = $_SESSION['user']['role'] ?? '';
$user_username = $_SESSION['user']['username'] ?? '';

include 'db.php';

$where_clause = ($user_role !== 'admin' && $user_role !== 'supervisor') ? " WHERE scan_operator = '" . $conn->real_escape_string($user_username) . "'" : "";

// Query untuk metrik dashboard hari ini
if ($user_role !== 'admin' && $user_role !== 'supervisor') {
    $total_fo = $conn->query("SELECT COUNT(*) as count FROM productions WHERE (DATE(scan_in) = CURDATE() OR DATE(scan_out) = CURDATE())" . ($where_clause ? " AND " . str_replace("WHERE ", "", $where_clause) : ""))->fetch_assoc()['count'];
} else {
    // Untuk admin dan supervisor, total scan berhasil hari ini dari semua role
    $total_fo = $conn->query("SELECT COUNT(*) as count FROM productions WHERE DATE(scan_out) = CURDATE()")->fetch_assoc()['count'];
}
$fo_gagal = $conn->query("SELECT COUNT(*) as count FROM productions WHERE scan_in IS NULL AND DATE(scan_time) = CURDATE()" . ($where_clause ? " AND " . str_replace("WHERE ", "", $where_clause) : ""))->fetch_assoc()['count'];
// FO Pending: baru scan in/start (belum scan out/finish) hari ini
$fo_pending = $conn->query("SELECT COUNT(*) as count FROM productions WHERE scan_in IS NOT NULL AND scan_out IS NULL AND DATE(scan_in) = CURDATE()" . ($where_clause ? " AND " . str_replace("WHERE ", "", $where_clause) : ""))->fetch_assoc()['count'];
// FO Selesai: sudah scan in/start dan scan out/finish hari ini
$fo_selesai = $conn->query("SELECT COUNT(*) as count FROM productions WHERE scan_in IS NOT NULL AND scan_out IS NOT NULL AND DATE(scan_out) = CURDATE()" . ($where_clause ? " AND " . str_replace("WHERE ", "", $where_clause) : ""))->fetch_assoc()['count'];

// Query untuk aktivitas scan terbaru
$scan_activities_query = "SELECT fo_number, process, machine, scan_operator, scan_in, scan_out, TIMEDIFF(scan_out, scan_in) AS duration FROM productions WHERE (scan_in IS NOT NULL OR scan_out IS NOT NULL)";
if ($user_role !== 'admin' && $user_role !== 'supervisor') {
    $scan_activities_query .= " AND scan_operator = '" . $conn->real_escape_string($user_username) . "'";
}
$scan_activities_query .= " ORDER BY COALESCE(scan_out, scan_in) DESC LIMIT 10";
$scan_activities_result = $conn->query($scan_activities_query);

// deteksi halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .sidebar.open {
            display: flex !important;
        }

        .card {
            margin-bottom: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background: White;
            color: black;
            border: none;
        }

        .table-hover tbody tr:hover {
            background: #f1f1f1;
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

        <div class="sidebar">
            <div class="logo">
                <img src="assets/img/logo_scanary.png" alt="Logo">
                <h4>Scanary</h4>
            </div>
            <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php if ($user_role === 'admin'): ?>
                <a href="generate_qr.php" class="<?= $current_page == 'generate_qr.php' ? 'active' : '' ?>"><i class="fas fa-qrcode"></i> Generate QR</a>
            <?php endif; ?>
            <a href="scan.php" class="<?= $current_page == 'scan.php' ? 'active' : '' ?>"><i class="fas fa-barcode"></i> Scan QR</a>
            <a href="history.php" class="<?= $current_page == 'history.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> History</a>
            <?php if ($user_role === 'admin' || $user_role === 'supervisor'): ?>
                <a href="monitoring.php" class="<?= $current_page == 'monitoring.php' ? 'active' : '' ?>"><i class="fas fa-desktop"></i> Monitoring</a>
                <a href="laporan.php" class="<?= $current_page == 'laporan.php' ? 'active' : '' ?>"><i class="fas fa-file-export"></i> Laporan</a>
            <?php endif; ?>
            <?php if ($user_role === 'admin'): ?>
                <a href="labels.php" class="<?= $current_page == 'labels.php' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Labels Produksi</a>
                <a href="manajemen_user.php" class="<?= $current_page == 'manajemen_user.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Manajemen User</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="content">
            <div class="header">
                <h4><i class="fas fa-tachometer-alt"></i> Dashboard</h4>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5>Total Scan Hari Ini</h5>
                            <p class="card-text"><?php echo $total_fo; ?></p>
                            <a href="view_detail.php?type=total" class="btn btn-light btn-sm">View Detail</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5>Gagal</h5>
                            <p class="card-text"><?php echo $fo_gagal; ?></p>
                            <a href="view_detail.php?type=gagal" class="btn btn-light btn-sm">View Detail</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5>Pending</h5>
                            <p class="card-text"><?php echo $fo_pending; ?></p>
                            <a href="view_detail.php?type=pending" class="btn btn-light btn-sm">View Detail</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5>Berhasil</h5>
                            <p class="card-text"><?php echo $fo_selesai; ?></p>
                            <a href="view_detail.php?type=berhasil" class="btn btn-light btn-sm">View Detail</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aktivitas Scan Terbaru -->
            <div class="card">
                <div class="card-header" style="background-color: white; color: black;">
                    <h5><i class="fas fa-clock"></i> Aktivitas Scan Terbaru</h5>
                </div>
                <div class="table-striped">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>FO Number</th>
                                <th>Process</th>
                                <th>Mesin</th>
                                <th>Operator</th>
                                <th>Scan In</th>
                                <th>Scan Out</th>
                                <th>Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($scan_activities_result->num_rows > 0): ?>
                                <?php while ($row = $scan_activities_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['fo_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['process']); ?></td>
                                        <td><?php echo htmlspecialchars($row['machine']); ?></td>
                                        <td><?php echo htmlspecialchars($row['scan_operator']); ?></td>
                                        <td><?= $row['scan_in'] ? $row['scan_in'] : '-' ?></td>
                                        <td><?= $row['scan_out'] ? $row['scan_out'] : '-' ?></td>
                                        <td><?php echo $row['scan_in'] && $row['scan_out'] ? htmlspecialchars($row['duration']) : '-' ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada aktivitas scan terbaru.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
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