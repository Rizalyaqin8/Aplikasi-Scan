<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}
$user_role = $_SESSION['user']['role'] ?? '';
$user_username = $_SESSION['user']['username'] ?? '';

include 'db.php';
include 'assets/phpqrcode/qrlib.php'; // Include library phpqrcode

$fo_number = $_GET['fo_number'] ?? '';
$process_param = $_GET['process'] ?? '';
if (empty($fo_number) || empty($process_param)) {
  header("Location: dashboard.php");
  exit;
}

$query = $conn->prepare("SELECT * FROM productions WHERE fo_number = ? AND process = ? LIMIT 1");
$query->bind_param("ss", $fo_number, $process_param);
$query->execute();
$result = $query->get_result();
$row = $result->fetch_assoc();

if (!$row) {
  header("Location: dashboard.php");
  exit;
}

// Decode QR code data
$qr_data = $row['qr_code'];
$data = json_decode($qr_data, true);
if (!$data) {
  $data = [];
}

// Generate QR Code ke file PNG sementara
$filename = 'temp_qr_view.png';
QRcode::png($qr_data, $filename, QR_ECLEVEL_L, 4);

// Baca file PNG, ubah ke base64 untuk ditampilkan di browser
$qrImageData = base64_encode(file_get_contents($filename));

// Hapus file PNG sementara setelah diubah ke base64
unlink($filename);

// Format base64 untuk tag img
$qrImageData = 'data:image/png;base64,' . $qrImageData;

// Extract details
$fo_number = $data['fo_number'] ?? $row['fo_number'];
$cable_type = $data['cable_type'] ?? $row['cable_type'];
$machine = $data['machine'] ?? $row['machine'];
$process = $data['process'] ?? $row['process'];
$order_number = $data['order_number'] ?? '';
$oa_customer = $data['oa_customer'] ?? '';
$length = $data['length'] ?? $row['qty'];

// Format production date
$date_formatted = date('d F Y'); // default to current
if (isset($data['date']) && !empty($data['date'])) {
  $date_obj = new DateTime($data['date']);
  $date_formatted = $date_obj->format('d F Y');
}

// Determine level_process if possible (this might not be stored, so assume based on process)
$level_process = '';
$processes_l3 = ['cabling', 'dr', 'dr (delivery)', 'inner sheath', 'armouring', 'outer sheath', 'final test', 'packing'];
if (in_array(strtolower($process), $processes_l3)) {
  $level_process = 'L3';
} elseif (in_array(strtolower($process), ['drawing', 'insulation', 'stranding'])) {
  $level_process = 'L2';
} else {
  $level_process = 'L1';
}

// deteksi halaman aktif
$current_page = 'dashboard.php'; // since it's subpage
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>View QR Code - <?php echo htmlspecialchars($fo_number); ?></title>
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
      background: #0d47a1;
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 20px 0;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    .logo-title {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
    }

    .logo-title img {
      width: 35px;
      height: auto;
      margin-right: 10px;
    }

    .logo-title h4 {
      font-size: 16px;
      margin: 0;
      font-weight: bold;
      color: #fff;
    }

    .sidebar a {
      color: white;
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
      background: rgba(255,255,255,0.2);
      color: #fff;
      transform: translateX(5px);
    }

    .content {
      margin-left: 260px;
      padding: 20px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
      background: #fff;
      border-radius: 15px;
      margin-right: 20px;
      margin-top: 20px;
      margin-bottom: 20px;
    }

    .header {
      background: #1976d2;
      color: white;
      padding: 20px;
      border-radius: 15px;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .btn-primary {
      background: #1976d2;
      border: none;
      transition: background 0.3s;
    }

    .btn-primary:hover {
      background: #1565c0;
    }

    .alert {
      border-radius: 10px;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        position: relative;
      }

      .content {
        margin-left: 0;
      }
    }

    /* Print styles */
    @media print {
      body * {
        visibility: hidden;
      }
      #print-area,
      #print-area * {
        visibility: visible;
      }
      #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        box-shadow: none;
        background: #fff !important;
      }
      #print-area table {
        border: 1px solid #000 !important;
        border-collapse: collapse !important;
      }
      #print-area th,
      #print-area td {
        border: 1px solid #000 !important;
        background: #fff !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      .btn {
        display: none !important;
      }
      .sidebar,
      .header,
      .content > *:not(#print-area) {
        display: none !important;
      }
    }

    /* Ensure table borders for screen */
    #print-area table.table-bordered {
      border: 1px solid #000;
    }
    #print-area table.table-bordered th,
    #print-area table.table-bordered td {
      border: 1px solid #000;
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
        </div>
    </div>

  <div class="sidebar">
    <div class="logo-title">
      <img src="assets/img/logo.png" alt="Logo">
      <h4>PT Kabelindo Murni.Tbk</h4>
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
      <a href="manajemen_user.php"><i class="fas fa-users"></i> Manajemen User</a>
    <?php endif; ?>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <div class="user-info" style="text-align: center; padding: 10px; border-top: 1px solid #495057; margin-top: auto;">
      <small style="color: #666;">Logged in as:</small><br>
      <strong style="color: #333;"><?php echo htmlspecialchars($user_username); ?></strong><br>
      <small style="color: #666;"><?php echo htmlspecialchars(ucfirst($user_role)); ?></small>
    </div>
  </div>

  <div class="content">
    <div class="header">
      <h3><i class="fas fa-qrcode"></i> View QR Code - <?php echo htmlspecialchars($fo_number); ?></h3>
      <p>Detail QR Code untuk FO <?php echo htmlspecialchars($fo_number); ?>.</p>
    </div>

    <!-- TAMPILAN LABEL QR -->
    <div id="print-area" class="label-container border p-3" style="max-width: 700px; margin:auto; font-family: Arial, sans-serif; background:#fff;">

      <!-- Bagian atas: QR Code kiri + detail kanan -->
      <?php
      $processes_with_special_layout = ['cabling', 'dr', 'inner sheath', 'armouring', 'outer sheath', 'final test', 'packing'];
      if (in_array(strtolower($process), $processes_with_special_layout) || $level_process === 'L3') {
      ?>
        <div style="text-align: left; margin-bottom: 1px; font-size: 13px;">
          <strong>PT. KABELINDO MURNI Tbk.</strong>
        </div>
        <div class="d-flex">
          <div class="qr-code me-3 text-center">
            <img src="<?= $qrImageData ?>" alt="QR Code" style="width:120px; height:120px;">
          </div>
          <div class="detail flex-grow-1" style="font-size: 12px; line-height: 1.2;">
            START/FINISH<br>
            <span><?= htmlentities($fo_number) ?></span><br>
            <?= htmlentities($cable_type) ?><br>
            <?php if ($level_process === 'L3' || in_array(strtolower($process), ['drawing', 'insulation', 'stranding'])): ?>
              Order : <?= htmlentities($order_number) ?> Length : <?= number_format($length, 0, ',', '.') ?> m<br>
              Date : <?= htmlentities($date_formatted) ?><br>
              OA Customer : <?= htmlentities($oa_customer) ?><br>
            <?php else: ?>
              Length : <?= number_format($length, 0, ',', '.') ?> m<br>
            <?php endif; ?>
            SNI IEC 60502-1
          </div>
        </div>

        <!-- Tabel proses -->
        <table class="table table-bordered mt-3" style="font-size:12px; width: 100%; border-collapse: collapse; border: 1px solid #000;">
          <thead class="bg-light text-center">
            <tr>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Process</th>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Qty</th>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Machine</th>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Date</th>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Operator</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="border: 1px solid #000; font-weight: bold; padding: 4px;"><?= htmlentities($process) ?></td>
              <td style="border: 1px solid #000; padding: 4px;"><?= number_format($length, 0, ',', '.') ?></td>
              <td style="border: 1px solid #000; padding: 4px;"><?= htmlentities($machine) ?></td>
              <td style="border: 1px solid #000; padding: 4px;"></td>
              <td style="border: 1px solid #000; padding: 4px;"></td>
            </tr>
          </tbody>
        </table>
      <?php
      } else {
      ?>
        <div style="text-align: center; margin-bottom: 10px; font-size: 11px;">
          <strong>PT. KABELINDO MURNI Tbk.</strong>
        </div>
        <div class="d-flex">
          <div class="qr-code me-3 text-center">
            <img src="<?= $qrImageData ?>" alt="QR Code" style="width:120px; height:120px;">
          </div>

          <div class="detail flex-grow-1">
            START/FINISH<br>
            <span><?= htmlentities($fo_number) ?></span><br>
            <?= htmlentities($cable_type) ?><br>
            Length : <?= number_format($length, 0, ',', '.') ?> m<br>
            Date : <?= htmlentities($date_formatted) ?><br>
            SNI IEC 60502-1:2009
          </div>
        </div>

        <!-- Tabel proses -->
        <table class="table table-bordered mt-3" style="font-size:12px; width: 100%; border-collapse: collapse; border: 1px solid #000;">
          <thead class="text-center">
            <tr>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Process</th>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Qty</th>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Machine</th>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Date</th>
              <th style="border: 1px solid #000; background: #fff; color: #000; padding: 4px;">Operator</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="border: 1px solid #000; font-weight: bold; padding: 4px;"><?= htmlentities($process) ?></td>
              <td style="border: 1px solid #000; padding: 4px;"><?= number_format($length, 0, ',', '.') ?></td>
              <td style="border: 1px solid #000; padding: 4px;"><?= htmlentities($machine) ?></td>
              <td style="border: 1px solid #000; padding: 4px;"><?= htmlentities($date_formatted) ?></td>
              <td style="border: 1px solid #000; padding: 4px;"></td>
            </tr>
          </tbody>
        </table>
      <?php
      }
      ?>

      <!-- Bagian bawah -->
      <div class="d-flex justify-content-between" style="font-size:12px;">
        <span></span>
        <span><?= date('j F Y g:i:s A') ?></span>
      </div>
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
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target) && window.innerWidth <= 767) {
                sidebar.classList.remove('open');
            }
        });

        function printQR() {
            window.print();
        }

        function saveQR() {
            const printArea = document.getElementById('print-area');
            const buttons = printArea.querySelectorAll('.btn');
            buttons.forEach(btn => btn.style.display = 'none'); // Hide buttons temporarily

            html2canvas(printArea, {
                backgroundColor: '#ffffff',
                scale: 2,
                useCORS: true,
                allowTaint: true,
                ignoreElements: function(element) {
                    return element.tagName.toLowerCase() === 'script' || element.classList.contains('btn');
                }
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'qr_label_<?= htmlspecialchars($fo_number) ?>.png';
                link.href = canvas.toDataURL();
                link.click();

                // Show buttons again
                buttons.forEach(btn => btn.style.display = '');
            });
        }
    </script>

  </div>
</body>

</html>
