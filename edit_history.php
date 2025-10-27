edithsory
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

include "db.php"; // gunakan db.php, variabel koneksi = $conn

// Ambil data berdasarkan ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = mysqli_query($conn, "SELECT * FROM productions WHERE id = $id");
    $data = mysqli_fetch_assoc($query);
    $qr_data = json_decode($data['qr_code'], true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $fo_number = $_POST['fo_number'];
    $cable_type = $_POST['cable_type'];
    $machine = $_POST['machine'];
    $process = $_POST['process'];
    $qty = $_POST['qty'];

    // Check if scan_in is not null, if so, don't update operator
    $query_check = mysqli_query($conn, "SELECT scan_in FROM productions WHERE id = $id");
    $row_check = mysqli_fetch_assoc($query_check);
    $is_scanned = !is_null($row_check['scan_in']);

    if (in_array($process, ['Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR'])) {
        $order_number = $_POST['order_number'];
        $oa_customer = $_POST['oa_customer'];
        $date = $_POST['date'];

        // Build new QR code JSON
        $new_qr_data = [
            'fo_number' => $fo_number,
            'process' => $process,
            'machine' => $machine,
            'qty' => $qty,
            'order_number' => $order_number,
            'oa_customer' => $oa_customer,
            'date' => $date
        ];
        $new_qr_code = json_encode($new_qr_data);
    } else {
        // For L2 processes, keep existing qr_code or set minimal
        $existing_qr = mysqli_query($conn, "SELECT qr_code FROM productions WHERE id = $id");
        $existing_row = mysqli_fetch_assoc($existing_qr);
        $new_qr_code = $existing_row['qr_code'];
    }

    if ($is_scanned) {
        // Don't update operator if already scanned
        $update = mysqli_query($conn, "UPDATE productions
                    SET fo_number='$fo_number',
                        cable_type='$cable_type',
                        machine='$machine',
                        process='$process',
                        qty='$qty',
                        qr_code='$new_qr_code'
                    WHERE id=$id");
    } else {
        $operator = $_POST['operator'];
        $update = mysqli_query($conn, "UPDATE productions
                    SET fo_number='$fo_number',
                        cable_type='$cable_type',
                        machine='$machine',
                        process='$process',
                        operator='$operator',
                        qty='$qty',
                        qr_code='$new_qr_code'
                    WHERE id=$id");
    }

    if ($update) {
        header("Location: history.php");
        exit;
    } else {
        echo "Gagal update data: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
        }
        .form-group label {
            font-weight: bold;
        }
        .btn-primary {
            background: #2575fc;
            border: none;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #6a11cb;
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        h3 {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h3><i class="fas fa-edit"></i> Edit History</h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $data['id'] ?>">

        <div class="form-group">
            <label><i class="fas fa-hashtag"></i> Nomor FO</label>
            <input type="text" name="fo_number" class="form-control" value="<?= $data['fo_number'] ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-cable"></i> Type Kabel</label>
            <input type="text" name="cable_type" class="form-control" value="<?= $data['cable_type'] ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-cogs"></i> Mesin</label>
            <input type="text" name="machine" class="form-control" value="<?= $data['machine'] ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-tasks"></i> Proses</label>
            <select name="process" class="form-control" required>
                <?php
                $process_list = [
                    "Drawing",
                    "Stranding",
                    "Insulation",
                    "Cabling",
                    "Inner Sheath",
                    "Armouring",
                    "Outer Sheath",
                    "Final Test",
                    "Packing",
                    "DR (Delivery)"
                ];
                foreach ($process_list as $p) {
                    $selected = ($data['process'] == $p) ? "selected" : "";
                    echo "<option value='$p' $selected>" . ucfirst($p) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fas fa-sort-numeric-up"></i> Qty</label>
            <input type="number" name="qty" class="form-control" value="<?= $data['qty'] ?>" required>
        </div>

        <?php if (in_array($data['process'], ['Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR'])): ?>
        <div class="form-group">
            <label><i class="fas fa-calendar"></i> Order Number</label>
            <input type="text" name="order_number" class="form-control" value="<?= $qr_data['order_number'] ?? '' ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-user-tie"></i> OA Customer</label>
            <input type="text" name="oa_customer" class="form-control" value="<?= $qr_data['oa_customer'] ?? '' ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-calendar-alt"></i> Date</label>
            <input type="date" name="date" class="form-control" value="<?= $qr_data['date'] ?? '' ?>" required>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label><i class="fas fa-user"></i> Operator</label>
            <input type="text" name="operator" class="form-control" value="<?= $data['operator'] ?>" required <?php if (!is_null($data['scan_in'])) echo 'readonly'; ?>>
            <?php if (!is_null($data['scan_in'])): ?>
                <small class="form-text text-muted">Operator tidak dapat diubah karena sudah masuk sistem scan.</small>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-6">
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Simpan</button>
            </div>
            <div class="col-6">
                <a href="history.php" class="btn btn-secondary btn-block"><i class="fas fa-times"></i> Batal</a>
            </div>
        </div>
    </form>
</div>
</body>
</html>