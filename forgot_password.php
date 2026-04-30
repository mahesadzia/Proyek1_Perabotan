<?php
session_start();

if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }

$msg   = '';
$type  = '';
$token_debug = ''; // Simulasi pengiriman token (tanpa SMTP)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg  = 'Format email tidak valid.';
        $type = 'error';
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $upd = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $upd->execute([$token, $expires, $user['id']]);

                // Di production: kirim email berisi link reset
                // Karena tidak ada SMTP, token ditampilkan langsung
                $token_debug = $token;
                $msg  = "Token reset berhasil dibuat. Gunakan link di bawah untuk reset password (berlaku 1 jam).";
                $type = 'success';
            } else {
                // Pesan generik agar tidak bocorkan info akun
                $msg  = "Jika email terdaftar, instruksi reset akan dikirimkan.";
                $type = 'info';
            }
        } catch (PDOException $e) {
            $msg  = "Error server. Coba lagi!";
            $type = 'error';
            error_log("Forgot password error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BALNIS - Lupa Password</title>
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
        .msg-box.error   { background: rgba(255,60,60,0.2);   border: 1px solid rgba(255,60,60,0.3);   color: #fff; }
        .msg-box.success { background: rgba(34,197,94,0.2);   border: 1px solid rgba(34,197,94,0.35);  color: #fff; }
        .msg-box.info    { background: rgba(0,191,255,0.15);  border: 1px solid rgba(0,191,255,0.3);   color: #fff; }

        .token-box {
            background: rgba(0,0,0,0.35);
            border: 1px dashed rgba(0,191,255,0.5);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            text-align: left;
        }
        .token-box p {
            color: rgba(255,255,255,0.7);
            font-size: 0.75rem;
            margin-bottom: 8px;
        }
        .token-box a {
            color: #00BFFF;
            font-size: 0.78rem;
            word-break: break-all;
            text-decoration: underline;
        }
        .token-box .token-note {
            margin-top: 8px;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.4);
            font-style: italic;
        }

        .subtitle {
            color: rgba(255,255,255,0.6);
            font-size: 0.875rem;
            margin-bottom: 24px;
            line-height: 1.5;
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="../img/logo.png" alt="Logo">
            <h1>BALNIS</h1>
        </div>

        <p class="subtitle">
            <i class="fas fa-key" style="color:#00BFFF;margin-right:6px;"></i>
            Masukkan email akun Anda untuk mendapatkan link reset password.
        </p>

        <?php if ($msg): ?>
            <div class="msg-box <?= $type ?>">
                <i class="fas fa-<?= $type==='success' ? 'check-circle' : ($type==='error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($token_debug): ?>
            <div class="token-box">
                <p><i class="fas fa-link" style="color:#00BFFF;margin-right:4px;"></i> Link Reset Password:</p>
                <a href="reset_password.php?token=<?= urlencode($token_debug) ?>">
                    reset_password.php?token=<?= htmlspecialchars(substr($token_debug, 0, 20)) ?>...
                </a>
                <p class="token-note">* Klik link di atas untuk melanjutkan reset password. Di produksi, link ini dikirim via email.</p>
            </div>
        <?php endif; ?>

        <?php if (!$token_debug): ?>
        <form method="POST" autocomplete="off">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email terdaftar" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <button type="submit" class="login-btn">
                <i class="fas fa-paper-plane" style="margin-right:8px;"></i> KIRIM LINK RESET
            </button>
        </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke halaman login
        </a>
    </div>
</body>
</html>
