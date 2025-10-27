<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$user_role = $_SESSION['user']['role'] ?? '';
$user_username = $_SESSION['user']['username'] ?? '';
include 'db.php';

$query = "SELECT * FROM productions WHERE scan_in IS NOT NULL";
if ($user_role !== 'admin' && $user_role !== 'supervisor') {
    $query .= " AND scan_operator = '" . $conn->real_escape_string($user_username) . "'";
}
$query .= " ORDER BY scan_in DESC, id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>History</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        body {
    background:#e3f2fd;
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
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: black;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 20px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            border-radius: 0 15px 15px 0;
             z-index: 999;
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
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
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
  
        .card {
            margin-top: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
        }

        .table-responsive {
            overflow-x: auto;
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
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
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
    </div>

    <div class="sidebar">
        <div class="logo-title">
            <img src="assets/img/logo_scanary.png" alt="Logo">
            <h4>Scanary</h4>
        </div>
       <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <?php if ($user_role === 'admin'): ?>
        <a href="generate_qr.php"><i class="fas fa-qrcode"></i> Generate QR</a>
        <?php endif; ?>
        <a href="scan.php"><i class="fas fa-barcode"></i> Scan QR</a>
        <a href="history.php" class="active"><i class="fas fa-history"></i> History </a>
        <?php if ($user_role === 'admin' || $user_role === 'supervisor'): ?>
        <a href="monitoring.php"><i class="fas fa-desktop"></i> Monitoring</a>
        <a href="laporan.php"><i class="fas fa-file-export"></i> Laporan </a>
        <?php endif; ?>
        <?php if ($user_role === 'admin'): ?>
        <a href="labels.php"><i class="fas fa-tags"></i> Labels Produksi</a>
        <a href="manajemen_user.php"><i class="fas fa-users"></i> Manajemen User</a>
        <?php endif; ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <div class="header">
            <h4><i class="fas fa-history"></i> History</h4>
            <p>Riwayat data produksi kabel yang telah dipindai.</p>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="historyTable" class="table table-bordered table-striped">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>No</th>
                                <th>No FO</th>
                                <th>Type Kabel</th>
                                <th>Mesin</th>
                                <th>Proses</th>
                                <th>Operator</th>
                                <th>Length</th>
                                <th>Start</th>
                                <th>Finish</th>
                                <th>Duration</th>
                                <?php if ($user_role === 'admin'): ?>
                                    <th>Action</th>
                                <?php endif; ?>
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
                                    <td><?php echo ($row['scan_in'] && $row['scan_out']) ? (new DateTime($row['scan_in']))->diff(new DateTime($row['scan_out']))->format('%H:%I:%S') : '-'; ?></td>
                                    <?php if ($user_role === 'admin'): ?>
                                        <td>
                                            <a href="edit_history.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_history.php?id=<?= $row['id'] ?>"
                                                onclick="return confirm('Yakin ingin menghapus data ini?')"
                                                class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
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

    <!-- JQuery & DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#historyTable').DataTable({
                "lengthMenu": [10, 25, 50, 100],
                "language": {
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Tidak ada data ditemukan",
                    "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    "infoEmpty": "Tidak ada data tersedia",
                    "infoFiltered": "(difilter dari total _MAX_ data)",
                    "search": "Cari :",
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
