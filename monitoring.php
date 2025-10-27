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

$fo_filter = '';
if (isset($_GET['fo_number'])) {
    $fo_filter = $conn->real_escape_string($_GET['fo_number']);
}

// Ambil data progress berdasarkan FO Number
$sql = "SELECT * FROM productions ";
if ($fo_filter) {
    $sql .= " WHERE fo_number='$fo_filter' ";
}
$sql .= " ORDER BY id";

$result = $conn->query($sql);

$process_counts = [];
$processes_done = [];
$cable_type = '';
$length = '';
$order = '';
$oa_customer = '';
$date = '';
$first = true;
$l3_first = true;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $process = $row['process'];
        if (!isset($process_counts[$process])) {
            $process_counts[$process] = ['total' => 0, 'done' => 0];
        }
        $process_counts[$process]['total']++;

        if ($first) {
            $cable_type = $row['cable_type'];
            $length = $row['qty'];
            $first = false;
        }

        // ambil data dari proses L3
        if (in_array($row['process'], ['Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR'])) {
            if ($l3_first) {
                $length = $row['qty'];
                $l3_first = false;
            }
            // parse qr_code JSON untuk order, oa_customer, date, dan cable_type
            $qr_data = json_decode($row['qr_code'], true);
            if ($qr_data) {
                $cable_type = $qr_data['cable_type'] ?? $cable_type;
                $order = $qr_data['order_number'] ?? '';
                $oa_customer = $qr_data['oa_customer'] ?? '';
                $date = $qr_data['date'] ?? '';
            }
        }

        if (!empty($row['scan_in']) && !empty($row['scan_out'])) {
            $process_counts[$process]['done']++;
            if (!in_array($process, $processes_done)) {
                $processes_done[] = $process;
            }
        }
    }
}

$all_processes = [
    'Drawing',
    'Stranding',
    'Insulation',
    'Cabling',
    'Inner Sheath',
    'Armouring',
    'Outer Sheath',
    'Final Test',
    'Packing',
    'DR'
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monitoring Progress Produksi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
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
            margin-left: 0px;
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

        .process-done {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 3px;
            display: inline-block;
        }

        .process-pending {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 3px;
            display: inline-block;
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

        .btn-secondary {
            background: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
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
        <?php if ($user_role === 'admin'): ?>
            <a href="generate_qr.php"><i class="fas fa-qrcode"></i> Generate QR</a>
        <?php endif; ?>
        <a href="scan.php"><i class="fas fa-barcode"></i> Scan QR</a>
        <a href="history.php"><i class="fas fa-history"></i> History</a>
        <a href="monitoring.php" class="active"><i class="fas fa-desktop"></i> Monitoring</a>
        <a href="laporan.php"><i class="fas fa-file-export"></i> Laporan </a>
        <?php if ($user_role === 'admin'): ?>
            <a href="labels.php"><i class="fas fa-tags"></i> Labels Produksi</a>
            <a href="manajemen_user.php"><i class="fas fa-users"></i> Manajemen User</a>
        <?php endif; ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>


    <div class="content">
        <div class="header">
            <h4><i class="fas fa-desktop"></i> Monitoring Progress Produksi</h4>
            <p>Lacak progress produksi kabel berdasarkan Nomor FO.</p>
        </div>

        <form method="GET" action="" class="form-inline mb-3">
            <label for="fo_number" class="mr-2">Nomor FO :</label>
            <input type="text" name="fo_number" id="fo_number" value="<?= htmlentities($fo_filter) ?>" class="form-control mr-2" placeholder="Masukkan Nomor FO" />
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
            <a href="monitoring.php" class="btn btn-secondary ml-2"><i class="fas fa-sync-alt"></i> Reset</a>
        </form>

        <?php if ($fo_filter): ?>
            <p><strong><?= htmlentities($fo_filter) ?></strong>
                <br>
                <strong><?= htmlentities($cable_type ?: '-') ?> SNI IEC 60502-1</strong>
                <br>
                Order: <?= htmlentities($order ?: '-') ?> Length: <?= htmlentities($length ?: '-') ?>
                <br>
                Date: <?= htmlentities($date ?: '-') ?>
                <br>
                OA Customer: <?= htmlentities($oa_customer ?: '-') ?>
                <br>
                <strong>SNI IEC 60502-1</strong>
            </p>

            <div>
                <?php foreach ($all_processes as $proc): ?>
                    <?php
                    $total = $process_counts[$proc]['total'] ?? 0;
                    $done = $process_counts[$proc]['done'] ?? 0;
                    $is_done = $done > 0 && $done >= $total; // Consider done if at least one is done, but show progress
                    $show_count = in_array($proc, ['Stranding', 'Insulation']);
                    $count_text = $show_count ? " ($done/$total)" : "";
                    ?>
                    <?php if ($is_done): ?>
                        <span class="process-done"><i class="fas fa-check"></i> <?= $proc ?><?= $count_text ?></span>
                    <?php else: ?>
                        <span class="process-pending"><i class="fas fa-times"></i> <?= $proc ?><?= $count_text ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>

        <?php endif; ?>
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