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
        $upload_message = '<div class="alert alert-danger">Error: Hanya file Excel (xlsx, xls) atau CSV yang diperbolehkan.</div>';
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
                $upload_message = '<div class="alert alert-warning">Info: Untuk upload Excel, silakan gunakan format CSV.</div>';
            }
            
            // Hapus file setelah diproses
            unlink($filepath);
            
            $upload_message = '<div class="alert alert-success">
                <strong>Upload berhasil!</strong><br>
                Data berhasil diproses: ' . $success_count . ' item<br>
                Gagal: ' . $error_count . ' item' . 
                (!empty($errors) ? '<br>Detail error:<br>' . implode('<br>', $errors) : '') . '
            </div>';
        } else {
            $upload_message = '<div class="alert alert-danger">Error: Gagal mengupload file.</div>';
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
        gambar,
        COALESCE(is_listed, 0) as is_listed
    FROM daftar_barang
    WHERE sales_id = ?
    ORDER BY nama_barang ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$barang = $stmt->get_result();
$total_barang = $barang->num_rows;
$stmt->close();

// Hitung total subtotal
$total_subtotal = 0;
$barang_data = [];
while ($row = $barang->fetch_assoc()) {
    $subtotal = $row['qty'] * $row['harga_ambil'];
    $total_subtotal += $subtotal;
    $row['subtotal'] = $subtotal;
    $barang_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang - <?= htmlspecialchars($sales['nama_sales']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --success-color: #4ade80;
            --danger-color: #f43f5e;
            --warning-color: #f59e0b;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --gray-color: #64748b;
            --border-color: #e2e8f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .page-title i {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 10px;
        }
        
        .sales-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .sales-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .sales-company {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: var(--gray-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .table-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }
        
        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Table Styling */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background-color: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
        }
        
        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-active {
            background-color: rgba(74, 222, 128, 0.1);
            color: #16a34a;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }

        .status-inactive {
            background-color: rgba(244, 63, 94, 0.1);
            color: #dc2626;
            border: 1px solid rgba(244, 63, 94, 0.3);
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            cursor: pointer;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        input:checked + .toggle-slider {
            background-color: var(--success-color);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .toggle-switch:hover .toggle-slider {
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Action Icons */
        .action-icons {
            display: flex;
            gap: 0.5rem;
        }
        
        .icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .icon-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-edit {
            background-color: var(--primary-color);
        }
        
        .btn-delete {
            background-color: var(--danger-color);
        }
        
        /* Upload Section */
        .upload-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .upload-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .upload-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .file-input {
            flex: 1;
            min-width: 300px;
        }
        
        .file-input input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            background: #f8fafc;
            transition: border-color 0.3s ease;
        }
        
        .file-input input[type="file"]:hover {
            border-color: var(--primary-color);
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: rgba(74, 222, 128, 0.1);
            border-color: var(--success-color);
            color: #166534;
        }
        
        .alert-danger {
            background-color: rgba(244, 63, 94, 0.1);
            border-color: var(--danger-color);
            color: #991b1b;
        }
        
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.1);
            border-color: var(--warning-color);
            color: #92400e;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray-color);
        }
        
        .empty-icon {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }
        
        /* Responsive Adjustments - Only Scale Down */
        @media (max-width: 1200px) {
            .container {
                padding: 15px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .table th,
            .table td {
                padding: 0.75rem;
            }
        }
        
        @media (max-width: 992px) {
            .container {
                padding: 12px;
            }
            
            .header {
                padding: 1.25rem;
            }
            
            .page-title {
                font-size: 1.4rem;
            }
            
            .btn {
                padding: 0.65rem 1rem;
                font-size: 0.9rem;
            }
            
            .table th,
            .table td {
                padding: 0.65rem;
                font-size: 0.95rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                padding: 1rem;
                border-radius: 10px;
            }
            
            .page-title {
                font-size: 1.3rem;
            }
            
            .sales-name {
                font-size: 1rem;
            }
            
            .sales-company {
                font-size: 0.85rem;
            }
            
            .action-buttons {
                gap: 0.5rem;
            }
            
            .btn {
                padding: 0.6rem 0.9rem;
                font-size: 0.85rem;
            }
            
            .table th,
            .table td {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
            
            .icon-btn {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 1.4rem;
            }
            
            .upload-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .file-input {
                min-width: auto;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 8px;
            }
            
            .header {
                padding: 0.9rem;
                border-radius: 8px;
            }
            
            .page-title {
                font-size: 1.2rem;
            }
            
            .page-title i {
                padding: 8px;
                font-size: 0.9rem;
            }
            
            .sales-info {
                padding: 0.6rem 0.8rem;
            }
            
            .table-header {
                padding: 1rem;
            }
            
            .table-title {
                font-size: 1.1rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }
            
            .btn {
                padding: 0.55rem 0.8rem;
                font-size: 0.8rem;
            }
            
            .icon-btn {
                width: 30px;
                height: 30px;
                font-size: 0.85rem;
            }
            
            .status-badge {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
            }
            
            .stat-card {
                padding: 0.9rem;
            }
            
            .stat-title {
                font-size: 0.85rem;
            }
            
            .stat-value {
                font-size: 1.3rem;
            }
            
            .upload-section {
                padding: 1.2rem;
            }
            
            .upload-title {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 375px) {
            /* iPhone 6/7/8 and similar small screens */
            .container {
                padding: 6px;
            }
            
            .header {
                padding: 0.8rem;
            }
            
            .page-title {
                font-size: 1.1rem;
            }
            
            .sales-name {
                font-size: 0.95rem;
            }
            
            .sales-company {
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 0.45rem;
                font-size: 0.8rem;
            }
            
            .btn {
                padding: 0.5rem 0.7rem;
                font-size: 0.75rem;
            }
            
            .icon-btn {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
            }
            
            .stat-card {
                padding: 0.8rem;
            }
            
            .stat-value {
                font-size: 1.2rem;
            }
            
            .upload-section {
                padding: 1rem;
            }
            
            .upload-title {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-boxes"></i>
                        Daftar Barang - <?= htmlspecialchars($sales['nama_sales']) ?>
                    </h1>
                </div>
                <div class="sales-info">
                    <div class="sales-name"><?= htmlspecialchars($sales['nama_sales']) ?></div>
                    <div class="sales-company"><?= htmlspecialchars($sales['perusahaan']) ?></div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="/index.php?path=order" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <a href="?path=tambah_barang&sales_id=<?= $sales_id ?>" class="btn btn-success">
                <i class="fas fa-plus"></i> Tambah Barang
            </a>
            <a href="https://wa.me/?text=<?= urlencode('Daftar Barang ' . $sales['nama_sales']) ?>" 
               target="_blank" class="btn btn-warning">
                <i class="fab fa-whatsapp"></i> Share WA
            </a>
        </div>

        <!-- Alert Messages -->
        <?= $upload_message ?>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-title">Total Barang</div>
                <div class="stat-value"><?= $total_barang ?> item</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Nilai Barang</div>
                <div class="stat-value">Rp <?= number_format($total_subtotal, 0, ',', '.') ?></div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-list"></i>
                    Daftar Produk
                </h2>
                <div style="font-size: 0.9rem; color: var(--gray-color);">
                    Total: <?= $total_barang ?> barang
                </div>
            </div>
            
            <?php if ($total_barang > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Barang</th>
                                <th style="width: 150px;">Harga</th>
                                <th style="width: 100px;">Qty</th>
                                <th style="width: 150px;">Subtotal</th>
                                <th style="width: 150px;">Status</th>
                                <th style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($barang_data as $index => $item): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                                <td style="font-weight: 600; color: var(--primary-color);">
                                    Rp <?= number_format($item['harga_ambil'], 0, ',', '.') ?>
                                </td>
                                <td><?= $item['qty'] ?></td>
                                <td style="font-weight: 600;">
                                    Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                                </td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox"
                                               data-id="<?= $item['id'] ?>"
                                               data-status="<?= $item['is_listed'] ?>"
                                               <?= $item['is_listed'] ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <div class="action-icons">
                                        <a href="?path=edit_barang&id=<?= $item['id'] ?>" 
                                           class="icon-btn btn-edit"
                                           title="Edit Barang">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?path=hapus_order_barang&id=<?= $item['id'] ?>" 
                                           class="icon-btn btn-delete"
                                           title="Hapus Barang"
                                           onclick="return confirm('Yakin ingin menghapus barang <?= htmlspecialchars($item['nama_barang']) ?>?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Total Row -->
                            <tr style="background-color: #f8fafc; font-weight: 600;">
                                <td colspan="3">TOTAL</td>
                                <td><?= $total_barang ?></td>
                                <td colspan="2">Rp <?= number_format($total_subtotal, 0, ',', '.') ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3>Belum ada barang</h3>
                    <p>Tambahkan barang pertama untuk sales ini</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Upload Section -->
        <div class="upload-section">
            <h3 class="upload-title">
                <i class="fas fa-file-upload"></i>
                Upload Via Excel/CSV
            </h3>
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="sales_id" value="<?= $sales_id ?>">
                
                <div class="file-input">
                    <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload & Proses
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effect to table rows
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8fafc';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });

            // Toggle Switch Functionality
            const toggleSwitches = document.querySelectorAll('.toggle-switch input');
            toggleSwitches.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const id = this.getAttribute('data-id');
                    const newStatus = this.checked ? 1 : 0;

                    // Send AJAX request
                    fetch('?path=toggle_listing', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(newStatus)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            // Revert on error
                            this.checked = !this.checked;
                            alert('Gagal mengupdate status listing');
                        }
                    })
                    .catch(error => {
                        // Revert on error
                        this.checked = !this.checked;
                        alert('Terjadi kesalahan jaringan');
                    });
                });
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>