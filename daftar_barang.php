<?php
include 'config.php';

// Ambil parameter dari URL
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$sales_id = isset($_GET['sales_id']) ? (int)$_GET['sales_id'] : 0;

// Validasi parameter
if ($supplier_id <= 0 || $sales_id <= 0) {
    die("Parameter tidak valid. Pastikan Anda mengakses halaman ini dari proses import barang.");
}

// Ambil data supplier
$supplier_data = null;
$stmt = $conn->prepare("SELECT * FROM supplier WHERE id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$supplier_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil data sales
$sales_data = null;
$stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$sales_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil data order terakhir untuk sales ini
$order_data = null;
$stmt = $conn->prepare("
    SELECT o.* 
    FROM orders o 
    WHERE o.sales_id = ? 
    ORDER BY o.tanggal_order DESC 
    LIMIT 1
");
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$order_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil daftar barang
$daftar_barang = [];
if ($order_data) {
    $order_id = $order_data['id'];
    $stmt = $conn->prepare("
        SELECT * FROM daftar_barang 
        WHERE order_id = ? AND supplier_id = ? AND sales_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("iii", $order_id, $supplier_id, $sales_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $daftar_barang[] = $row;
    }
    $stmt->close();
}

// Hitung total barang dan total nilai
$total_barang = count($daftar_barang);
$total_nilai = 0;
foreach ($daftar_barang as $barang) {
    $total_nilai += $barang['subtotal'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang - ALBAROKAH-DEMAK</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e5e9;
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

        /* Info Card */
        .info-card {
            background: linear-gradient(135deg, #21633E 0%, #2e8b57 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(33, 99, 62, 0.2);
        }

        .info-card h2 {
            font-size: 1.8em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #D4AF37;
        }

        .info-item h3 {
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .info-item p {
            font-size: 1.1em;
            font-weight: 600;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border-top: 4px solid #21633E;
            transition: transform 0.3s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .summary-card i {
            font-size: 2em;
            color: #21633E;
            margin-bottom: 10px;
        }

        .summary-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .summary-card .value {
            font-size: 2em;
            font-weight: 700;
            color: #21633E;
        }

        /* Table Section */
        .table-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h3 {
            color: #21633E;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
        }

        .btn-print {
            background: #D4AF37;
            color: white;
        }

        .btn-print:hover {
            background: #B8860B;
        }

        .btn-export {
            background: #28a745;
            color: white;
        }

        .btn-export:hover {
            background: #218838;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 800px;
        }

        .data-table th {
            background: #21633E;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e1e5e9;
            color: #333;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table .text-right {
            text-align: right;
        }

        .data-table .text-center {
            text-align: center;
        }

        /* Footer Actions */
        .footer-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e1e5e9;
        }

        .btn-primary {
            background: linear-gradient(135deg, #21633E 0%, #437057 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 99, 62, 0.2);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #D4AF37;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
        }

        .btn-warning:hover {
            background: #B8860B;
            transform: translateY(-2px);
        }

        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-data i {
            font-size: 3em;
            color: #e1e5e9;
            margin-bottom: 20px;
        }

        .no-data h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        /* Print Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            
            .container, .container * {
                visibility: visible;
            }
            
            .container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
                background: white;
            }
            
            .btn-action, .footer-actions {
                display: none !important;
            }
            
            .data-table {
                border: 1px solid #000;
            }
            
            .data-table th {
                background: #ccc !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .header h1 {
                font-size: 2em;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .table-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .btn-action {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }

            .footer-actions {
                flex-direction: column;
            }

            .btn-primary, .btn-secondary, .btn-warning {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .info-card h2 {
                font-size: 1.5em;
            }

            .summary-card .value {
                font-size: 1.5em;
            }

            .data-table {
                font-size: 0.9em;
            }

            .data-table th, .data-table td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-boxes"></i> Daftar Barang Import</h1>
            <p>Detail barang yang telah diimport ke sistem ALBAROKAH-DEMAK</p>
        </div>

        <!-- Info Card -->
        <div class="info-card">
            <h2><i class="fas fa-info-circle"></i> Informasi Supplier & Sales</h2>
            <div class="info-grid">
                <div class="info-item">
                    <h3>Nama Supplier</h3>
                    <p><?php echo htmlspecialchars($supplier_data['nama_supplier'] ?? '-'); ?></p>
                </div>
                <div class="info-item">
                    <h3>Kontak Supplier</h3>
                    <p><?php echo htmlspecialchars($supplier_data['kontak'] ?? '-'); ?></p>
                </div>
                <div class="info-item">
                    <h3>Nama Sales</h3>
                    <p><?php echo htmlspecialchars($sales_data['nama_sales'] ?? '-'); ?></p>
                </div>
                <div class="info-item">
                    <h3>Kontak Sales</h3>
                    <p><?php echo htmlspecialchars($sales_data['kontak'] ?? '-'); ?></p>
                </div>
                <div class="info-item">
                    <h3>Alamat Supplier</h3>
                    <p><?php echo htmlspecialchars($supplier_data['alamat'] ?? '-'); ?></p>
                </div>
                <div class="info-item">
                    <h3>Jenis Kunjungan</h3>
                    <p><?php echo htmlspecialchars($sales_data['kunjungan'] ?? '-'); ?></p>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <i class="fas fa-box"></i>
                <h3>Total Barang</h3>
                <div class="value"><?php echo number_format($total_barang); ?></div>
            </div>
            <div class="summary-card">
                <i class="fas fa-money-bill-wave"></i>
                <h3>Total Nilai</h3>
                <div class="value">Rp <?php echo number_format($total_nilai); ?></div>
            </div>
            <div class="summary-card">
                <i class="fas fa-calendar-alt"></i>
                <h3>Tanggal Import</h3>
                <div class="value">
                    <?php 
                    if ($order_data && $order_data['tanggal_order']) {
                        echo date('d/m/Y', strtotime($order_data['tanggal_order']));
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
            </div>
            <div class="summary-card">
                <i class="fas fa-clipboard-check"></i>
                <h3>Status Order</h3>
                <div class="value">
                    <?php 
                    if ($order_data) {
                        $status = $order_data['status'] ?? 'baru';
                        $status_labels = [
                            'baru' => 'Baru',
                            'diproses' => 'Diproses',
                            'selesai' => 'Selesai',
                            'sudah_dibayar' => 'Lunas'
                        ];
                        echo $status_labels[$status] ?? ucfirst($status);
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Daftar Barang</h3>
                <div class="table-actions">
                    <button onclick="printPage()" class="btn-action btn-print">
                        <i class="fas fa-print"></i> Cetak
                    </button>
                    <button onclick="exportToExcel()" class="btn-action btn-export">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <a href="import_barang.php?supplier_id=<?php echo $supplier_id; ?>&sales_id=<?php echo $sales_id; ?>&step=1&edit=1" 
                       class="btn-action btn-back">
                        <i class="fas fa-edit"></i> Edit Data
                    </a>
                </div>
            </div>

            <?php if ($total_barang > 0): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Nama Barang</th>
                            <th width="120" class="text-right">Harga Ambil (Rp)</th>
                            <th width="100" class="text-center">Qty</th>
                            <th width="150" class="text-right">Subtotal (Rp)</th>
                            <th width="150">Tanggal Input</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($daftar_barang as $barang): 
                            $harga_ambil = isset($barang['harga_ambil']) ? (int)$barang['harga_ambil'] : 0;
                            $qty = isset($barang['qty']) ? (int)$barang['qty'] : 1;
                            $subtotal = isset($barang['subtotal']) ? (int)$barang['subtotal'] : 0;
                            $created_at = isset($barang['created_at']) ? $barang['created_at'] : '';
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($barang['nama_barang'] ?? ''); ?></td>
                            <td class="text-right"><?php echo number_format($harga_ambil); ?></td>
                            <td class="text-center"><?php echo number_format($qty); ?></td>
                            <td class="text-right"><?php echo number_format($subtotal); ?></td>
                            <td>
                                <?php 
                                if ($created_at) {
                                    echo date('d/m/Y H:i', strtotime($created_at));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <!-- Total Row -->
                        <tr style="background: #f8f9fa; font-weight: 600;">
                            <td colspan="4" class="text-right">TOTAL</td>
                            <td class="text-right">Rp <?php echo number_format($total_nilai); ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <h3>Belum Ada Data Barang</h3>
                <p>Data barang belum tersedia. Silakan import barang terlebih dahulu.</p>
                <a href="import_barang.php" class="btn-primary" style="margin-top: 20px; display: inline-flex;">
                    <i class="fas fa-upload"></i> Import Barang
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer Actions -->
        <div class="footer-actions">
            <a href="import_barang.php" class="btn-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <?php if ($total_barang > 0): ?>
            <a href="javascript:void(0)" onclick="shareReport()" class="btn-warning">
                <i class="fas fa-share-alt"></i> Bagikan
            </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fungsi untuk mencetak halaman
        function printPage() {
            window.print();
        }

        // Fungsi untuk export ke Excel
        function exportToExcel() {
            // Buat data untuk export
            let table = document.querySelector('.data-table');
            if (!table) {
                alert('Tidak ada data untuk diexport');
                return;
            }

            // Buat string CSV
            let csv = [];
            let rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Hapus titik dari angka untuk format Excel yang benar
                    let text = cols[j].innerText.replace(/\./g, '');
                    row.push('"' + text + '"');
                }
                
                csv.push(row.join(','));
            }

            // Download file
            let csvString = csv.join('\n');
            let filename = `daftar_barang_<?php echo htmlspecialchars($supplier_data['nama_supplier'] ?? 'supplier'); ?>_<?php echo date('Y-m-d'); ?>.csv`;
            
            let blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            let link = document.createElement('a');
            let url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Fungsi untuk berbagi laporan
        function shareReport() {
            let supplierName = "<?php echo addslashes($supplier_data['nama_supplier'] ?? ''); ?>";
            let salesName = "<?php echo addslashes($sales_data['nama_sales'] ?? ''); ?>";
            let totalItems = <?php echo $total_barang; ?>;
            let totalValue = <?php echo $total_nilai; ?>;
            let date = "<?php echo date('d/m/Y'); ?>";
            
            let message = `📊 LAPORAN BARANG IMPORT\n\n` +
                         `Supplier: ${supplierName}\n` +
                         `Sales: ${salesName}\n` +
                         `Total Barang: ${totalItems.toLocaleString('id-ID')}\n` +
                         `Total Nilai: Rp ${totalValue.toLocaleString('id-ID')}\n` +
                         `Tanggal: ${date}\n\n` +
                         `Sistem ALBAROKAH-DEMAK`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Laporan Barang Import',
                    text: message,
                    url: window.location.href
                })
                .then(() => console.log('Berhasil berbagi'))
                .catch((error) => console.log('Error sharing:', error));
            } else {
                // Fallback untuk browser yang tidak support Web Share API
                navigator.clipboard.writeText(message).then(() => {
                    alert('Laporan telah disalin ke clipboard! Anda bisa menempelkannya di aplikasi chat atau email.');
                });
            }
        }

        // Fungsi untuk filter dan pencarian (bisa dikembangkan)
        function setupSearch() {
            // Implementasi pencarian sederhana
            let searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Cari nama barang...';
            searchInput.style.padding = '10px';
            searchInput.style.border = '2px solid #e1e5e9';
            searchInput.style.borderRadius = '8px';
            searchInput.style.width = '300px';
            searchInput.style.marginBottom = '15px';
            
            // Tambahkan ke header table
            let tableHeader = document.querySelector('.table-header');
            if (tableHeader) {
                tableHeader.appendChild(searchInput);
                
                searchInput.addEventListener('keyup', function() {
                    let filter = searchInput.value.toLowerCase();
                    let rows = document.querySelectorAll('.data-table tbody tr');
                    
                    rows.forEach(row => {
                        let text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }
        }

        // Jalankan setup saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            setupSearch();
            
            // Tambahkan efek loading jika ada banyak data
            if (<?php echo $total_barang; ?> > 50) {
                console.log('Data besar terdeteksi, optimasi tampilan...');
            }
        });
    </script>
</body>
</html>