<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: karyawan_dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT id, username, password, role, status FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: karyawan_dashboard.php");
                }
                exit();
            } else {
                $error = "Username belum terdaftar, password salah, atau akun belum aktif! Silahkan <a href='register.php' style='color:#00BFFF;'>daftar dulu</a>.";
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
    <title>BALNIS - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>

    <div class="login-container">
        <div class="logo-section">
            <img src="logo.png" alt="BALNIS Logo"> 
            <h1>BALNIS</h1>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>

            <div class="links-group">
                <a href="forgot_password.php">
                    <i class="fas fa-key"></i> Lupa Password
                </a>
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Daftar
                </a>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> MASUK
            </button>

            <div class="signup-prompt">
                Belum punya akun? <a href="register.php">Daftar sekarang</a>
            </div>
        </form>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        document.querySelector('input[name="username"]').focus();
    </script>

</body>
</html>
