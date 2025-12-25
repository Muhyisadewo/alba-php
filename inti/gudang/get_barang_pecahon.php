<?php
include __DIR__.'/../../config.php';

$search = $_GET['search'] ?? '';

$sql = "SELECT id, nama_barang, harga_ambil, qty, gambar, barcode, created_at
        FROM gudang_pecahon
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (nama_barang LIKE ? OR barcode LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $types = 'ss';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$barang_list = [];
while ($row = $result->fetch_assoc()) {
    $barang_list[] = $row;
}

header('Content-Type: application/json');
echo json_encode($barang_list);

$stmt->close();
$conn->close();
?>
