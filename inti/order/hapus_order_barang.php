<?php
include __DIR__ . '/../../config.php';

/* ===============================
   1. VALIDASI ID BARANG
================================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID barang tidak valid.");
}

$id = (int) $_GET['id'];

/* ===============================
   2. AMBIL DATA BARANG (UNTUK GAMBAR & SALES_ID)
================================ */
$stmt = $conn->prepare("
    SELECT sales_id, gambar 
    FROM daftar_barang 
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$barang = $result->fetch_assoc();
$stmt->close();

if (!$barang) {
    die("Data barang tidak ditemukan.");
}

$sales_id = $barang['sales_id'];
$gambar   = $barang['gambar'];

/* ===============================
   3. HAPUS FILE GAMBAR (JIKA ADA)
================================ */
if (!empty($gambar)) {
    $pathGambar = __DIR__ . "/../../uploads/barang/" . $gambar;
    if (file_exists($pathGambar)) {
        unlink($pathGambar);
    }
}

/* ===============================
   4. HAPUS DATA BARANG
================================ */
$stmtDelete = $conn->prepare("
    DELETE FROM daftar_barang 
    WHERE id = ?
");
$stmtDelete->bind_param("i", $id);
$stmtDelete->execute();
$stmtDelete->close();

/* ===============================
   5. REDIRECT KEMBALI KE DAFTAR BARANG
================================ */
header("Location: ?path=daftar_barang.php?sales_id=" . $sales_id);
exit;
