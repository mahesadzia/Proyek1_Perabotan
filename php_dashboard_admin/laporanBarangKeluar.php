<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include "konek.php";

$filter_dari   = $_GET['dari']   ?? '';
$filter_sampai = $_GET['sampai'] ?? '';
$where  = "";
$params = [];

if ($filter_dari && $filter_sampai) {
    $where  = "WHERE bk.tanggal BETWEEN ? AND ?";
    $params = [$filter_dari, $filter_sampai];
}

// Query langsung dari barang_keluar (tanpa join detail_barang_keluar)
$sql = "
    SELECT bk.id_keluar, bk.tanggal, ib.nama_barang,
           bk.jumlah, ib.harga_jual AS harga, bk.total
    FROM barang_keluar bk
    JOIN inventori_barang ib ON bk.id_barang = ib.id_barang
    $where
    ORDER BY bk.id_keluar DESC
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
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Barang Keluar</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="laporanBarangKeluar.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 12px;
            padding: 18px 24px 16px;
            border-bottom: 1px solid var(--border);
        }
        .filter-form label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .input-tgl {
            padding: 8px 12px;
            border: 1.5px solid var(--border);
            border-radius: 7px;
            font-family: 'DM Mono', monospace;
            font-size: 0.875rem;
            color: var(--text-main);
            background: #fff;
            width: 150px;
            transition: border-color 0.2s;
            outline: none;
        }
        .input-tgl:focus { border-color: var(--primary); }
        .input-tgl.error { border-color: #ef4444; }
        .tgl-hint {
            display: block;
            font-size: 0.68rem;
            color: #a0aec0;
            margin-top: 3px;
        }
        .filter-form button,
        .filter-form a button {
            padding: 9px 18px;
            border: none;
            border-radius: 7px;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--primary);
            color: #fff;
            transition: opacity 0.2s;
        }
        .filter-form button:hover,
        .filter-form a button:hover { opacity: 0.88; }
        .filter-form a button {
            background: #e2e8f0;
            color: var(--text-main);
        }
        .filter-info {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .grand-total td {
            background: #eef4ff;
            font-weight: 700;
            color: var(--primary);
            font-size: 0.9rem;
            border-top: 2px solid var(--primary);
        }
        .no-data {
            text-align: center;
            padding: 40px 16px !important;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        /* ── PRINT ─────────────────── */
        .print-header { display: none; }
        @media print {
            .sidebar, .sidebar-overlay,
            .filter-form, .btn,
            .hamburger, .page-title,
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .main { margin-left: 0 !important; }
            header {
                position: static !important;
                box-shadow: none !important;
                border-bottom: 2px solid #000 !important;
                padding: 10px 0 !important;
            }
            .card { margin: 0 !important; box-shadow: none !important; border: none !important; }
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 16px;
                padding-bottom: 10px;
                border-bottom: 2px solid #1565c0;
            }
            .print-header h2 { font-size: 1.1rem; font-weight: 700; color: #1565c0; }
            .print-header p  { font-size: 0.8rem; color: #555; margin-top: 4px; }
            table { font-size: 0.78rem !important; }
            th, td { padding: 8px 10px !important; }
            tbody tr:hover { background: transparent !important; }
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
    <a href="laporanBarangKeluar.php" class="active"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <header>
        <div class="header-title" style="display:flex;align-items:center;gap:10px;">
            <button class="hamburger" id="hamburger" aria-label="Menu"><span></span></button>
            <span><i class="fas fa-chart-bar"></i> LAPORAN BARANG KELUAR</span>
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

    <div class="page-title"><h1>Laporan Barang Keluar</h1></div>

    <div class="card">
        <!-- Hanya tampil saat cetak -->
        <div class="print-header">
            <h2><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</h2>
            <p>
                <?php if ($filter_dari && $filter_sampai): ?>
                    Periode: <?= date('d/m/Y', strtotime($filter_dari)) ?> &ndash; <?= date('d/m/Y', strtotime($filter_sampai)) ?>
                <?php else: ?>
                    Semua Periode
                <?php endif; ?>
                &bull; Dicetak oleh: <?= htmlspecialchars($_SESSION['username']) ?>
                &bull; <?= date('d/m/Y H:i') ?>
            </p>
        </div>

        <!-- Filter Form -->
        <div class="filter-form no-print">
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
            <a href="laporanBarangKeluar.php" style="text-decoration:none;">
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

        <!-- Card Header + Tombol -->
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-bar" style="color:#1565c0;margin-right:7px;"></i> Data Barang Keluar
            </h3>
            <div style="display:flex;gap:8px;" class="no-print">
                <button class="btn btn-blue" onclick="window.print()">
                    <i class="fa fa-print"></i> Cetak
                </button>
                <button class="btn btn-green" onclick="exportExcel()">
                    <i class="fa fa-file-excel"></i> Excel
                </button>
            </div>
        </div>

        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
            <table id="tabel-keluar">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Invoice</th>
                        <th>Tanggal</th>
                        <th>Item Jual</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Total</th>
                        <th class="no-print">Status</th>
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
                        <?php foreach ($rows as $no => $row): ?>
                        <tr>
                            <td><?= $no + 1 ?></td>
                            <td style="font-family:'DM Mono',monospace;font-size:0.82rem;color:#1565c0;">
                                INV-<?= str_pad($row['id_keluar'], 4, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td style="font-family:'DM Mono',monospace;font-size:0.82rem;color:#718096;">
                                <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                            </td>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                            <td><?= $row['jumlah'] ?></td>
                            <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                            <td><strong>Rp <?= number_format($row['total'], 0, ',', '.') ?></strong></td>
                            <td class="no-print"><span class="status lunas">Lunas</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="grand-total">
                            <td colspan="6" style="text-align:right;padding-right:16px;">GRAND TOTAL</td>
                            <td colspan="2">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
/* ─── Sidebar ─────────────────────────────── */
const hamburger = document.getElementById('hamburger');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('overlay');

function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('active'); hamburger.classList.add('open'); document.body.style.overflow='hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); hamburger.classList.remove('open'); document.body.style.overflow=''; }

hamburger.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
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


/* ─── Auto Format Tanggal ─────────────────── */
function formatTanggal(input) {
    let val = input.value.replace(/\D/g, '');
    if (val.length >= 3 && val.length <= 4)
        val = val.slice(0,2) + '/' + val.slice(2);
    else if (val.length >= 5)
        val = val.slice(0,2) + '/' + val.slice(2,4) + '/' + val.slice(4,8);
    input.value = val;
    input.classList.remove('error');
}

/* ─── Parse Tanggal ───────────────────────── */
function parseDate(str) {
    const parts = str.split('/');
    if (parts.length !== 3) return null;
    const [dd, mm, yyyy] = parts;
    if (dd.length !== 2 || mm.length !== 2 || yyyy.length !== 4) return null;
    const d = new Date(`${yyyy}-${mm}-${dd}`);
    if (isNaN(d.getTime())) return null;
    return `${yyyy}-${mm}-${dd}`;
}

/* ─── Filter ──────────────────────────────── */
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

    window.location.href = `laporanBarangKeluar.php?dari=${dari}&sampai=${sampai}`;
}

document.getElementById('dari').addEventListener('keydown', e => { if (e.key === 'Enter') filterData(); });
document.getElementById('sampai').addEventListener('keydown', e => { if (e.key === 'Enter') filterData(); });

/* ─── Export Excel ────────────────────────── */
function exportExcel() {
    const table = document.getElementById('tabel-keluar');
    const rows  = table.querySelectorAll('tr');
    let csv = "\uFEFF";

    rows.forEach(row => {
        const cells = row.querySelectorAll('th:not(.no-print), td:not(.no-print)');
        const rowData = Array.from(cells).map(cell => {
            let text = cell.innerText.replace(/\n/g, ' ').trim();
            if (text.includes(',') || text.includes('"'))
                text = '"' + text.replace(/"/g, '""') + '"';
            return text;
        });
        csv += rowData.join(',') + '\r\n';
    });

    const dari   = document.getElementById('dari').value;
    const sampai = document.getElementById('sampai').value;
    const label  = (dari && sampai) ? `_${dari.replace(/\//g,'-')}_sd_${sampai.replace(/\//g,'-')}` : '';

    const link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    link.download = `Laporan_Barang_Keluar${label}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
</body>
</html>
