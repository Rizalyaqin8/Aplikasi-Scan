<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$user_role = $_SESSION['user']['role'] ?? '';
if ($user_role !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);

        // Get the image path before deleting
        $stmt = $conn->prepare("SELECT qr_image_path FROM productions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $image_path = $row['qr_image_path'];

            // Delete the record from database
            $delete_stmt = $conn->prepare("DELETE FROM productions WHERE id = ?");
            $delete_stmt->bind_param("i", $id);
            if ($delete_stmt->execute()) {
                // Delete the image file if it exists
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
                $_SESSION['success'] = "Label berhasil dihapus.";
            } else {
                $_SESSION['error'] = "Gagal menghapus label.";
            }
        } else {
            $_SESSION['error'] = "Label tidak ditemukan.";
        }
    } elseif (isset($_POST['fo_number']) && isset($_POST['level'])) {
        $fo_number = $_POST['fo_number'];
        $level = $_POST['level'];

        // Build query using the same CASE logic as in labels.php
        $query = "SELECT qr_image_path FROM productions WHERE fo_number = ? AND CASE WHEN process IN ('Stranding', 'Insulation') THEN 'L2' WHEN process IN ('Cabling', 'Inner Sheath', 'Armouring', 'Outer Sheath', 'Final Test', 'Packing', 'DR') THEN 'L3' ELSE 'L1' END = ?";
        $params = [$fo_number, $level];
        $types = "ss";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $image_paths = [];
        while ($row = $result->fetch_assoc()) {
            $image_paths[] = $row['qr_image_path'];
        }

        // Delete the records from database
        $delete_stmt = $conn->prepare(str_replace("SELECT qr_image_path", "DELETE", $query));
        $delete_stmt->bind_param($types, ...$params);
        if ($delete_stmt->execute()) {
            // Delete the image files if they exist
            foreach ($image_paths as $image_path) {
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            $_SESSION['success'] = "Label untuk FO " . htmlspecialchars($fo_number) . " level " . htmlspecialchars($level) . " berhasil dihapus.";
        } else {
            $_SESSION['error'] = "Gagal menghapus label.";
        }
    }
}

header("Location: labels.php");
exit;
?>
