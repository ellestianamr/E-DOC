<?php
/**
 * ============================================================
 * HALAMAN: PENCARIAN DOKUMEN BERDASARKAN ISI
 * ============================================================
 * Alur halaman ini:
 *   1. user mengetik kata kunci
 *   2. kata kunci dikirim ke service Python (POST /search)
 *   3. service melakukan preprocessing kata kunci dengan pipeline
 *      yang sama seperti saat dokumen di-index
 *   4. service menghitung TF-IDF + cosine similarity, lalu
 *      mengembalikan daftar dokumen beserta skornya
 *   5. halaman ini menampilkannya dalam bentuk tabel
 *
 * Hak akses mengikuti aturan lama:
 *   - admin        -> boleh mencari di semua bidang
 *   - user bidang  -> hanya dokumen bidangnya sendiri
 */

include_once("pages/process/nlp_client.php");

$role = $_SESSION['role_name'] ?? '';
$is_admin = ($role === 'admin');

// Daftar bidang yang dipakai aplikasi (sama dengan menu lama)
$list_bidang = ['SEKRETARIAT', 'INDUSTRI', 'HUBINSYAKER', 'PPTK'];

// ---- Tentukan bidang yang boleh dicari ----
if ($is_admin) {
    // Admin boleh memilih. Kosong = semua bidang.
    $bidang_filter = isset($_GET['bidang']) ? strtoupper(trim($_GET['bidang'])) : '';
    if ($bidang_filter !== '' && !in_array($bidang_filter, $list_bidang)) {
        $bidang_filter = ''; // nilai aneh dari URL diabaikan
    }
} else {
    // User bidang DIKUNCI ke bidangnya sendiri, tidak bisa diubah lewat URL.
    $bidang_filter = strtoupper($role);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

$hasil          = [];
$tahapan_query  = null;
$info_hitung    = null;
$pesan_error    = '';
$sudah_mencari  = ($query !== '');

// ---- Cek dulu service Python hidup atau tidak ----
$status  = nlp_status();
$service_aktif = $status['ok'];
$statistik = $service_aktif ? ($status['data']['statistik'] ?? []) : [];

if ($sudah_mencari && $service_aktif) {
    $resp = nlp_search($query, $bidang_filter !== '' ? $bidang_filter : null, 50);
    if ($resp['ok']) {
        $hasil         = $resp['data']['hasil'] ?? [];
        $tahapan_query = $resp['data']['preprocessing'] ?? null;
        $info_hitung   = $resp['data']['info_perhitungan'] ?? null;
    } else {
        $pesan_error = $resp['error'];
    }
}
?>

<style>
  .cari-box { background:#fff; border-top:3px solid #145885; padding:20px; margin-bottom:15px; box-shadow:0 1px 1px rgba(0,0,0,0.1); }
  .hasil-table thead th { background:#145885; color:#fff; vertical-align:middle; }
  .hasil-table tbody td { vertical-align:middle; }
  .skor-bar { background:#e9ecef; border-radius:10px; height:18px; width:110px; overflow:hidden; display:inline-block; vertical-align:middle; }
  .skor-isi { background:#145885; height:100%; }
  .skor-angka { font-weight:bold; margin-left:6px; vertical-align:middle; }
  .tag-term { display:inline-block; background:#eaf2f8; color:#145885; border:1px solid #cfe2f0;
              border-radius:12px; padding:1px 9px; margin:2px 3px 2px 0; font-size:12px; }
  .action-icon { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px;
                 border-radius:6px; text-decoration:none; color:#fff; margin:0 3px; font-size:14px; }
  .action-view { background:rgba(0,0,0,0.15); }
  .action-download { background:rgba(20,88,133,0.9); }
  .tahap-pipeline { font-size:13px; }
  .tahap-pipeline td { padding:4px 8px; border-bottom:1px solid #f0f0f0; }
  .tahap-pipeline td:first-child { width:190px; color:#777; white-space:nowrap; }
  #excel-viewer-container table { border-collapse:collapse; width:100%; font-size:14px; font-family:Arial, sans-serif; }
  #excel-viewer-container th, #excel-viewer-container td { border:1px solid #ddd; padding:6px 10px; }
  #excel-viewer-container th { background:#f1f1f1; font-weight:bold; text-align:left; }
  #excel-viewer-container tr:nth-child(even) { background:#fafafa; }
</style>

<!-- ============ KOTAK PENCARIAN ============ -->
<div class="cari-box">
  <form method="GET" action="index.php">
    <input type="hidden" name="page" value="pencarian">

    <div class="row">
      <div class="<?= $is_admin ? 'col-md-7' : 'col-md-9' ?>">
        <div class="form-group" style="margin-bottom:8px;">
          <label>Kata Kunci</label>
          <input type="text" name="q" class="form-control"
                 placeholder="contoh: melatih pekerja"
                 value="<?= htmlspecialchars($query) ?>" autofocus>
        </div>
      </div>

      <?php if ($is_admin): ?>
      <div class="col-md-3">
        <div class="form-group" style="margin-bottom:8px;">
          <label>Bidang</label>
          <select name="bidang" class="form-control">
            <option value="">Semua Bidang</option>
            <?php foreach ($list_bidang as $b): ?>
              <option value="<?= $b ?>" <?= ($bidang_filter === $b) ? 'selected' : '' ?>><?= $b ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php endif; ?>

      <div class="col-md-2">
        <div class="form-group" style="margin-bottom:8px;">
          <label style="visibility:hidden;">Cari</label>
          <button type="submit" class="btn btn-primary btn-block">
            <i class="fa fa-search"></i> Cari
          </button>
        </div>
      </div>
    </div>

    <p class="text-muted" style="margin:6px 0 0 0;">
      <i class="fa fa-info-circle"></i>
      Pencarian membaca <b>isi file Excel</b>, bukan hanya judulnya.
      Kata kunci dicocokkan dalam <b>bentuk dasar</b>, jadi
      <i>"melatih pekerja"</i> tetap menemukan dokumen <i>"Rekap Pelatihan Kerja"</i>.
      <?php if (!$is_admin): ?>
        <br><i class="fa fa-lock"></i>
        Anda hanya dapat mencari dokumen bidang <b><?= htmlspecialchars($bidang_filter) ?></b>.
      <?php endif; ?>
    </p>
  </form>
</div>

<!-- ============ PERINGATAN SERVICE MATI ============ -->
<?php if (!$service_aktif): ?>
  <div class="alert alert-warning">
    <h4><i class="fa fa-warning"></i> Service pencarian belum aktif</h4>
    <p style="margin-bottom:6px;"><?= htmlspecialchars($status['error']) ?></p>
    <p style="margin-bottom:0;">
      Cara menyalakan: buka folder <code>nlp_service</code> lalu klik dua kali
      <code>start_service.bat</code>. Biarkan jendelanya tetap terbuka,
      kemudian muat ulang halaman ini.
    </p>
  </div>
<?php elseif (!empty($statistik) && ($statistik['dokumen_terindex'] ?? 0) == 0): ?>
  <div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    Belum ada dokumen yang ter-index. Jalankan
    <code>venv\Scripts\python reindex.py</code> di folder <code>nlp_service</code>.
  </div>
<?php endif; ?>

<?php if ($pesan_error): ?>
  <div class="alert alert-danger">
    <i class="fa fa-times-circle"></i> <?= htmlspecialchars($pesan_error) ?>
  </div>
<?php endif; ?>

<!-- ============ RINGKASAN PROSES KATA KUNCI ============ -->
<?php if ($sudah_mencari && $tahapan_query): ?>
<div class="box box-solid">
  <div class="box-header with-border" style="cursor:pointer;" data-widget="collapse" onclick="$(this).next().slideToggle()">
    <h3 class="box-title" style="font-size:15px;">
      <i class="fa fa-cogs"></i> Kata kunci Anda setelah diproses
    </h3>
  </div>
  <div class="box-body">
    <table class="tahap-pipeline" style="width:100%;">
      <tr>
        <td>Kata kunci asli</td>
        <td><?= htmlspecialchars($tahapan_query['teks_asli']) ?></td>
      </tr>
      <tr>
        <td>Setelah cleaning</td>
        <td><?= htmlspecialchars($tahapan_query['cleaning']) ?></td>
      </tr>
      <tr>
        <td>Setelah stopword removal</td>
        <td><?= htmlspecialchars(implode(', ', $tahapan_query['stopword_removal'])) ?></td>
      </tr>
      <tr>
        <td><b>Kata dasar yang dicari</b></td>
        <td>
          <?php foreach ($tahapan_query['stemming_final'] as $t): ?>
            <span class="tag-term"><?= htmlspecialchars($t) ?></span>
          <?php endforeach; ?>
          <?php if (empty($tahapan_query['stemming_final'])): ?>
            <span class="text-muted">(kosong - semua kata kunci terbuang di tahap stopword/angka)</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php if ($info_hitung && !empty($info_hitung['df_query'])): ?>
      <tr>
        <td>Muncul di berapa dokumen</td>
        <td>
          <?php foreach ($info_hitung['df_query'] as $term => $df): ?>
            <span class="tag-term"><?= htmlspecialchars($term) ?>: <?= (int)$df ?> dok</span>
          <?php endforeach; ?>
          <small class="text-muted">
            &nbsp;(dari <?= (int)($info_hitung['jumlah_dokumen_terindex'] ?? 0) ?> dokumen ter-index -
            makin jarang muncul, makin besar bobotnya)
          </small>
        </td>
      </tr>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ============ HASIL PENCARIAN ============ -->
<?php if ($sudah_mencari && $service_aktif && !$pesan_error): ?>
<div class="box">
  <div class="box-header with-border">
    <h3 class="box-title">
      Hasil Pencarian
      <small>&mdash; <?= count($hasil) ?> dokumen ditemukan untuk
        "<b><?= htmlspecialchars($query) ?></b>"</small>
    </h3>
  </div>
  <div class="box-body table-responsive no-padding">
    <table class="table table-hover hasil-table">
      <thead>
        <tr>
          <th style="width:45px;">No</th>
          <th>Dokumen</th>
          <th style="width:130px;">Bidang</th>
          <th style="width:190px;">Skor Relevansi</th>
          <th>Kata yang Cocok</th>
          <th style="width:120px; text-align:center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($hasil) > 0): ?>
          <?php $no = 1; foreach ($hasil as $h):
            $path_file = strtolower($h['bidang']) . '/' . $h['nama_file'];
          ?>
          <tr>
            <td><?= $no++ ?></td>
            <td>
              <b><?= htmlspecialchars($h['nama_dokumen']) ?></b><br>
              <small class="text-muted">
                <?= htmlspecialchars($h['ukuran_file']) ?> &middot;
                diperbarui <?= $h['updated_at'] ? date('d/m/Y', strtotime($h['updated_at'])) : '-' ?>
              </small>
            </td>
            <td><?= htmlspecialchars($h['bidang']) ?></td>
            <td>
              <span class="skor-bar">
                <span class="skor-isi" style="width:<?= min(100, (float)$h['persen']) ?>%;"></span>
              </span>
              <span class="skor-angka"><?= number_format((float)$h['persen'], 2) ?>%</span>
            </td>
            <td>
              <?php foreach (array_slice($h['term_cocok'], 0, 6) as $t): ?>
                <span class="tag-term"
                      title="muncul <?= (int)$t['tf'] ?> kali di dokumen ini"><?= htmlspecialchars($t['term']) ?></span>
              <?php endforeach; ?>
            </td>
            <td style="text-align:center;">
              <a href="javascript:void(0)" class="action-icon action-download"
                 title="Download"
                 onclick="downloadFileCari('<?= htmlspecialchars($path_file, ENT_QUOTES) ?>')">
                <i class="fa fa-download"></i>
              </a>
              <a href="javascript:void(0)" class="action-icon action-view"
                 title="Lihat isi"
                 onclick="viewExcelCari('<?= htmlspecialchars($path_file, ENT_QUOTES) ?>')">
                <i class="fa fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align:center; color:#888; padding:25px;">
              Tidak ada dokumen yang cocok dengan kata kunci tersebut.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php elseif (!$sudah_mencari): ?>
  <div class="box">
    <div class="box-body" style="text-align:center; padding:40px; color:#888;">
      <i class="fa fa-search" style="font-size:42px; color:#ddd;"></i>
      <p style="margin-top:12px;">Ketik kata kunci di atas untuk mencari dokumen berdasarkan isinya.</p>
      <?php if (!empty($statistik)): ?>
        <small><?= (int)($statistik['dokumen_terindex'] ?? 0) ?> dokumen siap dicari
          &middot; <?= (int)($statistik['term_unik'] ?? 0) ?> kata dasar dalam index</small>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- ============ POPUP LIHAT ISI EXCEL ============ -->
<div id="popupCari" style="display:none; position:fixed; top:50%; left:50%;
     transform:translate(-50%,-50%); width:80%; height:80vh; background:white;
     border-radius:10px; box-shadow:0 8px 30px rgba(0,0,0,0.4); z-index:9999;">
  <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #ddd;">
    <h4 id="popupCariTitle" style="margin:0;">Pratinjau Dokumen</h4>
    <button onclick="document.getElementById('popupCari').style.display='none'"
            style="background:#dc3545; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer;">
      &times; Tutup
    </button>
  </div>
  <div id="popupCariContent" style="width:100%; height:calc(100% - 50px); overflow:auto; padding:10px;"></div>
</div>

<script>
  // Download file hasil pencarian
  function downloadFileCari(fileName) {
    const namaDokumen = fileName.split('/').pop();
    Swal.fire({
      title: 'Download dokumen?',
      text: 'Klik OK untuk mulai mengunduh ' + namaDokumen,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'OK',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        const link = document.createElement('a');
        link.href = 'uploads/' + fileName;
        link.download = namaDokumen;
        document.body.appendChild(link);
        link.click();
        link.remove();
      }
    });
  }

  // Tampilkan isi Excel di popup (memakai library XLSX yang sudah dimuat script.php)
  async function viewExcelCari(fileName) {
    const namaDokumen = fileName.split('/').pop();
    document.getElementById('popupCariTitle').innerText = namaDokumen;
    document.getElementById('popupCariContent').innerHTML = '<p>Sedang memuat...</p>';
    document.getElementById('popupCari').style.display = 'block';

    try {
      const response = await fetch('uploads/' + fileName);
      if (!response.ok) throw new Error('Gagal mengambil file Excel');
      const arrayBuffer = await response.arrayBuffer();

      const workbook = XLSX.read(arrayBuffer, { type: 'array' });
      const sheet = workbook.Sheets[workbook.SheetNames[0]];
      const html = XLSX.utils.sheet_to_html(sheet);

      document.getElementById('popupCariContent').innerHTML =
        '<div id="excel-viewer-container" style="max-height:100%;overflow:auto;">' + html + '</div>';
    } catch (e) {
      console.error(e);
      document.getElementById('popupCariContent').innerHTML =
        '<p style="color:red;">Gagal menampilkan file Excel</p>';
    }
  }
</script>
