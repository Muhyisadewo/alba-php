<?php
include __DIR__ . '/../../config.php';

if (!isset($_GET['id']) || !isset($_GET['order_id'])) {
    die("Data tidak valid.");
}

$id = $_GET['id'];
$order_id = $_GET['order_id'];

$sql = "DELETE FROM riwayat_order WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: riwayat_order.php?id=" . $order_id);
    exit;
} else {
    echo "Gagal menghapus riwayat.";
}
?>
