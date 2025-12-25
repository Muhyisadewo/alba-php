<?php
include __DIR__ . '/../../config.php';

if (!isset($_GET['id'])) {
    exit('Akses tidak valid');
}

$id = intval($_GET['id']);
if ($id <= 0) exit('ID tidak valid');

// ambil data barang (untuk hapus gambar)
$stmt = $conn->prepare("SELECT gambar FROM daftar_barang WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) exit('Barang tidak ditemukan');

// hapus gambar dari folder
if (!empty($data['gambar'])) {
    $file = '../../uploads/barang/' . $data['gambar'];
    if (file_exists($file)) unlink($file);
}

// hapus data
$stmt = $conn->prepare("DELETE FROM daftar_barang WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "OK";
} else {
    echo "Gagal menghapus barang";
}
