<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
include 'konek.php';

// ─── Data user yang sedang login ──────────────────────────────────────────────
$uid = $_SESSION['user_id'];
$q_user = $conn->prepare("SELECT username, email, role, status, last_login, created_at FROM users WHERE id = ?");
$q_user->bind_param("i", $uid);
$q_user->execute();
$user_data = $q_user->get_result()->fetch_assoc();
$q_user->close();
$inisial = strtoupper(substr($user_data['username'] ?? 'A', 0, 1));



$notif = null; // ['type' => 'success|danger|warning', 'msg' => '...']

/* ─── SIMPAN ──────────────────────────────── */
if (isset($_POST['simpan'])) {
    $tanggal   = $_POST['tanggal'];
    $id_barang = (int) $_POST['id_barang'];
    $jumlah    = (int) $_POST['jumlah'];

    if ($jumlah <= 0) {
        $notif = ['type' => 'warning', 'msg' => 'Jumlah harus lebih dari 0.'];
    } else {
        // Ambil data barang dengan prepared statement
        $stmt = $conn->prepare("SELECT harga_jual, stok, nama_barang FROM inventori_barang WHERE id_barang = ?");
        $stmt->bind_param("i", $id_barang);
        $stmt->execute();
        $barang = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$barang) {
            $notif = ['type' => 'danger', 'msg' => 'Barang tidak ditemukan.'];
        } elseif ($jumlah > $barang['stok']) {
            $notif = ['type' => 'warning', 'msg' => "Stok tidak cukup! Stok tersedia: {$barang['stok']} unit."];
        } else {
            $total = $jumlah * $barang['harga_jual'];

            // Insert ke barang_keluar
            $ins = $conn->prepare("INSERT INTO barang_keluar (tanggal, id_barang, jumlah, total) VALUES (?, ?, ?, ?)");
            $ins->bind_param("siid", $tanggal, $id_barang, $jumlah, $total);

            // Kurangi stok
            $upd = $conn->prepare("UPDATE inventori_barang SET stok = stok - ? WHERE id_barang = ?");
            $upd->bind_param("ii", $jumlah, $id_barang);

            if ($ins->execute() && $upd->execute()) {
                $notif = ['type' => 'success', 'msg' => "Transaksi berhasil! <strong>{$barang['nama_barang']}</strong> x{$jumlah} — Rp " . number_format($total, 0, ',', '.')];
            } else {
                $notif = ['type' => 'danger', 'msg' => 'Gagal menyimpan transaksi. Coba lagi.'];
            }
            $ins->close(); $upd->close();
        }
    }
}

/* ─── HAPUS ───────────────────────────────── */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    $stmt = $conn->prepare("SELECT id_barang, jumlah FROM barang_keluar WHERE id_keluar = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        $upd = $conn->prepare("UPDATE inventori_barang SET stok = stok + ? WHERE id_barang = ?");
        $upd->bind_param("ii", $data['jumlah'], $data['id_barang']);
        $upd->execute(); $upd->close();

        $del = $conn->prepare("DELETE FROM barang_keluar WHERE id_keluar = ?");
        $del->bind_param("i", $id);
        $del->execute(); $del->close();

        $notif = ['type' => 'success', 'msg' => 'Data dihapus &amp; stok dikembalikan.'];
    }
}

/* ─── DATA UNTUK HALAMAN ──────────────────── */
$res_barang = mysqli_query($conn, "SELECT * FROM inventori_barang ORDER BY nama_barang ASC");

$res_riwayat = mysqli_query($conn, "
    SELECT bk.id_keluar, bk.tanggal, bk.jumlah, bk.total, ib.nama_barang, ib.harga_jual
    FROM barang_keluar bk
    JOIN inventori_barang ib ON bk.id_barang = ib.id_barang
    ORDER BY bk.id_keluar DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Keluar - Sistem Inventaris</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="barang_keluar.css">
    <link rel="stylesheet" href="responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<!-- ─── SIDEBAR ─────────────────────────── -->
<div class="sidebar" id="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php" class="active"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <h3>REPORT</h3>
    <a href="laporan_barangmasuk.php"><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a>
    <a href="laporan_barangkeluar.php"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- ─── MAIN ─────────────────────────────── -->
<div class="main-wrapper">

    <header>
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="hamburger" id="hamburger" aria-label="Menu"><span></span></button>
            <span><i class="fas fa-file-export"></i> BARANG KELUAR</span>
        </div>
        <div style="position:relative;">
            <button class="admin-header-btn" id="adminPopupBtn" onclick="toggleAdminPopup()" aria-haspopup="true">
                <div class="avatar-circle"><?php echo $inisial; ?></div>
                <span style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($user_data['username']); ?></span>
                <i class="fas fa-chevron-down chevron-icon"></i>
            </button>
            <div class="admin-popup" id="adminPopup">
                <div class="popup-header">
                    <div class="popup-avatar-large"><?php echo $inisial; ?></div>
                    <div class="popup-header-info">
                        <div class="popup-name"><?php echo htmlspecialchars($user_data['username']); ?></div>
                        <div class="popup-role-badge">
                            <i class="fas fa-shield-alt"></i>
                            <?php echo ucfirst($user_data['role'] ?? 'admin'); ?>
                        </div>
                    </div>
                </div>
                <div class="popup-body">
                    <div class="popup-row">
                        <i class="fas fa-envelope"></i>
                        <span class="popup-row-val"><?php echo htmlspecialchars($user_data['email'] ?? '-'); ?></span>
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-circle" style="color:#48bb78;font-size:0.6rem;"></i>
                        <span>Status:&nbsp;</span>
                        <span class="popup-row-val"><span class="status-dot"></span><?php echo ucfirst($user_data['status'] ?? 'active'); ?></span>
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-clock"></i>
                        <span>Login terakhir:&nbsp;</span>
                        <span class="popup-row-val">
                            <?php echo $user_data['last_login'] ? date('d M Y H:i', strtotime($user_data['last_login'])) : '-'; ?>
                        </span>
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Bergabung:&nbsp;</span>
                        <span class="popup-row-val">
                            <?php echo $user_data['created_at'] ? date('d M Y', strtotime($user_data['created_at'])) : '-'; ?>
                        </span>
                    </div>
                    <div class="popup-divider"></div>
                </div>
                <div class="popup-footer">
                    <a href="logout.php" class="popup-logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Keluar dari Akun
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Notifikasi -->
    <?php if ($notif): ?>
    <div class="alert alert-<?= $notif['type'] ?>">
        <i class="fas fa-<?= $notif['type']==='success' ? 'check-circle' : ($notif['type']==='warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
        <?= $notif['msg'] ?>
    </div>
    <?php endif; ?>

    <!-- ─── FORM INPUT ──────────────────── -->
    <div class="content-card">
        <h3><i class="fas fa-plus-circle"></i> Input Penjualan / Barang Keluar</h3>

        <form method="POST" id="formKeluar">
            <div class="form-grid">

                <div>
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal"
                           value="<?= date('Y-m-d') ?>" required>
                </div>

                <div>
                    <label>Pilih Barang</label>
                    <select name="id_barang" id="id_barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php
                        $barang_list = [];
                        while ($b = mysqli_fetch_assoc($res_barang)):
                            $barang_list[$b['id_barang']] = $b;
                        ?>
                        <option value="<?= $b['id_barang'] ?>"
                                data-harga="<?= $b['harga_jual'] ?>"
                                data-stok="<?= $b['stok'] ?>"
                                data-nama="<?= htmlspecialchars($b['nama_barang']) ?>">
                            <?= htmlspecialchars($b['nama_barang']) ?>
                            &nbsp;|&nbsp; Stok: <?= $b['stok'] ?>
                            &nbsp;|&nbsp; Rp <?= number_format($b['harga_jual'], 0, ',', '.') ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="stok-info" id="stokInfo"></div>
                </div>

                <div>
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" id="jumlah" min="1" placeholder="0" required>
                </div>

                <div>
                    <label>Total Harga (otomatis)</label>
                    <input type="text" id="total_display" placeholder="Rp 0"
                           readonly class="input-total">
                    <input type="hidden" name="total_harga" id="total_harga">
                </div>

            </div>

            <button type="submit" name="simpan" class="btn-submit">
                <i class="fas fa-save"></i> Simpan Transaksi
            </button>
        </form>
    </div>

    <!-- ─── TABEL RIWAYAT ───────────────── -->
    <div class="content-card">
        <h3><i class="fas fa-history"></i> Riwayat Penjualan</h3>
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Total</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($res_riwayat)): ?>
                    <tr>
                        <td style="color:var(--text-muted);font-size:0.8rem;"><?= $no++ ?></td>
                        <td style="font-family:'DM Mono',monospace;font-size:0.82rem;color:#718096;">
                            <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                        </td>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td><?= $row['jumlah'] ?></td>
                        <td style="font-family:'DM Mono',monospace;font-size:0.82rem;">
                            Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?>
                        </td>
                        <td class="total-bold">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                        <td style="text-align:center;">
                            <a href="barang_keluar.php?hapus=<?= $row['id_keluar'] ?>"
                               onclick="return confirm('Hapus data ini? Stok akan dikembalikan.')"
                               class="btn-del">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.main-wrapper -->

<script>
/* ─── Hamburger ───────────────────────────── */
const hamburger = document.getElementById('hamburger');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('overlay');

function openSidebar()  {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    hamburger.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    hamburger.classList.remove('open');
    document.body.style.overflow = '';
}

hamburger.addEventListener('click', () =>
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar()
);
overlay.addEventListener('click', closeSidebar);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
function toggleAdminPopup() {
    const btn   = document.getElementById('adminPopupBtn');
    const popup = document.getElementById('adminPopup');
    if (!btn || !popup) return;
    const isOpen = popup.classList.contains('popup-show');
    if (isOpen) {
        popup.classList.remove('popup-show');
        btn.classList.remove('popup-open');
    } else {
        popup.classList.add('popup-show');
        btn.classList.add('popup-open');
    }
}
document.addEventListener('click', function(e) {
    const btn   = document.getElementById('adminPopupBtn');
    const popup = document.getElementById('adminPopup');
    if (btn && popup && !btn.contains(e.target) && !popup.contains(e.target)) {
        popup.classList.remove('popup-show');
        btn.classList.remove('popup-open');
    }
});


/* ─── Hitung Total & Info Stok ────────────── */
const selectBarang = document.getElementById('id_barang');
const inputJumlah  = document.getElementById('jumlah');
const totalDisplay = document.getElementById('total_display');
const totalHarga   = document.getElementById('total_harga');
const stokInfo     = document.getElementById('stokInfo');

function updateUI() {
    const opt    = selectBarang.selectedOptions[0];
    const harga  = parseFloat(opt?.dataset.harga)  || 0;
    const stok   = parseInt(opt?.dataset.stok)     || 0;
    const nama   = opt?.dataset.nama               || '';
    const jumlah = parseInt(inputJumlah.value)     || 0;
    const total  = harga * jumlah;

    // Info stok
    if (nama) {
        if (stok === 0) {
            stokInfo.textContent = `Stok habis!`;
            stokInfo.className   = 'stok-info kritis';
        } else if (stok <= 10) {
            stokInfo.textContent = `Stok tersisa: ${stok} unit (menipis)`;
            stokInfo.className   = 'stok-info menipis';
        } else {
            stokInfo.textContent = `Stok tersisa: ${stok} unit`;
            stokInfo.className   = 'stok-info aman';
        }
        // Batasi max input jumlah sesuai stok
        inputJumlah.max = stok;
    } else {
        stokInfo.textContent = '';
        stokInfo.className   = 'stok-info';
    }

    // Total
    totalHarga.value   = total;
    totalDisplay.value = total > 0
        ? 'Rp ' + total.toLocaleString('id-ID')
        : 'Rp 0';
}

selectBarang.addEventListener('change', updateUI);
inputJumlah.addEventListener('input',  updateUI);

/* ─── Auto-dismiss notif setelah 5 detik ─── */
const alert = document.querySelector('.alert');
if (alert) setTimeout(() => {
    alert.style.transition = 'opacity 0.5s';
    alert.style.opacity    = '0';
    setTimeout(() => alert.remove(), 500);
}, 5000);
</script>

</body>
</html>
