<?php
include __DIR__ . '/../../config.php';
$nama = $_POST['nama_supplier'];
$sql = "INSERT INTO supplier (nama_supplier) VALUES (?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nama);
if($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
}
?>