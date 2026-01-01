<?php
include __DIR__ . '/../../config.php';

// Pastikan skrip ini diakses melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ambil dan sanitasi data
$sektor_id   = $_POST['sektor_id'] ?? 0;
$nama_barang = $_POST['nama_barang'] ?? '';
$harga_ambil = $_POST['harga_ambil'] ?? 0;
$qty         = $_POST['qty'] ?? 0;
$max_order   = $_POST['max_order'] ?? 0;
$expired_date = $_POST['expired_date'] ?? null;
$supplier_id = $_POST['supplier_id'] ?? 0;
$sales_id    = $_POST['sales_id'] ?? 0;
$barcode     = $_POST['barcode'] ?? '';
$deskripsi   = ''; // Didefinisikan untuk kolom gudang

// Handle expired_date format
if (empty($expired_date) || $expired_date == '0000-00-00') {
    $expired_date = null;
}

// Default gambar
$gambar = 'default.jpg';

// Handle file upload with image processing
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
    $target_dir = __DIR__ . "/../../uploads/barang/";
    
    // Pastikan folder ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Validasi tipe file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['gambar']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        echo "<script>alert('Tipe file tidak didukung! Gunakan JPG, PNG, GIF, atau WebP.'); window.history.back();</script>";
        exit;
    }

    // Generate nama file dengan ekstensi asli (tidak dikonversi ke webp)
    $file_extension = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
    $file_name = time() . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;

    // Pindahkan file langsung (tanpa konversi webp)
    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
        $gambar = $file_name;
        
        // Jika perlu resize, lakukan di sini tapi pertahankan format asli
        // Contoh resize ke maksimal 800x600
        list($width, $height) = getimagesize($target_file);
        $max_width = 800;
        $max_height = 600;
        
        if ($width > $max_width || $height > $max_height) {
            resizeImage($target_file, $target_file, $max_width, $max_height, $file_extension);
        }
    } else {
        echo "<script>alert('Gagal mengupload gambar!'); window.history.back();</script>";
        exit;
    }
}

// Fungsi resize gambar
function resizeImage($source_path, $target_path, $max_width, $max_height, $type) {
    list($width, $height) = getimagesize($source_path);
    
    // Hitung rasio
    $ratio = min($max_width / $width, $max_height / $height);
    
    if ($ratio < 1) {
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
    } else {
        $new_width = $width;
        $new_height = $height;
    }
    
    // Buat gambar baru
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Load gambar berdasarkan tipe
    switch($type) {
        case 'jpg':
        case 'jpeg':
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case 'png':
            $source_image = imagecreatefrompng($source_path);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;
        case 'gif':
            $source_image = imagecreatefromgif($source_path);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;
        case 'webp':
            $source_image = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    if (!$source_image) return false;
    
    // Resize
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, 
                      $new_width, $new_height, $width, $height);
    
    // Simpan berdasarkan tipe
    switch($type) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($new_image, $target_path, 85);
            break;
        case 'png':
            imagepng($new_image, $target_path, 8);
            break;
        case 'gif':
            imagegif($new_image, $target_path);
            break;
        case 'webp':
            imagewebp($new_image, $target_path, 85);
            break;
    }
    
    // Bersihkan memory
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return true;
}

// Mulai transaksi
$conn->begin_transaction();

try {
    // 1. INSERT KE TABEL GUDANG
$sql_gudang = "INSERT INTO gudang (sektor_id, nama_barang, deskripsi, qty, max_order, expired_date, harga_ambil, barcode, gambar, supplier_id, sales_id, created_at)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt_gudang = $conn->prepare($sql_gudang);
if (!$stmt_gudang) {
    throw new Exception("Gagal menyiapkan statement gudang: " . $conn->error);
}

// Perbaikan: String tipe data diubah dari "issdissssiis" menjadi "issdissssi i" (11 karakter)
$stmt_gudang->bind_param("issdissssii",
    $sektor_id,    // i
    $nama_barang,  // s
    $deskripsi,    // s
    $qty,          // d (atau i jika integer)
    $max_order,    // i
    $expired_date, // s
    $harga_ambil,  // s (atau d jika decimal)
    $barcode,      // s
    $gambar,       // s
    $supplier_id,  // i
    $sales_id      // i
);
    
    if (!$stmt_gudang->execute()) {
        throw new Exception("Gagal eksekusi insert gudang: " . $stmt_gudang->error);
    }
    
    $gudang_id = $conn->insert_id;
    $stmt_gudang->close();

    // 2. INSERT KE TABEL DAFTAR_BARANG
    $sql_barang = "INSERT INTO daftar_barang (nama_barang, harga_ambil, supplier_id, sales_id, gudang_id, gambar, barcode)
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_barang = $conn->prepare($sql_barang);
    if (!$stmt_barang) {
        throw new Exception("Gagal menyiapkan statement daftar_barang: " . $conn->error);
    }
    
    $stmt_barang->bind_param("sdiiiss", 
        $nama_barang, 
        $harga_ambil, 
        $supplier_id, 
        $sales_id, 
        $gudang_id, 
        $gambar, 
        $barcode
    );

    if (!$stmt_barang->execute()) {
        throw new Exception("Gagal eksekusi insert daftar_barang: " . $stmt_barang->error);
    }
    
    $stmt_barang->close();

    // Commit transaksi
    $conn->commit();

    // Redirect dengan sukses
    header("Location: index.php?path=sektor_detail&id=" . $sektor_id . "&success=Barang berhasil ditambahkan");
    exit;

} catch (Exception $e) {
    // Rollback jika error
    $conn->rollback();
    
    // Hapus file gambar jika ada
    if ($gambar !== 'default.jpg' && file_exists($target_dir . $gambar)) {
        unlink($target_dir . $gambar);
    }
    
    // Redirect dengan error
    header("Location: index.php?path=sektor_detail&id=" . $sektor_id . "&error=" . urlencode($e->getMessage()));
    exit;
}

$conn->close();
?>