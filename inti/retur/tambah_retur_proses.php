<?php
include __DIR__ . '/../../config.php';
ini_set('display_errors',1);
error_reporting(E_ALL);

if (!isset($_GET['dbid'])) die("Data tidak lengkap");

$daftar_barang_id = intval($_GET['dbid']);
$order_id = intval($_GET['order_id']);

// Ambil supplier_id dari daftar_barang
$sql = "SELECT db.supplier_id FROM daftar_barang db WHERE db.id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $daftar_barang_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$supplier_id = intval($row['supplier_id'] ?? 0);

if ($supplier_id <= 0) {
    die("Supplier tidak valid. Tidak bisa menambah retur.");
}

$alasan = "Barang tidak sesuai / rusak";

// Insert ke tabel returs
$stmt = $conn->prepare("
    INSERT INTO returs (daftar_barang_id, order_id, supplier_id, alasan)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiis", $daftar_barang_id, $order_id, $supplier_id, $alasan);
$stmt->execute();
$stmt->close();

header("Location: /index.php?path=retur");
exit;
