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

$type = $_GET['type'] ?? 'total';
$titles = [
    'total' => 'Total Scan Hari Ini',
    'gagal' => 'Scan Gagal Hari Ini',
    'pending' => 'Scan Pending Hari Ini',
    'berhasil' => 'Scan Berhasil Hari Ini'
];
$title = $titles[$type] ?? 'Detail Scan';

$query = "SELECT * FROM productions";
switch ($type) {
    case 'gagal':
    case 'aktif':
        $query .= " WHERE scan_in IS NULL AND DATE(scan_time) = CURDATE()" . ($where_clause ? str_replace("WHERE", " AND", $where_clause) : "");
        break;
    case 'pending':
        $query .= " WHERE scan_in IS NOT NULL AND scan_out IS NULL AND DATE(scan_in) = CURDATE()" . ($where_clause ? str_replace("WHERE", " AND", $where_clause) : "");
        break;
    case 'berhasil':
    case 'selesai':
        $query .= " WHERE scan_in IS NOT NULL AND scan_out IS NOT NULL AND DATE(scan_out) = CURDATE()" . ($where_clause ? str_replace("WHERE", " AND", $where_clause) : "");
        break;
    case 'total':
    default:
        $query .= " WHERE (DATE(scan_in) = CURDATE() OR DATE(scan_out) = CURDATE())" . ($where_clause ? " AND " . str_replace("WHERE ", "", $where_clause) : "");
        break;
}
$query .= " ORDER BY id DESC";
$result = $conn->query($query);

// deteksi halaman aktif
$current_page = 'dashboard.php'; // since it's subpage
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <style>
        body {
            background: #e3f2fd;
            font-family: 'Poppins', sans-serif;
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

        .logo,
        .logo-title {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0px;
        }

        .logo img,
        .logo-title img {
            width: 35px;
            height: auto;
            margin-right: 10px;
        }

        .logo h4,
        .logo-title h4 {
            font-size: 16px;
            margin: 0;
            font-weight: bold;
            color: black;
        }

        .sidebar a {
            color: black;
            display: block;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin: 5px 10px;
        }

        .sidebar a i {
            margin-right: 10px;
        }

        .sidebar a.active,
        .sidebar a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(5px);
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

        .header {
            background: #ffffff;
            color: black;
            padding: 5px;
            border-radius: 15px;

        }

        .header img {
            width: 40px;
            height: auto;
            margin-right: 15px;
        }

        .card {
            margin-top: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table th {
            background: #1976d2;
            color: white;
            border: none;
        }

        .table-hover tbody tr:hover {
            background: #f1f1f1;
        }

        .btn-warning {
            background: #ffc107;
            border: none;
        }

        .btn-danger {
            background: #dc3545;
            border: none;
        }

        .dropdown {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .dropbtn {
            background: none;
            border: none;
            color: black;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 120px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            border-radius: 5px;
            top: 100%;
            right: 0;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            border-radius: 5px;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown-content.show {
            display: block;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .sidebar.open {
                display: flex !important;
                position: fixed;
                left: 0;
                top: 60px;
                width: 250px;
                height: calc(100vh - 60px);
                z-index: 1000;
            }

            .content {
                margin-left: 0;
            }
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
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <?php if ($user_role === 'admin'): ?>
            <a href="generate_qr.php"><i class="fas fa-qrcode"></i> Generate QR</a>
        <?php endif; ?>
        <a href="scan.php"><i class="fas fa-barcode"></i> Scan QR</a>
        <a href="history.php"><i class="fas fa-history"></i> History</a>
        <?php if ($user_role === 'admin'): ?>
            <a href="monitoring.php"><i class="fas fa-desktop"></i> Monitoring</a>
            <a href="laporan.php"><i class="fas fa-file-export"></i> Laporan </a>
            <a href="labels.php"><i class="fas fa-tags"></i> Labels Produksi</a>
            <a href="manajemen_user.php"><i class="fas fa-users"></i> Manajemen User</a>
        <?php endif; ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <div class="header">
            <div>
                <h3><i class="fas fa-list"></i> <?php echo $title; ?></h3>

            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="detailTable" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No FO</th>
                                <th>Type Kabel</th>
                                <th>Mesin</th>
                                <th>Proses</th>
                                <th>Operator</th>
                                <th>Length</th>
                                <th>Scan In</th>
                                <th>Scan Out</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            while ($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlentities($row['fo_number']) ?></td>
                                    <td><?= htmlentities($row['cable_type']) ?></td>
                                    <td><?= htmlentities($row['machine']) ?></td>
                                    <td><?= htmlentities($row['process']) ?></td>
                                    <td><?= htmlentities($row['scan_operator'] ?? '-') ?></td>
                                    <td><?= htmlentities($row['qty']) ?></td>
                                    <td><?= $row['scan_in'] ? $row['scan_in'] : '-' ?></td>
                                    <td><?= $row['scan_out'] ? $row['scan_out'] : '-' ?></td>

                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- JQuery & DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }

        function toggleDropdown() {
            document.getElementById("dropdownMenu").classList.toggle("show");
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.closest('.dropdown')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                var i;
                for (i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }

        $(document).ready(function() {
            $('#detailTable').DataTable({
                "lengthMenu": [10, 25, 50, 100],
                "language": {
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Tidak ada data ditemukan",
                    "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    "infoEmpty": "Tidak ada data tersedia",
                    "infoFiltered": "(difilter dari total _MAX_ data)",
                    "search": "Cari FO:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Berikutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });
        });
    </script>
</body>

</html>