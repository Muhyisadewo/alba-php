<?php
include __DIR__ . '/../../config.php';

/* ===============================
   1. VALIDASI SALES ID
================================ */
if (!isset($_GET['sales_id']) || !is_numeric($_GET['sales_id'])) {
    die("Sales tidak ditemukan.");
}

$sales_id = (int) $_GET['sales_id'];

/* ===============================
   2. AMBIL DATA SALES
================================ */
$stmtSales = $conn->prepare("
    SELECT id, nama_sales, perusahaan 
    FROM sales 
    WHERE id = ?
");
$stmtSales->bind_param("i", $sales_id);
$stmtSales->execute();
$sales = $stmtSales->get_result()->fetch_assoc();
$stmtSales->close();

if (!$sales) {
    die("Data sales tidak ditemukan.");
}

/* ===============================
   3. PROSES UPLOAD EXCEL
================================ */
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
    $file = $_FILES['excel_file'];
    
    // Validasi ekstensi file
    $allowed_extensions = ['xlsx', 'xls', 'csv'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $upload_message = '<div style="color: red; padding: 10px; background: #fee; border-radius: 5px; margin-bottom: 15px;">Error: Hanya file Excel (xlsx, xls) atau CSV yang diperbolehkan.</div>';
    } else {
        // Pindahkan file ke server
        $upload_dir = __DIR__ . '/../../uploads/excel/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = 'upload_' . $sales_id . '_' . date('Ymd_His') . '.' . $file_extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Proses file Excel
            $success_count = 0;
            $error_count = 0;
            $errors = [];
            
            if ($file_extension == 'csv') {
                // Proses CSV
                if (($handle = fopen($filepath, "r")) !== FALSE) {
                    $row = 0;
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $row++;
                        if ($row == 1) continue; // Skip header
                        
                        if (count($data) >= 2) {
                            $nama_barang = trim($data[0]);
                            $harga = (int) str_replace(['.', ',', 'Rp', ' '], '', $data[1]);
                            
                            if (!empty($nama_barang) && $harga > 0) {
                                // Cek apakah barang sudah ada
                                $check_stmt = $conn->prepare("SELECT id FROM daftar_barang WHERE sales_id = ? AND nama_barang = ?");
                                $check_stmt->bind_param("is", $sales_id, $nama_barang);
                                $check_stmt->execute();
                                $check_result = $check_stmt->get_result();
                                
                                if ($check_result->num_rows > 0) {
                                    // Update harga
                                    $update_stmt = $conn->prepare("UPDATE daftar_barang SET harga_ambil = ? WHERE sales_id = ? AND nama_barang = ?");
                                    $update_stmt->bind_param("iis", $harga, $sales_id, $nama_barang);
                                    if ($update_stmt->execute()) {
                                        $success_count++;
                                    } else {
                                        $error_count++;
                                        $errors[] = "Baris $row: Gagal update " . $nama_barang;
                                    }
                                    $update_stmt->close();
                                } else {
                                    // Insert baru
                                    $insert_stmt = $conn->prepare("INSERT INTO daftar_barang (sales_id, nama_barang, harga_ambil, qty) VALUES (?, ?, ?, 0)");
                                    $insert_stmt->bind_param("isi", $sales_id, $nama_barang, $harga);
                                    if ($insert_stmt->execute()) {
                                        $success_count++;
                                    } else {
                                        $error_count++;
                                        $errors[] = "Baris $row: Gagal insert " . $nama_barang;
                                    }
                                    $insert_stmt->close();
                                }
                                $check_stmt->close();
                            }
                        }
                    }
                    fclose($handle);
                }
            } else {
                // Untuk file Excel (xls, xlsx) - butuh library PhpSpreadsheet
                // Jika belum install, bisa gunakan library sederhana atau batasi ke CSV saja
                $upload_message = '<div style="color: orange; padding: 10px; background: #fff8e1; border-radius: 5px; margin-bottom: 15px;">Info: Untuk upload Excel, silakan gunakan format CSV. Atau install PhpSpreadsheet.</div>';
            }
            
            // Hapus file setelah diproses
            unlink($filepath);
            
            $upload_message = '<div style="color: green; padding: 10px; background: #e8f5e9; border-radius: 5px; margin-bottom: 15px;">
                ✅ Upload berhasil!<br>
                Data berhasil diproses: ' . $success_count . ' item<br>
                Gagal: ' . $error_count . ' item' . 
                (!empty($errors) ? '<br>Detail error:<br>' . implode('<br>', $errors) : '') . '
            </div>';
        } else {
            $upload_message = '<div style="color: red; padding: 10px; background: #fee; border-radius: 5px; margin-bottom: 15px;">Error: Gagal mengupload file.</div>';
        }
    }
}

/* ===============================
   4. AMBIL SEMUA BARANG MILIK SALES
================================ */
$sql = "
    SELECT
        id,
        nama_barang,
        harga_ambil,
        qty,
        gambar
    FROM daftar_barang
    WHERE sales_id = ?
    ORDER BY nama_barang ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$barang = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang - <?= htmlspecialchars($sales['nama_sales']) ?></title>
    <style>
        body {
            background-color: #437057;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 1rem;
            font-family: Arial, sans-serif;
        }
        .container {
            background-color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            padding: 1.5rem;
            width: 100%;
            max-width: 48rem;
        }
        @media (min-width: 768px) {
            .container {
                max-width: 56rem;
            }
        }
        @media (min-width: 1024px) {
            .container {
                max-width: 64rem;
            }
        }
        /* Responsif untuk layar kecil: penuhi semua layar */
        @media (max-width: 767px) {
            body {
                padding: 0;
            }
            .container {
                width: 100%;
                max-width: none;
                padding: 1rem;
                border-radius: 0;
                box-shadow: none;
            }
        }
        h2 {
            font-size: 1.5rem;
            font-weight: bold;
            color: #437057;
            margin-bottom: 1rem;
            text-align: center;
        }
        @media (min-width: 768px) {
            h2 {
                font-size: 1.875rem;
            }
        }
        .info-box {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .info-box p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
            color: #374151;
        }
        .btn {
            display: inline-block;
            background-color: #437057;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #365a46;
        }
        .btn:focus {
            outline: 2px solid #437057;
            outline-offset: 2px;
        }
        .btn-excel {
            background-color: #217346;
        }
        .btn-excel:hover {
            background-color: #1a5c38;
        }
        .btn-upload {
            background-color: #3b82f6;
        }
        .btn-upload:hover {
            background-color: #2563eb;
        }
        .btn-whatsapp {
            background-color: #25D366;
        }
        .btn-whatsapp:hover {
            background-color: #1da851;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #d1d5db;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        tr:hover {
            background-color: #f9fafb;
        }
        img {
            max-width: 55px;
            height: 55px;
            border-radius: 0.25rem;
            object-fit: cover;
        }
        .aksi-btn {
            display: flex;
            gap: 0.25rem;
        }
        .aksi-btn a {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .edit-btn {
            background-color: #3b82f6;
            color: white;
        }
        .edit-btn:hover {
            background-color: #2563eb;
        }
        .delete-btn {
            background-color: #ef4444;
            color: white;
        }
        .delete-btn:hover {
            background-color: #dc2626;
        }
        /* Upload section */
        .upload-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            border: 2px dashed #dee2e6;
        }
        .upload-section h3 {
            color: #437057;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        .upload-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .upload-form input[type="file"] {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background: white;
            flex: 1;
            min-width: 200px;
        }
        .format-info {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #e9ecef;
            border-radius: 0.25rem;
        }
        .format-info code {
            background: #dee2e6;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        /* Responsif tabel */
        @media (max-width: 767px) {
            table {
                font-size: 0.65rem;
            }
            th, td {
                padding: 0.5rem;
            }
            .aksi-btn {
                flex-direction: column;
                gap: 0.125rem;
            }
            .aksi-btn a {
                text-align: center;
            }
            .upload-form {
                flex-direction: column;
                align-items: stretch;
            }
            .upload-form input[type="file"] {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <h2>Daftar Barang - <?= htmlspecialchars($sales['nama_sales']) ?></h2>

    <div class="info-box">
        <p><strong>Nama Sales:</strong> <?= htmlspecialchars($sales['nama_sales']) ?></p>
        <p><strong>Supplier / Perusahaan:</strong> <?= htmlspecialchars($sales['perusahaan']) ?></p>
    </div>

    <?= $upload_message ?>

    <div>
        <a class="btn" href="/index.php">Kembali</a>
        <a class="btn" href="?path=tambah_barang&sales_id=<?= $sales_id ?>">Tambah Barang</a>
        <a class="btn btn-excel" href="?path=generate_excel_simple&sales_id=<?= $sales_id ?>" target="_blank">
            📥 Download Template Excel
        </a>
    </div>

    <table>
        <tr>
            <th>No</th>
            <th>Nama Barang</th>
            <th>Harga</th>
            <th>Qty</th>
            <th>Subt</th>
            <th>Foto</th>
            <th>Aksi</th>
        </tr>

        <?php
        if ($barang->num_rows > 0) {
            $no = 1;
            $total_subtotal = 0;
            while ($row = $barang->fetch_assoc()) {
                $subtotal = $row['qty'] * $row['harga_ambil'];
                $total_subtotal += $subtotal;
        ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['nama_barang']); ?></td>
            <td>Rp <?= number_format($row['harga_ambil'],0,',','.'); ?></td>
            <td><?= $row['qty']; ?></td>
            <td>Rp <?= number_format($subtotal,0,',','.'); ?></td>
            <td>
                <?php if (!empty($row['gambar'])): ?>
                    <img 
                        src="../../uploads/barang/<?= rawurlencode($row['gambar']) ?>"
                        width="55" height="55"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                    >
                    <span style="display:none;color:red;">Gambar tidak ditemukan</span>
                <?php else: ?>
                    Belum ada
                <?php endif; ?>
            </td>
            <td class="aksi-btn">
                <a class="edit-btn" href="?path=edit_barang&id=<?= $row['id'] ?>">Edit</a>
                <a class="delete-btn" href="?path=hapus_order_barang&id=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus barang ini?')">Hapus</a>
            </td>
        </tr>
        <?php } ?>
        <tr style="font-weight: bold; background-color: #f0f9f0;">
            <td colspan="3">TOTAL</td>
            <td><?= $barang->num_rows; ?> item</td>
            <td>Rp <?= number_format($total_subtotal,0,',','.'); ?></td>
            <td colspan="2"></td>
        </tr>
        <?php } else { ?>
        <tr>
            <td colspan="7">Belum ada barang untuk sales ini</td>
        </tr>
        <?php } ?>
    </table>

    <!-- Upload Excel Section -->
    <div class="upload-section">
        <h3>📤 Upload Data Barang via Excel/CSV</h3>
        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <input type="hidden" name="sales_id" value="<?= $sales_id ?>">
            <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
            <button type="submit" class="btn btn-upload">Upload & Proses</button>
        </form>
        
        <div class="format-info">
            <strong>Format file:</strong><br>
            1. Gunakan file CSV atau Excel dengan 2 kolom: <code>nama_barang</code> dan <code>harga</code><br>
            2. Baris pertama adalah header (akan di-skip)<br>
            3. Contoh format CSV:<br>
            <code>nama_barang,harga<br>Produk A,150000<br>Produk B,75000</code><br>
            4. Jika barang sudah ada, harganya akan di-update. Jika baru, akan ditambahkan.
        </div>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>