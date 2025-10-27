<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$user_role = $_SESSION['user']['role'] ?? '';
$user_username = $_SESSION['user']['username'] ?? '';

if ($user_role !== 'admin' && $user_role !== 'supervisor') {
    header("Location: dashboard.php");
    exit;
}

include 'db.php';

// Handle export
if (isset($_GET['export'])) {
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $fo_number = $_GET['fo_number'] ?? '';

    $query = "SELECT *, TIMEDIFF(scan_out, scan_in) as duration FROM productions WHERE scan_in IS NOT NULL AND scan_out IS NOT NULL";
    if ($start_date) $query .= " AND DATE(scan_time) >= '$start_date'";
    if ($end_date) $query .= " AND DATE(scan_time) <= '$end_date'";
    if ($fo_number) $query .= " AND fo_number LIKE '%$fo_number%'";
    $query .= " ORDER BY scan_time DESC";

    $result = $conn->query($query);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_produksi.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'FO Number', 'Cable Type', 'Machine', 'Process', 'Length', 'Scan In', 'Scan Out', 'Durasi']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['fo_number'],
            $row['cable_type'],
            $row['machine'],
            $row['process'],
            $row['qty'],
            $row['scan_in'] ?? '-',
            $row['scan_out'] ?? '-',
            $row['duration'] ?? '-'
        ]);
    }
    fclose($output);
    exit;
}

// Handle filter
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$fo_number = $_GET['fo_number'] ?? '';

$query = "SELECT *, TIMEDIFF(scan_out, scan_in) as duration FROM productions WHERE scan_in IS NOT NULL AND scan_out IS NOT NULL";
if ($start_date) $query .= " AND DATE(scan_time) >= '$start_date'";
if ($end_date) $query .= " AND DATE(scan_time) <= '$end_date'";
if ($fo_number) $query .= " AND fo_number LIKE '%$fo_number%'";
$query .= " ORDER BY scan_time DESC";

$result = $conn->query($query);

// Summary stats
$total_productions = $result->num_rows;
$total_qty = 0;
$result->data_seek(0);
while ($row = $result->fetch_assoc()) {
    $total_qty += $row['qty'];
}
$result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laporan & Ekspor Data</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
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
        <?php if ($user_role === 'admin'): ?>
            <a href="generate_qr.php"><i class="fas fa-qrcode"></i> Generate QR</a>
        <?php endif; ?>
        <a href="scan.php"><i class="fas fa-barcode"></i> Scan QR</a>
        <a href="history.php"><i class="fas fa-history"></i> History</a>
        <a href="monitoring.php"><i class="fas fa-desktop"></i> Monitoring</a>
        <a href="laporan.php" class="active"><i class="fas fa-file-export"></i> Laporan </a>
        <?php if ($user_role === 'admin'): ?>
            <a href="labels.php"><i class="fas fa-tags"></i> Labels Produksi</a>
            <a href="manajemen_user.php"><i class="fas fa-users"></i> Manajemen User</a>
        <?php endif; ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <div class="header">
            <h4><i class="fas fa-file-export"></i> Laporan & Ekspor Data</h4>
            <p>Lihat dan ekspor data produksi.</p>
        </div>

        <!-- Filter Form -->
        <div class="card">
            <div class="card-body">
                <form method="GET" class="form-inline">
                    <div class="form-group mr-3">
                        <label class="mr-2">Tanggal Mulai:</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control">
                    </div>
                    <div class="form-group mr-3">
                        <label class="mr-2">Tanggal Akhir:</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
                    </div>
                    <div class="form-group mr-3">
                        <label class="mr-2">FO Number:</label>
                        <input type="text" name="fo_number" value="<?php echo htmlentities($fo_number); ?>" class="form-control" placeholder="Cari FO Number">
                    </div>
                    <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search"></i> Filter</button>
                    <a href="laporan.php" class="btn btn-secondary mr-2"><i class="fas fa-sync-alt"></i> Reset</a>
                    <a href="?export=1&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&fo_number=<?php echo urlencode($fo_number); ?>" class="btn btn-success">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </form>
            </div>
        </div>



        <!-- Data Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reportTable" class="table table-bordered table-striped">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>ID</th>
                                <th>FO Number</th>
                                <th>Cable Type</th>
                                <th>Machine</th>
                                <th>Process</th>
                                <th>Length</th>
                                <th>Start</th>
                                <th>Finish</th>
                                <th>Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlentities($row['fo_number']); ?></td>
                                    <td><?php echo htmlentities($row['cable_type']); ?></td>
                                    <td><?php echo htmlentities($row['machine']); ?></td>
                                    <td><?php echo htmlentities($row['process']); ?></td>
                                    <td><?php echo htmlentities($row['qty']); ?></td>
                                    <td><?php echo htmlentities($row['scan_in'] ?? '-'); ?></td>
                                    <td><?php echo htmlentities($row['scan_out'] ?? '-'); ?></td>
                                    <td><?php echo htmlentities($row['duration'] ?? '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#reportTable').DataTable({
                "lengthMenu": [10, 25, 50, 100],
                "language": {
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Tidak ada data ditemukan",
                    "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    "infoEmpty": "Tidak ada data tersedia",
                    "infoFiltered": "(difilter dari total _MAX_ data)",
                    "search": "Cari:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Berikutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });
        });

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