<?php
session_start();
$error = $success = '';

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

$reset_email = $_SESSION['reset_email'];
$user_id = $_SESSION['user_id'];

// Generate/verifikasi kode OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_code = trim($_POST['code']);
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cek kode OTP (simpan di session untuk demo)
        $otp_code = $_SESSION['otp_code'] ?? '123456'; // Demo: selalu 123456
        
        if ($input_code === $otp_code) {
            unset($_SESSION['reset_email'], $_SESSION['user_id'], $_SESSION['otp_code']);
            $_SESSION['reset_user_id'] = $user_id;
            header("Location: new_password.php");
            exit();
        } else {
            $error = "Kode verifikasi salah!";
        }
    } catch(PDOException $e) {
        $error = "Error server!";
    }
}

// Generate OTP baru (demo)
$_SESSION['otp_code'] = '123456'; // Demo OTP
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - BALNIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <style>
        .otp-container { text-align: center; }
        .otp-code { 
            background: rgba(255,255,255,0.2); 
            color: #00BFFF; 
            font-size: 2.5rem; 
            font-weight: bold; 
            padding: 20px; 
            border-radius: 15px; 
            margin: 20px 0; 
            letter-spacing: 10px; 
            font-family: monospace;
        }
        .resend-btn { 
            background: rgba(255,255,255,0.1); 
            color: #fff; 
            border: 1px solid rgba(255,255,255,0.3); 
            padding: 10px 25px; 
            border-radius: 25px; 
            text-decoration: none; 
            margin-top: 15px; 
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="img/logo.png" alt="Logo"> 
            <h1>Verifikasi Email</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="otp-container">
            <p style="color: #fff; margin-bottom: 20px;">
                Kode verifikasi telah dikirim ke <strong><?php echo htmlspecialchars($reset_email); ?></strong>
            </p>
            
            <div class="otp-code">123456</div>
            
            <p style="color: rgba(255,255,255,0.9); font-size: 0.95rem;">
                Masukkan kode di atas:
            </p>
            
            <form method="post" style="margin-top: 25px;">
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="text" name="code" placeholder="Masukkan kode 6 digit" maxlength="6" required 
                           style="text-align: center; font-size: 1.5rem; letter-spacing: 10px;">
                    <div class="toggle-password" style="display: none;"></div>
                </div>
                <button type="submit" class="login-btn" style="margin-top: 20px;">VERIFIKASI</button>
            </form>
            
            <a href="forgot_password.php" class="resend-btn">← Kembali</a>
        </div>
    </div>
</body>
</html>