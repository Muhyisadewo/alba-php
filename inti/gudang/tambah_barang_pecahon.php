<?php
include __DIR__.'/../../config.php';

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

// Ambil dan sanitasi data dari form
$nama_barang = trim($_POST['nama_barang'] ?? '');
$harga_ambil = floatval($_POST['harga_ambil'] ?? 0);
$qty = intval($_POST['qty'] ?? 0);
$barcode = trim($_POST['barcode'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$max_order = intval($_POST['max_order'] ?? 0);
$expired_date = trim($_POST['expired_date'] ?? '');
$supplier_id = intval($_POST['supplier_id'] ?? 0);
$sales_id = intval($_POST['sales_id'] ?? 0);

// Validasi input
if (empty($nama_barang) || $harga_ambil <= 0 || $qty <= 0 || $supplier_id <= 0 || $sales_id <= 0) {
    die('Data tidak lengkap atau tidak valid.');
}

// Handle upload gambar dengan kompresi dan konversi ke WebP
$gambar = 'default.jpg'; // Default jika tidak ada upload
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../uploads/barang/'; // Pastikan folder ini ada dan writable
    $file_name = basename($_FILES['gambar']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($file_ext, $allowed_ext)) {
        // Cek apakah GD library mendukung WebP
        if (!function_exists('imagewebp')) {
            die('Server tidak mendukung konversi ke WebP. Silakan hubungi administrator.');
        }

        // Baca gambar berdasarkan tipe
        $image = null;
        switch ($file_ext) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($_FILES['gambar']['tmp_name']);
                break;
            case 'png':
                $image = imagecreatefrompng($_FILES['gambar']['tmp_name']);
                break;
            case 'gif':
                $image = imagecreatefromgif($_FILES['gambar']['tmp_name']);
                break;
        }

        if (!$image) {
            die('Gagal memproses gambar.');
        }

        // Dapatkan dimensi asli
        $width = imagesx($image);
        $height = imagesy($image);

        // Resize jika lebih besar dari 800x800 (sesuaikan aturan)
        $max_width = 800;
        $max_height = 800;
        if ($width > $max_width || $height > $max_height) {
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = round($width * $ratio);
            $new_height = round($height * $ratio);

            $resized_image = imagecreatetruecolor($new_width, $new_height);
            
            // Jika PNG atau GIF, pertahankan transparansi
            if ($file_ext === 'png' || $file_ext === 'gif') {
                imagealphablending($resized_image, false);
                imagesavealpha($resized_image, true);
                $transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
                imagefill($resized_image, 0, 0, $transparent);
            }

            imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($image);
            $image = $resized_image;
        }

        // Kompres dan konversi ke WebP
        $new_file_name = uniqid() . '.webp';
        $upload_path = $upload_dir . $new_file_name;

        // Kualitas kompresi (0-100, 80 adalah baik untuk keseimbangan ukuran/kualitas)
        $quality = 80;
        if (imagewebp($image, $upload_path, $quality)) {
            $gambar = $new_file_name;
            imagedestroy($image); // Bebaskan memori
        } else {
            imagedestroy($image);
            die('Gagal mengkompres dan menyimpan gambar.');
        }
    } else {
        die('Format gambar tidak didukung.');
    }
}

// Insert ke database
$sql = "INSERT INTO gudang_pecahon (nama_barang, deskripsi, harga_ambil, qty, max_order, gambar, barcode, supplier_id, sales_id, created_at, expired_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param(
    'ssdiissiis',
    $nama_barang,
    $deskripsi,
    $harga_ambil,
    $qty,
    $max_order,
    $gambar,
    $barcode,
    $supplier_id,
    $sales_id,
    $expired_date
);
if ($stmt->execute()) {
    // Redirect kembali ke halaman gudang pecahon dengan pesan sukses
    header('Location: gudang_pecahon&success=1');
    exit;
} else {
    die('Gagal menambah barang: ' . $stmt->error);
}

$stmt->close(); 
$conn->close();
?>
