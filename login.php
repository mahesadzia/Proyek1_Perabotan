<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: php_dashboard_admin/dashboard_admin.php");
    } else {
        header("Location: php_dashboard_karyawan/dashboard_karyawan.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Email dan password wajib diisi!";
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT id, email, password, role, status FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header("Location: php_dashboard_admin/admin_dashboard.php");
                } else {
                    header("Location: php_dashboard_karyawan/dashboard_karyawan.php");
                }
                exit();
            } else {
                $error = "Email atau password salah, atau akun belum aktif!";
            }
        } catch(PDOException $e) {
            $error = "Error server. Coba lagi!";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BALNIS - Login System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="img/logo.png" alt="Logo"> 
            <h1>BALNIS</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="post" autocomplete="off">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <div class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </div>
            </div>

            <div class="links-group">
                <a href="forgot_password.php">Lupa Password?</a>
            </div>

            <button type="submit" class="login-btn">MASUK</button>
        </form>
    </div>

    <script>
        const toggleBtn = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        const icon = toggleBtn.querySelector('i');

        toggleBtn.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>