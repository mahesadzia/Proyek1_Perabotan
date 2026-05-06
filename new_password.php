<?php
session_start();
$error = $success = '';

if (!isset($_SESSION['reset_user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['reset_user_id']]);
            
            // Clear session
            session_destroy();
            $success = "✅ Password berhasil direset! Gunakan password baru untuk login.";
        } catch(PDOException $e) {
            $error = "Error server!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Baru - BALNIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="img/logo.png" alt="Logo"> 
            <h1>Password Baru</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div style="text-align: center; color: #fff; padding: 40px;">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #4CAF50; margin-bottom: 20px;"></i>
                <h2><?php echo $success; ?></h2>
                <a href="login.php" class="login-btn" style="margin-top: 25px;">MASUK SEKARANG</a>
            </div>
        <?php else: ?>
            <form method="post" autocomplete="off">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="new_password" placeholder="Password Baru" required id="newPassword">
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Konfirmasi Password" required id="confirmPassword">
                </div>

                <button type="submit" class="login-btn">SET PASSWORD BARU</button>
                <a href="forgot_password.php" class="back-link" style="margin-top: 15px; display: inline-block;">← Ubah Cara</a>
            </form>
        <?php endif; ?>

        <script>
            // Toggle password untuk 2 input
            document.querySelectorAll('input[type="password"]').forEach(input => {
                input.insertAdjacentHTML('afterend', `<div class="toggle-password" onclick="togglePassword(this)"><i class="fas fa-eye"></i></div>`);
            });
            
            function togglePassword(toggle) {
                const input = toggle.previousElementSibling;
                const icon = toggle.querySelector('i');
                input.type = input.type === 'password' ? 'text' : 'password';
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        </script>
    </div>
</body>
</html>