<?php
header('Content-Type: application/json');
include '../../config.php';

$nama = trim($_POST['nama_jenis'] ?? '');
if (!$nama) {
    echo json_encode(['success'=>false,'message'=>'Nama kosong']);
    exit;
}

if ($conn->connect_error) {
    echo json_encode(['success'=>false,'message'=>'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM jenis_kunjungan WHERE LOWER(nama_jenis)=LOWER(?)");
if (!$stmt) {
    echo json_encode(['success'=>false,'message'=>'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("s",$nama);
if (!$stmt->execute()) {
    echo json_encode(['success'=>false,'message'=>'Execute failed: ' . $stmt->error]);
    exit;
}

$cek = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($cek) {
    echo json_encode(['success'=>true,'id'=>$cek['id'],'nama'=>$nama]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO jenis_kunjungan (nama_jenis) VALUES (?)");
if (!$stmt) {
    echo json_encode(['success'=>false,'message'=>'Insert prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("s",$nama);
if (!$stmt->execute()) {
    echo json_encode(['success'=>false,'message'=>'Insert execute failed: ' . $stmt->error]);
    exit;
}

echo json_encode([
    'success'=>true,
    'id'=>$stmt->insert_id,
    'nama'=>$nama
]);
