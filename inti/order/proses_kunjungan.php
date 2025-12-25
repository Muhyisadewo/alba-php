<?php
include __DIR__ . '/../../config.php';

if (!isset($_GET['sales_id'])) {
    die("Sales ID tidak ditemukan");
}

$sales_id = $_GET['sales_id'];

$sql = "INSERT INTO riwayat_kunjungan (sales_id, tanggal_kunjungan)
        VALUES (?, CURDATE())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sales_id);

if ($stmt->execute()) {
    header("Location:?path=monitor_sales.php?sales_id=$sales_id&msg=sukses");
    exit;
} else {
    echo "Gagal mencatat kunjungan: " . $stmt->error;
}
?>
