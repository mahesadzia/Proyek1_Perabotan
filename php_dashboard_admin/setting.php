<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'konek.php';

$is_admin = ($_SESSION['role'] === 'admin');
$current_user_id = $_SESSION['user_id'];
$msg = '';
$msg_type = '';

// ─── Kelola Akun Saya: Ganti password ────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $old_pass  = $_POST['old_password'];
    $new_pass  = $_POST['new_password'];
    $conf_pass = $_POST['confirm_password'];

    if (empty($old_pass) || empty($new_pass) || empty($conf_pass)) {
        $msg = "Semua field wajib diisi!";
        $msg_type = 'error';
    } elseif ($new_pass !== $conf_pass) {
        $msg = "Password baru dan konfirmasi tidak cocok!";
        $msg_type = 'error';
    } elseif (strlen($new_pass) < 6) {
        $msg = "Password baru minimal 6 karakter!";
        $msg_type = 'error';
    } else {
        $res = mysqli_query($conn, "SELECT password FROM users WHERE id = $current_user_id");
        $row = mysqli_fetch_assoc($res);
        if (password_verify($old_pass, $row['password'])) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$current_user_id");
            $msg = "Password berhasil diubah!";
            $msg_type = 'success';
        } else {
            $msg = "Password lama salah!";
            $msg_type = 'error';
        }
    }
}

// ─── Kelola Akun Karyawan (admin only) ───────────────────────────────────────
if ($is_admin) {

    // Tambah akun karyawan
    if (isset($_POST['action']) && $_POST['action'] === 'add_karyawan') {
        $new_username = trim(mysqli_real_escape_string($conn, $_POST['new_username']));
        $new_email    = trim(mysqli_real_escape_string($conn, $_POST['new_email']));
        $new_password = $_POST['new_karyawan_password'];
        $new_role     = in_array($_POST['new_role'], ['admin','karyawan']) ? $_POST['new_role'] : 'karyawan';

        if (empty($new_username) || empty($new_email) || empty($new_password)) {
            $msg = "Semua field tambah akun wajib diisi!";
            $msg_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $msg = "Password minimal 6 karakter!";
            $msg_type = 'error';
        } else {
            $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$new_username' OR email='$new_email'");
            if (mysqli_num_rows($check) > 0) {
                $msg = "Username atau email sudah digunakan!";
                $msg_type = 'error';
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_query($conn, "INSERT INTO users (username, email, password, role, status) VALUES ('$new_username','$new_email','$hashed','$new_role','active')");
                $msg = "Akun karyawan berhasil ditambahkan!";
                $msg_type = 'success';
            }
        }
    }

    // Edit password karyawan
    if (isset($_POST['action']) && $_POST['action'] === 'edit_karyawan_password') {
        $target_id   = (int)$_POST['target_id'];
        $reset_pass  = $_POST['reset_password'];
        $reset_conf  = $_POST['reset_confirm'];

        if (empty($reset_pass) || empty($reset_conf)) {
            $msg = "Password dan konfirmasi wajib diisi!";
            $msg_type = 'error';
        } elseif ($reset_pass !== $reset_conf) {
            $msg = "Password dan konfirmasi tidak cocok!";
            $msg_type = 'error';
        } elseif (strlen($reset_pass) < 6) {
            $msg = "Password minimal 6 karakter!";
            $msg_type = 'error';
        } else {
            $hashed = password_hash($reset_pass, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$target_id");
            $msg = "Password karyawan berhasil direset!";
            $msg_type = 'success';
        }
    }

    // Hapus akun karyawan
    if (isset($_POST['action']) && $_POST['action'] === 'delete_karyawan') {
        $target_id = (int)$_POST['target_id'];
        if ($target_id === $current_user_id) {
            $msg = "Tidak bisa menghapus akun yang sedang digunakan!";
            $msg_type = 'error';
        } else {
            mysqli_query($conn, "DELETE FROM users WHERE id=$target_id");
            $msg = "Akun berhasil dihapus!";
            $msg_type = 'success';
        }
    }

    // Toggle status aktif/nonaktif
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $target_id     = (int)$_POST['target_id'];
        $current_status = mysqli_real_escape_string($conn, $_POST['current_status']);
        $new_status    = ($current_status === 'active') ? 'inactive' : 'active';
        if ($target_id !== $current_user_id) {
            mysqli_query($conn, "UPDATE users SET status='$new_status' WHERE id=$target_id");
            $msg = "Status akun diperbarui!";
            $msg_type = 'success';
        }
    }

    // Ambil daftar semua user
    $q_users = mysqli_query($conn, "SELECT id, username, email, role, status, created_at, last_login FROM users ORDER BY role DESC, username ASC");
}

// Data akun sendiri
$q_me = mysqli_query($conn, "SELECT username, email, role, created_at, last_login FROM users WHERE id=$current_user_id");
$me   = mysqli_fetch_assoc($q_me);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting - Sistem Inventaris</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        /* ── Setting page ── */
        .setting-wrapper {
            padding: 28px 32px;
            max-width: 960px;
        }
        .setting-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .setting-title i { color: var(--primary); }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--border);
            margin-bottom: 28px;
        }
        .tab-btn {
            padding: 10px 22px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .tab-btn.active, .tab-btn:hover {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        /* Cards */
        .setting-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 28px;
            margin-bottom: 24px;
        }
        .setting-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .setting-card h3 i { color: var(--primary); font-size: 0.9rem; }

        /* Info row */
        .info-row {
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f4f8;
            font-size: 0.875rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--text-muted); width: 150px; flex-shrink: 0; }
        .info-value { font-weight: 600; color: var(--text-main); }

        /* Form */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .form-row.single { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-group input,
        .form-group select {
            padding: 9px 14px;
            border: 1.5px solid var(--border);
            border-radius: 7px;
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--text-main);
            background: #fafbfc;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
        }
        .btn-submit {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 9px 22px;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: var(--primary-dark); }

        /* Alert */
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Table */
        .user-table { width: 100%; border-collapse: collapse; font-size: 0.845rem; }
        .user-table th {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 700;
            padding: 10px 12px;
            text-align: left;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .user-table td {
            padding: 11px 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .user-table tr:last-child td { border-bottom: none; }
        .user-table tr:hover td { background: #f8fbff; }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-admin    { background: #ede9fe; color: #7c3aed; }
        .badge-karyawan { background: #e0f2fe; color: #0369a1; }
        .badge-active   { background: #f0fdf4; color: #16a34a; }
        .badge-inactive { background: #fef2f2; color: #dc2626; }
        .badge-pending  { background: #fefce8; color: #ca8a04; }

        /* Action buttons */
        .action-btns { display: flex; gap: 6px; }
        .btn-icon {
            border: none;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 0.78rem;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            transition: opacity 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-icon:hover { opacity: 0.8; }
        .btn-edit   { background: #fff7ed; color: #ea580c; }
        .btn-toggle { background: #f0fdf4; color: #16a34a; }
        .btn-toggle.inactive { background: #fef2f2; color: #dc2626; }
        .btn-delete { background: #fef2f2; color: #dc2626; }
        .btn-me     { background: var(--primary-light); color: var(--primary); cursor: default; font-size:0.72rem; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 500;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 14px;
            padding: 30px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
            animation: popIn 0.2s ease;
        }
        @keyframes popIn {
            from { opacity:0; transform:scale(0.92); }
            to   { opacity:1; transform:scale(1); }
        }
        .modal-box h4 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-box h4 i { color: var(--primary); }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-cancel {
            background: #f1f5f9;
            color: var(--text-muted);
            border: none;
            border-radius: 7px;
            padding: 9px 18px;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }
        .btn-danger {
            background: #dc2626;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 9px 18px;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }
        .me-highlight td { background: #f0f4ff !important; }

        /* Tab panels */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        @media(max-width:640px) {
            .setting-wrapper { padding: 16px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="sidebar" id="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <h3>REPORT</h3>
    <a href="laporan_barangmasuk.php"><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a>
    <a href="laporanBarangKeluar.php"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>
    <a href="setting.php" class="active"><i class="fas fa-cog"></i> Setting</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="hamburger" id="hamburger" aria-label="Menu"><span></span></button>
            <span><i class="fas fa-cog"></i> SETTING</span>
        </div>
        <div style="font-size:0.875rem;font-weight:500;color:#718096;">
            <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="fa fa-user-circle" style="color:#1565c0;"></i>
        </div>
    </header>

    <div class="setting-wrapper">

        <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>">
            <i class="fas fa-<?php echo $msg_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('tab-akun-saya', this)">
                <i class="fas fa-user"></i> Kelola Akun Saya
            </button>
            <?php if ($is_admin): ?>
            <button class="tab-btn" onclick="switchTab('tab-kelola-user', this)">
                <i class="fas fa-users"></i> Kelola Akun Karyawan
            </button>
            <?php endif; ?>
        </div>

        <!-- ── TAB: AKUN SAYA ─────────────────────────────────── -->
        <div class="tab-panel active" id="tab-akun-saya">

            <!-- Info akun -->
            <div class="setting-card">
                <h3><i class="fas fa-id-card"></i> Informasi Akun</h3>
                <div class="info-row">
                    <span class="info-label">Username</span>
                    <span class="info-value"><?php echo htmlspecialchars($me['username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($me['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role</span>
                    <span class="info-value">
                        <span class="badge badge-<?php echo $me['role']; ?>">
                            <i class="fas fa-<?php echo $me['role']==='admin'?'crown':'user'; ?>"></i>
                            <?php echo ucfirst($me['role']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Terdaftar</span>
                    <span class="info-value"><?php echo $me['created_at'] ? date('d M Y', strtotime($me['created_at'])) : '-'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Login Terakhir</span>
                    <span class="info-value"><?php echo $me['last_login'] ? date('d M Y H:i', strtotime($me['last_login'])) : '-'; ?></span>
                </div>
            </div>

            <!-- Ganti password -->
            <div class="setting-card">
                <h3><i class="fas fa-lock"></i> Ganti Password</h3>
                <form method="post">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password Lama</label>
                            <input type="password" name="old_password" placeholder="Password saat ini" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" placeholder="Min. 6 karakter" required>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" placeholder="Ulangi password baru" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Simpan Password</button>
                </form>
            </div>
        </div>

        <?php if ($is_admin): ?>
        <!-- ── TAB: KELOLA KARYAWAN ────────────────────────────── -->
        <div class="tab-panel" id="tab-kelola-user">

            <!-- Tambah akun karyawan -->
            <div class="setting-card">
                <h3><i class="fas fa-user-plus"></i> Tambah Akun Baru</h3>
                <form method="post">
                    <input type="hidden" name="action" value="add_karyawan">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="new_username" placeholder="username unik" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="new_email" placeholder="email@contoh.com" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="new_karyawan_password" placeholder="Min. 6 karakter" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="new_role">
                                <option value="karyawan">Karyawan</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-plus"></i> Tambah Akun</button>
                </form>
            </div>

            <!-- Daftar semua user -->
            <div class="setting-card">
                <h3><i class="fas fa-users"></i> Daftar Akun User</h3>
                <div style="overflow-x:auto;">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while($u = mysqli_fetch_assoc($q_users)): ?>
                            <tr <?php if($u['id']==$current_user_id) echo 'class="me-highlight"'; ?>>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($u['username']); ?>
                                    <?php if($u['id']==$current_user_id): ?>
                                        <span class="badge btn-me" style="margin-left:6px;">Anda</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $u['role']; ?>">
                                        <i class="fas fa-<?php echo $u['role']==='admin'?'crown':'user'; ?>"></i>
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $u['status']; ?>">
                                        <?php echo ucfirst($u['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $u['created_at'] ? date('d/m/Y', strtotime($u['created_at'])) : '-'; ?></td>
                                <td>
                                    <?php if($u['id'] !== $current_user_id): ?>
                                    <div class="action-btns">
                                        <!-- Reset password -->
                                        <button class="btn-icon btn-edit"
                                            onclick="openResetModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-key"></i> Reset
                                        </button>
                                        <!-- Toggle status -->
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="target_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $u['status']; ?>">
                                            <button type="submit" class="btn-icon btn-toggle <?php echo $u['status']==='inactive'?'inactive':''; ?>">
                                                <i class="fas fa-<?php echo $u['status']==='active'?'ban':'check'; ?>"></i>
                                                <?php echo $u['status']==='active'?'Nonaktifkan':'Aktifkan'; ?>
                                            </button>
                                        </form>
                                        <!-- Hapus -->
                                        <button class="btn-icon btn-delete"
                                            onclick="openDeleteModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                        <span style="font-size:0.78rem;color:var(--text-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /tab-kelola-user -->
        <?php endif; ?>

    </div><!-- /setting-wrapper -->
</div><!-- /main-wrapper -->

<!-- ── Modal Reset Password ─── -->
<div class="modal-overlay" id="modal-reset">
    <div class="modal-box">
        <h4><i class="fas fa-key"></i> Reset Password</h4>
        <p id="reset-username-label" style="font-size:0.85rem;color:var(--text-muted);margin-bottom:16px;"></p>
        <form method="post" id="form-reset">
            <input type="hidden" name="action" value="edit_karyawan_password">
            <input type="hidden" name="target_id" id="reset-target-id">
            <div class="form-group" style="margin-bottom:14px;">
                <label>Password Baru</label>
                <input type="password" name="reset_password" placeholder="Min. 6 karakter" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="reset_confirm" placeholder="Ulangi password" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-reset')">Batal</button>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal Hapus Akun ─── -->
<div class="modal-overlay" id="modal-delete">
    <div class="modal-box">
        <h4><i class="fas fa-trash" style="color:#dc2626;"></i> Hapus Akun</h4>
        <p id="delete-username-label" style="font-size:0.875rem;color:var(--text-main);margin-bottom:8px;"></p>
        <p style="font-size:0.82rem;color:var(--text-muted);">Aksi ini tidak dapat dibatalkan. Yakin ingin melanjutkan?</p>
        <form method="post" id="form-delete">
            <input type="hidden" name="action" value="delete_karyawan">
            <input type="hidden" name="target_id" id="delete-target-id">
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-delete')">Batal</button>
                <button type="submit" class="btn-danger"><i class="fas fa-trash"></i> Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
// Sidebar toggle
const hamburger = document.getElementById('hamburger');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('overlay');
function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('active'); hamburger.classList.add('open'); document.body.style.overflow='hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); hamburger.classList.remove('open'); document.body.style.overflow=''; }
hamburger.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
document.addEventListener('keydown', e => { if(e.key==='Escape') { closeSidebar(); closeModal('modal-reset'); closeModal('modal-delete'); }});

// Tabs
function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}

// Modal helpers
function openResetModal(id, username) {
    document.getElementById('reset-target-id').value = id;
    document.getElementById('reset-username-label').textContent = 'Username: ' + username;
    document.getElementById('modal-reset').classList.add('show');
}
function openDeleteModal(id, username) {
    document.getElementById('delete-target-id').value = id;
    document.getElementById('delete-username-label').innerHTML = 'Anda akan menghapus akun <strong>' + username + '</strong>.';
    document.getElementById('modal-delete').classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// Open tab based on hash
<?php if ($msg && isset($_POST['action']) && in_array($_POST['action'], ['add_karyawan','edit_karyawan_password','delete_karyawan','toggle_status'])): ?>
window.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelectorAll('.tab-btn')[1];
    if(btn) switchTab('tab-kelola-user', btn);
});
<?php endif; ?>
</script>
</body>
</html>
