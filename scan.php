<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$user_role = $_SESSION['user']['role'] ?? '';
$user_username = $_SESSION['user']['username'] ?? '';
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .sidebar.open {
            display: flex !important;
        }

        .card {
            margin-top: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        #reader {
            width: 100%;
            max-width: 300px;
            height: auto;
            margin: 10px auto;
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

        @media (max-width: 767px) {
            .sidebar {
                display: none;
            }

            .sidebar.open {
                display: flex !important;
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
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <?php if ($user_role === 'admin'): ?>
            <a href="generate_qr.php"><i class="fas fa-qrcode"></i> Generate QR</a>
        <?php endif; ?>
        <a href="scan.php" class="active"><i class="fas fa-barcode"></i>Scan QR</a>
        <a href="history.php"><i class="fas fa-history"></i> History</a>
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
            <h4>📷 Scan QR Code</h4>
            <p>Gunakan kamera untuk memindai QR Code.</p>
        </div>

        <div class="card">
            <div class="card-body text-center">
                <div id="reader"></div>
                <p class="mt-3 text-muted">Pastikan QR Code terlihat jelas di kamera.</p>
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

    <form id="scanForm" action="scan.php" method="post" style="display:none;">
        <input type="hidden" name="qr_data" id="qr_data">
    </form>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        function onScanSuccess(decodedText) {
            document.getElementById("qr_data").value = decodedText;
            document.getElementById("scanForm").submit();
        }

        function onScanFailure(error) {
            console.warn(`QR scan failed: ${error}`);
        }

        document.addEventListener("DOMContentLoaded", function() {
            let html5QrcodeScanner = new Html5Qrcode("reader");

            Html5Qrcode.getCameras().then(cameras => {
                if (cameras && cameras.length > 0) {
                    let cameraId = cameras[0].id;
                    html5QrcodeScanner.start(
                        cameraId, {
                            fps: 10,
                            qrbox: {
                                width: 200,
                                height: 200
                            }
                        },
                        onScanSuccess,
                        onScanFailure
                    ).catch(err => console.error("Camera start failed", err));
                } else {
                    alert("Tidak ada kamera terdeteksi.");
                }
            }).catch(err => console.error("Error mendapatkan kamera", err));
        });
    </script>
</body>

</html>

<?php
// === Proses setelah submit QR ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['qr_data'])) {
    $qr = $_POST['qr_data'];
    $data = json_decode($qr, true);

    if ($data && isset($data['fo_number'], $data['process'])) {
        $fo_number = $conn->real_escape_string($data['fo_number']);
        $process = $conn->real_escape_string($data['process']);
        $machine = $conn->real_escape_string($data['machine'] ?? '');
        $qty = intval($data['qty']);

        // cek apakah qr ada di productions
        $check = $conn->query("SELECT id, scan_in, scan_out FROM productions WHERE qr_code='" . $conn->real_escape_string($qr) . "' LIMIT 1");

        if ($row = $check->fetch_assoc()) {
            if ($row['scan_in'] == null) {
                // scan pertama, set scan_in (update only one record)
                $conn->query("UPDATE productions SET scan_in=NOW(), scan_operator='" . $conn->real_escape_string($user_username) . "' WHERE qr_code='" . $conn->real_escape_string($qr) . "' AND scan_in IS NULL LIMIT 1");
                echo "<script>alert('QR Code berhasil discan sebagai Proses IN / Start');window.location.href='history.php';</script>";
            } elseif ($row['scan_out'] == null) {
                // scan kedua, set scan_out dan scan_time (update only one record)
                $conn->query("UPDATE productions SET scan_out=NOW(), scan_time=NOW(), scan_operator='" . $conn->real_escape_string($user_username) . "' WHERE qr_code='" . $conn->real_escape_string($qr) . "' AND scan_out IS NULL LIMIT 1");
                echo "<script>alert('QR Code berhasil discan sebagai Proses OUT / Finish');window.location.href='history.php';</script>";
            } else {
                echo "<script>alert('QR Code ini sudah selesai diproses!');window.location.href='history.php';</script>";
            }
        } else {
            echo "<script>alert('QR tidak ditemukan di database!');window.location.href='scan.php';</script>";
        }
    } else {
        echo "<script>alert('Data QR tidak valid!');window.location.href='scan.php';</script>";
    }
    exit;
}
?>