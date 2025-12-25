<?php
include __DIR__.'/../../config.php';
$nama = $_POST['nama_sales'];
$s_id = $_POST['supplier_id'];

// Ambil nama supplier berdasarkan supplier_id
$sql_supplier = "SELECT nama_supplier FROM supplier WHERE id = ?";
$stmt_supplier = $conn->prepare($sql_supplier);
$stmt_supplier->bind_param("i", $s_id);
$stmt_supplier->execute();
$result = $stmt_supplier->get_result();
$supplier = $result->fetch_assoc();
$perusahaan = $supplier['nama_supplier'];

// Set defaults untuk field lainnya agar simpel
$kontak = ''; // Kosongkan kontak
$jenis_kunjungan_id = 1; // Default ke jenis kunjungan pertama (misal "Regular")
$interval_kunjungan = 7; // Default 7 hari

$sql = "INSERT INTO sales (nama_sales, perusahaan, kontak, supplier_id, jenis_kunjungan_id, interval_kunjungan) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssiii", $nama, $perusahaan, $kontak, $s_id, $jenis_kunjungan_id, $interval_kunjungan);
if($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
}
?>
