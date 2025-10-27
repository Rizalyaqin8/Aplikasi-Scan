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
include 'assets/phpqrcode/qrlib.php'; // Include library phpqrcode

$err = "";
$success = "";
$qrImageData = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Ensure qr_images directory exists
        if (!is_dir('qr_images')) {
            mkdir('qr_images', 0755, true);
        }
        $level_process = $_POST['level_process'] ?? '';
        $fo_number = $_POST['fo_number'];
        $cable_type = $_POST['cable_type'] ?? '';
        $order_number = $_POST['order_number'] ?? '';
        $oa_customer = $_POST['oa_customer'] ?? '';
        $date_production = $_POST['date_production'] ?? '';
        if ($date_production) {
            $date_obj = new DateTime($date_production);
            $date = $date_obj->format('d F Y');
        } else {
            $date = date('d F Y');
        }
        $length = intval($_POST['length'] ?? 0);
        if ($level_process === 'L1') {
            $process = 'Drawing';
            $machine = '';
            $bobin = $_POST['bobin'] ?? '';
            $diameter = $_POST['diameter'] ?? '';
            $wire_length = intval($_POST['wire_length'] ?? 0);
            $length = $wire_length;
            $data = [
                'fo_number' => $fo_number,
                'cable_type' => $cable_type,
                'machine' => $machine,
                'process' => $process,
                'order_number' => $order_number,
                'oa_customer' => $oa_customer,
                'date' => $date_production,
                'length' => $length,
                'bobin' => $bobin,
                'diameter' => $diameter,
                'wire_length' => $wire_length
            ];
            $data = json_encode($data);
            $timestamp = time();
            $filename = 'qr_images/qr_' . strtolower(str_replace(' ', '_', $process)) . '_' . $timestamp . '.png';
            QRcode::png($data, $filename, QR_ECLEVEL_L, 4);
            if (file_exists($filename)) {
                $qrImageData = $filename;
                $stmt = $conn->prepare("INSERT INTO productions (fo_number, cable_type, machine, process, qty, qr_code, qr_image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiss", $fo_number, $cable_type, $machine, $process, $length, $data, $filename);
                if ($stmt->execute()) {
                    $success = "QR Code untuk Drawing berhasil dibuat dan data disimpan.";
                } else {
                    $err = "Gagal menyimpan data ke database.";
                }
            } else {
                $err = "Gagal membuat QR Code untuk Drawing.";
            }
        } elseif ($level_process === 'L2') {
            $machine_stranding = $_POST['machine_stranding'] ?? '';
            $machine_insulation = $_POST['machine_insulation'] ?? '';
            $processes = ['Stranding', 'Insulation'];
            $machines = [$machine_stranding, $machine_insulation];
            $qrImageData = [];
            $err = "";
            foreach ($processes as $index => $process) {
                $machine = $machines[$index];
                // Generate 4 QR codes for each process
                for ($i = 1; $i <= 4; $i++) {
                    $data = json_encode([
                        'fo_number' => $fo_number,
                        'cable_type' => $cable_type,
                        'machine' => $machine,
                        'process' => $process,
                        'order_number' => $order_number,
                        'oa_customer' => $oa_customer,
                        'date' => $date_production,
                        'length' => $length,
                        'qr_id' => $i
                    ]);
                    $timestamp = time() . '_' . $index . '_' . $i;
                    $filename = 'qr_images/qr_' . strtolower(str_replace(' ', '_', $process)) . '_' . $timestamp . '.png';
                    QRcode::png($data, $filename, QR_ECLEVEL_L, 4);
                    if (file_exists($filename)) {
                        $qrImageData[$process . '_' . $i] = $filename;
                        $stmt = $conn->prepare("INSERT INTO productions (fo_number, cable_type, machine, process, qty, qr_code, qr_image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssiss", $fo_number, $cable_type, $machine, $process, $length, $data, $filename);
                        if (!$stmt->execute()) {
                            $err .= "Gagal menyimpan data proses $process ($i) ke database. ";
                        }
                    } else {
                        $err .= "Gagal membuat QR Code untuk $process ($i). ";
                    }
                }
            }
            if (!$err) {
                $success = "QR Code untuk 4 Stranding dan 4 Insulation berhasil dibuat dan data disimpan.";
            }
        } elseif ($level_process === 'L3') {
            $processes = ['Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR'];
            $machines = [];
            foreach ($processes as $process) {
                $machine_key = 'machine_' . strtolower(str_replace(' ', '_', $process));
                $machines[$process] = $_POST[$machine_key] ?? '';
            }
            $qrImageData = [];
            $err = "";
            foreach ($processes as $process) {
                $machine = $machines[$process];
                if (!empty($machine)) {
                    $data = json_encode([
                        'fo_number' => $fo_number,
                        'cable_type' => $cable_type,
                        'machine' => $machine,
                        'process' => $process,
                        'order_number' => $order_number,
                        'oa_customer' => $oa_customer,
                        'date' => $date_production,
                        'length' => $length
                    ]);
                    $timestamp = time() . '_' . strtolower(str_replace(' ', '_', $process));
                    $filename = 'qr_images/qr_' . strtolower(str_replace(' ', '_', $process)) . '_' . $timestamp . '.png';
                    QRcode::png($data, $filename, QR_ECLEVEL_L, 4);
                    $qrImageData[$process] = $filename;
                    $stmt = $conn->prepare("INSERT INTO productions (fo_number, cable_type, machine, process, qty, qr_code, qr_image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssiss", $fo_number, $cable_type, $machine, $process, $length, $data, $filename);
                    if (!$stmt->execute()) {
                        $err .= "Gagal menyimpan data proses $process ke database. ";
                    }
                }
            }
            if (!$err) {
                $success = "QR Code untuk proses L3 yang diisi berhasil dibuat dan data disimpan.";
            }
        } else {
            if ($level_process === 'L1') {
                $process = 'Drawing';
                $machine = '';
            } elseif ($level_process !== 'L1') {
                $machine = $_POST['machine'];
                $process = $_POST['process'];
            }
            $bobin = '';
            $diameter = '';
            $wire_length = 0;
            if ($process === 'Drawing') {
                $bobin = $_POST['bobin'] ?? '';
                $diameter = $_POST['diameter'] ?? '';
                $wire_length = intval($_POST['wire_length'] ?? 0);
            }
            $data = [
                'fo_number' => $fo_number,
                'cable_type' => $cable_type,
                'machine' => $machine,
                'process' => $process,
                'order_number' => $order_number,
                'oa_customer' => $oa_customer,
                'date' => $date_production,
                'length' => $length
            ];
            if ($process === 'Drawing') {
                $data['bobin'] = $bobin;
                $data['diameter'] = $diameter;
                $data['wire_length'] = $wire_length;
            }
            $data = json_encode($data);
            $timestamp = time();
            $filename = 'qr_images/qr_' . strtolower(str_replace(' ', '_', $process)) . '_' . $timestamp . '.png';
            if (!is_dir('qr_images')) {
                mkdir('qr_images', 0755, true);
            }
            QRcode::png($data, $filename, QR_ECLEVEL_L, 4);
            $qrImageData = $filename;
            $stmt = $conn->prepare("INSERT INTO productions (fo_number, cable_type, machine, process, qty, qr_code, qr_image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiss", $fo_number, $cable_type, $machine, $process, $length, $data, $filename);
            if ($stmt->execute()) {
                $success = "QR Code berhasil dibuat dan data disimpan.";
            } else {
                $err = "Gagal menyimpan data ke database.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Generate QR Code</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet"/>
    <link href="assets/css/style.css" rel="stylesheet"/>
    <style>
        .sidebar.open {
            display: flex !important;
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

        .user-info {
            display: flex;
            align-items: center;
        }
    </style>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

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
        <a href="generate_qr.php" class="active"><i class="fas fa-qrcode"></i> Generate QR</a>
        <a href="scan.php"><i class="fas fa-barcode"></i> Scan QR</a>
        <a href="history.php"><i class="fas fa-history"></i> History</a>
        <a href="monitoring.php"><i class="fas fa-desktop"></i> Monitoring</a>
        <a href="laporan.php"><i class="fas fa-file-export"></i> Laporan </a>
         <a href="labels.php"><i class="fas fa-tags"></i> Labels Produksi</a>
        <a href="manajemen_user.php"><i class="fas fa-users"></i> Manajemen User</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

<div class="content">
    <div class="header">
        <h4><i class="fas fa-qrcode"></i> Generate QR Code</h4>
        <p>Pilih level data produksi untuk menghasilkan QR Code.</p>
    </div>

        <?php if (!empty($qrImageData)): ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?php echo $err; ?></div>
        <?php endif; ?>

        <!-- TAMPILAN LABEL QR SETELAH BERHASIL GENERATE -->
        <div id="print-area" class="label-container border p-3" style="max-width: 700px; margin:auto; font-family: Arial, sans-serif; background:#fff;">

          <!-- Bagian atas: QR Code kiri + detail kanan -->
          <?php
          $processes_with_special_layout = ['cabling', 'dr (delivery)', 'inner sheath', 'armouring', 'outer sheath', 'final test', 'packing'];
          $level_process = $level_process ?? '';
          if (($level_process === 'L2' || $level_process === 'L3') && is_array($qrImageData)) {
              // Special case for L2 and L3 with multiple QR codes
              // Prepare machine mapping for display
              if ($level_process === 'L2') {
                  $machines = [
                      'Stranding' => $_POST['machine_stranding'] ?? '',
                      'Insulation' => $_POST['machine_insulation'] ?? ''
                  ];
              } else {
                  $processes = ['Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR'];
                  $machines = [];
                  foreach ($processes as $proc) {
                      $machine_key = 'machine_' . strtolower(str_replace(' ', '_', $proc));
                      $machines[$proc] = $_POST[$machine_key] ?? '';
                  }
              }
              foreach ($qrImageData as $proc => $imgData) {
                  $machine_display = $machines[$proc] ?? '';
          ?>
              <div style="text-align: left; margin-bottom: 1px; font-size: 13px;">
                <strong>PT. KABELINDO MURNI Tbk.</strong>
              </div>
              <div class="d-flex mb-4 align-items-center">
                <div class="qr-code me-3 text-center" style="flex-shrink: 0;">
                  <img src="<?= $imgData ?>" alt="QR Code <?= htmlentities($proc) ?>" style="width:120px; height:120px;">
                </div>
              <div class="detail flex-grow-1" style="font-size: 12px; line-height: 1.4; margin-top: 0;">
                Start/Finish<br>
                <span><?= htmlentities($fo_number) ?></span><br>
                <?= htmlentities($cable_type) ?><br>
                <?php if ($level_process !== 'L2'): ?>
                Order : <?= htmlentities($order_number) ?>  Length : <?= number_format($length, 0, ',', '.') ?> m<br>
                Date : <?= htmlentities($date) ?><br>
                OA Customer : <?= htmlentities($oa_customer) ?><br>
                <?php else: ?>
                Length : <?= number_format($length, 0, ',', '.') ?> m<br>
                <?php endif; ?>
                SNI IEC 60502-1
              </div>
              </div>

            <!-- Tabel proses -->
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
                  <td style="border: 1px solid #000; font-weight: bold; padding: 2px 5px;"><?= htmlentities($proc) ?></td>
                  <td style="border: 1px solid #000; padding: 2px 5px;"><?= number_format($length, 0, ',', '.') ?></td>
                  <td style="border: 1px solid #000; padding: 2px 5px;"><?= htmlentities($machine_display) ?></td>
                  <td style="border: 1px solid #000; padding: 2px 5px;"></td>
                  <td style="border: 1px solid #000; padding: 2px 5px;"></td>
                </tr>
              </tbody>
            </table>
          <?php
              }
          } elseif (in_array(strtolower($process), $processes_with_special_layout)) {
          ?>
              <div style="text-align: left; margin-bottom: 10px; font-size: 11px;">
                <strong>PT. KABELINDO MURNI Tbk.</strong>
              </div>
            <div class="d-flex">
              <div class="qr-code me-3 text-center">
                <img src="<?= $qrImageData ?>" alt="QR Code" style="width:120px; height:120px;">
              </div>
                <div class="detail flex-grow-1" style="font-size: 12px; line-height: 1.2;">
                Start/Finish<br>
                <span><?= htmlentities($fo_number) ?></span><br>
                <?= htmlentities($cable_type) ?><br>
                Order : <?= htmlentities($order_number) ?><br>
                OA Customer : <?= htmlentities($oa_customer) ?><br>
                <?php if ($level_process === 'L3' || in_array(strtolower($process), ['drawing', 'insulation', 'stranding'])): ?>
                Length : <?= number_format($length, 0, ',', '.') ?> m<br>
                <?php else: ?>
                Length : <?= number_format($length, 0, ',', '.') ?> m<br>
                <?php endif; ?>
                Date : <?= htmlentities($date) ?><br>
                SNI IEC 60502-1
              </div>
            </div>

            <!-- Tabel proses -->
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
                  <td style="border: 1px solid #000; font-weight: bold; padding: 2px 5px;"><?= htmlentities($process) ?></td>
                  <td style="border: 1px solid #000; padding: 2px 5px;"><?= number_format($length, 0, ',', '.') ?></td>
                  <td style="border: 1px solid #000; padding: 2px 5px;"><?= htmlentities($machine) ?></td>
                  <td style="border: 1px solid #000; padding: 2px 5px;"></td>
                  <td style="border: 1px solid #000; padding: 2px 5px;"></td>
                </tr>
              </tbody>
            </table>
          <?php
          } else {
            if ($level_process === 'L1') {
              // Special display for L1 Drawing
              ?>
              <div style="text-align: left; margin-bottom: 10px; font-size: 11px;">
                <strong>PT. KABELINDO MURNI Tbk.</strong>
              </div>
              <div class="d-flex">
                <div class="qr-code me-3 text-center">
                  <img src="<?= $qrImageData ?>" alt="QR Code" style="width:120px; height:120px;">
                </div>
                <div class="detail flex-grow-1" style="font-size: 12px; line-height: 1.4;">
                  START/FINISH<br>
                  <span><?= htmlentities($fo_number) ?></span><br>
                  Bobin : <?= htmlentities($bobin) ?><br>
                  Diameter : <?= htmlentities($diameter) ?><br>
                  Wire Length : <?= number_format($wire_length, 0, ',', '.') ?> m<br>
                  Date : <?= htmlentities($date) ?><br>
                  RPRP-2510-00012
                </div>
              </div>
              <?php
            } else {
              // Original display for other single processes
              ?>
              <div style="text-align: left; margin-bottom: 10px; font-size: 11px;">
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
                  SNI IEC 60502-1:2009
                </div>
              </div>

              <!-- Tabel proses -->
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
                    <td><?= htmlentities($process) ?></td>
                    <td><?= htmlentities($length) ?></td>
                    <td><?= htmlentities($machine) ?></td>
                    <td></td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
              <?php
            }
          }
          ?>

          <!-- Bagian bawah -->
          <div class="d-flex justify-content-between" style="font-size:12px;">
            <span><?= htmlentities($kode ?? '') ?></span>
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
            <a href="generate_qr.php" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Generate Ulang
            </a>
        </div>

        <script>
          function printQR() {
              var printContents = document.getElementById("print-area").innerHTML;
              var originalContents = document.body.innerHTML;

              document.body.innerHTML = printContents;
              window.print();
              document.body.innerHTML = originalContents;
              location.reload(); // reload biar balik normal
          }

          function saveQR() {
              html2canvas(document.getElementById('print-area')).then(canvas => {
                  const link = document.createElement('a');
                  link.download = 'qr_label.png';
                  link.href = canvas.toDataURL();
                  link.click();
              });
          }
        </script>
    <?php else: ?>

        <!-- FORM INPUT HANYA MUNCUL JIKA BELUM GENERATE QR -->
        <form method="POST" action="" id="qrForm">
            <div class="form-group">
                <label><i class="fas fa-layer-group"></i> Level Proses</label>
                <select name="level_process" id="level_process" class="form-control" required>
                    <option value="">Pilih Level Proses</option>
                    <option value="L1">L1 - Drawing</option>
                    <option value="L2">L2 - Stranding & Insulation</option>
                    <option value="L3">L3 - Cabling, Inner Sheath, Armouring, Outer Sheath, Final Test, Packing, DR Stock</option>
                </select>
            </div>
        <div id="additional_fields" style="display:none;">
                <div class="form-group" id="fo_group">
                    <label><i class="fas fa-hashtag"></i> Nomor FO</label>
                    <input type="text" name="fo_number" class="form-control" />
                </div>
                <div class="form-group" id="cable_type_group">
                    <label><i class="fas fa-cable"></i> Type Kabel</label>
                    <input type="text" name="cable_type" class="form-control" required id="cable_type" />
                </div>

                <!-- L1: Single machine after process -->
                <div class="form-group" id="process_group">
                    <label><i class="fas fa-tasks"></i> Proses</label>
                    <select name="process" class="form-control" required>
                        <option value="">Pilih proses</option>
                        <option>Drawing</option>
                        <option>Stranding</option>
                        <option>Insulation</option>
                        <option>Cabling</option>
                        <option>Inner Sheath</option>
                        <option>Armouring</option>
                        <option>Outer Sheath</option>
                        <option>Final Test</option>
                        <option>Packing</option>
                        <option>DR Stock</option>
                    </select>
                </div>
                <div class="form-group" id="machine_single_group">
                    <label><i class="fas fa-cogs"></i> Mesin</label>
                    <input type="text" name="machine" class="form-control" required />
                </div>

                <!-- L1 specific fields -->
                <div class="form-group" id="bobin_group" style="display:none;">
                    <label><i class="fas fa-hashtag"></i> Bobin</label>
                    <input type="text" name="bobin" class="form-control" />
                </div>
                <div class="form-group" id="diameter_group" style="display:none;">
                    <label><i class="fas fa-ruler"></i> Diameter</label>
                    <input type="text" name="diameter" class="form-control" />
                </div>
                <div class="form-group" id="wire_length_group" style="display:none;">
                    <label><i class="fas fa-sort-numeric-up"></i> Wire Length</label>
                    <input type="number" name="wire_length" class="form-control" min="1" />
                </div>

                <!-- L2: Specific machines -->
                <div class="form-group" id="machine_stranding_group" style="display:none;">
                    <label><i class="fas fa-cogs"></i> Mesin untuk Stranding</label>
                    <input type="text" name="machine_stranding" class="form-control" />
                </div>
                <div class="form-group" id="machine_insulation_group" style="display:none;">
                    <label><i class="fas fa-cogs"></i> Mesin untuk Isolasi</label>
                    <input type="text" name="machine_insulation" class="form-control" />
                </div>

                <!-- L3: Machines per process -->
                <div id="l3_machines" style="display:none;">
                    <div class="form-group" id="machine_cabling_group">
                        <label><i class="fas fa-cogs"></i> Mesin untuk Cabling</label>
                        <input type="text" name="machine_cabling" class="form-control" />
                    </div>
                    <div class="form-group" id="machine_inner_sheath_group">
                        <label><i class="fas fa-cogs"></i> Mesin untuk Inner Sheath</label>
                        <input type="text" name="machine_inner_sheath" class="form-control" />
                    </div>
                    <div class="form-group" id="machine_armouring_group">
                        <label><i class="fas fa-cogs"></i> Mesin untuk Armouring</label>
                        <input type="text" name="machine_armouring" class="form-control" />
                    </div>
                    <div class="form-group" id="machine_outer_sheath_group">
                        <label><i class="fas fa-cogs"></i> Mesin untuk Outer Sheath</label>
                        <input type="text" name="machine_outer_sheath" class="form-control" />
                    </div>
                    <div class="form-group" id="machine_final_test_group">
                        <label><i class="fas fa-cogs"></i> Mesin untuk Final Test</label>
                        <input type="text" name="machine_final_test" class="form-control" />
                    </div>
                    <div class="form-group" id="machine_packing_group">
                        <label><i class="fas fa-cogs"></i> Mesin untuk Packing</label>
                        <input type="text" name="machine_packing" class="form-control" />
                    </div>
                    <div class="form-group" id="machine_dr_group">
                        <label><i class="fas fa-cogs"></i> Mesin untuk DR Stock</label>
                        <input type="text" name="machine_dr" class="form-control" />
                    </div>
                </div>

                <div class="form-group" id="order_group">
                    <label><i class="fas fa-hashtag"></i> Order</label>
                    <input type="text" name="order_number" class="form-control" />
                </div>

                <div class="form-group" id="oa_customer_group">
                    <label><i class="fas fa-user"></i> OA Customer</label>
                    <select name="oa_customer" class="form-control">
                        <option value="">Pilih OA Customer</option>
                        <option>202500507 SSB</option>
                        <option>202500508 C5</option>
                        <option>202500509 SSB</option>
                        <option>202500510 SSB</option>
                        <option>202500511 SSB</option>
                    </select>
                </div>

                <div class="form-group" id="date_group" style="display:none;">
                    <label><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" name="date_production" class="form-control" />
                </div>

                <div class="form-group" id="length_group">
                    <label><i class="fas fa-sort-numeric-up"></i> Length</label>
                    <input type="number" name="length" class="form-control" min="1" required />
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-qrcode"></i> Generate QR</button>
            </div>
        </form>
            <script>
            function toggleFields() {
                var level = document.getElementById('level_process').value;
                var additionalFields = document.getElementById('additional_fields');
                var orderGroup = document.getElementById('order_group');
                var oaCustomerGroup = document.getElementById('oa_customer_group');
                var processGroup = document.getElementById('process_group');
                var processSelect = processGroup.querySelector('select');
                var orderInput = orderGroup.querySelector('input');
                var oaCustomerSelect = oaCustomerGroup.querySelector('select');

                var machineSingleGroup = document.getElementById('machine_single_group');
                var machineSingleInput = machineSingleGroup.querySelector('input');
                var machineStrandingGroup = document.getElementById('machine_stranding_group');
                var machineStrandingInput = machineStrandingGroup.querySelector('input');
                var machineInsulationGroup = document.getElementById('machine_insulation_group');
                var machineInsulationInput = machineInsulationGroup.querySelector('input');
                var l3Machines = document.getElementById('l3_machines');
                var l3Inputs = l3Machines.querySelectorAll('input');

                if (level) {
                    additionalFields.style.display = 'block';
                } else {
                    additionalFields.style.display = 'none';
                }

                // Default: hide all specific
                processGroup.style.display = 'block';
                processSelect.required = true;
                machineSingleGroup.style.display = 'block';
                machineSingleInput.required = true;
                machineStrandingGroup.style.display = 'none';
                machineStrandingInput.required = false;
                machineInsulationGroup.style.display = 'none';
                machineInsulationInput.required = false;
                l3Machines.style.display = 'none';
                l3Inputs.forEach(function(input) { input.required = false; });

                if (level === 'L1') {
                    // L1: show only fo, bobin, diameter, wire_length, date
                    document.getElementById('fo_group').style.display = 'block';
                    document.getElementById('cable_type_group').style.display = 'none';
                    processGroup.style.display = 'none';
                    machineSingleGroup.style.display = 'none';
                    document.getElementById('bobin_group').style.display = 'block';
                    document.getElementById('diameter_group').style.display = 'block';
                    document.getElementById('wire_length_group').style.display = 'block';
                    document.getElementById('date_group').style.display = 'block';
                    document.getElementById('length_group').style.display = 'none';
                    document.querySelector('#fo_group input').required = true;
                    document.querySelector('#cable_type').required = false;
                    processSelect.required = false;
                    machineSingleInput.required = false;
                    document.querySelector('#bobin_group input').required = true;
                    document.querySelector('#diameter_group input').required = true;
                    document.querySelector('#wire_length_group input').required = true;
                    document.querySelector('#date_group input').required = true;
                    document.querySelector('#length_group input').required = false;
                } else if (level === 'L2') {
                    // L2: hide process and single, show stranding/insulation
                    processGroup.style.display = 'none';
                    processSelect.required = false;
                    machineSingleGroup.style.display = 'none';
                    machineSingleInput.required = false;
                    machineStrandingGroup.style.display = 'block';
                    machineStrandingInput.required = true;
                    machineInsulationGroup.style.display = 'block';
                    machineInsulationInput.required = true;
                } else if (level === 'L3') {
                    // L3: hide process and single, show all L3 machines, order, oa, date
                    processGroup.style.display = 'none';
                    processSelect.required = false;
                    machineSingleGroup.style.display = 'none';
                    machineSingleInput.required = false;
                    l3Machines.style.display = 'block';
                    l3Inputs.forEach(function(input) { input.required = false; });
                    document.getElementById('date_group').style.display = 'block';
                    document.querySelector('#date_group input').required = true;
                }

                if (level === 'L3') {
                    orderGroup.style.display = 'block';
                    oaCustomerGroup.style.display = 'block';
                    orderInput.required = true;
                    oaCustomerSelect.required = true;
                } else {
                    orderGroup.style.display = 'none';
                    oaCustomerGroup.style.display = 'none';
                    orderInput.required = false;
                    oaCustomerSelect.required = false;
                }
            }

            function toggleDrawingFields() {
                var process = document.getElementById('process').value;
                if (process === 'Drawing') {
                    document.getElementById('bobin_group').style.display = 'block';
                    document.getElementById('diameter_group').style.display = 'block';
                    document.getElementById('wire_length_group').style.display = 'block';
                    document.querySelector('#bobin_group input').required = true;
                    document.querySelector('#diameter_group input').required = true;
                    document.querySelector('#wire_length_group input').required = true;
                } else {
                    document.getElementById('bobin_group').style.display = 'none';
                    document.getElementById('diameter_group').style.display = 'none';
                    document.getElementById('wire_length_group').style.display = 'none';
                    document.querySelector('#bobin_group input').required = false;
                    document.querySelector('#diameter_group input').required = false;
                    document.querySelector('#wire_length_group input').required = false;
                }
            }

            document.getElementById('level_process').addEventListener('change', toggleFields);
            document.getElementById('process').addEventListener('change', toggleDrawingFields);

            // Initialize on page load
            window.onload = function() {
                toggleFields();
            };
        </script>
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
