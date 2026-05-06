<?php
session_start();
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT id, email, password, role FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $error = "Email tidak ditemukan atau akun tidak aktif!";
        } elseif (!empty($old_password)) {
            if (!password_verify($old_password, $user['password'])) {
                $error = "Password lama salah!";
            } elseif ($new_password !== $confirm_password) {
                $error = "Konfirmasi password tidak cocok!";
            } elseif (strlen($new_password) < 6) {
                $error = "Password baru minimal 6 karakter!";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashed_password, $user['id']]);
                $success = "Password berhasil diubah!";
            }
        } else {
            $_SESSION['reset_email'] = $email;
            $_SESSION['user_id'] = $user['id'];
            header("Location: verify_email.php");
            exit();
        }
        
    } catch(PDOException $e) {
        $error = "Error server. Coba lagi!";
        error_log("Forgot password error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BALNIS - Ganti Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="img/logo.png" alt="Logo"> 
            <h1>Ganti Password</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
            <a href="login.php" class="login-btn">Kembali ke Login</a>
        <?php else: ?>
            <form action="" method="post" autocomplete="off">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="old_password" placeholder="Password Lama (Kosongkan jika lupa)" id="oldPassword">
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="new_password" placeholder="Password Baru" id="newPassword">
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Konfirmasi Password Baru" id="confirmPassword">
                </div>

                <button type="submit" class="login-btn">UBAH PASSWORD</button>
                <a href="login.php" class="back-link">← Kembali ke Login</a>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('input[type="password"]').forEach((input, index) => {
            input.insertAdjacentHTML('afterend', `
                <div class="toggle-password" onclick="togglePassword(this)">
                    <i class="fas fa-eye"></i>
                </div>
            `);
        });

        function togglePassword(toggle) {
            const input = toggle.previousElementSibling;
            const icon = toggle.querySelector('i');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    </script>
</body>
</html>