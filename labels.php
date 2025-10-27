<?php
date_default_timezone_set('Asia/Jakarta');
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

// Handle success/error messages
$err = "";
$success = "";
if (isset($_SESSION['error'])) {
    $err = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Query to get FO numbers with their levels (showing each level separately if multiple)
$query = "SELECT fo_number,
    CASE
        WHEN process IN ('Stranding', 'Insulation') THEN 'L2'
        WHEN process IN ('Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR') THEN 'L3'
        ELSE 'L1' END as level,
    MIN(qr_image_path) as qr_image_path,
    MIN(cable_type) as cable_type
    FROM productions WHERE qr_image_path IS NOT NULL
    GROUP BY fo_number, CASE WHEN process IN ('Stranding', 'Insulation') THEN 'L2' WHEN process IN ('Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR') THEN 'L3' ELSE 'L1' END ORDER BY level, fo_number DESC";

$result = $conn->query($query);

// Group by level
$grouped_labels = ['L1' => [], 'L2' => [], 'L3' => []];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $grouped_labels[$row['level']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Labels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
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
        <a href="labels.php" class="active"><i class="fas fa-tags"></i> Labels Produksi</a>
        <a href="manajemen_user.php"><i class="fas fa-users"></i> Manajemen User</a>
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
            margin-top: 0px;
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
            <h4>Labels</h4>
            <p>Labels Hasil Generate</p>
            <?php if (!empty($err)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($err); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="labelsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>FO Number</th>
                                <th>Type Kabel</th>
                                <th>Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            foreach ($grouped_labels as $level => $labels):
                                if (empty($labels)) continue;
                            ?>

                                <?php foreach ($labels as $row): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['fo_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['cable_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['level']); ?></td>
                                        <td>
                                            <a href="view_label.php?fo_number=<?php echo htmlspecialchars($row['fo_number']); ?>&level=<?php echo htmlspecialchars($row['level']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo htmlspecialchars($row['qr_image_path']); ?>" download class="btn btn-success btn-sm">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button onclick="printLabel('<?php echo htmlspecialchars($row['fo_number']); ?>')" class="btn btn-info btn-sm">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <form method="POST" action="delete_label.php" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus label untuk FO <?php echo htmlspecialchars($row['fo_number']); ?> level <?php echo htmlspecialchars($row['level']); ?>?')">
                                                <input type="hidden" name="fo_number" value="<?php echo htmlspecialchars($row['fo_number']); ?>">
                                                <input type="hidden" name="level" value="<?php echo htmlspecialchars($row['level']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
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
        $(document).ready(function() {
            $('#labelsTable').DataTable({
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

        function printLabel(fo_number) {
            window.open('view_label.php?fo_number=' + fo_number, '_blank').print();
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