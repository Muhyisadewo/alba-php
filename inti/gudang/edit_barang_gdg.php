<?php
// edit_barang_gdg.php
include __DIR__ . '/../../config.php';

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$barang_id = $_GET['id'] ?? 0;

// Validasi ID
if ($barang_id <= 0) {
    die("ID barang tidak valid");
}

// Ambil data barang dari tabel gudang dengan semua kolom
$barang_sql = "SELECT g.*, s.nama_sektor 
               FROM gudang g
               LEFT JOIN sektor s ON g.sektor_id = s.id
               WHERE g.id = ?";
$barang_stmt = $conn->prepare($barang_sql);

if (!$barang_stmt) {
    die("Error prepare statement: " . $conn->error);
}

$barang_stmt->bind_param("i", $barang_id);
$barang_stmt->execute();
$barang_result = $barang_stmt->get_result();

if ($barang_result->num_rows === 0) {
    die("Barang tidak ditemukan");
}

$barang = $barang_result->fetch_assoc();
$barang_stmt->close();

// Ambil semua supplier
$supplier_sql = "SELECT id, nama_supplier FROM supplier ORDER BY nama_supplier ASC";
$supplier_result = $conn->query($supplier_sql);

if (!$supplier_result) {
    die("Error query supplier: " . $conn->error);
}

// Ambil sales berdasarkan supplier barang ini
$sales_result = null;
if (!empty($barang['supplier_id'])) {
    $sales_sql = "SELECT id, nama_sales FROM sales WHERE supplier_id = ? ORDER BY nama_sales ASC";
    $sales_stmt = $conn->prepare($sales_sql);
    
    if ($sales_stmt) {
        $sales_stmt->bind_param("i", $barang['supplier_id']);
        $sales_stmt->execute();
        $sales_result = $sales_stmt->get_result();
        $sales_stmt->close();
    }
}

// Proses update jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil semua data dari POST
    $nama_barang = $_POST['nama_barang'] ?? '';
    $harga_ambil = $_POST['harga_ambil'] ?? 0;
    $qty = $_POST['qty'] ?? 0;
    $max_order = $_POST['max_order'] ?? 0;
    $barcode = $_POST['barcode'] ?? '';
    $supplier_id = $_POST['supplier_id'] ?? 0;
    $sales_id = $_POST['sales_id'] ?? 0;
    $sektor_id = $_POST['sektor_id'] ?? 0;
    
    // Handle tanggal expired (format dari date input adalah YYYY-MM-DD)
    $expired_date = $_POST['expired_date'] ?? null;
    if (empty($expired_date) || $expired_date == '0000-00-00') {
        $expired_date = null;
    }
    
    // Handle file upload
    $gambar = $barang['gambar'] ?? 'default.jpg';
    if (isset($_FILES['gambar']['error']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/barang/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['gambar']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (in_array($fileExt, $allowedTypes)) {
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadFile)) {
                // Hapus gambar lama jika bukan default
                if (!empty($barang['gambar']) && $barang['gambar'] !== 'default.jpg') {
                    $oldFile = $uploadDir . $barang['gambar'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $gambar = $fileName;
            } else {
                $error = "Gagal mengupload gambar";
            }
        } else {
            $error = "Tipe file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.";
        }
    }
    
    // Jika tidak ada error, proses update
    if (!isset($error)) {
        // Update semua kolom di tabel gudang
        $update_sql = "UPDATE gudang SET
                       nama_barang = ?,
                       harga_ambil = ?,
                       qty = ?,
                       max_order = ?,
                       expired_date = ?,
                       barcode = ?,
                       gambar = ?,
                       supplier_id = ?,
                       sales_id = ?,
                       sektor_id = ?,
                       updated_at = NOW()
                       WHERE id = ?";

        $update_stmt = $conn->prepare($update_sql);

        if ($update_stmt) {
            // Bind parameters sesuai dengan tipe data
            $update_stmt->bind_param("sdiisssiiii",
                $nama_barang,
                $harga_ambil,
                $qty,
                $max_order,
                $expired_date,
                $barcode,
                $gambar,
                $supplier_id,
                $sales_id,
                $sektor_id,
                $barang_id
            );
            
            if ($update_stmt->execute()) {
                // Redirect dengan pesan sukses
                header("Location: ?path=sektor_detail.php?id=" . $sektor_id . "&success=Barang berhasil diperbarui");
                exit;
            } else {
                $error = "Gagal memperbarui barang: " . $update_stmt->error;
            }
            
            $update_stmt->close();
        } else {
            $error = "Gagal menyiapkan statement update: " . $conn->error;
        }
    }
}

// Ambil semua sektor untuk dropdown
$sektor_sql = "SELECT id, nama_sektor FROM sektor ORDER BY nama_sektor ASC";
$sektor_result = $conn->query($sektor_sql);

// Format expired_date untuk input date
$expired_date_formatted = '';
if (!empty($barang['expired_date']) && $barang['expired_date'] != '0000-00-00') {
    $expired_date_formatted = date('Y-m-d', strtotime($barang['expired_date']));
}

// Pastikan gambar memiliki path yang benar
$gambar_path = '../../uploads/barang/' . ($barang['gambar'] ?? 'default.jpg');
if (!file_exists(__DIR__ . '/../../uploads/barang/' . ($barang['gambar'] ?? 'default.jpg'))) {
    $gambar_path = '../../uploads/barang/default.jpg';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang - <?php echo htmlspecialchars($barang['nama_barang']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Flatpickr CSS untuk kalender modern -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Flatpickr Theme Material Blue (opsional, lebih modern) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
        }
        
        .container {
            max-width: 1200px;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            border-bottom: none;
            padding: 1.5rem 2rem;
        }
        
        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .gambar-preview {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border: 3px solid #e9ecef;
            border-radius: 12px;
            margin: 0 auto 20px;
            display: block;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .gambar-preview:hover {
            transform: scale(1.05);
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-label.required::after {
            content: "*";
            color: #e53e3e;
            margin-left: 4px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .input-group-text {
            background-color: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-weight: 500;
        }
        
        .input-group .form-control {
            border-right: none;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .input-group .btn {
            border-left: none;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        .input-group .form-control:focus {
            border-right: none;
        }
        
        .btn-custom {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 140px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .btn-outline-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(160, 174, 192, 0.3);
        }
        
        .info-badge {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            color: #4a5568;
            font-size: 14px;
        }
        
        .info-badge i {
            color: var(--primary-color);
            margin-right: 8px;
        }
        
        .expired-warning {
            color: #e53e3e;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(229, 62, 62, 0.1);
            padding: 5px 10px;
            border-radius: 6px;
            margin-top: 5px;
        }
        
        .expired-good {
            color: #38a169;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(56, 161, 105, 0.1);
            padding: 5px 10px;
            border-radius: 6px;
            margin-top: 5px;
        }
        
        .flatpickr-calendar {
            border-radius: 15px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
            border: none !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }
        
        .flatpickr-day.selected {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
            font-weight: 600;
        }
        
        .flatpickr-day.today {
            border-color: var(--success-color) !important;
            color: var(--success-color) !important;
        }
        
        .flatpickr-day.today:hover {
            background: var(--success-color) !important;
            color: white !important;
        }
        
        .flatpickr-day:hover {
            background: rgba(67, 97, 238, 0.1) !important;
        }
        
        /* Styling khusus untuk input expired date */
        .expired-date-group {
            position: relative;
        }
        
        .expired-date-group .form-control {
            background-color: white;
            cursor: pointer;
            padding-right: 45px;
        }
        
        .expired-date-group::after {
            content: "📅";
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            pointer-events: none;
            z-index: 1;
        }
        
        /* Section header untuk setiap kelompok input */
        .section-header {
            border-left: 4px solid var(--primary-color);
            padding-left: 15px;
            margin: 30px 0 20px 0;
            font-weight: 700;
            color: #2d3748;
            font-size: 1.2rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin-top: 10px;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .gambar-preview {
                width: 150px;
                height: 150px;
            }
            
            .btn-custom {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .row > div {
                margin-bottom: 15px;
            }
            
            .section-header {
                margin-top: 20px;
            }
        }
        
        /* Animasi untuk form */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-row {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .form-row:nth-child(1) { animation-delay: 0.1s; }
        .form-row:nth-child(2) { animation-delay: 0.2s; }
        .form-row:nth-child(3) { animation-delay: 0.3s; }
        .form-row:nth-child(4) { animation-delay: 0.4s; }
        
        /* Hover effect untuk input groups */
        .input-group:hover .form-control,
        .input-group:hover .input-group-text {
            border-color: #cbd5e0;
        }
        
        /* Tooltip styling */
        .tooltip-icon {
            color: #718096;
            cursor: help;
            margin-left: 5px;
        }
        
        /* Loading overlay untuk form submit */
        .form-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Custom checkbox untuk toggle switch (jika ada) */
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="form-loading" id="loadingOverlay">
        <div class="spinner mb-3"></div>
        <h5>Menyimpan perubahan...</h5>
        <p class="text-muted">Mohon tunggu sebentar</p>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1"><i class="fas fa-edit me-2"></i>Edit Barang Gudang</h4>
                    <p class="mb-0 opacity-75">Ubah data barang <?php echo htmlspecialchars($barang['nama_barang']); ?></p>
                </div>
                <a href="barang_sektor.php?id=<?php echo $barang['sektor_id']; ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <div><?php echo htmlspecialchars($_GET['success']); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="editBarangForm">
                    <input type="hidden" name="sektor_id" value="<?php echo $barang['sektor_id']; ?>">
                    
                    <!-- Preview Gambar -->
                    <div class="row mb-4">
                        <div class="col-md-12 text-center">
                            <img src="<?php echo $gambar_path; ?>" 
                                 alt="Preview Gambar" 
                                 class="gambar-preview"
                                 id="gambarPreview"
                                 onclick="document.getElementById('gambarInput').click()"
                                 onerror="this.src='../../uploads/barang/default.jpg'">
                            
                            <div class="mt-2">
                                <input type="file" 
                                       class="form-control d-none" 
                                       id="gambarInput" 
                                       name="gambar" 
                                       accept="image/*">
                                
                                <div class="btn-group" role="group">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm"
                                            onclick="document.getElementById('gambarInput').click()">
                                        <i class="fas fa-camera me-1"></i> Ubah Gambar
                                    </button>
                                    <?php if ($barang['gambar'] !== 'default.jpg'): ?>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="resetGambar()">
                                        <i class="fas fa-times me-1"></i> Hapus
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Ukuran maksimal 5MB. Format: JPG, PNG, GIF, WebP
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section: Informasi Barang -->
                    <h5 class="section-header">
                        <i class="fas fa-box me-2"></i>Informasi Barang
                    </h5>
                    
                    <!-- Baris 1: Nama Barang, Harga Ambil -->
                    <div class="row form-row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama_barang" class="form-label required">
                                    <i class="fas fa-tag"></i> Nama Barang
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="nama_barang"
                                       name="nama_barang"
                                       value="<?php echo htmlspecialchars($barang['nama_barang'] ?? ''); ?>"
                                       required
                                       placeholder="Masukkan nama barang">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="harga_ambil" class="form-label required">
                                    <i class="fas fa-money-bill-wave"></i> Harga Ambil
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           class="form-control"
                                           id="harga_ambil"
                                           name="harga_ambil"
                                           value="<?php echo $barang['harga_ambil'] ?? 0; ?>"
                                           min="0"
                                           step="100"
                                           required
                                           placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section: Stok & Expired -->
                    <h5 class="section-header">
                        <i class="fas fa-warehouse me-2"></i>Stok & Pengaturan
                    </h5>
                    
                    <!-- Baris 2: Quantity, Max Order, Expired Date -->
                    <div class="row form-row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="qty" class="form-label required">
                                    <i class="fas fa-cubes"></i> Quantity
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="qty" 
                                           name="qty" 
                                           value="<?php echo $barang['qty'] ?? 0; ?>" 
                                           min="0" 
                                           required
                                           placeholder="Jumlah stok">
                                    <span class="input-group-text">pcs</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_order" class="form-label">
                                    <i class="fas fa-shopping-cart"></i> Maximal Order
                                    <i class="fas fa-question-circle tooltip-icon" 
                                       title="Maksimal jumlah yang bisa dipesan dalam satu transaksi. 0 = tidak terbatas"></i>
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="max_order" 
                                           name="max_order" 
                                           value="<?php echo $barang['max_order'] ?? 0; ?>" 
                                           min="0" 
                                           placeholder="0 = tidak terbatas">
                                    <span class="input-group-text">pcs</span>
                                </div>
                                <div class="form-text">
                                    <?php if ($barang['max_order'] > 0): ?>
                                        <i class="fas fa-info-circle text-primary me-1"></i>
                                        Saat ini: maksimal <?php echo $barang['max_order']; ?> pcs per transaksi
                                    <?php else: ?>
                                        <i class="fas fa-infinity text-muted me-1"></i>
                                        Tidak ada batasan order
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="expired_date" class="form-label">
                                    <i class="fas fa-calendar-alt"></i> Tanggal Expired
                                </label>
                                <div class="expired-date-group">
                                    <input type="text" 
                                           class="form-control flatpickr-input" 
                                           id="expired_date" 
                                           name="expired_date" 
                                           value="<?php echo $expired_date_formatted; ?>"
                                           placeholder="Klik untuk memilih tanggal"
                                           data-input
                                           autocomplete="off">
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary"
                                            onclick="clearExpiredDate()">
                                        <i class="fas fa-times"></i> Hapus
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="openCalendar()">
                                        <i class="fas fa-calendar"></i> Buka Kalender
                                    </button>
                                </div>
                                <div id="expiredStatus" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section: Informasi Tambahan -->
                    <h5 class="section-header">
                        <i class="fas fa-info-circle me-2"></i>Informasi Tambahan
                    </h5>
                    
                    <!-- Baris 3: Barcode -->
                    <div class="row form-row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="barcode" class="form-label">
                                    <i class="fas fa-barcode"></i> Barcode
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="barcode"
                                       name="barcode"
                                       value="<?php echo htmlspecialchars($barang['barcode'] ?? ''); ?>"
                                       placeholder="Kode barcode (opsional)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section: Pemasok -->
                    <h5 class="section-header">
                        <i class="fas fa-truck me-2"></i>Informasi Pemasok
                    </h5>
                    
                    <!-- Baris 4: Supplier, Sales, Sektor -->
                    <div class="row form-row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label required">
                                    <i class="fas fa-industry"></i> Supplier
                                </label>
                                <select class="form-control" id="supplier_id" name="supplier_id" required>
                                    <option value="">Pilih Supplier...</option>
                                    <?php 
                                    $supplier_result->data_seek(0); // Reset pointer result set
                                    while($supplier = $supplier_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $supplier['id']; ?>" 
                                            <?php echo ($supplier['id'] == ($barang['supplier_id'] ?? 0)) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['nama_supplier']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="sales_id" class="form-label required">
                                    <i class="fas fa-user-tie"></i> Sales
                                </label>
                                <select class="form-control" id="sales_id" name="sales_id" required>
                                    <option value="">Pilih Sales...</option>
                                    <?php if ($sales_result): ?>
                                        <?php while($sales = $sales_result->fetch_assoc()): ?>
                                            <option value="<?php echo $sales['id']; ?>" 
                                                <?php echo ($sales['id'] == ($barang['sales_id'] ?? 0)) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sales['nama_sales']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="sektor" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Sektor
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($barang['nama_sektor'] ?? 'Tidak diketahui'); ?>" 
                                       disabled
                                       readonly>
                                <small class="text-muted">
                                    <i class="fas fa-lock me-1"></i>
                                    Sektor tidak dapat diubah dari halaman ini
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Sistem -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="info-badge">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <i class="fas fa-fingerprint"></i>
                                            <strong>ID Barang:</strong> #<?php echo str_pad($barang['id'], 6, '0', STR_PAD_LEFT); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <i class="fas fa-calendar-plus"></i>
                                            <strong>Dibuat:</strong> <?php echo date('d/m/Y H:i', strtotime($barang['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <i class="fas fa-history"></i>
                                            <strong>Terakhir Diupdate:</strong> 
                                            <?php if (!empty($barang['updated_at']) && $barang['updated_at'] != '0000-00-00 00:00:00'): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($barang['updated_at'])); ?>
                                            <?php else: ?>
                                                Belum pernah diupdate
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tombol Aksi -->
                    <div class="row mt-5">
                        <div class="col-md-12">
                            <hr>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="barang_sektor.php?id=<?php echo $barang['sektor_id']; ?>" 
                                       class="btn btn-outline-secondary btn-custom">
                                        <i class="fas fa-times me-1"></i> Batal
                                    </a>
                                </div>
                                <div class="d-flex gap-3">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-custom"
                                            onclick="previewData()">
                                        <i class="fas fa-eye me-1"></i> Preview
                                    </button>
                                    <button type="submit" 
                                            class="btn btn-primary btn-custom" 
                                            id="submitBtn">
                                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Preview Data -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Preview Data Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="previewContent">
                    <!-- Content akan diisi via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Flatpickr JS untuk kalender -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
    
    <script>
        // Deklarasi variabel global
        let expiredDatePicker;
        
        // Inisialisasi saat halaman load
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Flatpickr untuk kalender modern
            expiredDatePicker = flatpickr("#expired_date", {
                dateFormat: "Y-m-d",
                locale: "id", // Bahasa Indonesia
                minDate: "today", // Tidak bisa memilih tanggal kemarin
                defaultDate: "<?php echo $expired_date_formatted; ?>",
                disableMobile: false,
                allowInput: true,
                clickOpens: true,
                theme: "material_blue", // Tema modern
                position: "auto right",
                enableTime: false, // Hanya tanggal, tanpa waktu
                static: false,
                wrap: false,
                weekNumbers: false,
                altFormat: "j F Y", // Format tampilan: 15 Januari 2024
                altInput: false,
                ariaDateFormat: "j F Y",
                mode: "single",
                monthSelectorType: "static",
                prevArrow: "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><path d='M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z'/></svg>",
                nextArrow: "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><path d='M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z'/></svg>",
                onChange: function(selectedDates, dateStr, instance) {
                    updateExpiredStatus(dateStr);
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    console.log("Kalender dibuka");
                },
                onClose: function(selectedDates, dateStr, instance) {
                    console.log("Kalender ditutup");
                }
            });
            
            // Update status expired saat pertama kali load
            const expiredDate = document.getElementById('expired_date').value;
            if (expiredDate) {
                updateExpiredStatus(expiredDate);
            }
            
            // Event untuk gambar preview
            document.getElementById('gambarInput').addEventListener('change', handleImageUpload);
            
            // Event untuk perubahan supplier
            document.getElementById('supplier_id').addEventListener('change', loadSalesBySupplier);
            
            // Setup tooltips
            setupTooltips();
            
            // Setup form validation
            setupFormValidation();
        });
        
        // Fungsi untuk update status expired
        function updateExpiredStatus(dateStr) {
            const statusDiv = document.getElementById('expiredStatus');
            
            if (!dateStr) {
                statusDiv.innerHTML = '';
                return;
            }
            
            const today = new Date();
            const expiredDate = new Date(dateStr);
            const diffTime = expiredDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let statusHtml = '';
            let icon = '';
            
            if (diffDays < 0) {
                // Sudah expired
                icon = '<i class="fas fa-exclamation-triangle"></i>';
                statusHtml = `<div class="expired-warning">
                    ${icon} SUDAH EXPIRED (${Math.abs(diffDays)} hari yang lalu)
                </div>`;
            } else if (diffDays === 0) {
                // Expired hari ini
                icon = '<i class="fas fa-exclamation-circle"></i>';
                statusHtml = `<div class="expired-warning">
                    ${icon} EXPIRED HARI INI
                </div>`;
            } else if (diffDays <= 7) {
                // Akan expired dalam 7 hari
                icon = '<i class="fas fa-clock"></i>';
                statusHtml = `<div class="expired-warning">
                    ${icon} Akan expired dalam ${diffDays} hari
                </div>`;
            } else if (diffDays <= 30) {
                // Akan expired dalam 30 hari
                icon = '<i class="fas fa-calendar-week"></i>';
                statusHtml = `<div class="expired-good">
                    ${icon} Akan expired dalam ${diffDays} hari
                </div>`;
            } else {
                // Masih lama
                icon = '<i class="fas fa-check-circle"></i>';
                statusHtml = `<div class="expired-good">
                    ${icon} Masih ${diffDays} hari lagi
                </div>`;
            }
            
            statusDiv.innerHTML = statusHtml;
        }
        
        // Fungsi untuk membuka kalender
        function openCalendar() {
            expiredDatePicker.open();
        }
        
        // Fungsi untuk menghapus tanggal expired
        function clearExpiredDate() {
            expiredDatePicker.clear();
            document.getElementById('expiredStatus').innerHTML = '';
            showToast('Tanggal expired telah dihapus', 'info');
        }
        
        // Fungsi untuk handle upload gambar
        function handleImageUpload(e) {
            const file = e.target.files[0];
            if (file) {
                // Validasi ukuran file (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Ukuran file terlalu besar. Maksimal 5MB', 'error');
                    e.target.value = '';
                    return;
                }
                
                // Validasi tipe file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showToast('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP', 'error');
                    e.target.value = '';
                    return;
                }
                
                // Preview gambar
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('gambarPreview');
                    preview.src = e.target.result;
                    preview.classList.add('uploaded');
                    showToast('Gambar berhasil dipilih', 'success');
                }
                reader.readAsDataURL(file);
            }
        }
        
        // Fungsi untuk reset gambar ke default
        function resetGambar() {
            if (confirm('Yakin ingin menghapus gambar ini?')) {
                document.getElementById('gambarPreview').src = '../../uploads/barang/default.jpg';
                document.getElementById('gambarInput').value = '';
                showToast('Gambar telah direset ke default', 'info');
            }
        }
        
        // Fungsi untuk load sales berdasarkan supplier
        function loadSalesBySupplier() {
            const supplierId = this.value;
            const salesSelect = document.getElementById('sales_id');
            
            if (!supplierId) {
                salesSelect.innerHTML = '<option value="">Pilih Sales...</option>';
                return;
            }
            
            // Tampilkan loading
            salesSelect.innerHTML = '<option value="">Memuat sales...</option>';
            salesSelect.disabled = true;
            
            // Fetch data sales dari server
            fetch(`get_sales.php?supplier_id=${supplierId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    salesSelect.innerHTML = '<option value="">Pilih Sales...</option>';
                    salesSelect.disabled = false;
                    
                    if (data && data.length > 0) {
                        data.forEach(sales => {
                            const option = document.createElement('option');
                            option.value = sales.id;
                            option.textContent = sales.nama_sales;
                            salesSelect.appendChild(option);
                        });
                        showToast(`${data.length} sales ditemukan`, 'success');
                    } else {
                        salesSelect.innerHTML = '<option value="">Tidak ada sales untuk supplier ini</option>';
                        showToast('Tidak ada sales ditemukan', 'warning');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    salesSelect.innerHTML = '<option value="">Error loading sales</option>';
                    salesSelect.disabled = false;
                    showToast('Gagal memuat data sales', 'error');
                });
        }
        
        // Fungsi untuk setup tooltips
        function setupTooltips() {
            // Tooltip untuk max order
            const maxOrderTooltip = new bootstrap.Tooltip(document.querySelector('[title="Maksimal jumlah yang bisa dipesan dalam satu transaksi. 0 = tidak terbatas"]'), {
                placement: 'right'
            });

            // Tooltip untuk field lainnya
            const tooltips = {
                'harga_ambil': 'Harga beli dari supplier',
                'max_order': 'Maksimal jumlah yang bisa dipesan dalam satu transaksi. 0 = tidak terbatas.',
                'expired_date': 'Tanggal kadaluarsa produk. Kosongkan jika produk tidak memiliki expired date.',
                'barcode': 'Kode barcode untuk scanning (opsional)'
            };

            // Tambahkan tooltip ke semua label
            document.querySelectorAll('.form-label').forEach(label => {
                const inputId = label.getAttribute('for');
                if (inputId && tooltips[inputId]) {
                    label.setAttribute('title', tooltips[inputId]);
                    label.style.cursor = 'help';
                }
            });
        }
        
        // Fungsi untuk setup form validation
        function setupFormValidation() {
            const form = document.getElementById('editBarangForm');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validasi harga
                const hargaAmbil = parseFloat(document.getElementById('harga_ambil').value);

                if (hargaAmbil <= 0) {
                    showToast('Harga ambil harus lebih dari 0', 'error');
                    document.getElementById('harga_ambil').focus();
                    return;
                }

                // Validasi quantity
                const qty = parseInt(document.getElementById('qty').value);
                if (qty < 0) {
                    showToast('Quantity tidak boleh negatif', 'error');
                    document.getElementById('qty').focus();
                    return;
                }

                // Validasi max order
                const maxOrder = parseInt(document.getElementById('max_order').value);
                if (maxOrder < 0) {
                    showToast('Maximal order tidak boleh negatif', 'error');
                    document.getElementById('max_order').focus();
                    return;
                }

                if (maxOrder > 0 && maxOrder > qty) {
                    showToast('Maximal order tidak boleh lebih besar dari quantity tersedia', 'error');
                    document.getElementById('max_order').focus();
                    return;
                }

                // Validasi supplier dan sales
                const supplier = document.getElementById('supplier_id').value;
                const sales = document.getElementById('sales_id').value;

                if (!supplier) {
                    showToast('Silakan pilih supplier', 'error');
                    document.getElementById('supplier_id').focus();
                    return;
                }

                if (!sales) {
                    showToast('Silakan pilih sales', 'error');
                    document.getElementById('sales_id').focus();
                    return;
                }

                // Tampilkan loading
                showLoading(true);

                // Submit form
                setTimeout(() => {
                    form.submit();
                }, 1500);
            });
        }
        
        // Fungsi untuk preview data sebelum submit
        function previewData() {
            const formData = new FormData(document.getElementById('editBarangForm'));
            const previewContent = document.getElementById('previewContent');
            
            // Build preview content
            let html = `
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <img src="${document.getElementById('gambarPreview').src}" 
                             class="img-fluid rounded" 
                             style="max-height: 200px;" 
                             alt="Preview">
                    </div>
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Nama Barang</th>
                                <td>${document.getElementById('nama_barang').value}</td>
                            </tr>
                            <tr>
                                <th>Harga Ambil</th>
                                <td>Rp ${parseInt(document.getElementById('harga_ambil').value).toLocaleString('id-ID')}</td>
                            </tr>
                            <tr>
                                <th>Quantity</th>
                                <td>${document.getElementById('qty').value} pcs</td>
                            </tr>
                            <tr>
                                <th>Max Order</th>
                                <td>${document.getElementById('max_order').value > 0 ? document.getElementById('max_order').value + ' pcs' : 'Tidak terbatas'}</td>
                            </tr>
            `;
            
            // Tambahkan expired date jika ada
            const expiredDate = document.getElementById('expired_date').value;
            if (expiredDate) {
                const dateObj = new Date(expiredDate);
                const formattedDate = dateObj.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });
                html += `<tr>
                            <th>Tanggal Expired</th>
                            <td>${formattedDate}</td>
                        </tr>`;
            }
            
            // Tutup tabel
            html += `</table></div></div>`;
            
            // Set content dan show modal
            previewContent.innerHTML = html;
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
        }
        
        // Fungsi untuk menampilkan toast notification
        function showToast(message, type = 'info') {
            // Buat elemen toast
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white bg-${getToastColor(type)} border-0 position-fixed top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${getToastIcon(type)} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            // Tambahkan ke body
            document.body.insertAdjacentHTML('beforeend', toastHtml);
            
            // Inisialisasi dan show toast
            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 3000
            });
            
            toast.show();
            
            // Hapus elemen setelah toast hilang
            toastEl.addEventListener('hidden.bs.toast', function() {
                toastEl.remove();
            });
        }
        
        // Helper function untuk toast color
        function getToastColor(type) {
            switch(type) {
                case 'success': return 'success';
                case 'error': return 'danger';
                case 'warning': return 'warning';
                default: return 'primary';
            }
        }
        
        // Helper function untuk toast icon
        function getToastIcon(type) {
            switch(type) {
                case 'success': return 'check-circle';
                case 'error': return 'exclamation-circle';
                case 'warning': return 'exclamation-triangle';
                default: return 'info-circle';
            }
        }
        
        // Fungsi untuk menampilkan/menyembunyikan loading
        function showLoading(show) {
            const loadingOverlay = document.getElementById('loadingOverlay');
            const submitBtn = document.getElementById('submitBtn');
            
            if (show) {
                loadingOverlay.style.display = 'flex';
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
                submitBtn.disabled = true;
            } else {
                loadingOverlay.style.display = 'none';
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Perubahan';
                submitBtn.disabled = false;
            }
        }
        
        // Handle error gambar
        document.getElementById('gambarPreview').addEventListener('error', function() {
            this.src = '../../uploads/barang/default.jpg';
            showToast('Gambar tidak ditemukan, menggunakan gambar default', 'warning');
        });
        

    </script>
</body>
</html>

<?php
// Tutup koneksi
if (isset($conn)) {
    $conn->close();
}
?>