<?php
include __DIR__ . '/../../config.php';

if (!isset($_GET['sales_id']) || !isset($_GET['order_id'])) {
    die("Sales ID atau Order ID tidak ditemukan");
}

$sales_id = $_GET['sales_id'];
$order_id = $_GET['order_id'];

// Ambil data order untuk konfirmasi
$order_query = "SELECT o.*, s.nama_sales, s.perusahaan FROM orders o JOIN sales s ON o.sales_id = s.id WHERE o.id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows == 0) {
    die("Order tidak ditemukan");
}

$order = $order_result->fetch_assoc();
$order_stmt->close();

// Ambil detail order
$detail_query = "SELECT rod.* FROM riwayat_order_detail rod 
                 JOIN riwayat_order ro ON rod.riwayat_order_id = ro.id 
                 WHERE ro.order_id = ?";
$detail_stmt = $conn->prepare($detail_query);
$detail_stmt->bind_param("i", $order_id);
$detail_stmt->execute();
$detail_result = $detail_stmt->get_result();
$order_details = [];
while ($row = $detail_result->fetch_assoc()) {
    $order_details[] = $row;
}
$detail_stmt->close();

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_pembayaran = $_POST['tanggal_pembayaran'];
    $jumlah_bayar = str_replace(['Rp', '.', ','], '', $_POST['jumlah_bayar']);
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $catatan = $_POST['catatan'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert ke tabel riwayat_pembayaran
        $sql = "INSERT INTO riwayat_pembayaran (order_id, tanggal_pembayaran, jumlah_pembayaran, metode_pembayaran, catatan, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdss", $order_id, $tanggal_pembayaran, $jumlah_bayar, $metode_pembayaran, $catatan);
        $stmt->execute();
        $stmt->close();

        // Update status order menjadi sudah dibayar
        $update_sql = "UPDATE orders SET status = 'sudah_dibayar' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $order_id);
        $update_stmt->execute();
        $update_stmt->close();

        $conn->commit();

        header("Location:?path=monitor_sales&sales_id=$sales_id&msg=pembayaran_sukses");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal mencatat pembayaran: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pembayaran - ALBA</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #437057;
            --primary-dark: #2d4d3b;
            --gold-accent: #c5a059;
            --text-dark: #2c3e50;
            --bg-light: #f4f7f6;
            --white: #ffffff;
            --shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --radius: 20px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-light);
            background-image: radial-gradient(circle at top right, #e8edea, transparent);
            color: var(--text-dark);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .card {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .order-info {
            background: rgba(67, 112, 87, 0.05);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(67, 112, 87, 0.1);
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 112, 87, 0.2);
        }

        .btn-back {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 700;
            margin-top: 20px;
            transition: color 0.3s ease;
        }

        .btn-back:hover {
            color: var(--gold-accent);
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }

        .btn-detail {
            background: linear-gradient(135deg, var(--gold-accent), #d4af37);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-detail:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(197, 160, 89, 0.3);
        }

        .order-details {
            background: rgba(67, 112, 87, 0.02);
            border: 1px solid rgba(67, 112, 87, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .order-details.show {
            display: block;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(67, 112, 87, 0.1);
        }

        .detail-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Proses Pembayaran</h2>

        <div class="card">
            <div class="order-info">
                <h3>Detail Order</h3>
                <p><strong>Kode Order:</strong> ORD-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Sales:</strong> <?php echo htmlspecialchars($order['nama_sales']); ?> (<?php echo htmlspecialchars($order['perusahaan']); ?>)</p>
                <p><strong>Tanggal Order:</strong> <?php echo date('d/m/Y', strtotime($order['tanggal_order'])); ?></p>
                <p><strong>Total Tagihan:</strong> Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></p>
                <button type="button" class="btn-detail" onclick="toggleOrderDetails()">Lihat Detail Order</button>
            </div>

            <div class="order-details" id="orderDetails">
                <h4>Detail Barang Order</h4>
                <?php if (!empty($order_details)): ?>
                    <?php foreach ($order_details as $detail): ?>
                        <div class="detail-item">
                            <div>
                                <strong><?php echo htmlspecialchars($detail['nama_barang']); ?></strong><br>
                                <small>Harga: Rp <?php echo number_format($detail['harga'], 0, ',', '.'); ?> | Qty: <?php echo $detail['qty']; ?> | Satuan: <?php echo htmlspecialchars($detail['satuan'] ?? 'pcs'); ?></small>
                            </div>
                            <div>
                                <strong>Rp <?php echo number_format($detail['subtotal'], 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Tidak ada detail order yang tersedia.</p>
                <?php endif; ?>
            </div>

            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="tanggal_pembayaran">Tanggal Pembayaran</label>
                    <input type="date" id="tanggal_pembayaran" name="tanggal_pembayaran" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="jumlah_bayar">Jumlah Bayar</label>
                    <input type="text" id="jumlah_bayar" name="jumlah_bayar" placeholder="Rp 0" value="Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="metode_pembayaran">Metode Pembayaran</label>
                    <select id="metode_pembayaran" name="metode_pembayaran" required>
                        <option value="">Pilih Metode</option>
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer">Transfer Bank</option>
                        <option value="Cek">Cek</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="catatan">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="3" placeholder="Catatan pembayaran (opsional)"></textarea>
                </div>

                <button type="submit" class="btn-submit">Simpan Pembayaran</button>
            </form>

            <a href="?path=monitor_sales&sales_id=<?php echo $sales_id; ?>" class="btn-back">← Kembali ke Monitor Sales</a>
        </div>
    </div>

    <script>
        // Format input jumlah bayar
        document.getElementById('jumlah_bayar').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                e.target.value = 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
            } else {
                e.target.value = '';
            }
        });

        // Toggle order details visibility
        function toggleOrderDetails() {
            const detailsDiv = document.getElementById('orderDetails');
            const button = document.querySelector('.btn-detail');

            if (detailsDiv.classList.contains('show')) {
                detailsDiv.classList.remove('show');
                button.textContent = 'Lihat Detail Order';
            } else {
                detailsDiv.classList.add('show');
                button.textContent = 'Sembunyikan Detail Order';
            }
        }
    </script>
</body>
</html>
