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



$filter_dari   = $_GET['dari']   ?? '';
$filter_sampai = $_GET['sampai'] ?? '';

$where  = "";
$params = [];

if ($filter_dari && $filter_sampai) {
    $where  = "WHERE bm.tanggal_masuk BETWEEN ? AND ?";
    $params = [$filter_dari, $filter_sampai];
}

$sql = "
SELECT bm.id_masuk, bm.tanggal_masuk, s.nama_supplier,
       ib.nama_barang, dbm.jumlah, dbm.harga_beli,
       (dbm.jumlah * dbm.harga_beli) AS total
FROM barang_masuk bm
JOIN detail_barang_masuk dbm ON bm.id_masuk = dbm.id_masuk
JOIN inventori_barang ib ON dbm.id_barang = ib.id_barang
LEFT JOIN supplier s ON bm.id_supplier = s.id_supplier
$where
ORDER BY bm.tanggal_masuk DESC
";

$stmt = mysqli_prepare($conn, $sql);
if ($params) { mysqli_stmt_bind_param($stmt, 'ss', $params[0], $params[1]); }
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$grand_total = 0;
$rows = [];
while ($data = mysqli_fetch_assoc($result)) {
    $grand_total += $data['total'];
    $rows[] = $data;
}

$tampil_dari   = $filter_dari   ? date('d/m/Y', strtotime($filter_dari))   : '';
$tampil_sampai = $filter_sampai ? date('d/m/Y', strtotime($filter_sampai)) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembelian</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="laporan_barangmasuk.css">
    <link rel="stylesheet" href="responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="sidebar" id="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <a href="dashboard_admin.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <h3>REPORT</h3>
    <a href="laporan_barangmasuk.php" class="active"><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a>
    <a href="laporan_barangkeluar.php"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="hamburger" id="hamburger" aria-label="Menu"><span></span></button>
            <span><i class="fa fa-file-invoice"></i> LAPORAN PEMBELIAN</span>
        </div>
        <button class="btn-print" onclick="window.print()"><i class="fa fa-print"></i> Cetak</button>
        <div style="position:relative;margin-left:auto;">
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

    <div class="card">
        <div class="filter-form">
            <div>
                <label>Dari Tanggal</label>
                <input type="text" class="input-tgl" id="dari" placeholder="dd/mm/yyyy" maxlength="10"
                       value="<?= htmlspecialchars($tampil_dari) ?>" oninput="formatTanggal(this)">
                <span class="tgl-hint">contoh: 01/01/2025</span>
            </div>
            <div>
                <label>Sampai Tanggal</label>
                <input type="text" class="input-tgl" id="sampai" placeholder="dd/mm/yyyy" maxlength="10"
                       value="<?= htmlspecialchars($tampil_sampai) ?>" oninput="formatTanggal(this)">
                <span class="tgl-hint">contoh: 31/12/2025</span>
            </div>
            <button onclick="filterData()"><i class="fas fa-search"></i> Filter</button>
            <a href="laporan_barangmasuk.php" style="width:auto;">
                <button type="button"><i class="fas fa-undo"></i> Reset</button>
            </a>
            <?php if ($filter_dari && $filter_sampai): ?>
                <div class="filter-info">
                    <i class="fas fa-filter"></i>
                    <?= date('d/m/Y', strtotime($filter_dari)) ?> &ndash; <?= date('d/m/Y', strtotime($filter_sampai)) ?>
                    &nbsp;(<?= count($rows) ?> data)
                </div>
            <?php endif; ?>
        </div>

        <div class="card-header">
            <h3><i class="fa fa-history"></i> Riwayat Barang Masuk</h3>
        </div>

        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
            <table class="table-report">
                <thead>
                    <tr>
                        <th>No</th><th>ID Transaksi</th><th>Tanggal</th><th>Supplier</th>
                        <th>Barang</th><th>Jumlah</th><th>Harga Beli</th><th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                Tidak ada data<?= ($filter_dari && $filter_sampai) ? ' pada rentang tanggal tersebut' : '' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $no => $data): ?>
                        <tr>
                            <td><?= $no + 1 ?></td>
                            <td>#<?= htmlspecialchars($data['id_masuk']) ?></td>
                            <td><?= date('d/m/Y', strtotime($data['tanggal_masuk'])) ?></td>
                            <td><?= htmlspecialchars($data['nama_supplier'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($data['nama_barang']) ?></td>
                            <td><?= $data['jumlah'] ?></td>
                            <td>Rp <?= number_format($data['harga_beli'], 0, ',', '.') ?></td>
                            <td><strong>Rp <?= number_format($data['total'], 0, ',', '.') ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="grand-total">
                            <td colspan="7" style="text-align:right;padding-right:16px;">GRAND TOTAL</td>
                            <td>Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- <script>
const hamburger = document.getElementById('hamburger');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('overlay');

function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('active'); hamburger.classList.add('open'); document.body.style.overflow='hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); hamburger.classList.remove('open'); document.body.style.overflow=''; }

hamburger.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
document.addEventListener('keydown', e => { if (e.key==='Escape') closeSidebar(); });
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


function formatTanggal(input) {
    let val = input.value.replace(/\D/g, '');
    if (val.length >= 3 && val.length <= 4)      val = val.slice(0,2) + '/' + val.slice(2);
    else if (val.length >= 5) val = val.slice(0,2) + '/' + val.slice(2,4) + '/' + val.slice(4,8);
    input.value = val;
    input.classList.remove('error');
}

function parseDate(str) {
    const parts = str.split('/');
    if (parts.length !== 3) return null;
    const [dd, mm, yyyy] = parts;
    if (dd.length !== 2 || mm.length !== 2 || yyyy.length !== 4) return null;
    const d = new Date(`${yyyy}-${mm}-${dd}`);
    if (isNaN(d.getTime())) return null;
    return `${yyyy}-${mm}-${dd}`;
}

function filterData() {
    const dariInput   = document.getElementById('dari');
    const sampaiInput = document.getElementById('sampai');
    const dari   = parseDate(dariInput.value);
    const sampai = parseDate(sampaiInput.value);
    let valid = true;
    if (!dari)   { dariInput.classList.add('error');   dariInput.focus();   valid = false; }
    if (!sampai) { sampaiInput.classList.add('error'); if (valid) sampaiInput.focus(); valid = false; }
    if (!valid) { alert('Format tanggal tidak valid. Gunakan format dd/mm/yyyy'); return; }
    if (dari > sampai) { alert('Tanggal "Dari" tidak boleh lebih besar dari "Sampai"'); dariInput.classList.add('error'); return; }
    window.location.href = `laporan_barangmasuk.php?dari=${dari}&sampai=${sampai}`;
}

document.getElementById('dari').addEventListener('keydown', e => { if(e.key==='Enter') filterData(); });
document.getElementById('sampai').addEventListener('keydown', e => { if(e.key==='Enter') filterData(); });
</script> -->
</body>
</html>
