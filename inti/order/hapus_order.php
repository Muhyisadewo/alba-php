<?php
include __DIR__ . '/../../config.php';

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil ID order dari URL
if (!isset($_GET['id'])) {
    die("ID order tidak ditemukan.");
}

$id = intval($_GET['id']);

// Hapus detail order terkait
$sql_delete_detail = "DELETE FROM riwayat_order_detail WHERE riwayat_order_id = ?";
$stmt_detail = $conn->prepare($sql_delete_detail);
$stmt_detail->bind_param("i", $id);
$stmt_detail->execute();
$stmt_detail->close();

// Hapus order
$sql_delete_order = "DELETE FROM riwayat_order WHERE id = ?";
$stmt_order = $conn->prepare($sql_delete_order);
$stmt_order->bind_param("i", $id);

if ($stmt_order->execute()) {
    header("Location: /index.php?path=riwayat_order.php");
    exit();
} else {
    echo "Error menghapus order: " . $conn->error;
}

$stmt_order->close();
$conn->close();
?>
