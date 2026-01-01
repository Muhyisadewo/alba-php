<?php
include __DIR__.'/../../config.php';

if (!isset($_GET['id'])) {
    die("Data tidak ditemukan.");
}

$id = $_GET['id'];

// Ambil sektor_id untuk redirect
$sql = "SELECT sektor_id FROM gudang WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Barang tidak ditemukan.");
}

$sektor_id = $data['sektor_id'];

// Hapus
$deleteSql = "DELETE FROM gudang WHERE id = ?";
$stmt2 = $conn->prepare($deleteSql);
$stmt2->bind_param("i", $id);

if ($stmt2->execute()) {
    header("Location: index.php?path=sektor_detail&id=" . $sektor_id);
    exit;
} else {
    echo "Gagal menghapus data.";
}
?>
