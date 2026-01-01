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
        body {
            background-color: #437057;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            font-family: Arial, sans-serif;
        }
        .wrap {
            background-color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            padding: 1.5rem;
            width: 100%;
            max-width: 28rem;
        }
        @media (min-width: 768px) {
            .wrap {
                max-width: 32rem;
            }
        }
        @media (min-width: 1024px) {
            .wrap {
                max-width: 36rem;
            }
        }
        /* Responsif untuk layar kecil: penuhi semua layar */
        @media (max-width: 767px) {
            body {
                padding: 0;
            }
            .wrap {
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
        p {
            color: #dc2626;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        input[type="text"], input[type="number"], input[type="file"] {
            width: 90%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input[type="text"]:focus, input[type="number"]:focus, input[type="file"]:focus {
            border-color: #437057;
            box-shadow: 0 0 0 3px rgba(67, 112, 87, 0.1);
        }
        input[readonly] {
            background-color: #f9fafb;
            color: #6b7280;
        }
        img {
            max-width: 100px;
            height: auto;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
        }
        .btn-primary {
            width: 100%;
            background-color: #437057;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #365a46;
        }
        .btn-primary:focus {
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
            margin-top: 1rem;
            text-align: center;
            transition: background-color 0.2s;
        }
        .btn-back:hover {
            background-color: #4b5563;
        }
        .btn-back:focus {
            outline: 2px solid #6b7280;
            outline-offset: 2px;
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
