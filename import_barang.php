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
            echo "<script>alert('Supplier dan Sales berhasil ditambahkan!'); window.location.href='import_barang.php?supplier_id=$supplier_id&sales_id=$sales_id';</script>";
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

    // Buat order jika belum ada
    $stmt = $conn->prepare("SELECT id FROM orders WHERE sales_id=? ORDER BY tanggal_order DESC LIMIT 1");
    $stmt->bind_param("i", $sales_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $stmt = $conn->prepare("INSERT INTO orders (sales_id, tanggal_order, total_harga) VALUES (?, NOW(), 0)");
        $stmt->bind_param("i", $sales_id);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();
    } else {
        $order_id = (int)$order['id'];
    }

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

        // Insert barang (qty default 1, subtotal = harga)
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

    echo "<script>alert('Upload selesai! Berhasil: $success_count, Gagal: $error_count');</script>";
}

// Ambil data supplier dan sales jika ada di URL
$current_supplier_id = $_GET['supplier_id'] ?? 0;
$current_sales_id = $_GET['sales_id'] ?? 0;

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
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
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

        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #21633E;
        }

        .form-section h3 {
            color: #21633E;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
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
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        input, select, textarea {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
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
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2em;
            transition: background 0.3s;
        }

        .btn-add-jenis:hover {
            background: #1a4d2e;
        }

        .btn-submit {
            background: linear-gradient(135deg, #21633E 0%, #437057 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        .excel-section {
            background: linear-gradient(135deg, #D4AF37 0%, #B8860B 100%);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: center;
            color: white;
        }

        .excel-section h3 {
            margin-bottom: 15px;
            font-size: 1.4em;
        }

        .excel-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-excel {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-excel:hover {
            background: white;
            color: #D4AF37;
        }

        .btn-excel i {
            font-size: 1.2em;
        }

        .upload-form {
            margin-top: 20px;
        }

        .file-input {
            margin-bottom: 15px;
        }

        .file-input input[type="file"] {
            display: block;
            margin: 0 auto;
            padding: 10px;
            border: 2px dashed #D4AF37;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .current-data {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .current-data h4 {
            color: #2e7d32;
            margin-bottom: 10px;
        }

        .current-data p {
            margin: 5px 0;
            color: #333;
        }

        .back-btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
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
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
        }

        .modal-content h3 {
            margin-bottom: 15px;
            color: #21633E;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .excel-buttons {
                flex-direction: column;
                align-items: center;
            }

            .container {
                padding: 20px;
            }

            .header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-upload"></i> Import Barang</h1>
            <p>Masukkan data supplier dan sales, lalu upload daftar barang via Excel</p>
        </div>

        <?php if ($supplier_data && $sales_data): ?>
        <div class="current-data">
            <h4><i class="fas fa-check-circle"></i> Data Supplier & Sales</h4>
            <p><strong>Supplier:</strong> <?php echo htmlspecialchars($supplier_data['nama_supplier']); ?> (<?php echo htmlspecialchars($supplier_data['kontak']); ?>)</p>
            <p><strong>Sales:</strong> <?php echo htmlspecialchars($sales_data['nama_sales']); ?> (<?php echo htmlspecialchars($sales_data['kontak']); ?>)</p>
        </div>
        <?php endif; ?>

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
                <i class="fas fa-save"></i> Simpan Data Supplier & Sales
            </button>
        </form>

        <?php if ($current_supplier_id > 0 && $current_sales_id > 0): ?>
        <!-- Excel Section -->
        <div class="excel-section">
            <h3><i class="fas fa-file-excel"></i> Upload Daftar Barang</h3>
            <p>Download template Excel, isi kolom nama_barang dan harga, lalu upload kembali</p>

            <div class="excel-buttons">
                <a href="generate_template.php" class="btn-excel" target="_blank">
                    <i class="fas fa-download"></i> Download Template Excel
                </a>
            </div>

            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="supplier_id" value="<?php echo $current_supplier_id; ?>">
                <input type="hidden" name="sales_id" value="<?php echo $current_sales_id; ?>">

                <div class="file-input">
                    <input type="file" name="excel_file" accept=".csv,.xls,.xlsx" required>
                    <small style="color: rgba(255,255,255,0.8); display: block; margin-top: 5px;">
                        Format: CSV, XLS, atau XLSX. Kolom: nama_barang, harga
                    </small>
                </div>

                <button type="submit" name="upload_excel" class="btn-excel">
                    <i class="fas fa-upload"></i> Upload & Simpan Barang
                </button>
            </form>
        </div>
        <?php endif; ?>
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

    <script>
        function openModal() {
            document.getElementById('modalJenis').style.display = 'flex';
            document.getElementById('namaJenis').focus();
            document.addEventListener('keydown', handleModalKeydown);
        }

        function closeModal() {
            document.getElementById('modalJenis').style.display = 'none';
            document.removeEventListener('keydown', handleModalKeydown);
        }

        function handleModalKeydown(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
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

        // Initialize interval state on page load
        document.addEventListener('DOMContentLoaded', function() {
            aktifkanInterval();
        });
    </script>
</body>
</html>
