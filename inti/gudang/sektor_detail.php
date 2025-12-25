<?php
// barang_sektor.php
include __DIR__ . '/../../config.php';

$sektor_id = $_GET['id'] ?? 0;

// Validasi ID
if ($sektor_id <= 0) {
    die("ID sektor tidak valid");
}

// Ambil data sektor
$sektor_sql = "SELECT nama_sektor FROM sektor WHERE id = ?";
$sektor_stmt = $conn->prepare($sektor_sql);
$sektor_stmt->bind_param("i", $sektor_id);
$sektor_stmt->execute();
$sektor_result = $sektor_stmt->get_result();
$sektor = $sektor_result->fetch_assoc();

if (!$sektor) {
    die("Sektor tidak ditemukan");
}

// Ambil barang dari gudang berdasarkan sektor
$barang_sql = "SELECT g.*, s.nama_sektor 
               FROM gudang g
               LEFT JOIN sektor s ON g.sektor_id = s.id
               WHERE g.sektor_id = ?
               ORDER BY g.created_at DESC";
$barang_stmt = $conn->prepare($barang_sql);
$barang_stmt->bind_param("i", $sektor_id);
$barang_stmt->execute();
$barang_result = $barang_stmt->get_result();

// Ambil semua supplier untuk dropdown (digunakan dalam modal)
$supplier_sql = "SELECT id, nama_supplier FROM supplier ORDER BY nama_supplier ASC";
$supplier_result = $conn->query($supplier_sql);

// Ambil semua sektor untuk dropdown (jika diperlukan)
$sektor_all_sql = "SELECT id, nama_sektor FROM sektor ORDER BY nama_sektor ASC";
$sektor_all_result = $conn->query($sektor_all_sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang di Sektor <?php echo htmlspecialchars($sektor['nama_sektor']); ?> - Gudang</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Flatpickr CSS untuk kalender modern -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
        }
        
        .container {
            max-width: 1200px;
            margin-top: 20px;
            margin-bottom: 100px;
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
        
        .list-group-item {
            border: none;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        /* Floating Add Button */
        .floating-add-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.4);
            transition: all 0.3s ease;
        }
        
        .floating-add-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.5);
            color: white;
        }
        
        /* Item barang styling */
        .barang-item {
            padding: 1.25rem;
        }
        
        .barang-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .barang-details h6 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .barang-details small {
            color: #718096;
            font-size: 0.875rem;
        }
        
        .badge-stock {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 20px;
        }
        
        .badge-stock.low {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .badge-stock.medium {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .badge-stock.high {
            background-color: #d1fae5;
            color: #059669;
        }
        
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .qty-input {
            width: 70px !important;
            text-align: center;
            font-weight: 600;
        }
        
        .btn-action {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .search-container {
            position: relative;
            max-width: 400px;
        }
        
        .search-container .form-control {
            padding-left: 45px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
        }
        
        .search-container .search-icon {
            position: absolute;
            left: 15px;
            top: 30%;
            transform: translateY(-50%);
            color: #718096;
            z-index: 10;
        }
        
        .fixed-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e9ecef;
            padding: 0;
            max-height: 190px;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
            z-index: 999;
        }
        
        /* Styling untuk modal */
        .modal-xl {
            max-width: 1200px;
        }
        
        .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            border-bottom: none;
            padding: 1.5rem 2rem;
        }
        
        .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin-top: 10px;
            }
            
            .barang-item {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 15px;
            }
            
            .barang-details {
                width: 100%;
            }
            
            .barang-actions {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .qty-controls {
                gap: 5px;
            }
            
            .qty-input {
                width: 60px !important;
            }
            
            .floating-add-btn {
                bottom: 20px;
                right: 20px;
                width: 55px;
                height: 55px;
                font-size: 1.3rem;
            }
            
            .search-container {
                max-width: 100%;
            }
            
            .modal-body {
                padding: 1.5rem;
                max-height: 60vh;
            }
        }
        
        @media (max-width: 576px) {
            .barang-image {
                width: 60px;
                height: 60px;
            }
            
            .btn-action {
                width: 35px;
                height: 35px;
                font-size: 0.875rem;
            }
            
            .qty-input {
                width: 50px !important;
                font-size: 0.875rem;
            }
            
            .floating-add-btn {
                bottom: 15px;
                right: 15px;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .barang-item {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .barang-item:nth-child(1) { animation-delay: 0.1s; }
        .barang-item:nth-child(2) { animation-delay: 0.2s; }
        .barang-item:nth-child(3) { animation-delay: 0.3s; }
        .barang-item:nth-child(4) { animation-delay: 0.4s; }
        .barang-item:nth-child(5) { animation-delay: 0.5s; }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #e2e8f0;
            margin-bottom: 1.5rem;
        }
        
        .empty-state h4 {
            color: #718096;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: #a0aec0;
            max-width: 500px;
            margin: 0 auto;
        }
        
        /* Loading overlay */
        .loading-overlay {
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
        
        .loading-spinner {
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
        
        /* Toast notification */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner mb-3"></div>
        <h5>Memproses...</h5>
    </div>
    
    <!-- Floating Add Button -->
    <button type="button" class="floating-add-btn" data-bs-toggle="modal" data-bs-target="#tambahBarangModal">
        <i class="fas fa-plus"></i>
    </button>
    
    <div class="container">
        <!-- Header Card -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><i class="fas fa-warehouse me-2"></i><?php echo htmlspecialchars($sektor['nama_sektor']); ?></h4>
                        <p class="mb-0 opacity-75">Ada <?php echo $barang_result->num_rows; ?> barang</p>
                    </div>
                    <a href="inti/gudang/index.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="card-body border-bottom">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           id="searchInput" 
                           class="form-control" 
                           placeholder="Cari dengan nama atau barcode..." 
                           autocomplete="off">
                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Cari barang secara real-time
                    </div>
                </div>
            </div>
            
            <!-- Barang List -->
            <form action="?path=ambil_barang.php" method="POST" id="ambilBarangForm">
                <input type="hidden" name="sektor_id" value="<?php echo $sektor_id; ?>">
                <div class="list-group list-group-flush" id="barangList">
                <?php if ($barang_result->num_rows > 0): ?>
                    <?php while ($barang = $barang_result->fetch_assoc()): 
                        // Tentukan status stok berdasarkan quantity
                        $stock_class = '';
                        if ($barang['qty'] <= 10) {
                            $stock_class = 'low';
                        } elseif ($barang['qty'] <= 50) {
                            $stock_class = 'medium';
                        } else {
                            $stock_class = 'high';
                        }
                        
                        // Format tanggal expired jika ada
                        $expired_text = '';
                        if (!empty($barang['expired_date']) && $barang['expired_date'] != '0000-00-00') {
                            $expired_date = date('d/m/Y', strtotime($barang['expired_date']));
                            $today = new DateTime();
                            $exp_date = new DateTime($barang['expired_date']);
                            $diff = $today->diff($exp_date)->days;
                            
                            if ($exp_date < $today) {
                                $expired_text = '<span class="badge bg-danger ms-2">Expired</span>';
                            } elseif ($diff <= 7) {
                                $expired_text = '<span class="badge bg-warning ms-2">Akan Expired</span>';
                            }
                        }
                    ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center barang-item" 
                             data-id="<?php echo $barang['id']; ?>" 
                             data-nama="<?php echo htmlspecialchars($barang['nama_barang']); ?>"
                             data-barcode="<?php echo htmlspecialchars($barang['barcode'] ?? ''); ?>">
                            
                            <!-- Info Barang -->
                            <div class="d-flex align-items-center gap-3">
                                <img src="../../uploads/barang/<?php echo htmlspecialchars($barang['gambar'] ?? 'default.jpg'); ?>" 
                                     alt="Gambar Barang" 
                                     class="barang-image"
                                     onerror="this.src='../../uploads/barang/default.jpg'">
                                
                                <div class="barang-details">
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($barang['nama_barang']); ?>
                                        <?php if (!empty($barang['max_order']) && $barang['max_order'] > 0): ?>
                                            <small class="text-muted ms-2">
                                                <i class="fas fa-shopping-cart"></i> Max: <?php echo $barang['max_order']; ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php echo $expired_text; ?>
                                    </h6>
                                    
                                    <div class="d-flex flex-wrap gap-3">
                                        <small>
                                            <i class="fas fa-cubes me-1"></i>
                                            <span class="badge badge-stock <?php echo $stock_class; ?>">
                                                <?php echo $barang['qty']; ?> pcs
                                            </span>
                                        </small>
                                        
                                        <small>
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            Rp <?php echo number_format($barang['harga_ambil'], 0, ',', '.'); ?>
                                        </small>
                                        
                                        <small>
                                            <i class="fas fa-tags me-1"></i>
                                            Rp <?php echo number_format($barang['harga_jual'], 0, ',', '.'); ?>
                                        </small>
                                        
                                        <?php if (!empty($barang['barcode'])): ?>
                                        <small>
                                            <i class="fas fa-barcode me-1"></i>
                                            <?php echo htmlspecialchars($barang['barcode']); ?>
                                        </small>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($barang['expired_date']) && $barang['expired_date'] != '0000-00-00'): ?>
                                        <small>
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            Exp: <?php echo date('d/m/Y', strtotime($barang['expired_date'])); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($barang['deskripsi'])): ?>
                                    <small class="d-block mt-2 text-muted">
                                        <i class="fas fa-align-left me-1"></i>
                                        <?php echo htmlspecialchars(substr($barang['deskripsi'], 0, 100)); ?>
                                        <?php if (strlen($barang['deskripsi']) > 100): ?>...<?php endif; ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="d-flex align-items-center gap-2">
                                <!-- Tombol Edit -->
                                <a href="?path=edit_barang_gdg.php?id=<?php echo $barang['id']; ?>" 
                                   class="btn btn-action btn-warning" 
                                   title="Edit Barang">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <!-- Tombol Delete -->
                                <button type="button" 
                                        class="btn btn-action btn-danger" 
                                        title="Hapus Barang"
                                        onclick="showDeleteModal(<?php echo $barang['id']; ?>, '<?php echo addslashes($barang['nama_barang']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                
                                <!-- Quantity Controls -->
                                <div class="qty-controls">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary" 
                                            onclick="changeQty(this, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    
                                    <input type="number" 
                                           name="ambil[<?php echo $barang['id']; ?>]" 
                                           value="0" 
                                           min="0" 
                                           max="<?php echo $barang['qty']; ?>" 
                                           class="form-control form-control-sm qty-input" 
                                           readonly>
                                    
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary" 
                                            onclick="changeQty(this, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h4>Tidak ada barang di sektor ini</h4>
                        <p>Mulai tambahkan barang pertama Anda dengan mengklik tombol <i class="fas fa-plus"></i> di pojok kanan bawah</p>
                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#tambahBarangModal">
                            <i class="fas fa-plus me-2"></i>Tambah Barang Pertama
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <br><br>
    <!-- Fixed Bottom Bar -->
    <?php if ($barang_result->num_rows > 0): ?>
    <div class="fixed-bottom-bar">
        <div class="container">
            <form action="?path=ambil_barang.php" method="POST" id="ambilBarangForm">
                <input type="hidden" name="sektor_id" value="<?php echo $sektor_id; ?>">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Ambil Barang</h6>
                                <small class="text-muted">Kamu ambil: <span id="totalSelected">0</span> item</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="submit" class="btn btn-success btn-lg px-5" id="submitAmbilBtn">
                            <i class="fas fa-check-circle me-2"></i>Ambil Sekarang
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Tambah Barang (SAMA PERSIS dengan Edit) -->
    <div class="modal fade" id="tambahBarangModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Barang Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="?path=tambah_barang_sektor.php" enctype="multipart/form-data" id="tambahBarangForm">
                    <input type="hidden" name="sektor_id" value="<?php echo $sektor_id; ?>">
                    
                    <div class="modal-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Preview Gambar -->
                        <div class="row mb-4">
                            <div class="col-md-12 text-center">
                                <img src="../../uploads/barang/default.jpg" 
                                     alt="Preview Gambar" 
                                     class="gambar-preview"
                                     id="gambarPreview"
                                     onclick="document.getElementById('gambarInput').click()"
                                     style="width: 180px; height: 180px; object-fit: cover; border-radius: 12px; border: 3px solid #e9ecef; margin-bottom: 15px; cursor: pointer;">
                                
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
                                            <i class="fas fa-camera me-1"></i> Pilih Gambar
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm"
                                                onclick="resetGambar()">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </button>
                                    </div>
                                    
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Ukuran maksimal 5MB. Format: JPG, PNG, GIF, WebP
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section: Informasi Barang -->
                        <h5 class="section-header" style="border-left: 4px solid #4361ee; padding-left: 15px; margin: 30px 0 20px 0; font-weight: 700; color: #2d3748; font-size: 1.2rem;">
                            <i class="fas fa-box me-2"></i>Informasi Barang
                        </h5>
                        
                        <!-- Baris 1: Nama Barang, Harga Ambil, Harga Jual -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nama_barang" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-tag"></i> Nama Barang *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nama_barang" 
                                           name="nama_barang" 
                                           required
                                           placeholder="Masukkan nama barang"
                                           style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px;">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="harga_ambil" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-money-bill-wave"></i> Harga Ambil *
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" 
                                               class="form-control" 
                                               id="harga_ambil" 
                                               name="harga_ambil" 
                                               min="0" 
                                               step="100" 
                                               required
                                               placeholder="0"
                                               style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px;">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="harga_jual" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-tags"></i> Harga Jual *
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" 
                                               class="form-control" 
                                               id="harga_jual" 
                                               name="harga_jual" 
                                               min="0" 
                                               step="100" 
                                               required
                                               placeholder="0"
                                               style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section: Stok & Pengaturan -->
                        <h5 class="section-header" style="border-left: 4px solid #4361ee; padding-left: 15px; margin: 30px 0 20px 0; font-weight: 700; color: #2d3748; font-size: 1.2rem;">
                            <i class="fas fa-warehouse me-2"></i>Stok & Pengaturan
                        </h5>
                        
                        <!-- Baris 2: Quantity, Max Order, Expired Date -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="qty" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-cubes"></i> Quantity *
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               class="form-control" 
                                               id="qty" 
                                               name="qty" 
                                               min="0" 
                                               required
                                               placeholder="Jumlah stok"
                                               style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px;">
                                        <span class="input-group-text">pcs</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="max_order" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-shopping-cart"></i> Maximal Order
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               class="form-control" 
                                               id="max_order" 
                                               name="max_order" 
                                               min="0" 
                                               placeholder="0 = tidak terbatas"
                                               style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px;">
                                        <span class="input-group-text">pcs</span>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle text-muted me-1"></i>
                                        Maksimal jumlah yang bisa dipesan per transaksi
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expired_date" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-calendar-alt"></i> Tanggal Expired
                                    </label>
                                    <div class="expired-date-group" style="position: relative;">
                                        <input type="text" 
                                               class="form-control flatpickr-input" 
                                               id="expired_date" 
                                               name="expired_date" 
                                               placeholder="Klik untuk memilih tanggal"
                                               data-input
                                               autocomplete="off"
                                               style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px; background-color: white; cursor: pointer; padding-right: 45px;">
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
                        <h5 class="section-header" style="border-left: 4px solid #4361ee; padding-left: 15px; margin: 30px 0 20px 0; font-weight: 700; color: #2d3748; font-size: 1.2rem;">
                            <i class="fas fa-info-circle me-2"></i>Informasi Tambahan
                        </h5>
                        
                        <!-- Baris 3: Barcode, Deskripsi -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="barcode" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-barcode"></i> Barcode
                                    </label>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control" 
                                               id="barcode" 
                                               name="barcode" 
                                               placeholder="Kode barcode (opsional)"
                                               style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px;">
                                        <button type="button" 
                                                class="btn btn-outline-secondary" 
                                                id="scanBarcodeBtn">
                                            <i class="fas fa-camera"></i> Scan
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-align-left"></i> Deskripsi
                                    </label>
                                    <textarea class="form-control" 
                                              id="deskripsi" 
                                              name="deskripsi" 
                                              rows="3"
                                              placeholder="Deskripsi barang (opsional)"
                                              style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px;"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section: Pemasok -->
                        <h5 class="section-header" style="border-left: 4px solid #4361ee; padding-left: 15px; margin: 30px 0 20px 0; font-weight: 700; color: #2d3748; font-size: 1.2rem;">
                            <i class="fas fa-truck me-2"></i>Informasi Pemasok
                        </h5>
                        
                        <!-- Baris 4: Supplier, Sales, Sektor -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="supplier_id" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-industry"></i> Supplier *
                                    </label>
                                    <div class="input-group">
                                        <select class="form-control" 
                                                id="supplier_id" 
                                                name="supplier_id" 
                                                required
                                                style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px;">
                                            <option value="">Pilih Supplier...</option>
                                            <?php 
                                            $supplier_result->data_seek(0); // Reset pointer
                                            while($supplier = $supplier_result->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $supplier['id']; ?>">
                                                    <?php echo htmlspecialchars($supplier['nama_supplier']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <button class="btn btn-outline-primary" 
                                                type="button" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalTambahSupplier">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sales_id" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-user-tie"></i> Sales *
                                    </label>
                                    <div class="input-group">
                                        <select class="form-control" 
                                                id="sales_id" 
                                                name="sales_id" 
                                                required
                                                style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px;">
                                            <option value="">Pilih Supplier terlebih dahulu</option>
                                        </select>
                                        <button class="btn btn-outline-primary" 
                                                type="button" 
                                                id="btnTambahSalesQuick">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sektor" class="form-label" style="font-weight: 600; color: #2d3748;">
                                        <i class="fas fa-map-marker-alt"></i> Sektor
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($sektor['nama_sektor']); ?>" 
                                           disabled
                                           readonly
                                           style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px 15px; background-color: #f8f9fa;">
                                    <small class="text-muted">
                                        <i class="fas fa-lock me-1"></i>
                                        Barang akan ditambahkan ke sektor ini
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitTambahBtn">
                            <i class="fas fa-plus-circle me-1"></i> Tambah Barang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Supplier -->
    <div class="modal fade" id="modalTambahSupplier" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Supplier Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" 
                           id="nama_supplier_baru" 
                           class="form-control" 
                           placeholder="Nama Supplier"
                           style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" id="simpanSupplierCepat">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Sales -->
    <div class="modal fade" id="modalTambahSales" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Sales Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" 
                           id="nama_sales_baru" 
                           class="form-control" 
                           placeholder="Nama Sales"
                           style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" id="simpanSalesCepat">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scanner Container (akan dibuat dinamis) -->
    <div id="scannerContainer" style="display: none;"></div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Flatpickr JS untuk kalender -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
    
    <!-- QuaggaJS untuk barcode scanner -->
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    
    <script>
        // Variabel global
        let expiredDatePicker;
        let scanning = false;
        let quaggaInitialized = false;
        
        // Inisialisasi saat halaman load
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Flatpickr untuk kalender modern
            expiredDatePicker = flatpickr("#expired_date", {
                dateFormat: "Y-m-d",
                locale: "id",
                minDate: "today",
                disableMobile: false,
                allowInput: true,
                clickOpens: true,
                theme: "material_blue",
                enableTime: false,
                altFormat: "j F Y",
                ariaDateFormat: "j F Y",
                mode: "single",
                onChange: function(selectedDates, dateStr, instance) {
                    updateExpiredStatus(dateStr);
                }
            });
            
            // Setup fungsi-fungsi
            setupEventListeners();
            setupRealTimeSearch();
            updateTotalSelected();
        });
        
        // Setup semua event listeners
        function setupEventListeners() {
            // Gambar preview
            document.getElementById('gambarInput').addEventListener('change', handleImageUpload);
            
            // Supplier change untuk load sales
            document.getElementById('supplier_id').addEventListener('change', loadSalesBySupplier);
            
            // Barcode scanner button
            document.getElementById('scanBarcodeBtn').addEventListener('click', toggleBarcodeScanner);
            
            // Simpan supplier cepat
            document.getElementById('simpanSupplierCepat').addEventListener('click', simpanSupplierCepat);
            
            // Simpan sales cepat
            document.getElementById('simpanSalesCepat').addEventListener('click', simpanSalesCepat);
            
            // Tombol tambah sales quick
            document.getElementById('btnTambahSalesQuick').addEventListener('click', function() {
                const supplierId = document.getElementById('supplier_id').value;
                if (!supplierId) {
                    showToast('Silakan pilih supplier terlebih dahulu!', 'warning');
                    return;
                }
                const modalSales = new bootstrap.Modal(document.getElementById('modalTambahSales'));
                modalSales.show();
                document.getElementById('nama_sales_baru').focus();
            });
            
            // Form validation untuk tambah barang
            document.getElementById('tambahBarangForm').addEventListener('submit', validateTambahBarangForm);
            
            // Form validation untuk ambil barang
            document.getElementById('ambilBarangForm').addEventListener('submit', validateAmbilBarangForm);
            
            // Auto-calculate harga jual
            document.getElementById('harga_ambil').addEventListener('blur', function() {
                const hargaAmbil = parseFloat(this.value);
                const hargaJual = document.getElementById('harga_jual');
                
                if (hargaAmbil > 0 && (!hargaJual.value || hargaJual.value == 0)) {
                    const calculatedPrice = Math.ceil(hargaAmbil * 1.3 / 100) * 100;
                    hargaJual.value = calculatedPrice;
                    showToast(`Harga jual otomatis dihitung: Rp ${calculatedPrice.toLocaleString('id-ID')}`, 'info');
                }
            });
            
            // Quantity input change events
            document.querySelectorAll('.qty-input').forEach(input => {
                input.addEventListener('change', updateTotalSelected);
            });
        }
        
        // Setup real-time search
        function setupRealTimeSearch() {
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performSearch(this.value);
                }, 300);
            });
        }
        
        // Fungsi untuk change quantity
        function changeQty(button, delta) {
            const input = button.parentElement.querySelector('.qty-input');
            const currentValue = parseInt(input.value);
            const maxValue = parseInt(input.max);
            const newValue = currentValue + delta;
            
            if (newValue >= 0 && newValue <= maxValue) {
                input.value = newValue;
                updateTotalSelected();
                showToast(`Quantity diubah: ${newValue}`, 'info');
            }
        }
        
        // Update total barang yang dipilih
        function updateTotalSelected() {
            let total = 0;
            document.querySelectorAll('.qty-input').forEach(input => {
                total += parseInt(input.value) || 0;
            });
            document.getElementById('totalSelected').textContent = total;
        }
        
        // Real-time search
        function performSearch(searchTerm) {
            const barangList = document.getElementById('barangList');
            const sektorId = <?php echo $sektor_id; ?>;
            
            showLoading(true);
            
            fetch(`?path=get_barang.php?sektor_id=${sektorId}&search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    
                    if (data.length > 0) {
                        const html = data.map(barang => {
                            // Tentukan status stok
                            let stock_class = '';
                            if (barang.qty <= 10) stock_class = 'low';
                            else if (barang.qty <= 50) stock_class = 'medium';
                            else stock_class = 'high';
                            
                            // Format expired date
                            let expired_text = '';
                            if (barang.expired_date && barang.expired_date !== '0000-00-00') {
                                const expDate = new Date(barang.expired_date);
                                const today = new Date();
                                const diff = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));
                                
                                if (expDate < today) {
                                    expired_text = '<span class="badge bg-danger ms-2">Expired</span>';
                                } else if (diff <= 7) {
                                    expired_text = '<span class="badge bg-warning ms-2">Akan Expired</span>';
                                }
                            }
                            
                            return `
                                <div class="list-group-item d-flex justify-content-between align-items-center barang-item" 
                                     data-id="${barang.id}" 
                                     data-nama="${barang.nama_barang}"
                                     data-barcode="${barang.barcode || ''}">
                                    
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="../../uploads/barang/${barang.gambar || 'default.jpg'}" 
                                             alt="Gambar Barang" 
                                             class="barang-image"
                                             onerror="this.src='../../uploads/barang/default.jpg'">
                                        
                                        <div class="barang-details">
                                            <h6 class="mb-1">
                                                ${barang.nama_barang}
                                                ${barang.max_order > 0 ? 
                                                    `<small class="text-muted ms-2">
                                                        <i class="fas fa-shopping-cart"></i> Max: ${barang.max_order}
                                                    </small>` : ''}
                                                ${expired_text}
                                            </h6>
                                            
                                            <div class="d-flex flex-wrap gap-3">
                                                <small>
                                                    <i class="fas fa-cubes me-1"></i>
                                                    <span class="badge badge-stock ${stock_class}">
                                                        ${barang.qty} pcs
                                                    </span>
                                                </small>
                                                
                                                <small>
                                                    <i class="fas fa-money-bill-wave me-1"></i>
                                                    Rp ${parseInt(barang.harga_ambil).toLocaleString('id-ID')}
                                                </small>
                                                
                                                <small>
                                                    <i class="fas fa-tags me-1"></i>
                                                    Rp ${parseInt(barang.harga_jual).toLocaleString('id-ID')}
                                                </small>
                                                
                                                ${barang.barcode ? `
                                                <small>
                                                    <i class="fas fa-barcode me-1"></i>
                                                    ${barang.barcode}
                                                </small>` : ''}
                                                
                                                ${barang.expired_date && barang.expired_date !== '0000-00-00' ? `
                                                <small>
                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                    Exp: ${new Date(barang.expired_date).toLocaleDateString('id-ID')}
                                                </small>` : ''}
                                            </div>
                                            
                                            ${barang.deskripsi ? `
                                            <small class="d-block mt-2 text-muted">
                                                <i class="fas fa-align-left me-1"></i>
                                                ${barang.deskripsi.substring(0, 100)}${barang.deskripsi.length > 100 ? '...' : ''}
                                            </small>` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="?path=edit_barang_gdg.php?id=${barang.id}" 
                                           class="btn btn-action btn-warning" 
                                           title="Edit Barang">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-action btn-danger" 
                                                title="Hapus Barang"
                                                onclick="showDeleteModal(${barang.id}, '${barang.nama_barang.replace(/'/g, "\\'")}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        
                                        <div class="qty-controls">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary" 
                                                    onclick="changeQty(this, -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            
                                            <input type="number" 
                                                   name="ambil[${barang.id}]" 
                                                   value="0" 
                                                   min="0" 
                                                   max="${barang.qty}" 
                                                   class="form-control form-control-sm qty-input" 
                                                   readonly>
                                            
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary" 
                                                    onclick="changeQty(this, 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                        
                        barangList.innerHTML = html;
                        updateTotalSelected();
                    } else {
                        barangList.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h4>Tidak ada barang yang ditemukan</h4>
                                <p>Tidak ada barang yang sesuai dengan pencarian "${searchTerm}"</p>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                                    <i class="fas fa-times me-2"></i>Hapus Pencarian
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showLoading(false);
                    barangList.innerHTML = '<div class="alert alert-danger" role="alert">Terjadi kesalahan saat mencari barang.</div>';
                });
        }
        
        // Clear search
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            performSearch('');
        }
        
        // Update expired status
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
                icon = '<i class="fas fa-exclamation-triangle"></i>';
                statusHtml = `<div class="alert alert-danger alert-sm p-2">
                    ${icon} SUDAH EXPIRED (${Math.abs(diffDays)} hari yang lalu)
                </div>`;
            } else if (diffDays === 0) {
                icon = '<i class="fas fa-exclamation-circle"></i>';
                statusHtml = `<div class="alert alert-warning alert-sm p-2">
                    ${icon} EXPIRED HARI INI
                </div>`;
            } else if (diffDays <= 7) {
                icon = '<i class="fas fa-clock"></i>';
                statusHtml = `<div class="alert alert-warning alert-sm p-2">
                    ${icon} Akan expired dalam ${diffDays} hari
                </div>`;
            } else if (diffDays <= 30) {
                icon = '<i class="fas fa-calendar-week"></i>';
                statusHtml = `<div class="alert alert-info alert-sm p-2">
                    ${icon} Akan expired dalam ${diffDays} hari
                </div>`;
            } else {
                icon = '<i class="fas fa-check-circle"></i>';
                statusHtml = `<div class="alert alert-success alert-sm p-2">
                    ${icon} Masih ${diffDays} hari lagi
                </div>`;
            }
            
            statusDiv.innerHTML = statusHtml;
        }
        
        // Buka kalender
        function openCalendar() {
            expiredDatePicker.open();
        }
        
        // Hapus tanggal expired
        function clearExpiredDate() {
            expiredDatePicker.clear();
            document.getElementById('expiredStatus').innerHTML = '';
            showToast('Tanggal expired telah dihapus', 'info');
        }
        
        // Handle upload gambar
        function handleImageUpload(e) {
            const file = e.target.files[0];
            if (file) {
                // Validasi ukuran file
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
                    showToast('Gambar berhasil dipilih', 'success');
                };
                reader.readAsDataURL(file);
            }
        }
        
        // Reset gambar ke default
        function resetGambar() {
            document.getElementById('gambarPreview').src = '../../uploads/barang/default.jpg';
            document.getElementById('gambarInput').value = '';
            showToast('Gambar telah direset ke default', 'info');
        }
        
        // Load sales berdasarkan supplier
        function loadSalesBySupplier() {
            const supplierId = this.value;
            const salesSelect = document.getElementById('sales_id');
            
            if (!supplierId) {
                salesSelect.innerHTML = '<option value="">Pilih Supplier terlebih dahulu</option>';
                salesSelect.disabled = false;
                return;
            }
            
            salesSelect.innerHTML = '<option value="">Memuat sales...</option>';
            salesSelect.disabled = true;
            
            fetch(`?path=get_sales.php?supplier_id=${supplierId}`)
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
        
        // Toggle barcode scanner
        function toggleBarcodeScanner() {
            if (scanning) {
                stopBarcodeScanner();
            } else {
                startBarcodeScanner();
            }
        }
        
        // Start barcode scanner
        function startBarcodeScanner() {
            // Create scanner modal
            const scannerHtml = `
                <div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title">Scan Barcode</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopBarcodeScanner()"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div id="interactive" style="width: 100%; height: 300px; border: 2px dashed #ddd;"></div>
                                <p class="mt-3 text-muted">Arahkan kamera ke barcode</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="stopBarcodeScanner()">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('scannerContainer').innerHTML = scannerHtml;
            
            // Show modal
            const scannerModal = new bootstrap.Modal(document.getElementById('scannerModal'));
            scannerModal.show();
            
            // Initialize Quagga
            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#interactive'),
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: "environment"
                    }
                },
                decoder: {
                    readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader", "upc_reader", "upc_e_reader"]
                }
            }, function(err) {
                if (err) {
                    console.error(err);
                    showToast('Gagal mengakses kamera', 'error');
                    scannerModal.hide();
                    stopBarcodeScanner();
                    return;
                }
                Quagga.start();
                scanning = true;
                quaggaInitialized = true;
                
                // Update button
                document.getElementById('scanBarcodeBtn').innerHTML = '<i class="fas fa-stop"></i> Stop';
                document.getElementById('scanBarcodeBtn').classList.remove('btn-outline-secondary');
                document.getElementById('scanBarcodeBtn').classList.add('btn-danger');
            });
            
            // Handle barcode detection
            Quagga.onDetected(function(result) {
                const code = result.codeResult.code;
                document.getElementById('barcode').value = code;
                showToast(`Barcode berhasil dipindai: ${code}`, 'success');
                stopBarcodeScanner();
                scannerModal.hide();
            });
        }
        
        // Stop barcode scanner
        function stopBarcodeScanner() {
            if (quaggaInitialized) {
                Quagga.stop();
                quaggaInitialized = false;
            }
            scanning = false;
            
            // Update button
            const scanBtn = document.getElementById('scanBarcodeBtn');
            if (scanBtn) {
                scanBtn.innerHTML = '<i class="fas fa-camera"></i> Scan';
                scanBtn.classList.remove('btn-danger');
                scanBtn.classList.add('btn-outline-secondary');
            }
            
            // Remove modal
            const scannerModal = document.getElementById('scannerModal');
            if (scannerModal) {
                scannerModal.remove();
            }
        }
        
        // Show delete modal
        function showDeleteModal(barangId, barangNama) {
            const modalHtml = `
                <div class="modal fade" id="deleteModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Konfirmasi Hapus</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-4">
                                    <i class="fas fa-trash-alt fa-3x text-danger"></i>
                                </div>
                                <p class="text-center">Apakah Anda yakin ingin menghapus barang ini?</p>
                                <h6 class="text-center mb-3">"${barangNama}"</h6>
                                <p class="text-muted text-center small">Tindakan ini tidak dapat dibatalkan.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
            
            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                window.location.href = `?path=hapus_barang.php?id=${barangId}`;
            });
            
            document.getElementById('deleteModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('deleteModal').remove();
            });
        }
        
        // Simpan supplier cepat
        function simpanSupplierCepat() {
            const nama = document.getElementById('nama_supplier_baru').value.trim();
            if (!nama) {
                showToast('Nama supplier harus diisi', 'warning');
                return;
            }
            
            showLoading(true);
            
            fetch('?path=tambah_supplier_ajax.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'nama_supplier=' + encodeURIComponent(nama)
            })
            .then(res => res.json())
            .then(data => {
                showLoading(false);
                
                if(data.success) {
                    const opt = new Option(nama, data.id);
                    document.getElementById('supplier_id').add(opt);
                    document.getElementById('supplier_id').value = data.id;
                    
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('modalTambahSupplier')).hide();
                    document.getElementById('nama_supplier_baru').value = '';
                    
                    // Show success message
                    showToast('Supplier berhasil ditambahkan', 'success');
                    
                    // Auto open sales modal
                    setTimeout(() => {
                        const modalSales = new bootstrap.Modal(document.getElementById('modalTambahSales'));
                        modalSales.show();
                        document.getElementById('nama_sales_baru').focus();
                    }, 300);
                } else {
                    showToast(data.message || 'Gagal menambahkan supplier', 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                showToast('Terjadi kesalahan', 'error');
                console.error('Error:', error);
            });
        }
        
        // Simpan sales cepat
        function simpanSalesCepat() {
            const nama = document.getElementById('nama_sales_baru').value.trim();
            const supplierId = document.getElementById('supplier_id').value;
            
            if (!nama) {
                showToast('Nama sales harus diisi', 'warning');
                return;
            }
            
            if (!supplierId) {
                showToast('Silakan pilih supplier terlebih dahulu', 'warning');
                return;
            }
            
            showLoading(true);
            
            fetch('?path=tambah_sales_ajax.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `nama_sales=${encodeURIComponent(nama)}&supplier_id=${supplierId}`
            })
            .then(res => res.json())
            .then(data => {
                showLoading(false);
                
                if(data.success) {
                    const opt = new Option(nama, data.id);
                    document.getElementById('sales_id').add(opt);
                    document.getElementById('sales_id').value = data.id;
                    
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('modalTambahSales')).hide();
                    document.getElementById('nama_sales_baru').value = '';
                    
                    // Show success message
                    showToast('Sales berhasil ditambahkan', 'success');
                } else {
                    showToast(data.message || 'Gagal menambahkan sales', 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                showToast('Terjadi kesalahan', 'error');
                console.error('Error:', error);
            });
        }
        
        // Validasi form tambah barang
        function validateTambahBarangForm(e) {
            e.preventDefault();
            
            const hargaAmbil = parseFloat(document.getElementById('harga_ambil').value);
            const hargaJual = parseFloat(document.getElementById('harga_jual').value);
            const qty = parseInt(document.getElementById('qty').value);
            const maxOrder = parseInt(document.getElementById('max_order').value);
            const supplier = document.getElementById('supplier_id').value;
            const sales = document.getElementById('sales_id').value;
            
            // Validasi harga
            if (hargaAmbil <= 0) {
                showToast('Harga ambil harus lebih dari 0', 'error');
                document.getElementById('harga_ambil').focus();
                return;
            }
            
            if (hargaJual <= 0) {
                showToast('Harga jual harus lebih dari 0', 'error');
                document.getElementById('harga_jual').focus();
                return;
            }
            
            if (hargaJual < hargaAmbil) {
                showToast('Harga jual tidak boleh lebih rendah dari harga ambil', 'error');
                document.getElementById('harga_jual').focus();
                return;
            }
            
            // Validasi quantity
            if (qty < 0) {
                showToast('Quantity tidak boleh negatif', 'error');
                document.getElementById('qty').focus();
                return;
            }
            
            // Validasi max order
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
            
            // Tampilkan loading dan submit form
            showLoading(true);
            document.getElementById('submitTambahBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            document.getElementById('submitTambahBtn').disabled = true;
            
            setTimeout(() => {
                e.target.submit();
            }, 1000);
        }
        
        // Validasi form ambil barang
        function validateAmbilBarangForm(e) {
            let totalSelected = 0;
            document.querySelectorAll('.qty-input').forEach(input => {
                totalSelected += parseInt(input.value) || 0;
            });
            
            if (totalSelected === 0) {
                e.preventDefault();
                showToast('Silakan pilih setidaknya satu barang dengan quantity lebih dari 0', 'warning');
                return;
            }
            
            // Tampilkan loading
            showLoading(true);
            document.getElementById('submitAmbilBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
            document.getElementById('submitAmbilBtn').disabled = true;
        }
        
        // Fungsi untuk menampilkan toast notification
        function showToast(message, type = 'info') {
            const toastId = 'toast-' + Date.now();
            const toastColor = type === 'success' ? 'success' : 
                              type === 'error' ? 'danger' : 
                              type === 'warning' ? 'warning' : 'primary';
            
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white bg-${toastColor} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                            type === 'error' ? 'exclamation-circle' : 
                                            type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            const toastContainer = document.querySelector('.toast-container');
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 3000
            });
            
            toast.show();
            
            toastEl.addEventListener('hidden.bs.toast', function() {
                toastEl.remove();
            });
        }
        
        // Tampilkan/menyembunyikan loading
        function showLoading(show) {
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.style.display = show ? 'flex' : 'none';
        }
    </script>
</body>
</html>

<?php
// Tutup koneksi
$conn->close();
?>