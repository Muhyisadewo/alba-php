<?php
// hapus_barang_pecahon.php
include __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barang_id = $_POST['id'] ?? 0;
    
    if ($barang_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        exit;
    }
    
    // Ambil data barang untuk mendapatkan nama gambar
    $sql = "SELECT gambar FROM gudang_pecahon WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $barang = $result->fetch_assoc();
    
    if (!$barang) {
        echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
        exit;
    }
    
    // Mulai transaksi
    $conn->begin_transaction();
    
    try {
        // Hapus dari daftar_barang jika ada relasi
        $delete_daftar_sql = "DELETE FROM daftar_barang WHERE gudang_pecahon_id = ?";
        $delete_daftar_stmt = $conn->prepare($delete_daftar_sql);
        $delete_daftar_stmt->bind_param("i", $barang_id);
        $delete_daftar_stmt->execute();
        
        // Hapus dari gudang_pecahon
        $delete_sql = "DELETE FROM gudang_pecahon WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $barang_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception("Gagal menghapus barang");
        }
        
        // Hapus file gambar jika bukan default
        if ($barang['gambar'] && $barang['gambar'] !== 'default.jpg') {
            $gambarPath = __DIR__ . '/../../uploads/barang/' . $barang['gambar'];
            if (file_exists($gambarPath)) {
                unlink($gambarPath);
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Barang berhasil dihapus']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    $delete_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}
?>