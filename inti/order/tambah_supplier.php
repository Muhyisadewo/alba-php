<?php
include __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_supplier = $conn->real_escape_string($_POST['nama_supplier']);
    $kontak        = $conn->real_escape_string($_POST['kontak']);
    $alamat        = $conn->real_escape_string($_POST['alamat']);

    $sqlSupplier = "INSERT INTO supplier (nama_supplier, kontak, alamat, created_at) 
                    VALUES ('$nama_supplier', '$kontak', '$alamat', NOW())";

    if ($conn->query($sqlSupplier)) {
        echo "<script>alert('Supplier berhasil ditambahkan!');window.location.href='inti/order/index.php';';</script>";
    } else {
        echo "Gagal menambahkan supplier: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Supplier</title>
    <style>
        body {
            background-color: #437057;
            min-height: 100vh;
            display: flex;
            align-items: center;
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
            max-width: 28rem;
            overflow: hidden;
        }
        @media (min-width: 768px) {
            .container {
                max-width: 32rem;
            }
        }
        @media (min-width: 1024px) {
            .container {
                max-width: 36rem;
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
            color: #1f2937;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        @media (min-width: 768px) {
            h2 {
                font-size: 1.875rem;
            }
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        input, textarea {
            width: 90%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus, textarea:focus {
            border-color: #437057;
            box-shadow: 0 0 0 3px rgba(67, 112, 87, 0.1);
        }
        textarea {
            resize: none;
        }
        button[type="submit"] {
            width: 100%;
            background-color: #437057;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button[type="submit"]:hover {
            background-color: #365a46;
        }
        button[type="submit"]:focus {
            outline: 2px solid #437057;
            outline-offset: 2px;
        }
        .btn-back {
            display: inline-block;
            background-color: #6b7280;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: background-color 0.2s;
            margin-top: 1.5rem;
            text-align: center;
        }
        .btn-back:hover {
            background-color: #4b5563;
        }
        .btn-back:focus {
            outline: 2px solid #6b7280;
            outline-offset: 2px;
        }
        .btn-back-container {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Tambah Supplier Baru</h2>
        <form method="POST">
            <div class="form-group">
                <label for="nama_supplier">Nama Supplier</label>
                <input type="text" id="nama_supplier" name="nama_supplier" required>
            </div>

            <div class="form-group">
                <label for="kontak">Kontak</label>
                <input type="text" id="kontak" name="kontak" required>
            </div>

            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea id="alamat" name="alamat" rows="3" required></textarea>
            </div>

            <button type="submit">SIMPAN SUPPLIER</button>
        </form>

        <div class="btn-back-container">
            <a href="index.php" class="btn-back">Kembali</a>
        </div>
    </div>
</body>
</html>
