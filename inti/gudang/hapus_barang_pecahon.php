<?php
include __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

$barang_id = (int) ($_POST['id'] ?? 0);
if ($barang_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

/* ===============================
   Ambil data barang
================================ */
$stmt = $conn->prepare("SELECT gambar FROM gudang_pecahon WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

$stmt->bind_param("i", $barang_id);
$stmt->execute();
$result = $stmt->get_result();
$barang = $result->fetch_assoc();
$stmt->close();

if (!$barang) {
    echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
    exit;
}

/* ===============================
   Transaksi
================================ */
$conn->begin_transaction();

try {
    // Hapus relasi (SESUIKAN NAMA KOLOM)
    $relasi_stmt = $conn->prepare(
        "DELETE FROM daftar_barang WHERE id = ?"
    );
    if ($relasi_stmt) {
        $relasi_stmt->bind_param("i", $barang_id);
        $relasi_stmt->execute();
        $relasi_stmt->close();
    }

    // Hapus barang utama
    $delete_stmt = $conn->prepare(
        "DELETE FROM gudang_pecahon WHERE id = ?"
    );
    if (!$delete_stmt) {
        throw new Exception($conn->error);
    }

    $delete_stmt->bind_param("i", $barang_id);
    if (!$delete_stmt->execute()) {
        throw new Exception("Gagal menghapus barang");
    }
    $delete_stmt->close();

    // Hapus gambar
    if ($barang['gambar'] && $barang['gambar'] !== 'default.jpg') {
        $path = __DIR__ . '/../../uploads/barang/' . $barang['gambar'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    $conn->commit();
    header("Location: ?path=gudang_pecahon&success=Barang berhasil dihapus");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    header("Location: ?path=gudang_pecahon&error=" . urlencode($e->getMessage()));
    exit;
}
