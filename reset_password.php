<?php
session_start();

if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }

$token = trim($_GET['token'] ?? '');
$msg   = '';
$type  = '';
$valid = false;
$user  = null;

// Validasi token
if (empty($token)) {
    $msg  = 'Token tidak valid atau tidak ditemukan.';
    $type = 'error';
} else {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id, username, reset_expires FROM users WHERE reset_token = ? AND status = 'active'");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $msg  = 'Token tidak valid atau sudah digunakan.';
            $type = 'error';
        } elseif (strtotime($user['reset_expires']) < time()) {
            $msg  = 'Token sudah kedaluwarsa. Silakan minta reset ulang.';
            $type = 'error';
        } else {
            $valid = true;
        }
    } catch (PDOException $e) {
        $msg  = 'Error server. Coba lagi!';
        $type = 'error';
        error_log("Reset password error: " . $e->getMessage());
    }
}

// Proses ganti password
if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (strlen($pass1) < 6) {
        $msg  = 'Password minimal 6 karakter.';
        $type = 'error';
    } elseif ($pass1 !== $pass2) {
        $msg  = 'Konfirmasi password tidak cocok.';
        $type = 'error';
    } else {
        try {
            $hashed = password_hash($pass1, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $upd->execute([$hashed, $user['id']]);

            $msg   = 'Password berhasil diubah! Silakan login dengan password baru.';
            $type  = 'success';
            $valid = false; // Sembunyikan form setelah berhasil
        } catch (PDOException $e) {
            $msg  = 'Gagal menyimpan password baru. Coba lagi!';
            $type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BALNIS - Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <style>
        .msg-box {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            text-align: left;
            line-height: 1.5;
        }
        .msg-box.error   { background: rgba(255,60,60,0.2);  border: 1px solid rgba(255,60,60,0.3);  color: #fff; }
        .msg-box.success { background: rgba(34,197,94,0.2);  border: 1px solid rgba(34,197,94,0.35); color: #fff; }

        .subtitle {
            color: rgba(255,255,255,0.6);
            font-size: 0.875rem;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .strength-bar {
            height: 4px;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }
        .strength-label {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.5);
            text-align: right;
            margin-top: 4px;
        }

        .match-hint {
            font-size: 0.72rem;
            margin-top: 6px;
            text-align: left;
            padding-left: 4px;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: rgba(255,255,255,0.6);
            font-size: 0.85rem;
            text-decoration: none;
        }
        .back-link:hover { color: #fff; }
        .back-link i { margin-right: 5px; }

        .login-btn-outline {
            width: 100%;
            padding: 14px;
            background: transparent;
            border: 1px solid #00BFFF;
            border-radius: 30px;
            color: #00BFFF;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 8px;
        }
        .login-btn-outline:hover {
            background: rgba(0,191,255,0.15);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="../img/logo.png" alt="Logo">
            <h1>BALNIS</h1>
        </div>

        <?php if ($valid): ?>
            <p class="subtitle">
                <i class="fas fa-lock-open" style="color:#00BFFF;margin-right:6px;"></i>
                Halo, <strong style="color:#fff;"><?= htmlspecialchars($user['username']) ?></strong>!
                Silakan buat password baru Anda.
            </p>
        <?php else: ?>
            <p class="subtitle">
                <i class="fas fa-shield-alt" style="color:#00BFFF;margin-right:6px;"></i>
                Reset Password
            </p>
        <?php endif; ?>

        <?php if ($msg): ?>
            <div class="msg-box <?= $type ?>">
                <i class="fas fa-<?= $type==='success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($valid): ?>
        <form method="POST" action="reset_password.php?token=<?= urlencode($token) ?>" autocomplete="off" id="resetForm">
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password baru" required minlength="6">
                <div class="toggle-password" onclick="togglePass('password', this)">
                    <i class="fas fa-eye"></i>
                </div>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <div class="strength-label" id="strengthLabel">Kekuatan password</div>

            <div class="input-group" style="margin-top:20px;">
                <i class="fas fa-lock"></i>
                <input type="password" name="password2" id="password2" placeholder="Konfirmasi password baru" required>
                <div class="toggle-password" onclick="togglePass('password2', this)">
                    <i class="fas fa-eye"></i>
                </div>
            </div>
            <div class="match-hint" id="matchHint"></div>

            <button type="submit" class="login-btn" style="margin-top:20px;">
                <i class="fas fa-save" style="margin-right:8px;"></i> SIMPAN PASSWORD BARU
            </button>
        </form>

        <?php elseif ($type === 'success'): ?>
            <a href="login.php" class="login-btn" style="display:block;text-decoration:none;padding:14px;text-align:center;">
                <i class="fas fa-sign-in-alt" style="margin-right:8px;"></i> MASUK SEKARANG
            </a>
        <?php endif; ?>

        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke halaman login
        </a>
    </div>

    <script>
    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        const isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }

    const passInput  = document.getElementById('password');
    const pass2Input = document.getElementById('password2');
    const fill       = document.getElementById('strengthFill');
    const label      = document.getElementById('strengthLabel');
    const hint       = document.getElementById('matchHint');

    function checkStrength(val) {
        let score = 0;
        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { pct: '0%',   color: 'transparent', text: 'Kekuatan password' },
            { pct: '25%',  color: '#ef4444',      text: 'Lemah' },
            { pct: '50%',  color: '#f97316',      text: 'Cukup' },
            { pct: '75%',  color: '#eab308',      text: 'Baik' },
            { pct: '100%', color: '#22c55e',       text: 'Kuat' },
            { pct: '100%', color: '#22c55e',       text: 'Sangat Kuat' },
        ];
        const lv = levels[Math.min(score, 5)];
        fill.style.width      = lv.pct;
        fill.style.background = lv.color;
        label.textContent     = lv.text;
        label.style.color     = score > 0 ? lv.color : 'rgba(255,255,255,0.5)';
    }

    function checkMatch() {
        if (!pass2Input.value) { hint.textContent = ''; return; }
        if (passInput.value === pass2Input.value) {
            hint.style.color   = '#22c55e';
            hint.textContent   = '✓ Password cocok';
        } else {
            hint.style.color   = '#ef4444';
            hint.textContent   = '✗ Password tidak cocok';
        }
    }

    if (passInput)  passInput.addEventListener('input',  () => { checkStrength(passInput.value); checkMatch(); });
    if (pass2Input) pass2Input.addEventListener('input',  checkMatch);
    </script>
</body>
</html>
