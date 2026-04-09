<?php
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = "Semua field wajib diisi!";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif ($username === 'admin' && $role !== 'admin') {
        $error = "Username 'admin' hanya untuk role Admin!";
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkStmt->execute([$username, $email]);
            
            if ($checkStmt->fetch()) {
                $error = "Username atau email sudah terdaftar!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
                $insertStmt->execute([$username, $email, $hashed_password, $role]);
                
                $success = "Registrasi berhasil! Silahkan <a href='login.php'>login</a> sekarang.";
            }
        } catch(PDOException $e) {
            $error = "Error server: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BALNIS - Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="register.css">
</head>
<body>

    <div class="register-container">
        <div class="logo-section">
            <i class="fas fa-user-plus"></i>
            <h1>DAFTAR AKUN</h1>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" id="registerForm">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" id="username" placeholder="Username (min 3 char)" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Password (min 6 char)" required>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" id="confirmPassword" placeholder="Konfirmasi Password" required>
                </div>

                <div class="input-group">
                    <label class="role-label">Pilih Role:</label>
                    <div class="role-group">
                        <label class="role-option admin-role" for="role-admin">
                            <input type="radio" id="role-admin" name="role" value="admin" 
                                   <?php echo (isset($_POST['role']) && $_POST['role']=='admin') ? 'checked' : ''; ?> required>
                            <i class="fas fa-user-shield"></i>
                            <span>Admin</span>
                            <div class="role-badge">Full Access</div>
                        </label>
                        
                        <label class="role-option karyawan-role" for="role-karyawan">
                            <input type="radio" id="role-karyawan" name="role" value="karyawan" 
                                   <?php echo (!isset($_POST['role']) || $_POST['role']=='karyawan') ? 'checked' : ''; ?> required>
                            <i class="fas fa-user"></i>
                            <span>Karyawan</span>
                            <div class="role-badge">Limited</div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="register-btn">
                    <i class="fas fa-user-plus"></i> DAFTAR AKUN BARU
                </button>
            </form>
        <?php endif; ?>

        <div class="links-group">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Sudah punya akun? Login
            </a>
        </div>
    </div>

    <script>
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const strengthDiv = document.getElementById('passwordStrength');
        const adminRadio = document.getElementById('role-admin');
        const karyawanRadio = document.getElementById('role-karyawan');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = '';
            let colorClass = '';

            if (password.length === 0) {
                strength = '';
            } else if (password.length < 6) {
                strength = 'Password terlalu pendek';
                colorClass = 'weak';
            } else if (password.length < 8) {
                strength = 'Password lemah';
                colorClass = 'medium';
            } else if (/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
                strength = 'Password KUAT ✓';
                colorClass = 'strong';
            } else {
                strength = 'Password sedang';
                colorClass = 'good';
            }

            strengthDiv.textContent = strength;
            strengthDiv.className = `password-strength ${colorClass}`;
        });

        usernameInput.addEventListener('input', function() {
            const username = this.value.toLowerCase().trim();
            if (username === 'admin') {
                adminRadio.checked = true;
                document.querySelector('.admin-role').classList.add('selected');
                document.querySelector('.karyawan-role').classList.remove('selected');
            }
        });

        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirm = this.value;
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    this.style.borderColor = '#28a745';
                    this.style.boxShadow = '0 0 0 2px rgba(40, 167, 69, 0.2)';
                } else {
                    this.style.borderColor = '#dc3545';
                    this.style.boxShadow = '0 0 0 2px rgba(220, 53, 69, 0.2)';
                }
            } else {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });

        [adminRadio, karyawanRadio].forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.role-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.parentElement.classList.add('selected');
            });
        });

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmPasswordInput.value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
                return false;
            }
        });

        usernameInput.focus();
    </script>

</body>
</html>