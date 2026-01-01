<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Create allowed_emails table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS allowed_emails (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Handle adding new email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_email'])) {
    $email = trim($_POST['email']);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO allowed_emails (email) VALUES (?)");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
        $message = "Email berhasil ditambahkan!";
    } else {
        $error = "Format email tidak valid!";
    }
}

// Handle deleting email
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM allowed_emails WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $message = "Email berhasil dihapus!";
}

// Get all allowed emails
$result = $conn->query("SELECT * FROM allowed_emails ORDER BY created_at DESC");
$allowed_emails = [];
while ($row = $result->fetch_assoc()) {
    $allowed_emails[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Perizinan - ALBA</title>
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

        .user-info {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .user-info p {
            color: #2e7d32;
            font-weight: 600;
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

        .form-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .form-group input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1em;
        }

        .form-group input:focus {
            outline: none;
            border-color: #21633E;
            box-shadow: 0 0 0 3px rgba(33, 99, 62, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #21633E 0%, #437057 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        .email-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .email-list h3 {
            color: #21633E;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .email-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .email-item:last-child {
            margin-bottom: 0;
        }

        .email-info {
            flex: 1;
        }

        .email-address {
            font-weight: 600;
            color: #333;
        }

        .email-date {
            color: #666;
            font-size: 0.9em;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background 0.3s;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

        .logout-btn {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            margin-left: 10px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .header h1 {
                font-size: 2em;
            }

            .form-group {
                flex-direction: column;
            }

            .email-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cogs"></i> Pengaturan Perizinan</h1>
            <p>Kelola email yang diizinkan mengakses sistem ALBA</p>
        </div>

        <div class="user-info">
            <p><i class="fas fa-user"></i> Login sebagai: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
        </div>

        <?php if (isset($message)): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="message error">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="form-section">
            <h3><i class="fas fa-plus"></i> Tambah Email Baru</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Masukkan alamat email" required>
                    <button type="submit" name="add_email" class="btn-submit">
                        <i class="fas fa-plus"></i> Tambah Email
                    </button>
                </div>
            </form>
        </div>

        <div class="email-list">
            <h3><i class="fas fa-list"></i> Email yang Diizinkan (<?php echo count($allowed_emails); ?>)</h3>
            <?php if (empty($allowed_emails)): ?>
            <p style="color: #666; text-align: center; padding: 20px;">
                <i class="fas fa-info-circle"></i> Belum ada email yang diizinkan.
            </p>
            <?php else: ?>
                <?php foreach ($allowed_emails as $email): ?>
                <div class="email-item">
                    <div class="email-info">
                        <div class="email-address"><?php echo htmlspecialchars($email['email']); ?></div>
                        <div class="email-date">Ditambahkan: <?php echo date('d/m/Y H:i', strtotime($email['created_at'])); ?></div>
                    </div>
                    <a href="?delete=<?php echo $email['id']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus email ini?')">
                        <i class="fas fa-trash"></i> Hapus
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="text-align: center;">
            <a href="../../index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <a href="../../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>
