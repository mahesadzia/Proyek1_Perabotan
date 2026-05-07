<?php
echo "<!DOCTYPE html><html><head><title>BALNIS Setup</title>";
echo "<style>body{font-family:Arial;background:#f0f8ff;padding:40px;} .success{background:#d4edda;padding:20px;border-radius:10px;margin:20px 0;}</style></head><body>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🚀 BALNIS - SETUP LENGKAP</h2>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'karyawan') NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        otp_code VARCHAR(6) NULL,
        otp_expires DATETIME NULL,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✅ Tabel <strong>users</strong> siap!</div>";
    
    $admin_email = "ekimaulana102@gmail.com";
    $admin_pass = "admin123";
    
    $check_admin = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
    $check_admin->execute();
    
    if ($check_admin->rowCount() == 0) {
        $hashed = password_hash($admin_pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, 'admin', 'active')")
            ->execute([$admin_email, $hashed]);
        echo "<div class='success'>👑 <strong>ADMIN:</strong> $admin_email / $admin_pass</div>";
    } else {
        echo "<div>👑 Admin sudah ada</div>";
    }
    
    $karyawans = [
        'yourkiii13045@gmail.com' => 'karyawan123'
    ];
    
    foreach ($karyawans as $email => $pass) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() == 0) {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, 'karyawan', 'active')")
                ->execute([$email, $hashed]);
            echo "<div class='success'>👤 <strong>$email</strong> / $pass</div>";
        }
    }
    
    echo "<hr>";
    echo "<div style='background:#e3f2fd;padding:30px;border-radius:15px;font-size:18px;'>";
    echo "<h3>🎉 <strong>SETUP SELESAI 100%!</strong></h3>";
    echo "<p><strong>ADMIN:</strong> $admin_email / $admin_pass</p>";
    echo "<p><strong>KARYAWAN:</strong></p>";
    echo "<ul>";
    foreach ($karyawans as $email => $pass) {
        echo "<li>$email <span style='color:#666'>(pass: $pass)</span></li>";
    }
    echo "</ul>";
    echo "<a href='login.php' style='background:#00BFFF;color:white;padding:15px 30px;text-decoration:none;border-radius:10px;font-weight:bold;font-size:18px;'>🚀 MULAI LOGIN</a>";
    echo "</div>";
    
    echo "<hr><p style='color:#666;text-align:center;'>Jalankan <strong>sekali saja</strong>. Simpan file ini aman!</p>";
    
} catch(PDOException $e) {
    echo "<div style='background:#ffebee;color:#c62828;padding:20px;border-radius:10px;'>❌ ERROR: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>