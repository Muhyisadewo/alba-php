<?php
// Pastikan file config.php sudah ada dan koneksi database berjalan
include __DIR__ . '/../../config.php';

// Ambil ID sektor dari parameter GET
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi ID: Pastikan ID adalah angka positif
if ($id <= 0) {
    // Jika ID tidak valid, redirect kembali dengan pesan error
    header("Location: ?path=sektor.php?error=ID sektor tidak valid");
    exit();
}

// Persiapkan query DELETE dengan prepared statement untuk keamanan
$sql = "DELETE FROM sektor WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id); // "i" untuk integer
    $stmt->execute();
    
    // Periksa apakah ada baris yang terpengaruh (berhasil dihapus)
    if ($stmt->affected_rows > 0) {
        // Berhasil hapus, redirect kembali dengan pesan sukses
        header("Location: ?path=sektor.php?success=Sektor berhasil dihapus");
    } else {
        // Tidak ada baris terpengaruh (mungkin ID tidak ada), redirect dengan pesan error
        header("Location: ?path=sektor.php?error=Sektor tidak ditemukan atau sudah dihapus");
    }
    
    $stmt->close();
} else {
    // Jika prepare gagal, redirect dengan pesan error
    header("Location: ?path=sektor.php?error=Gagal memproses penghapusan");
}

// Tutup koneksi database
$conn->close();
exit();
?>