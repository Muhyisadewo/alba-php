<?php
// edit_barang_pecahon.php
include __DIR__ . '/../../config.php';

// Debug: Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$barang_id = $_GET['id'] ?? 0;

// Validasi ID
if ($barang_id <= 0) {
    die("ID tidak valid");
}

// Ambil data barang berdasarkan ID
$barang_sql = "SELECT * FROM gudang_pecahon WHERE id = ?";
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

// Cek apakah data berhasil diambil
if (!$barang) {
    die("Gagal mengambil data barang");
}

// Debug: Tampilkan data barang
// var_dump($barang); // Uncomment untuk debugging

// Proses update jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari POST
    $nama_barang = $_POST['nama_barang'] ?? '';
    $harga_ambil = $_POST['harga_ambil'] ?? 0;
    $qty = $_POST['qty'] ?? 0;
    $barcode = $_POST['barcode'] ?? '';
    $supplier_id = $_POST['supplier_id'] ?? 0;
    $sales_id = $_POST['sales_id'] ?? 0;
    
    // Validasi input dasar
    if (empty($nama_barang) || $harga_ambil <= 0 || $qty < 0 || $supplier_id <= 0 || $sales_id <= 0) {
        $error = "Data tidak valid. Silakan periksa kembali.";
    } else {
        // Handle file upload
        $gambar = $barang['gambar'] ?? 'default.jpg'; // Default ke gambar lama
        
        if (isset($_FILES['gambar']['error']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/barang/';
            
            // Pastikan folder upload ada
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate nama file unik
            $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['gambar']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            // Cek tipe file
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($fileExt, $allowedTypes)) {
                // Pindahkan file upload
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
            // Update data barang
            $update_sql = "UPDATE gudang_pecahon SET 
                           nama_barang = ?,
                           harga_ambil = ?,
                           qty = ?,
                           barcode = ?,
                           gambar = ?,
                           supplier_id = ?,
                           sales_id = ?,
                           updated_at = NOW()
                           WHERE id = ?";
            
            $update_stmt = $conn->prepare($update_sql);
            
            if ($update_stmt) {
                $update_stmt->bind_param("sdisssii", 
                    $nama_barang,
                    $harga_ambil,
                    $qty,
                    $barcode,
                    $gambar,
                    $supplier_id,
                    $sales_id,
                    $barang_id
                );
                
                if ($update_stmt->execute()) {
                    // Jika berhasil, redirect dengan pesan sukses
                    header("Location: ?path=gudang_pecahon.php?success=Barang berhasil diperbarui");
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
}

// Ambil data supplier untuk dropdown
$supplier_sql = "SELECT id, nama_supplier FROM supplier ORDER BY nama_supplier ASC";
$supplier_result = $conn->query($supplier_sql);

if (!$supplier_result) {
    die("Error query supplier: " . $conn->error);
}

// Ambil data sales berdasarkan supplier_id barang ini (jika ada)
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
    <title>Edit Barang Pecahon - <?php echo htmlspecialchars($barang['nama_barang']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
            margin-top: 30px;
        }
        
        .gambar-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .btn-custom {
            min-width: 120px;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .gambar-preview {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Edit Barang Pecahon</h4>
                <a href="?path=gudang_pecahon.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="editBarangForm">
                    <div class="row mb-4">
                        <div class="col-md-12 text-center">
                            <img src="<?php echo $gambar_path; ?>" 
                                 alt="Preview Gambar" 
                                 class="gambar-preview"
                                 id="gambarPreview"
                                 onerror="this.src='../../uploads/barang/default.jpg'">
                            <div class="mt-2">
                                <input type="file" 
                                       class="form-control d-none" 
                                       id="gambarInput" 
                                       name="gambar" 
                                       accept="image/*">
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm"
                                        onclick="document.getElementById('gambarInput').click()">
                                    <i class="fas fa-camera me-1"></i> Ubah Gambar
                                </button>
                                <small class="text-muted d-block mt-1">Kosongkan jika tidak ingin mengubah gambar</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama_barang" class="form-label">Nama Barang *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nama_barang" 
                                       name="nama_barang" 
                                       value="<?php echo htmlspecialchars($barang['nama_barang'] ?? ''); ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="harga_ambil" class="form-label">Harga Ambil *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="harga_ambil" 
                                           name="harga_ambil" 
                                           value="<?php echo $barang['harga_ambil'] ?? 0; ?>" 
                                           min="0" 
                                           step="100" 
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="qty" class="form-label">Quantity *</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="qty" 
                                       name="qty" 
                                       value="<?php echo $barang['qty'] ?? 0; ?>" 
                                       min="0" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="barcode" class="form-label">Barcode</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="barcode" 
                                       name="barcode" 
                                       value="<?php echo htmlspecialchars($barang['barcode'] ?? ''); ?>"
                                       placeholder="Masukkan barcode">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Supplier *</label>
                                <select class="form-control" id="supplier_id" name="supplier_id" required>
                                    <option value="">Pilih Supplier</option>
                                    <?php while($supplier = $supplier_result->fetch_assoc()): ?>
                                        <option value="<?php echo $supplier['id']; ?>" 
                                            <?php echo ($supplier['id'] == ($barang['supplier_id'] ?? 0)) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['nama_supplier']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sales_id" class="form-label">Sales *</label>
                                <select class="form-control" id="sales_id" name="sales_id" required>
                                    <option value="">Pilih Sales</option>
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
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 d-flex justify-content-end gap-2">
                            <a href="pecahon.php" class="btn btn-secondary btn-custom">
                                <i class="fas fa-times me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary btn-custom" id="submitBtn">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer text-muted">
                <small>ID: <?php echo $barang['id']; ?> | 
                Dibuat: <?php echo date('d/m/Y H:i', strtotime($barang['created_at'])); ?></small>
                <?php if (!empty($barang['updated_at'])): ?>
                    <br><small>Terakhir diubah: <?php echo date('d/m/Y H:i', strtotime($barang['updated_at'])); ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar saat dipilih
        document.getElementById('gambarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('gambarPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Dynamic load sales berdasarkan supplier
        document.getElementById('supplier_id').addEventListener('change', function() {
            const supplierId = this.value;
            const salesSelect = document.getElementById('sales_id');
            
            if (supplierId) {
                // Tampilkan loading
                salesSelect.innerHTML = '<option value="">Memuat sales...</option>';
                
                fetch('?path=get_sales.php?supplier_id=' + supplierId)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        salesSelect.innerHTML = '<option value="">Pilih Sales</option>';
                        if (data.length > 0) {
                            data.forEach(sales => {
                                const option = document.createElement('option');
                                option.value = sales.id;
                                option.textContent = sales.nama_sales;
                                salesSelect.appendChild(option);
                            });
                        } else {
                            salesSelect.innerHTML = '<option value="">Tidak ada sales</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        salesSelect.innerHTML = '<option value="">Error loading sales</option>';
                    });
            } else {
                salesSelect.innerHTML = '<option value="">Pilih Supplier terlebih dahulu</option>';
            }
        });

        // Validasi form sebelum submit
        document.getElementById('editBarangForm').addEventListener('submit', function(e) {
            const harga = document.getElementById('harga_ambil').value;
            const qty = document.getElementById('qty').value;
            const supplier = document.getElementById('supplier_id').value;
            const sales = document.getElementById('sales_id').value;
            
            // Validasi dasar
            if (harga <= 0) {
                e.preventDefault();
                alert('Harga ambil harus lebih dari 0');
                document.getElementById('harga_ambil').focus();
                return;
            }
            
            if (qty < 0) {
                e.preventDefault();
                alert('Quantity tidak boleh negatif');
                document.getElementById('qty').focus();
                return;
            }
            
            if (!supplier) {
                e.preventDefault();
                alert('Silakan pilih supplier');
                document.getElementById('supplier_id').focus();
                return;
            }
            
            if (!sales) {
                e.preventDefault();
                alert('Silakan pilih sales');
                document.getElementById('sales_id').focus();
                return;
            }
            
            // Tampilkan loading
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            submitBtn.disabled = true;
            
            // Biarkan form submit
        });

        // Handle error gambar
        document.getElementById('gambarPreview').addEventListener('error', function() {
            this.src = '../../uploads/barang/default.jpg';
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