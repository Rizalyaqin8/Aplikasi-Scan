<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

include "db.php"; // gunakan db.php, variabel koneksi = $conn

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $delete = mysqli_query($conn, "DELETE FROM productions WHERE id = $id");

    if ($delete) {
        header("Location: history.php");
        exit;
    } else {
        echo "Gagal hapus data: " . mysqli_error($conn);
    }
} else {
    header("Location: history.php");
    exit;
}
