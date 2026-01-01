<?php
include 'config.php';

// Check if allowed_emails table exists and has any records
$result = $conn->query("SELECT COUNT(*) as count FROM allowed_emails");
$row = $result->fetch_assoc();
$has_emails = $row['count'] > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_admin'])) {
    $email = trim($_POST['admin_email']);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Create table if it doesn't exist
        $conn->query("
            CREATE TABLE IF NOT EXISTS allowed_emails (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Add the admin email
        $stmt = $conn->prepare("INSERT IGNORE INTO allowed_emails (email) VALUES (?)");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $success = "Email admin berhasil ditambahkan! Silakan login melalui login.php";
        } else {
            $error = "Email sudah terdaftar atau terjadi kesalahan.";
        }
        $stmt->close();
    } else {
        $error = "Format email tidak valid!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin - ALBA</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .setup-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #21633E 0%, #437057 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            box-shadow: 0 5px 15px rgba(33, 99, 62, 0.3);
        }

        h1 {
            color: #21633E;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1em;
        }

        .info-box {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
        }

        .info-box h3 {
            color: #2e7d32;
            margin-bottom: 10px;
        }

        .info-box ul {
            color: #333;
            padding-left: 20px;
        }

        .info-box li {
            margin-bottom: 5px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }

        input[type="email"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: #21633E;
            box-shadow: 0 0 0 3px rgba(33, 99, 62, 0.1);
        }

        .btn-setup {
            width: 100%;
            background: linear-gradient(135deg, #21633E 0%, #437057 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-setup:hover {
            transform: translateY(-2px);
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

        .login-link {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .login-link:hover {
            background: #5a6268;
        }

        @media (max-width: 480px) {
            .setup-container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <i class="fas fa-cogs"></i>
        </div>
        <h1>Setup Admin</h1>
        <p class="subtitle">Konfigurasi email admin untuk pertama kali</p>

        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> Panduan Setup</h3>
            <ul>
                <li>Masukkan email yang akan menjadi admin sistem</li>
                <li>Email ini akan memiliki akses penuh ke semua fitur</li>
                <li>Setelah setup, hapus file ini untuk keamanan</li>
                <li>Gunakan email yang valid dan aktif</li>
            </ul>
        </div>

        <?php if (isset($error)): ?>
        <div class="message error">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if (!$has_emails || isset($success)): ?>
        <form method="POST">
            <div class="form-group">
                <label for="admin_email">Email Admin *</label>
                <input type="email" id="admin_email" name="admin_email" required placeholder="Masukkan email admin" autofocus>
            </div>

            <button type="submit" name="setup_admin" class="btn-setup">
                <i class="fas fa-user-plus"></i> Setup Admin Email
            </button>
        </form>
        <?php else: ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> Sistem sudah dikonfigurasi. Email admin sudah ada dalam database.
        </div>

        <a href="login.php" class="login-link">
            <i class="fas fa-sign-in-alt"></i> Pergi ke Login
        </a>
        <?php endif; ?>
    </div>
</body>
</html>
