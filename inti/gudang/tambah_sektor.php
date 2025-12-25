<?php
include __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_sektor = $_POST['nama_sektor'];
    $deskripsi = $_POST['deskripsi'];

    // Insert sektor baru
    $sql = "INSERT INTO sektor (nama_sektor, deskripsi) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nama_sektor, $deskripsi);

    if ($stmt->execute()) {
        echo "<script>alert('Sektor berhasil ditambahkan!'); window.location.href='inti/gudang/index.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan sektor: " . $stmt->error . "'); window.location.href='inti/gudang/index.php';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
