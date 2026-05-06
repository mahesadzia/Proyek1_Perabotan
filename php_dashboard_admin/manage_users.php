<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=balnis_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                
                if (empty($email) || empty($password)) {
                    $message = '<div class="alert alert-danger">Email dan password wajib diisi!</div>';
                } else {
                    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $check->execute([$email]);
                    
                    if ($check->rowCount() == 0) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, 'active')");
                        $stmt->execute([$email, $hashed, $role]);
                        $message = '<div class="alert alert-success">✅ Akun <strong>' . htmlspecialchars($email) . '</strong> berhasil dibuat!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">❌ Email sudah terdaftar!</div>';
                    }
                }
            }
            
            if ($_POST['action'] == 'deactivate') {
                $user_id = $_POST['user_id'];
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role != 'admin'");
                $stmt->execute([$user_id]);
                $message = '<div class="alert alert-warning">⚠️ Akun dinonaktifkan!</div>';
            }
            
            if ($_POST['action'] == 'activate') {
                $user_id = $_POST['user_id'];
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = '<div class="alert alert-success">✅ Akun diaktifkan!</div>';
            }
            
            if ($_POST['action'] == 'reset_password') {
                $user_id = $_POST['user_id'];
                $new_password = 'password123';
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user_id]);
                $message = '<div class="alert alert-info">🔑 Password direset ke: <strong>password123</strong></div>';
            }
        }
    }
    
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role DESC, created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - BALNIS Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; font-size: 28px; }
        .logout { background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 25px; }
        
        .add-form { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .add-form h3 { margin-bottom: 20px; color: #333; }
        .form-row { display: flex; gap: 15px; margin-bottom: 20px; }
        .form-group { flex: 1; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #667eea; }
        .btn { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 12px 30px; border: none; border-radius: 25px; cursor: pointer; font-size: 16px; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        
        .table-container { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 20px 15px; text-align: left; border-bottom: 1px solid #f1f3f4; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        .admin-row { background: #e3f2fd !important; }
        .karyawan-row { background: #f1f8e9; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        
        .action-btn { padding: 8px 15px; margin: 0 5px 5px 0; border: none; border-radius: 20px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-info { background: #17a2b8; color: white; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> Kelola Pengguna</h1>
            <a href="admin_dashboard.php" class="btn">← Dashboard</a>
            <a href="../logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        
        <?php echo $message ?? ''; ?>
        
        <div class="add-form">
            <h3><i class="fas fa-user-plus"></i> Tambah Pengguna Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email (contoh: user@balnis.com)" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="form-group">
                        <select name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="karyawan">Karyawan</option>
                            <option value="admin">Admin (Hati-hati!)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn"><i class="fas fa-plus"></i> Tambah Pengguna</button>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-id-card"></i> ID</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-user-tag"></i> Role</th>
                        <th><i class="fas fa-toggle-on"></i> Status</th>
                        <th><i class="fas fa-calendar"></i> Terakhir Login</th>
                        <th><i class="fas fa-cog"></i> Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="<?php echo $user['role'] == 'admin' ? 'admin-row' : 'karyawan-row'; ?>">
                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span style="padding: 4px 12px; background: <?php echo $user['role']=='admin' ? '#2196F3' : '#4CAF50'; ?>; color: white; border-radius: 20px; font-size: 12px;">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-<?php echo $user['status']; ?>">
                                <?php echo $user['status'] == 'active' ? '✅ AKTIF' : '❌ NONAKTIF'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Belum pernah'; ?>
                        </td>
                        <td>
                            <?php if ($user['role'] != 'admin'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    
                                    <?php if ($user['status'] == 'active'): ?>
                                        <button type="submit" name="action" value="deactivate" class="action-btn btn-danger" onclick="return confirm('Nonaktifkan akun <?php echo $user['email']; ?>?')">
                                            <i class="fas fa-pause"></i> Nonaktif
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="activate" class="action-btn btn-success" onclick="return confirm('Aktifkan akun <?php echo $user['email']; ?>?')">
                                            <i class="fas fa-play"></i> Aktifkan
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="action" value="reset_password" class="action-btn btn-info" onclick="return confirm('Reset password ke password123?')">
                                        <i class="fas fa-key"></i> Reset PW
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #999; font-size: 12px;">(Admin dilindungi)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>