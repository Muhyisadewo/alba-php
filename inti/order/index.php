<?php
include __DIR__ . '/../../config.php';

// Pastikan Anda telah menambahkan meta tag viewport di bagian <head> untuk responsivitas!

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query ke tabel supplier
$sql = "SELECT id, nama_supplier FROM supplier";
if (!empty($search)) {
    // Penggunaan real_escape_string sudah benar untuk pencegahan SQL Injection dasar
    $sql .= " WHERE nama_supplier LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$sql .= " ORDER BY nama_supplier ASC";

$result = $conn->query($sql);

// ================== HANDLE DELETE SUPPLIER ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);

    $stmt = $conn->prepare("DELETE FROM supplier WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<script>alert('Gagal menghapus supplier');</script>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Daftar Supplier - ALBA Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* Reset dasar untuk konsistensi */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background:#437057;
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="luxury" patternUnits="userSpaceOnUse" width="20" height="20"><circle cx="10" cy="10" r="1" fill="%23437057" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23luxury)"/></svg>');
            z-index: -1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(67, 112, 87, 0.3);
        }

        h2 {
            font-family: Arial, sans-serif;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 30px;
            color: #437057;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            letter-spacing: 2px;
        }

        .search-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .search-container form {
            display: flex;
            width: 100%;
            max-width: 500px;
            background: #fff;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border: 2px solid #437057;
        }

        .search-container input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            outline: none;
            font-size: 1rem;
            background: transparent;
            color: #2c3e50;
        }

        .search-container button {
            padding: 15px 25px;
            background: linear-gradient(45deg, #437057, #5a8f6b);
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .search-container button:hover {
            background: linear-gradient(45deg, #5a8f6b, #437057);
            transform: scale(1.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        thead {
            background: linear-gradient(45deg, #437057, #5a8f6b);
            color: #fff;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            font-weight: 700;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: rgba(67, 112, 87, 0.1);
            transform: translateY(-2px);
        }

        .supplier-link {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .supplier-link:hover {
            color: #437057;
        }

        .delete-icon {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .delete-icon:hover {
            background: linear-gradient(45deg, #c0392b, #a93226);
            transform: scale(1.1);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.3);
        }

        .no-data {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
            padding: 20px;
        }

        .add-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #437057, #5a8f6b);
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 2rem;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .add-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        /* Container untuk card supplier (untuk mobile) */
        .suppliers-container {
            display: none;
        }

        /* Card supplier untuk mobile */
        .supplier-card {
            background: #fff;
            border-radius: 15px;
            margin-bottom: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(67, 112, 87, 0.3);
        }

        .supplier-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .card-header-link {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.3s ease;
        }

        .card-header-link:hover {
            color: #437057;
        }

        .supplier-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .supplier-card .card-number {
            font-weight: 700;
            color: #437057;
            font-size: 1.2rem;
        }

        .supplier-card .supplier-name {
            font-size: 1.1rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .supplier-card .actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }

        /* Responsivitas: Prioritas layar kecil (mobile-first) */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 20px;
                border-radius: 15px;
            }

            h2 {
                font-size: 2rem;
                margin-bottom: 20px;
            }

            .search-container form {
                max-width: 100%;
            }

            .search-container input, .search-container button {
                padding: 12px 15px;
                font-size: 0.9rem;
            }

            /* Sembunyikan tabel, tampilkan card supplier */
            table {
                display: none;
            }

            .suppliers-container {
                display: block;
            }

            .add-btn {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
        }

        /* Untuk layar sedang (tablet) */
        @media (min-width: 769px) and (max-width: 1024px) {
            .container {
                padding: 25px;
            }

            h2 {
                font-size: 2.2rem;
            }

            th, td {
                padding: 12px;
            }

            .add-btn {
                width: 55px;
                height: 55px;
                font-size: 1.8rem;
            }
        }

        /* Untuk layar besar (desktop) */
        @media (min-width: 1025px) {
            .container {
                padding: 40px;
            }

            h2 {
                font-size: 3rem;
            }

            th, td {
                padding: 18px;
            }

            .add-btn {
                width: 70px;
                height: 70px;
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Daftar Supplier</h2>

        <div class="search-container">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Cari supplier..." value="<?= htmlspecialchars($search); ?>">
                <button type="submit">Cari</button>
            </form>
        </div>

        <!-- Tabel untuk desktop/tablet -->
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Supplier</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                $no = 1;
                while($row = $result->fetch_assoc()) { 
                    $nama_supplier = htmlspecialchars($row['nama_supplier']);
                    $url_detail = 'index.php?path=supplier_detail.php&nama=' . urlencode($nama_supplier);
                    ?>
                    <tr>
                        <td data-label="No"><?= $no++; ?></td>
                        
                        <td data-label="Nama Supplier">
                            <a href="<?= $url_detail; ?>" class="supplier-link">
                                <?= $nama_supplier; ?>
                            </a>
                        </td>
                        
                        <td data-label="Aksi">
                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus supplier ini?');">
                                <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                                <button type="submit" class="delete-icon" title="Hapus Supplier">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
            <?php }
            } else {
                echo "<tr><td colspan='3' class='no-data'>Belum ada supplier.</td></tr>";
            }
            ?>
            </tbody>
        </table>

        <!-- Container card supplier untuk mobile -->
        <div class="suppliers-container">
            <?php
            if ($result && $result->num_rows > 0) {
                $no = 1;
                mysqli_data_seek($result, 0); // Reset pointer result
                while($row = $result->fetch_assoc()) {
                    $nama_supplier = htmlspecialchars($row['nama_supplier']);
                    $url_detail = '../../index.php?path=supplier_detail/' . urlencode($nama_supplier);
                    ?>
                    <div class="supplier-card">
                        <a href="<?= $url_detail; ?>" class="card-header-link">
                            <div class="card-header">
                                <span class="card-number">#<?= $no++; ?></span>
                                <span class="supplier-name"><?= $nama_supplier; ?></span>
                            </div>
                        </a>
                        <div class="actions">
                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus supplier ini?');">
                                <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                                <button type="submit" class="delete-icon" title="Hapus Supplier">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
            <?php }
            } else {
                echo "<div class='supplier-card no-data'>Belum ada supplier.</div>";
            }
            ?>
        </div>
    </div>

    <button class="add-btn" onclick="window.location.href='?path=tambah_supplier.php'">+</button>

</body>
</html>

<?php
$conn->close();
?>