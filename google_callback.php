<?php
session_start();
require_once 'google_config.php';
require_once 'config.php';

$client = require 'google_config.php';

if (isset($_GET['code'])) {
    try {
        // Exchange authorization code for access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (!isset($token['error'])) {
            $client->setAccessToken($token);

            // Get user profile information
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            $email = $google_account_info->email;
            $name = $google_account_info->name;

            // Check if email is in allowed_emails table
            $stmt = $conn->prepare("SELECT email FROM allowed_emails WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Email is allowed, create session
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;
                $_SESSION['login_type'] = 'google';

                header('Location: index.php');
                exit;
            } else {
                // Email not allowed
                $_SESSION['google_error'] = "Email Google Anda ($email) tidak diizinkan mengakses sistem!";
                header('Location: login.php');
                exit;
            }
            $stmt->close();
        } else {
            $_SESSION['google_error'] = "Gagal mendapatkan akses token dari Google.";
            header('Location: login.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['google_error'] = "Terjadi kesalahan saat login dengan Google: " . $e->getMessage();
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}

$conn->close();
?>
