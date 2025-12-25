<?php
include __DIR__ . '/../../config.php';

$sektor_id = $_GET['sektor_id'] ?? 0;
$search = $_GET['search'] ?? '';

$barang_sql = "SELECT id, nama_barang, harga_ambil, qty, gambar, barcode, created_at
               FROM gudang
               WHERE sektor_id = ?";
$params = [$sektor_id];
$types = 'i';

if (!empty($search)) {
    $barang_sql .= " AND (nama_barang LIKE ? OR barcode LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $types .= 'ss';
}

$barang_sql .= " ORDER BY created_at DESC";

$barang_stmt = $conn->prepare($barang_sql);
$barang_stmt->bind_param($types, ...$params);
$barang_stmt->execute();
$barang_result = $barang_stmt->get_result();

$barang_list = [];
while ($barang = $barang_result->fetch_assoc()) {
    $barang_list[] = $barang;
}

header('Content-Type: application/json');
echo json_encode($barang_list);

$conn->close();
?>
