<?php
include __DIR__ . '/../../config.php';

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil ID sales dari URL
if (!isset($_GET['id'])) {
    die("ID sales tidak ditemukan.");
}

$id = intval($_GET['id']);

// Ambil nama supplier sebelum menghapus untuk redirect
$sql_get_supplier = "SELECT nama_supplier FROM sales WHERE id = ?";
$stmt = $conn->prepare($sql_get_supplier);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nama_supplier = $row['nama_supplier'];
} else {
    die("Sales tidak ditemukan.");
}

// Hapus riwayat kunjungan terkait sales
$sql_delete_riwayat = "DELETE FROM riwayat_kunjungan WHERE sales_id = ?";
$stmt = $conn->prepare($sql_delete_riwayat);
$stmt->bind_param("i", $id);
$stmt->execute();

// Hapus semua order terkait sales
$sql_delete_orders = "DELETE FROM orders WHERE sales_id = ?";
$stmt = $conn->prepare($sql_delete_orders);
$stmt->bind_param("i", $id);
$stmt->execute();

// Hapus sales
$sql_delete_sales = "DELETE FROM sales WHERE id = ?";
$stmt = $conn->prepare($sql_delete_sales);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: supplier_detail.php?nama=" . urlencode($nama_supplier) . "&message=Sales berhasil dihapus");
    exit();
} else {
    echo "Error menghapus sales: " . $conn->error;
}

$conn->close();
?>
