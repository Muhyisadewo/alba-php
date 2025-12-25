<?php
// ambil_barang.php - FIXED VERSION
include __DIR__ . '/../../config.php';

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buat log file untuk debugging
$log_file = __DIR__ . '/ambil_barang_debug.log';
$log_message = "[" . date('Y-m-d H:i:s') . "] ========== AMBIL BARANG DIPANGGIL ==========\n";
$log_message .= "[" . date('Y-m-d H:i:s') . "] POST Data: " . print_r($_POST, true) . "\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sektor_id = $_POST['sektor_id'] ?? 0;
    $ambil_barang = $_POST['ambil'] ?? [];
    
    // Simpan ke log
    $log_message = "[" . date('Y-m-d H:i:s') . "] Sektor ID: $sektor_id\n";
    $log_message .= "[" . date('Y-m-d H:i:s') . "] Data ambil: " . print_r($ambil_barang, true) . "\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Validasi dasar
    if ($sektor_id <= 0) {
        $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: sektor_id tidak valid\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        header("Location: ?path=sektor_detail.php?id=" . $sektor_id . "&error=Sektor+tidak+valid");
        exit;
    }
    
    // Cek apakah ada barang yang dipilih (dengan qty > 0)
    $barang_dipilih = false;
    foreach ($ambil_barang as $jumlah) {
        if ((int)$jumlah > 0) {
            $barang_dipilih = true;
            break;
        }
    }
    
    if (!$barang_dipilih) {
        $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: Tidak ada barang yang dipilih (semua qty = 0)\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        header("Location: ?path=sektor_detail.php?id=" . $sektor_id . "&error=Tidak+ada+barang+yang+dipilih");
        exit;
    }
    
    $errors = [];
    $success_count = 0;
    $updated_items = [];
    
    // Mulai transaksi
    $conn->begin_transaction();
    $log_message = "[" . date('Y-m-d H:i:s') . "] Transaksi dimulai\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    try {
        foreach ($ambil_barang as $barang_id => $jumlah) {
            $jumlah = (int)$jumlah;
            
            $log_message = "[" . date('Y-m-d H:i:s') . "] Proses Barang ID: $barang_id, Jumlah: $jumlah\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            if ($jumlah <= 0) {
                $log_message = "[" . date('Y-m-d H:i:s') . "] Skip: Jumlah 0\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                continue;
            }
            
            // 1. AMBIL DATA BARANG DARI GUDANG
            $check_sql = "SELECT id, nama_barang, qty, sektor_id FROM gudang WHERE id = ?";
            $log_message = "[" . date('Y-m-d H:i:s') . "] SQL Check: $check_sql (ID: $barang_id)\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                $error_msg = "Error prepare: " . $conn->error;
                $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: $error_msg\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                $errors[] = "Error sistem";
                continue;
            }
            
            $check_stmt->bind_param("i", $barang_id);
            if (!$check_stmt->execute()) {
                $error_msg = "Error execute: " . $check_stmt->error;
                $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: $error_msg\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                $errors[] = "Error sistem";
                $check_stmt->close();
                continue;
            }
            
            $check_result = $check_stmt->get_result();
            $barang = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if (!$barang) {
                $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: Barang ID $barang_id tidak ditemukan\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                $errors[] = "Barang ID $barang_id tidak ditemukan";
                continue;
            }
            
            $log_message = "[" . date('Y-m-d H:i:s') . "] Barang ditemukan: " . $barang['nama_barang'] . 
                          ", Qty: " . $barang['qty'] . 
                          ", Sektor ID: " . $barang['sektor_id'] . "\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            // 2. CEK APAKAH BARANG ADA DI SEKTOR YANG BENAR
            if ($barang['sektor_id'] != $sektor_id) {
                $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: Barang tidak di sektor ini. " .
                              "Expected: $sektor_id, Actual: " . $barang['sektor_id'] . "\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                $errors[] = "Barang '" . $barang['nama_barang'] . "' tidak berada di sektor ini";
                continue;
            }
            
            // 3. CEK STOK CUKUP
            if ($barang['qty'] < $jumlah) {
                $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: Stok tidak cukup. " .
                              "Tersedia: " . $barang['qty'] . ", Diminta: $jumlah\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                $errors[] = "Stok '" . $barang['nama_barang'] . "' tidak cukup (tersedia: " . $barang['qty'] . ")";
                continue;
            }
            
            // 4. UPDATE QTY DI TABEL GUDANG
            $update_sql = "UPDATE gudang SET qty = qty - ?, updated_at = NOW() WHERE id = ?";
            $log_message = "[" . date('Y-m-d H:i:s') . "] SQL Update: $update_sql (-$jumlah, ID: $barang_id)\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                $error_msg = "Error prepare update: " . $conn->error;
                $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: $error_msg\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                $errors[] = "Error sistem";
                continue;
            }
            
            $update_stmt->bind_param("ii", $jumlah, $barang_id);
            
            if (!$update_stmt->execute()) {
                $error_msg = "Error execute update: " . $update_stmt->error;
                $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: $error_msg\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                $errors[] = "Gagal update stok '" . $barang['nama_barang'] . "'";
                $update_stmt->close();
                continue;
            }
            
            $affected_rows = $update_stmt->affected_rows;
            $update_stmt->close();
            
            $log_message = "[" . date('Y-m-d H:i:s') . "] UPDATE BERHASIL. Affected rows: $affected_rows\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            // 5. SIMPAN KE LOG TRANSAKSI (jika ada tabel transaksi)
            // Cek apakah tabel transaksi_tmp ada
            $table_check = $conn->query("SHOW TABLES LIKE 'transaksi_tmp'");
            if ($table_check && $table_check->num_rows > 0) {
                $log_message = "[" . date('Y-m-d H:i:s') . "] Tabel transaksi_tmp ditemukan\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                
                // Insert ke transaksi_tmp
                $insert_sql = "INSERT INTO transaksi_tmp (barang_id, sektor_id, jumlah, status, created_at) 
                              VALUES (?, ?, ?, 'pending', NOW()) 
                              ON DUPLICATE KEY UPDATE jumlah = jumlah + ?, updated_at = NOW()";
                
                $insert_stmt = $conn->prepare($insert_sql);
                if ($insert_stmt) {
                    $insert_stmt->bind_param("iiii", $barang_id, $sektor_id, $jumlah, $jumlah);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Data dimasukkan ke transaksi_tmp\n";
                    file_put_contents($log_file, $log_message, FILE_APPEND);
                }
            }
            
            $success_count++;
            $updated_items[] = $barang['nama_barang'] . " (" . $jumlah . " pcs)";
            
            $log_message = "[" . date('Y-m-d H:i:s') . "] Barang $barang_id berhasil diproses\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
        }
        
        $log_message = "[" . date('Y-m-d H:i:s') . "] Total berhasil: $success_count, Total errors: " . count($errors) . "\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        
        // VERIFIKASI: Cek stok terakhir setelah update
        $log_message = "[" . date('Y-m-d H:i:s') . "] === VERIFIKASI STOK TERAKHIR ===\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        
        foreach ($ambil_barang as $barang_id => $jumlah) {
            $jumlah = (int)$jumlah;
            if ($jumlah > 0) {
                $verif_sql = "SELECT nama_barang, qty FROM gudang WHERE id = ?";
                $verif_stmt = $conn->prepare($verif_sql);
                $verif_stmt->bind_param("i", $barang_id);
                $verif_stmt->execute();
                $verif_result = $verif_stmt->get_result();
                $verif = $verif_result->fetch_assoc();
                $verif_stmt->close();
                
                if ($verif) {
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Stok akhir '" . $verif['nama_barang'] . "': " . $verif['qty'] . "\n";
                    file_put_contents($log_file, $log_message, FILE_APPEND);
                }
            }
        }
        
        // 6. COMMIT atau ROLLBACK
        if (empty($errors)) {
            $conn->commit();
            $log_message = "[" . date('Y-m-d H:i:s') . "] TRANSAKSI COMMIT BERHASIL\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            // Redirect dengan pesan sukses
            $message = "✅ Barang berhasil diambil! " . $success_count . " barang diproses.";
            if (!empty($updated_items)) {
                $message .= "\nBarang: " . implode(", ", $updated_items);
            }
            
            $log_message = "[" . date('Y-m-d H:i:s') . "] Redirect ke sektor_detail.php dengan pesan sukses\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            header("Location: ?path=sektor_detail.php?id=" . $sektor_id . "&success=" . urlencode($message));
            exit;
            
        } else {
            $conn->rollback();
            $log_message = "[" . date('Y-m-d H:i:s') . "] TRANSAKSI ROLLBACK karena ada errors\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            $error_message = implode("; ", $errors);
            $log_message = "[" . date('Y-m-d H:i:s') . "] Errors: $error_message\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
            header("Location: ?path=sektor_detail.php?id=" . $sektor_id . "&error=" . urlencode($error_message));
            exit;
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $log_message = "[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . "\n";
        $log_message .= "[" . date('Y-m-d H:i:s') . "] Stack trace: " . $e->getTraceAsString() . "\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        
        header("Location: ?path=sektor_detail.php?id=" . $sektor_id . "&error=" . urlencode("Terjadi kesalahan sistem"));
        exit;
    }
    
} else {
    $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: Bukan POST request\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    header("Location: inti/gudang/index.php");
    exit;
}

$log_message = "[" . date('Y-m-d H:i:s') . "] ========== AMBIL BARANG SELESAI ==========\n\n";
file_put_contents($log_file, $log_message, FILE_APPEND);
?>