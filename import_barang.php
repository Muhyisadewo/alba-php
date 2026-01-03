<?php
include 'config.php';

// Ambil jenis kunjungan untuk dropdown
$jenisKunjungan = [];
$res = $conn->query("SELECT id, nama_jenis FROM jenis_kunjungan ORDER BY nama_jenis ASC");
while ($r = $res->fetch_assoc()) {
    $jenisKunjungan[] = $r;
}

// Proses simpan supplier dan sales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_data'])) {
    $nama_supplier = $conn->real_escape_string(trim($_POST['nama_supplier']));
    $kontak_supplier = $conn->real_escape_string(trim($_POST['kontak_supplier']));
    $alamat_supplier = $conn->real_escape_string(trim($_POST['alamat_supplier']));

    $nama_sales = $conn->real_escape_string(trim($_POST['nama_sales']));
    $kontak_sales = $conn->real_escape_string(trim($_POST['kontak_sales']));
    $jenis_id = (int)$_POST['jenis_kunjungan'];
    $interval = (int)$_POST['interval_kunjungan'];

    // Insert supplier
    $sqlSupplier = "INSERT INTO supplier (nama_supplier, kontak, alamat, created_at)
                    VALUES ('$nama_supplier', '$kontak_supplier', '$alamat_supplier', NOW())";

    if ($conn->query($sqlSupplier)) {
        $supplier_id = $conn->insert_id;

        // Ambil nama_jenis untuk kolom kunjungan
        $stmt_jenis = $conn->prepare("SELECT nama_jenis FROM jenis_kunjungan WHERE id = ?");
        $stmt_jenis->bind_param("i", $jenis_id);
        $stmt_jenis->execute();
        $result_jenis = $stmt_jenis->get_result();
        $jenis_data = $result_jenis->fetch_assoc();
        $nama_jenis = $jenis_data['nama_jenis'] ?? '';
        $stmt_jenis->close();

        // Insert sales
        $stmt = $conn->prepare("
            INSERT INTO sales
            (nama_sales, perusahaan, kontak, supplier_id, jenis_kunjungan_id, kunjungan, interval_kunjungan)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssisii",
            $nama_sales,
            $nama_supplier,
            $kontak_sales,
            $supplier_id,
            $jenis_id,
            $nama_jenis,
            $interval
        );

        if ($stmt->execute()) {
            $sales_id = $conn->insert_id;
            header("Location: import_barang.php?supplier_id=$supplier_id&sales_id=$sales_id&step=2");
            exit();
        } else {
            echo "Error menambah sales: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error menambah supplier: " . $conn->error;
    }
}

// Proses upload Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_excel'])) {
    $supplier_id = (int)$_POST['supplier_id'];
    $sales_id = (int)$_POST['sales_id'];

    if ($supplier_id <= 0 || $sales_id <= 0) {
        die("Supplier dan Sales harus dipilih terlebih dahulu.");
    }

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        die("File Excel tidak valid.");
    }

    $file = $_FILES['excel_file']['tmp_name'];
    $handle = fopen($file, "r");

    if ($handle === false) {
        die("Gagal membaca file.");
    }

    // Skip header
    fgetcsv($handle);

    $success_count = 0;
    $error_count = 0;

    // Buat order baru dengan status 'sudah_dibayar' agar tidak muncul sebagai tagihan
    $stmt = $conn->prepare("INSERT INTO orders (sales_id, tanggal_order, total_harga, status) VALUES (?, NOW(), 0, 'sudah_dibayar')");
    $stmt->bind_param("i", $sales_id);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    while (($data = fgetcsv($handle)) !== false) {
        $nama_barang = trim($data[0] ?? '');
        $harga_str = trim($data[1] ?? '');

        if (empty($nama_barang) || empty($harga_str)) {
            $error_count++;
            continue;
        }

        $harga_ambil = (int)str_replace(['Rp', '.', ' '], '', $harga_str);
        if ($harga_ambil <= 0) {
            $error_count++;
            continue;
        }

        // Insert barang ke daftar_barang (qty default 1, subtotal = harga)
        $stmt = $conn->prepare("
            INSERT INTO daftar_barang
            (order_id, supplier_id, sales_id, nama_barang, harga_ambil, qty, subtotal, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
        ");
        $stmt->bind_param(
            "iiisii",
            $order_id,
            $supplier_id,
            $sales_id,
            $nama_barang,
            $harga_ambil,
            $harga_ambil
        );

        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
        $stmt->close();
    }

    fclose($handle);

    // Update total order
    $stmt = $conn->prepare("
        UPDATE orders SET total_harga = (
            SELECT SUM(subtotal) FROM daftar_barang WHERE order_id = ?
        ) WHERE id = ?
    ");
    $stmt->bind_param("ii", $order_id, $order_id);
    $stmt->execute();
    $stmt->close();

    // Jika tidak ada error, redirect ke step 3
    if ($error_count == 0) {
        header("Location: import_barang.php?supplier_id=$supplier_id&sales_id=$sales_id&step=3&success=$success_count");
        exit();
    } else {
        echo "<script>alert('Upload selesai! Berhasil: $success_count, Gagal: $error_count');</script>";
    }
}

// Tentukan step yang sedang aktif
$step = $_GET['step'] ?? 1;
$current_supplier_id = $_GET['supplier_id'] ?? 0;
$current_sales_id = $_GET['sales_id'] ?? 0;
$success_count = $_GET['success'] ?? 0;

$supplier_data = null;
$sales_data = null;

if ($current_supplier_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM supplier WHERE id = ?");
    $stmt->bind_param("i", $current_supplier_id);
    $stmt->execute();
    $supplier_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($current_sales_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->bind_param("i", $current_sales_id);
    $stmt->execute();
    $sales_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Barang - ALBAROKAH-DEMAK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #21633E 0%, #1A2C21 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .header h1 {
            color: #21633E;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 1.1em;
        }

        .back-btn {
            position: absolute;
            left: 0;
            top: 10px;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 4px;
            background: #e1e5e9;
            z-index: 1;
        }

        .progress-bar {
            position: absolute;
            top: 20px;
            left: 10%;
            height: 4px;
            background: #21633E;
            transition: width 0.5s ease;
            z-index: 2;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 3;
            position: relative;
            flex: 1;
        }

        .step-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #e1e5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-weight: bold;
            color: #666;
            border: 4px solid white;
            transition: all 0.3s;
        }

        .step.active .step-icon {
            background: #21633E;
            color: white;
            transform: scale(1.1);
        }

        .step.completed .step-icon {
            background: #4caf50;
            color: white;
        }

        .step-label {
            font-size: 0.9em;
            color: #666;
            font-weight: 600;
            text-align: center;
        }

        .step.active .step-label {
            color: #21633E;
        }

        /* Step Content */
        .step-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .step-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Sections */
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #21633E;
        }

        .form-section h3 {
            color: #21633E;
            margin-bottom: 20px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        input, select, textarea {
            padding: 14px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
            width: 100%;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #21633E;
            box-shadow: 0 0 0 3px rgba(33, 99, 62, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .jenis-container {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .jenis-container select {
            flex: 1;
        }

        .btn-add-jenis {
            background: #21633E;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2em;
            transition: background 0.3s;
            height: 48px;
        }

        .btn-add-jenis:hover {
            background: #1a4d2e;
        }

        /* Buttons */
        .btn-submit {
            background: linear-gradient(135deg, #21633E 0%, #437057 100%);
            color: white;
            border: none;
            padding: 16px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 99, 62, 0.2);
        }

        /* Step 2 - Excel Section */
        .excel-section {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            margin-top: 20px;
            border: 2px solid #e1e5e9;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .excel-section h3 {
            color: #21633E;
            margin-bottom: 20px;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .excel-instructions {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #D4AF37;
        }

        .excel-instructions h4 {
            color: #D4AF37;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .excel-instructions ol {
            margin-left: 20px;
            color: #555;
        }

        .excel-instructions li {
            margin-bottom: 10px;
        }

        .excel-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .btn-excel {
            background: linear-gradient(135deg, #D4AF37 0%, #B8860B 100%);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            min-width: 200px;
        }

        .btn-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.2);
        }

        .upload-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-top: 20px;
            border: 2px dashed #D4AF37;
        }

        .upload-form h4 {
            color: #D4AF37;
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .file-input {
            margin-bottom: 20px;
        }

        .file-input input[type="file"] {
            display: block;
            margin: 0 auto;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            color: #333;
            width: 100%;
            max-width: 400px;
        }

        .file-input input[type="file"]::file-selector-button {
            background: #21633E;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }

        /* Preview Table */
        .preview-table-container {
            margin: 30px 0;
            overflow-x: auto;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .preview-table th {
            background: #21633E;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .preview-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e1e5e9;
        }

        .preview-table tr:hover {
            background: #f8f9fa;
        }

        .preview-table tr:last-child td {
            border-bottom: none;
        }

        /* Step 3 - Success Section */
        .success-section {
            text-align: center;
            padding: 40px 20px;
            animation: zoomIn 0.5s ease;
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .success-icon {
            font-size: 80px;
            color: #4caf50;
            margin-bottom: 20px;
        }

        .success-section h2 {
            color: #21633E;
            margin-bottom: 15px;
            font-size: 2em;
        }

        .success-section p {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .preview-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin: 30px auto;
            max-width: 600px;
            text-align: left;
            border: 2px solid #e1e5e9;
        }

        .preview-card h4 {
            color: #21633E;
            margin-bottom: 20px;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preview-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-item label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        .info-item .value {
            color: #333;
            font-size: 1em;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn-action {
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 180px;
        }

        .btn-view {
            background: #21633E;
            color: white;
            border: none;
        }

        .btn-view:hover {
            background: #1a4d2e;
            transform: translateY(-2px);
        }

        .btn-edit {
            background: #D4AF37;
            color: white;
            border: none;
        }

        .btn-edit:hover {
            background: #B8860B;
            transform: translateY(-2px);
        }

        .btn-close {
            background: #dc3545;
            color: white;
            border: none;
        }

        .btn-close:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            animation: modalFadeIn 0.3s ease;
            max-height: 80vh;
            overflow-y: auto;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-content h3 {
            margin-bottom: 20px;
            color: #21633E;
        }

        .modal-content input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-save {
            background: #21633E;
            color: white;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .header h1 {
                font-size: 2em;
            }

            .form-row,
            .preview-info {
                grid-template-columns: 1fr;
            }

            .excel-buttons,
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-excel,
            .btn-action {
                width: 100%;
                max-width: 300px;
            }

            .progress-steps::before {
                left: 15%;
                right: 15%;
            }

            .progress-bar {
                left: 15%;
            }

            .back-btn {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 15px;
                display: inline-block;
            }
        }

        @media (max-width: 480px) {
            .progress-steps {
                flex-direction: column;
                gap: 20px;
            }

            .progress-steps::before {
                display: none;
            }

            .progress-bar {
                display: none;
            }

            .step {
                flex-direction: row;
                gap: 15px;
            }

            .step-icon {
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if ($step > 1): ?>
            <a href="import_barang.php?supplier_id=<?php echo $current_supplier_id; ?>&sales_id=<?php echo $current_sales_id; ?>&step=<?php echo $step-1; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <?php endif; ?>
            <h1><i class="fas fa-upload"></i> Import Barang</h1>
            <p>Langkah <?php echo $step; ?> dari 3: <?php 
                if ($step == 1) echo 'Input Data Supplier & Sales';
                elseif ($step == 2) echo 'Upload Excel Barang';
                else echo 'Selesai';
            ?></p>
        </div>

        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="progress-bar" style="width: <?php echo ($step-1)*40; ?>%;"></div>
            <div class="step <?php echo $step >= 1 ? 'completed' : ''; ?> <?php echo $step == 1 ? 'active' : ''; ?>">
                <div class="step-icon">
                    <?php if ($step > 1): ?>
                    <i class="fas fa-check"></i>
                    <?php else: ?>
                    1
                    <?php endif; ?>
                </div>
                <div class="step-label">Data Supplier & Sales</div>
            </div>
            <div class="step <?php echo $step >= 2 ? 'completed' : ''; ?> <?php echo $step == 2 ? 'active' : ''; ?>">
                <div class="step-icon">
                    <?php if ($step > 2): ?>
                    <i class="fas fa-check"></i>
                    <?php else: ?>
                    2
                    <?php endif; ?>
                </div>
                <div class="step-label">Upload Excel</div>
            </div>
            <div class="step <?php echo $step >= 3 ? 'completed' : ''; ?> <?php echo $step == 3 ? 'active' : ''; ?>">
                <div class="step-icon">
                    3
                </div>
                <div class="step-label">Selesai</div>
            </div>
        </div>

        <!-- Step 1: Supplier & Sales Form -->
        <div class="step-content <?php echo $step == 1 ? 'active' : ''; ?>" id="step1">
            <form method="POST" enctype="multipart/form-data">
                <!-- Supplier Form -->
                <div class="form-section">
                    <h3><i class="fas fa-truck"></i> Data Supplier</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama_supplier">Nama Supplier *</label>
                            <input type="text" id="nama_supplier" name="nama_supplier" required
                                   value="<?php echo htmlspecialchars($supplier_data['nama_supplier'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="kontak_supplier">Kontak Supplier</label>
                            <input type="text" id="kontak_supplier" name="kontak_supplier"
                                   value="<?php echo htmlspecialchars($supplier_data['kontak'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label for="alamat_supplier">Alamat Supplier</label>
                        <textarea id="alamat_supplier" name="alamat_supplier"><?php echo htmlspecialchars($supplier_data['alamat'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Sales Form -->
                <div class="form-section">
                    <h3><i class="fas fa-user-tie"></i> Data Sales</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama_sales">Nama Sales *</label>
                            <input type="text" id="nama_sales" name="nama_sales" required
                                   value="<?php echo htmlspecialchars($sales_data['nama_sales'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="kontak_sales">Kontak Sales</label>
                            <input type="text" id="kontak_sales" name="kontak_sales"
                                   value="<?php echo htmlspecialchars($sales_data['kontak'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="jenis_kunjungan">Jenis Kunjungan *</label>
                            <div class="jenis-container">
                                <select id="jenis_kunjungan" name="jenis_kunjungan" required onchange="aktifkanInterval()">
                                    <option value="">-- Pilih Jenis --</option>
                                    <?php foreach ($jenisKunjungan as $j): ?>
                                    <option value="<?php echo $j['id']; ?>"
                                            <?php echo ($sales_data && $sales_data['jenis_kunjungan_id'] == $j['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($j['nama_jenis']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn-add-jenis" onclick="openModal()" title="Tambah Jenis Kunjungan">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="interval_kunjungan" id="intervalLabel">Interval Kunjungan *</label>
                            <input type="number" id="interval_kunjungan" name="interval_kunjungan" min="1"
                                   value="<?php echo htmlspecialchars($sales_data['interval_kunjungan'] ?? ''); ?>"
                                   <?php echo (!$sales_data || !$sales_data['jenis_kunjungan_id']) ? 'disabled' : ''; ?> required>
                        </div>
                    </div>
                </div>

                <button type="submit" name="simpan_data" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan & Lanjutkan ke Upload Excel
                </button>
            </form>
        </div>

        <!-- Step 2: Excel Upload -->
        <div class="step-content <?php echo $step == 2 ? 'active' : ''; ?>" id="step2">
            <?php if ($current_supplier_id > 0 && $current_sales_id > 0): ?>
            <div class="excel-section">
                <h3><i class="fas fa-file-excel"></i> Upload Daftar Barang</h3>
                
                <div class="excel-instructions">
                    <h4><i class="fas fa-info-circle"></i> Petunjuk Upload</h4>
                    <ol>
                        <li>Download template Excel dengan mengklik tombol <strong>"Download Template Excel"</strong></li>
                        <li>Isi template dengan data barang yang ingin diimport</li>
                        <li>Pastikan format sesuai dengan contoh di bawah ini</li>
                        <li>Upload file Excel yang telah diisi</li>
                    </ol>
                </div>

                <!-- Preview Contoh Format -->
                <div class="preview-table-container">
                    <h4 style="color: #21633E; margin-bottom: 15px;">
                        <i class="fas fa-eye"></i> Contoh Format Excel:
                    </h4>
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>nama_barang</th>
                                <th>harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Baju Anak Katun 100%</td>
                                <td>50000</td>
                            </tr>
                            <tr>
                                <td>Celana Jeans Premium</td>
                                <td>120000</td>
                            </tr>
                            <tr>
                                <td>Sepatu Sneakers</td>
                                <td>85000</td>
                            </tr>
                            <tr>
                                <td>Tas Ransel Anak</td>
                                <td>75000</td>
                            </tr>
                        </tbody>
                    </table>
                    <p style="color: #666; margin-top: 10px; font-size: 0.9em;">
                        <i class="fas fa-lightbulb"></i> <strong>Catatan:</strong> 
                        Kolom <strong>nama_barang</strong> dan <strong>harga</strong> wajib diisi. 
                        Harga harus berupa angka tanpa titik atau koma (contoh: 50000, bukan 50.000)
                    </p>
                </div>

                <div class="excel-buttons">
                    <a href="generate_template.php" class="btn-excel" target="_blank">
                        <i class="fas fa-download"></i> Download Template Excel
                    </a>
                    <button type="button" class="btn-excel" onclick="showExample()">
                        <i class="fas fa-question-circle"></i> Cara Pengisian
                    </button>
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="supplier_id" value="<?php echo $current_supplier_id; ?>">
                    <input type="hidden" name="sales_id" value="<?php echo $current_sales_id; ?>">

                    <h4><i class="fas fa-upload"></i> Upload File Excel</h4>
                    
                    <div class="file-input">
                        <input type="file" name="excel_file" accept=".csv,.xls,.xlsx" required>
                        <small style="color: #666; display: block; margin-top: 10px;">
                            Format yang didukung: CSV, XLS, atau XLSX. Maksimal ukuran file: 5MB
                        </small>
                    </div>

                    <button type="submit" name="upload_excel" class="btn-submit" style="background: linear-gradient(135deg, #D4AF37 0%, #B8860B 100%);">
                        <i class="fas fa-upload"></i> Upload & Proses Barang
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Step 3: Success Page -->
        <div class="step-content <?php echo $step == 3 ? 'active' : ''; ?>" id="step3">
            <?php if ($current_supplier_id > 0 && $current_sales_id > 0 && $success_count > 0): ?>
            <div class="success-section">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Import Berhasil!</h2>
                <p>Selamat! <strong><?php echo $success_count; ?> barang</strong> telah berhasil diimport ke sistem.</p>
                
                <div class="preview-card">
                    <h4><i class="fas fa-info-circle"></i> Preview Data Supplier & Sales</h4>
                    <div class="preview-info">
                        <div class="info-item">
                            <label>Nama Supplier:</label>
                            <div class="value"><?php echo htmlspecialchars($supplier_data['nama_supplier'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Kontak Supplier:</label>
                            <div class="value"><?php echo htmlspecialchars($supplier_data['kontak'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Nama Sales:</label>
                            <div class="value"><?php echo htmlspecialchars($sales_data['nama_sales'] ?? ''); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Kontak Sales:</label>
                            <div class="value"><?php echo htmlspecialchars($sales_data['kontak'] ?? ''); ?></div>
                        </div>
                        <div class="info-item full-width">
                            <label>Alamat Supplier:</label>
                            <div class="value"><?php echo htmlspecialchars($supplier_data['alamat'] ?? ''); ?></div>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="daftar_barang.php?supplier_id=<?php echo $current_supplier_id; ?>&sales_id=<?php echo $current_sales_id; ?>" 
                       class="btn-action btn-view">
                        <i class="fas fa-list"></i> Lihat Daftar Lengkap
                    </a>
                    <a href="import_barang.php?supplier_id=<?php echo $current_supplier_id; ?>&sales_id=<?php echo $current_sales_id; ?>&step=1&edit=1" 
                       class="btn-action btn-edit">
                        <i class="fas fa-edit"></i> Edit Data
                    </a>
                    <button onclick="closeWindow()" class="btn-action btn-close">
                        <i class="fas fa-times"></i> Tutup Browser
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal untuk tambah jenis kunjungan -->
    <div id="modalJenis" class="modal">
        <div class="modal-content">
            <h3>Tambah Jenis Kunjungan</h3>
            <input type="text" id="namaJenis" placeholder="Contoh: Mingguan" required>
            <div class="modal-buttons">
                <button class="btn-save" onclick="simpanJenis()">Simpan</button>
                <button class="btn-cancel" onclick="closeModal()">Batal</button>
            </div>
        </div>
    </div>

    <!-- Modal untuk contoh pengisian -->
    <div id="modalExample" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <h3><i class="fas fa-question-circle"></i> Cara Pengisian Excel</h3>
            
            <div style="margin-bottom: 20px;">
                <h4 style="color: #21633E; margin-bottom: 10px;">Format Kolom:</h4>
                <ul style="margin-left: 20px; margin-bottom: 15px;">
                    <li><strong>Kolom A (nama_barang)</strong>: Nama produk (wajib)</li>
                    <li><strong>Kolom B (harga)</strong>: Harga dalam angka (wajib)</li>
                </ul>
                
                <h4 style="color: #21633E; margin-bottom: 10px;">Contoh yang Benar:</h4>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <p><strong>✓ Contoh Benar:</strong></p>
                    <p>Baju Anak Katun 100% | 50000</p>
                    <p>Celana Jeans Premium | 120000</p>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <p><strong>✗ Contoh Salah:</strong></p>
                    <p>Baju Anak Katun 100% | Rp 50.000 (salah karena ada "Rp" dan titik)</p>
                    <p>Celana Jeans Premium | (salah karena harga kosong)</p>
                </div>
                
                <h4 style="color: #21633E; margin-bottom: 10px;">Tips:</h4>
                <ol style="margin-left: 20px;">
                    <li>Gunakan format .xlsx untuk hasil terbaik</li>
                    <li>Pastikan tidak ada baris kosong di antara data</li>
                    <li>Harga harus berupa angka bulat tanpa desimal</li>
                    <li>Maksimal 1000 baris data per file</li>
                </ol>
            </div>
            
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeExampleModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalJenis').style.display = 'flex';
            document.getElementById('namaJenis').focus();
        }

        function closeModal() {
            document.getElementById('modalJenis').style.display = 'none';
        }

        function showExample() {
            document.getElementById('modalExample').style.display = 'flex';
        }

        function closeExampleModal() {
            document.getElementById('modalExample').style.display = 'none';
        }

        function aktifkanInterval() {
            const select = document.getElementById('jenisSelect') || document.getElementById('jenis_kunjungan');
            const intervalInput = document.getElementById('interval_kunjungan');
            const intervalLabel = document.getElementById('intervalLabel');

            if (select.value) {
                intervalInput.disabled = false;
                const jenis = select.options[select.selectedIndex].text;
                intervalLabel.textContent = 'Berapa ' + jenis + '?';
            } else {
                intervalInput.disabled = true;
                intervalLabel.textContent = 'Interval Kunjungan';
            }
        }

        function simpanJenis() {
            const nama = document.getElementById('namaJenis').value.trim();
            if (!nama) {
                alert('Nama wajib diisi');
                return;
            }

            const btn = document.querySelector('.btn-save');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            fetch('inti/order/ajax_tambah_jenis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'nama_jenis=' + encodeURIComponent(nama)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('jenis_kunjungan');
                    const opt = document.createElement('option');
                    opt.value = data.id;
                    opt.text = data.nama;
                    opt.selected = true;
                    select.appendChild(opt);
                    aktifkanInterval();
                    closeModal();
                    document.getElementById('namaJenis').value = '';
                    alert(data.message || 'Jenis kunjungan berhasil ditambahkan');
                } else {
                    alert(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan. Silakan coba lagi.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Simpan';
            });
        }

        function closeWindow() {
            if (confirm('Apakah Anda yakin ingin menutup halaman ini?\n\nData telah tersimpan dengan aman.')) {
                window.close();
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modalJenis = document.getElementById('modalJenis');
            const modalExample = document.getElementById('modalExample');
            
            if (event.target == modalJenis) {
                closeModal();
            }
            if (event.target == modalExample) {
                closeExampleModal();
            }
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeExampleModal();
            }
        });

        // Initialize interval state on page load
        document.addEventListener('DOMContentLoaded', function() {
            aktifkanInterval();
            
            // Set progress bar based on current step
            const progressBar = document.querySelector('.progress-bar');
            const steps = document.querySelectorAll('.step');
            
            steps.forEach((step, index) => {
                if (step.classList.contains('active')) {
                    const progress = (index / (steps.length - 1)) * 100;
                    progressBar.style.width = progress + '%';
                }
            });
        });
    </script>
</body>
</html>