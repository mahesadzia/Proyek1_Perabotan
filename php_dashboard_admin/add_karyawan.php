<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Email dan password wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Cek email sudah ada
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            
            if ($check->rowCount() > 0) {
                $error = "Email sudah terdaftar!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, 'karyawan', 'active')");
                $stmt->execute([$email, $hashed_password]);
                $success = "✅ Akun karyawan <strong>$email</strong> berhasil dibuat!";
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Karyawan - BALNIS Admin</title>
    <link rel="stylesheet" href="../login.css">
    <style>
        .admin-form { max-width: 500px; margin: 50px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .btn { background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <div class="admin-form">
        <h2><i class="fas fa-user-plus"></i> Tambah Akun Karyawan</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="input-group" style="margin-bottom: 20px;">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Karyawan" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            
            <div class="input-group" style="margin-bottom: 20px;">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            
            <button type="submit" class="btn">✅ Buat Akun</button>
            <a href="admin_dashboard.php" class="btn btn-danger">← Kembali</a>
        </form>
        
        <hr style="margin: 30px 0;">
        <h3>Daftar Karyawan Aktif:</h3>
        <?php
        $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
        $stmt = $pdo->prepare("SELECT email, created_at FROM users WHERE role = 'karyawan' AND status = 'active' ORDER BY created_at DESC");
        $stmt->execute();
        $karyawans = $stmt->fetchAll();
        
        if ($karyawans): ?>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($karyawans as $karyawan): ?>
                    <div style="padding: 10px; border-bottom: 1px solid #eee;">
                        📧 <?php echo htmlspecialchars($karyawan['email']); ?> 
                        <small>(Dibuat: <?php echo date('d/m/Y H:i', strtotime($karyawan['created_at'])); ?>)</small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Belum ada karyawan.</p>
        <?php endif; ?>
    </div>
</body>
</html>