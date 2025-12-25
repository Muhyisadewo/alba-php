<?php
include __DIR__ . '/../../config.php';

if (!isset($_GET['id'])) {
    die("ID retur tidak ditemukan.");
}

$retur_id = intval($_GET['id']);

// Hapus data berdasarkan id
$query = "DELETE FROM returs WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $retur_id);

if ($stmt->execute()) {
    echo "<script>alert('Data retur berhasil dihapus.'); window.location='index.php';</script>";
} else {
    echo "Error: " . $conn->error;
}
?>
