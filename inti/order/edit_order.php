<?php
include __DIR__ . '/../../config.php';

$message = '';

if (!isset($_GET['id'])) {
    die("ID order tidak ditemukan.");
}

$id = $_GET['id'];

// Ambil data order dan sales terkait
$sql = "
SELECT o.*, s.nama_sales, s.nama_supplier, s.no_sales, s.kunjungan 
FROM orders o
JOIN sales s ON o.sales_id = s.id
WHERE o.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Order tidak ditemukan.");
}

$row = $result->fetch_assoc();
$sales_id = $row['sales_id']; // Ambil id sales
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Data untuk tabel sales
    $nama_sales = $_POST['nama_sales'];
    $nama_supplier = $_POST['nama_supplier'];
    $no_sales = $_POST['no_sales'];
    $kunjungan = $_POST['kunjungan'];

    // Data untuk tabel orders
    $nama_barang = $_POST['nama_barang'];
    $riwayat_order = $_POST['riwayat_order'];

    // 1. Update DATA SALES
    $sqlSales = "UPDATE sales SET nama_sales=?, nama_supplier=?, no_sales=?, kunjungan=? WHERE id=?";
    $stmtSales = $conn->prepare($sqlSales);
    $stmtSales->bind_param("ssssi", $nama_sales, $nama_supplier, $no_sales, $kunjungan, $sales_id);
    
    if (!$stmtSales->execute()) {
        die("Gagal update data sales: " . $stmtSales->error);
    }
    $stmtSales->close();

    // 2. Update DATA ORDERS
    $sqlOrder = "UPDATE orders SET nama_barang=?, riwayat_order=? WHERE id=?";
    $stmtOrder = $conn->prepare($sqlOrder);
    $stmtOrder->bind_param("ssi", $nama_barang, $riwayat_order, $id);

    if ($stmtOrder->execute()) {
        $message = "Order berhasil diperbarui!";
        
        // Refresh data setelah update
        $sqlReload = "
            SELECT o.*, s.nama_sales, s.nama_supplier, s.no_sales, s.kunjungan 
            FROM orders o
            JOIN sales s ON o.sales_id = s.id
            WHERE o.id = ?
        ";
        $stmtReload = $conn->prepare($sqlReload);
        $stmtReload->bind_param("i", $id);
        $stmtReload->execute();
        $result = $stmtReload->get_result();
        $row = $result->fetch_assoc();
        $stmtReload->close();
    } else {
        $message = "Error update order: " . $stmtOrder->error;
    }

    $stmtOrder->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Order - ALBA</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: #437057;
            color: #333;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #fff;
            font-size: 2.5em;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(67, 112, 87, 0.3);
        }
        .form-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #437057;
            font-weight: 600;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #437057;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        input[type="text"]:focus, textarea:focus {
            border-color: #2d5a3d;
            box-shadow: 0 0 10px rgba(67, 112, 87, 0.3);
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        .btn-container {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            background: linear-gradient(45deg, #437057, #5a8f6a);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 112, 87, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(45deg, #666, #888);
        }
        .btn-secondary:hover {
            box-shadow: 0 8px 25px rgba(102, 102, 102, 0.4);
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
        }
        .success {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }
        .error {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
            border: 1px solid #F44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Order</h2>

        <div class="form-container">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'berhasil') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nama_sales">Nama Sales:</label>
                    <input type="text" id="nama_sales" name="nama_sales" value="<?= htmlspecialchars($row['nama_sales']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="nama_supplier">Nama Supplier:</label>
                    <input type="text" id="nama_supplier" name="nama_supplier" value="<?= htmlspecialchars($row['nama_supplier']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="nama_barang">Nama Barang:</label>
                    <input type="text" id="nama_barang" name="nama_barang" value="<?= htmlspecialchars($row['nama_barang']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="no_sales">No Sales:</label>
                    <input type="text" id="no_sales" name="no_sales" value="<?= htmlspecialchars($row['no_sales']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="kunjungan">Kunjungan:</label>
                    <textarea id="kunjungan" name="kunjungan"><?= htmlspecialchars($row['kunjungan']); ?></textarea>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn">Update</button>
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Kembali</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
