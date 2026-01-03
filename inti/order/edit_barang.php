<?php
require_once __DIR__ . '/helpers/image_helper.php';
require_once __DIR__ . '/../../config.php';

/* ===============================
   1. VALIDASI ID BARANG
================================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Barang tidak ditemukan.");
}
$id = (int) $_GET['id'];

/* ===============================
   2. AMBIL DATA BARANG
================================ */
$stmt = $conn->prepare("SELECT * FROM daftar_barang WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data barang tidak ditemukan.");
}

/* ===============================
   3. AMBIL SALES ID
================================ */
$sales_id = (int) ($data['sales_id'] ?? 0);
if ($sales_id <= 0 && !empty($data['order_id'])) {
    $q = $conn->prepare("SELECT sales_id FROM orders WHERE id = ?");
    $q->bind_param("i", $data['order_id']);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $q->close();
    $sales_id = (int) ($r['sales_id'] ?? 0);
}
if ($sales_id <= 0) {
    die("Sales ID tidak valid.");
}

/* ===============================
   4. PROSES UPDATE
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_barang = trim($_POST['nama_barang']);
    $harga_ambil = (int) $_POST['harga_ambil'];
    $qty         = (int) $_POST['qty'];
    $subtotal    = $harga_ambil * $qty;

    if ($nama_barang === '' || $harga_ambil <= 0 || $qty <= 0) {
        die("Data tidak valid.");
    }

    // default: gambar lama
    $gambarName = $data['gambar'];

    /* ===============================
       5. UPLOAD + CONVERT WEBP
    ================================ */
    if (!empty($_FILES['gambar']['name'])) {

        $newImage = uploadImageWebp(
            $_FILES['gambar'],
            __DIR__ . '/../../uploads/barang/'
        );

        if ($newImage !== null) {

            // hapus gambar lama
            if (!empty($gambarName)) {
                $oldPath = __DIR__ . '/../../uploads/barang/' . $gambarName;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $gambarName = $newImage;
        } else {
            die("Gagal upload gambar. Format harus JPG / PNG / WEBP.");
        }
    }

    /* ===============================
       6. UPDATE DATABASE
    ================================ */
    $stmt = $conn->prepare("
        UPDATE daftar_barang SET
            nama_barang = ?,
            harga_ambil = ?,
            qty = ?,
            subtotal = ?,
            gambar = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param(
        "siiisi",
        $nama_barang,
        $harga_ambil,
        $qty,
        $subtotal,
        $gambarName,
        $id
    );

    if ($stmt->execute()) {
        header("Location: ?path=daftar_barang&sales_id={$sales_id}");
        exit;
    }

    die("Gagal menyimpan perubahan.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang</title>
    <style>
        /* Mobile-first CSS untuk layar HP kecil */

        /* Reset dan base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding: 10px;
        }

        .wrap {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.5rem;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        p {
            color: #dc2626;
            font-weight: 500;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        input[readonly] {
            background-color: #f9fafb;
            color: #6b7280;
        }

        img {
            max-width: 100px;
            height: auto;
            border-radius: 5px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }

        .btn-primary {
            background: #27ae60;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            background: #229954;
        }

        .btn-back {
            display: inline-block;
            background: #6b7280;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 0.9rem;
            transition: background 0.3s ease;
            width: 100%;
            text-align: center;
        }

        .btn-back:hover {
            background: #4b5563;
        }

        /* Media queries untuk layar yang lebih besar */
        @media (min-width: 576px) {
            body {
                padding: 20px;
            }

            .wrap {
                max-width: 500px;
                padding: 20px;
            }

            h2 {
                font-size: 1.8rem;
            }

            .btn-primary,
            .btn-back {
                width: auto;
                display: inline-block;
            }

            img {
                max-width: 120px;
            }
        }

        @media (min-width: 768px) {
            .wrap {
                max-width: 600px;
            }

            form {
                gap: 20px;
            }

            input[type="text"],
            input[type="number"],
            input[type="file"] {
                padding: 15px;
            }

            .btn-primary {
                padding: 15px 30px;
            }

            .btn-back {
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>

<div class="wrap">
    <h2>Edit Barang</h2>

    <?php if (isset($error)) echo "<p style='color:red;'>".$error."</p>"; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Nama Barang</label>
        <input type="text" name="nama_barang" value="<?= $data['nama_barang']; ?>" required>

        <label>Harga Ambil</label>
        <input type="number" name="harga_ambil" id="harga_ambil" value="<?= $data['harga_ambil']; ?>" required>

        <label>Qty</label>
        <input type="number" name="qty" id="qty" value="<?= $data['qty']; ?>" required>

        <label>Subtotal</label>
        <input type="number" id="subtotal" readonly value="<?= $data['subtotal']; ?>">

        <label>Gambar Barang</label>
        <?php if ($data['gambar'] != '') { ?>
            <img src="../../uploads/barang/<?= $data['gambar']; ?>">
        <?php } ?>
        <input type="file" name="gambar">

        <button type="submit" class="btn-primary">Simpan Perubahan</button>

    </form>

    <a class="btn-back" href="?path=daftar_barang&sales_id=<?= $sales_id; ?>">Kembali</a>
</div>

<script>
    document.getElementById("harga_ambil").addEventListener("input", calculateSubtotal);
    document.getElementById("qty").addEventListener("input", calculateSubtotal);

    function calculateSubtotal() {
        let harga = document.getElementById("harga_ambil").value;
        let qty = document.getElementById("qty").value;
        document.getElementById("subtotal").value = harga * qty;
    }
</script>

</body>
</html>
