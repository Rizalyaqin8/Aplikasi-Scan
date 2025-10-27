<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$user_role = $_SESSION['user']['role'] ?? '';
$user_username = $_SESSION['user']['username'] ?? '';

// deteksi halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);

include 'db.php';

if (!isset($_GET['fo_number']) && (!isset($_GET['id']) || !is_numeric($_GET['id']))) {
    header("Location: labels.php");
    exit;
}

$labels = [];
$grouped_labels = [];
$level_filter = $_GET['level'] ?? null;

if (isset($_GET['fo_number'])) {
    $fo_number = $_GET['fo_number'];

    // Query to get labels for the FO number, filtered by level if provided
    $query = "SELECT * FROM productions WHERE fo_number = ? AND qr_image_path IS NOT NULL";
    $params = [$fo_number];
    $types = "s";

    if ($level_filter) {
        $processes = [];
        if ($level_filter === 'L2') {
            $processes = ['Stranding', 'Insulation'];
        } elseif ($level_filter === 'L3') {
            $processes = ['Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR'];
        } elseif ($level_filter === 'L1') {
            // L1 is everything else, so exclude L2 and L3 processes
            $query .= " AND process NOT IN ('Stranding', 'Insulation', 'Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR')";
        } else {
            $processes = [];
        }

        if (!empty($processes)) {
            $placeholders = str_repeat('?,', count($processes) - 1) . '?';
            $query .= " AND process IN ($placeholders)";
            $params = array_merge($params, $processes);
            $types .= str_repeat('s', count($processes));
        }
    }

    $query .= " ORDER BY CASE process
        WHEN 'Stranding' THEN 1
        WHEN 'Insulation' THEN 2
        WHEN 'Cabling' THEN 3
        WHEN 'Inner Sheath' THEN 4
        WHEN 'Armouring' THEN 5
        WHEN 'Outer Sheath' THEN 6
        WHEN 'Final Test' THEN 7
        WHEN 'Packing' THEN 8
        WHEN 'DR' THEN 9
        ELSE 10 END, process";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: labels.php");
        exit;
    }

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row;
        $process = $row['process'];
        $grouped_labels[$process][] = $row;
    }
} else {
    $id = intval($_GET['id']);

    // Query to get the specific label data
    $query = "SELECT * FROM productions WHERE id = ? AND qr_image_path IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: labels.php");
        exit;
    }

    $labels[] = $result->fetch_assoc();
    $process = $labels[0]['process'];
    $grouped_labels[$process][] = $labels[0];
}


?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Label</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
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

        .sidebar .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .sidebar .logo img {
            width: 35px;
            margin-right: 10px;
        }

        .sidebar .logo h4 {
            margin: 0;
            font-size: 16px;
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

        @media (min-width: 1025px) {
            .content {
                margin-left: 20px;
            }
        }

        .header {
            background: #ffffff;
            ;
            color: black;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;

        }

        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #1976d2;
            border: none;
        }

        .btn-primary:hover {
            background: #1565c0;
            transform: translateY(-2px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-success:hover,
        .btn-info:hover,
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        #print-area {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background: #fff;
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

        @media (max-width: 1024px) {
            .sidebar {
                display: none;
            }

            .sidebar.open {
                display: flex !important;
            }

            .content {
                margin-left: 0;
            }

            .label-container {
                max-width: 100% !important;
                padding: 10px !important;
            }

            .table-responsive table {
                font-size: 10px !important;
            }

            .detail {
                font-size: 10px !important;
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
            <h4>View Label</h4>
            <p>QR Code Label Details</p>
        </div>

        <!-- Loop through grouped labels by process -->
        <div id="all-print-areas">
            <?php $global_index = 0; ?>
            <?php foreach ($grouped_labels as $process => $rows): ?>
                <h5 class="text-center mb-3">Process: <?= htmlspecialchars($process) ?></h5>
                <?php foreach ($rows as $row): ?>
                    <div id="print-area-<?= $global_index ?>" class="label-container border p-3 mb-4" style="max-width: 700px; margin:auto; font-family: Arial, sans-serif; background:#fff;">

                    <!-- Decode QR code data for each label -->
                    <?php
                    $qr_data = json_decode($row['qr_code'], true);
                    $fo_number = $qr_data['fo_number'] ?? $row['fo_number'];
                    $cable_type = $qr_data['cable_type'] ?? $row['cable_type'];
                    $machine = $qr_data['machine'] ?? $row['machine'];
                    $process = $qr_data['process'] ?? $row['process'];
                    $order_number = $qr_data['order_number'] ?? '';
                    $oa_customer = $qr_data['oa_customer'] ?? '';
                    $date_production = $qr_data['date'] ?? '';
                    $length = $qr_data['length'] ?? $row['qty'];
                    $bobin = $qr_data['bobin'] ?? '';
                    $diameter = $qr_data['diameter'] ?? '';
                    $wire_length = $qr_data['wire_length'] ?? '';

                    if ($date_production) {
                        $date_obj = new DateTime($date_production);
                        $date = $date_obj->format('d F Y');
                    } else {
                        $date = date('d F Y');
                    }

                    // Determine level process based on process
                    if (in_array($process, ['Stranding', 'Insulation'])) {
                        $level_process = 'L2';
                    } elseif (in_array($process, ['Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR'])) {
                        $level_process = 'L3';
                    } else {
                        $level_process = 'L1';
                    }
                    ?>

                    <!-- Bagian atas: QR Code kiri + detail kanan -->
                    <?php
                    $processes_with_special_layout = ['cabling', 'inner sheath', 'armouring', 'outer sheath', 'final test', 'packing', 'dr'];
                    if (($level_process === 'L2' || $level_process === 'L3') && in_array(strtolower($process), $processes_with_special_layout)) {
                    ?>
                        <div style="text-align: left; margin-bottom: 1px; font-size: 13px;">
                            <strong>PT. KABELINDO MURNI Tbk.</strong>
                        </div>
                        <div class="d-flex mb-4 align-items-center">
                            <div class="qr-code me-3 text-center" style="flex-shrink: 0;">
                                <img src="<?= htmlspecialchars($row['qr_image_path']) ?>" alt="QR Code" style="width:120px; height:120px;">
                            </div>
                            <div class="detail flex-grow-1" style="font-size: 12px; line-height: 1.4; margin-top: 0;">
                                Start/Finish<br>
                                <span><?= htmlspecialchars($fo_number) ?></span><br>
                                <?= htmlspecialchars($cable_type) ?><br>
                                <?php if ($level_process !== 'L2'): ?>
                                    Order : <?= htmlspecialchars($order_number) ?> Length : <?= number_format($length, 0, ',', '.') ?> m<br>
                                    Date : <?= htmlspecialchars($date) ?><br>
                                    OA Customer : <?= htmlspecialchars($oa_customer) ?><br>
                                <?php else: ?>
                                    Length : <?= number_format($length, 0, ',', '.') ?> m<br>
                                <?php endif; ?>
                                SNI IEC 60502-1
                            </div>
                        </div>

                        <!-- Tabel proses -->
                        <div class="table-responsive">
                            <table class="table table-bordered mt-3" style="font-size:12px; width: 100%; border-collapse: collapse;">
                                <thead class="bg-light text-center" style="border: 1px solid #000;">
                                    <tr>
                                        <th style="border: 1px solid #000;">Process</th>
                                        <th style="border: 1px solid #000;">Qty</th>
                                        <th style="border: 1px solid #000;">Machine</th>
                                        <th style="border: 1px solid #000;">Date</th>
                                        <th style="border: 1px solid #000;">Operator</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border: 1px solid #000;">
                                        <td style="border: 1px solid #000; font-weight: bold; padding: 2px 5px;"><?= htmlspecialchars($process) ?></td>
                                        <td style="border: 1px solid #000; padding: 2px 5px;"><?= number_format($length, 0, ',', '.') ?></td>
                                        <td style="border: 1px solid #000; padding: 2px 5px;"><?= htmlspecialchars($machine) ?></td>
                                        <td style="border: 1px solid #000; padding: 2px 5px;"></td>
                                        <td style="border: 1px solid #000; padding: 2px 5px;"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php
                    } else {
                        if ($level_process === 'L1' && strtolower($process) === 'drawing') {
                            // Special display for L1 Drawing
                            ?>
                            <div style="text-align: left; margin-bottom: 10px; font-size: 11px;">
                                <strong>PT. KABELINDO MURNI Tbk.</strong>
                            </div>
                            <div class="d-flex">
                                <div class="qr-code me-3 text-center">
                                    <img src="<?= htmlspecialchars($row['qr_image_path']) ?>" alt="QR Code" style="width:120px; height:120px;">
                                    <br>
                                    <div style="text-align: left; margin-bottom: 10px; font-size: 12px;"> <br>
                                <strong>DRAWING</strong>
                            </div>
                                </div>
                                <div class="detail flex-grow-1" style="font-size: 12px; line-height: 1.4;">
                                    START/FINISH<br>
                                    <span><?= htmlspecialchars($fo_number) ?></span><br>
                                    Bobin : <?= htmlspecialchars($bobin) ?><br>
                                    Diameter : <?= htmlspecialchars($diameter) ?><br>
                                    Wire Length : <?= number_format($wire_length, 0, ',', '.') ?> m<br>
                                    Date : <?= htmlspecialchars($date) ?><br>
                                    RPRP-2510-00012
                                </div>
                            </div>
                            <?php
                        } else {
                            // General display for other processes
                            ?>
                            <div style="text-align: left; margin-bottom: 10px; font-size: 11px;">
                                <strong>PT. KABELINDO MURNI Tbk.</strong>
                            </div>
                            <div class="d-flex">
                                <div class="qr-code me-3 text-center">
                                    <img src="<?= htmlspecialchars($row['qr_image_path']) ?>" alt="QR Code" style="width:120px; height:120px;">
                                </div>

                                <div class="detail flex-grow-1">
                                    START/FINISH<br>
                                    <span><?= htmlspecialchars($fo_number) ?></span><br>
                                    <?= htmlspecialchars($cable_type) ?><br>
                                    Length : <?= number_format($length, 0, ',', '.') ?> m<br>
                                    SNI IEC 60502-1:2009
                                </div>
                            </div>

                            <!-- Tabel proses -->
                            <div class="table-responsive">
                                <table class="table table-bordered mt-3" style="font-size:14px;">
                                    <thead class="bg-light text-center">
                                        <tr>
                                            <th>Process</th>
                                            <th>Qty</th>
                                            <th>Machine</th>
                                            <th>Date</th>
                                            <th>Operator</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?= htmlspecialchars($process) ?></td>
                                            <td><?= number_format($length, 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($machine) ?></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <?php
                        }
                    }
                    ?>

                    <!-- Bagian bawah -->
                    <div class="d-flex justify-content-between" style="font-size:12px;">
                        <span></span>
                        <span><?= date('j F Y g:i:s A') ?></span>
                    </div>
                </div>
                <?php $global_index++; ?>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <!-- Tombol print dan save -->
        <div class="text-center mt-3">
            <button onclick="printQR()" class="btn btn-success">
                <i class="fas fa-print"></i> Cetak QR
            </button>
            <button onclick="saveQR()" class="btn btn-info">
                <i class="fas fa-download"></i> Simpan QR
            </button>
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
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target) && window.innerWidth <= 1024) {
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

            function printQR() {
                var printContents = '';
                var labels = document.querySelectorAll('[id^="print-area-"]');
                labels.forEach(function(label) {
                    printContents += label.outerHTML + '<br>';
                });
                var originalContents = document.body.innerHTML;

                document.body.innerHTML = printContents;
                window.print();
                document.body.innerHTML = originalContents;
                location.reload(); // reload biar balik normal
            }

            function saveQR() {
                var container = document.getElementById('all-print-areas');
                var originalStyle = container.style.cssText;
                container.style.margin = '0';
                container.style.width = 'auto';
                container.style.maxWidth = 'none';
                html2canvas(container).then(canvas => {
                    container.style.cssText = originalStyle;
                    const link = document.createElement('a');
                    link.download = 'qr_labels.png';
                    link.href = canvas.toDataURL();
                    link.click();
                });
            }
        </script>
    </div>
</body>

</html>