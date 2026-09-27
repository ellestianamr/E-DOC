<?php
/**
 * ============================================================
 * HALAMAN: PROSES PREPROCESSING (untuk demo / presentasi)
 * ============================================================
 * Menampilkan hasil TIAP TAHAP pipeline NLTK secara berurutan:
 *
 *   teks asli
 *      -> 1. case folding      (huruf kecil semua)
 *      -> 2. cleaning          (regex: angka, tanda baca, spasi)
 *      -> 3. tokenizing        (NLTK word_tokenize)
 *      -> 4. stopword removal  (NLTK + daftar tambahan)
 *      -> 5. stemming          (Sastrawi)
 *
 * Sumber teks bisa diketik manual ATAU diambil dari dokumen
 * Excel yang sudah diupload.
 */

include_once("pages/process/nlp_client.php");

$role = $_SESSION['role_name'] ?? '';
$is_admin = ($role === 'admin');

// ---- Daftar dokumen yang boleh dipilih user (mengikuti hak akses) ----
// Dipakai prepared statement sesuai permintaan.
$daftar_dokumen = [];
if ($is_admin) {
    $stmt = $koneksi->prepare(
        "SELECT id, nama_dokumen, bidang, total_term FROM dokumen
         WHERE total_term > 0 ORDER BY bidang ASC, nama_dokumen ASC"
    );
} else {
    $bidang_user = strtoupper($role);
    $stmt = $koneksi->prepare(
        "SELECT id, nama_dokumen, bidang, total_term FROM dokumen
         WHERE total_term > 0 AND bidang = ? ORDER BY nama_dokumen ASC"
    );
    $stmt->bind_param("s", $bidang_user);
}
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $daftar_dokumen[] = $r;
}
$stmt->close();

// ---- Contoh teks bawaan, biar tinggal klik saat presentasi ----
$contoh_teks = 'Rekap Pelatihan Kerja Tahun 2024 (BLK Kota). '
             . 'Jumlah peserta pelatihan menjahit: 30 orang, '
             . 'pelatihan las listrik 25 orang. '
             . 'Seluruh peserta mendapat sertifikat kompetensi kerja!';

// ---- Ambil input dari form ----
$sumber     = $_POST['sumber']     ?? 'teks';
$teks_input = $_POST['teks']       ?? '';
$id_pilih   = isset($_POST['id_dokumen']) ? (int) $_POST['id_dokumen'] : 0;
$diproses   = ($_SERVER['REQUEST_METHOD'] === 'POST');

$hasil       = null;
$pesan_error = '';

$status = nlp_status();
$service_aktif = $status['ok'];

if ($diproses && $service_aktif) {
    if ($sumber === 'dokumen') {
        // Pastikan dokumen yang dipilih memang boleh diakses user ini
        $boleh = false;
        foreach ($daftar_dokumen as $d) {
            if ((int) $d['id'] === $id_pilih) { $boleh = true; break; }
        }
        if (!$boleh) {
            $pesan_error = 'Dokumen tidak ditemukan atau bukan bidang Anda.';
        } else {
            $resp = nlp_preprocess('', $id_pilih, 4000);
            if ($resp['ok']) { $hasil = $resp['data']; }
            else { $pesan_error = $resp['error']; }
        }
    } else {
        if (trim($teks_input) === '') {
            $pesan_error = 'Teks masih kosong.';
        } else {
            $resp = nlp_preprocess($teks_input, null, 4000);
            if ($resp['ok']) { $hasil = $resp['data']; }
            else { $pesan_error = $resp['error']; }
        }
    }
}

/** Tampilkan daftar token sebagai deretan tag */
function tampil_token($list, $warna = 'biru')
{
    if (empty($list)) {
        echo '<span class="text-muted">(kosong)</span>';
        return;
    }
    foreach ($list as $t) {
        echo '<span class="tok tok-' . $warna . '">' . htmlspecialchars($t) . '</span>';
    }
}
?>

<style>
  .pp-box { background:#fff; border-top:3px solid #145885; padding:20px; margin-bottom:15px; box-shadow:0 1px 1px rgba(0,0,0,0.1); }
  .tahap { background:#fff; border:1px solid #e3e3e3; border-left:4px solid #145885;
           border-radius:3px; padding:14px 18px; margin-bottom:12px; }
  .tahap-judul { font-weight:bold; color:#145885; margin-bottom:4px; font-size:15px; }
  .tahap-ket { color:#888; font-size:12px; margin-bottom:10px; }
  .tahap-isi { background:#f7f9fb; border:1px solid #eef2f5; border-radius:3px;
               padding:10px 12px; font-family:Consolas, monospace; font-size:13px;
               word-break:break-word; line-height:1.9; max-height:230px; overflow:auto; }
  .tok { display:inline-block; border-radius:11px; padding:1px 9px; margin:2px 3px 2px 0; font-size:12px; }
  .tok-biru  { background:#eaf2f8; color:#145885; border:1px solid #cfe2f0; }
  .tok-abu   { background:#f0f0f0; color:#666;    border:1px solid #e0e0e0; }
  .tok-hijau { background:#e6f5ea; color:#1d7a3d; border:1px solid #c8e6d0; }
  .badge-jml { background:#145885; color:#fff; border-radius:10px; padding:2px 10px;
               font-size:11px; font-weight:normal; margin-left:6px; }
  .panah { text-align:center; color:#c0c8d0; font-size:20px; margin:-6px 0 6px 0; }
  .ringkas-table td { padding:5px 10px; border-bottom:1px solid #f0f0f0; font-size:13px; }
  .ringkas-table td:first-child { color:#777; }
  .regex-code { background:#2d2d2d; color:#9cdcfe; padding:2px 7px; border-radius:3px;
                font-family:Consolas, monospace; font-size:12px; }
</style>

<!-- ============ FORM INPUT ============ -->
<div class="pp-box">
  <form method="POST" action="index.php?page=preprocessing">
    <div class="form-group">
      <label style="margin-right:18px;">
        <input type="radio" name="sumber" value="teks"
               <?= $sumber !== 'dokumen' ? 'checked' : '' ?> onchange="gantiSumber()"> Ketik teks sendiri
      </label>
      <label>
        <input type="radio" name="sumber" value="dokumen"
               <?= $sumber === 'dokumen' ? 'checked' : '' ?> onchange="gantiSumber()"> Ambil dari dokumen yang sudah diupload
      </label>
    </div>

    <!-- Pilihan 1: teks manual -->
    <div id="area-teks" class="form-group">
      <label>Teks</label>
      <textarea name="teks" class="form-control" rows="4"
                placeholder="Tulis kalimat apa saja..."><?= htmlspecialchars($teks_input !== '' ? $teks_input : $contoh_teks) ?></textarea>
      <small class="text-muted">
        Contoh bawaan sudah diisi. Silakan diubah sesuka Anda.
      </small>
    </div>

    <!-- Pilihan 2: dari dokumen -->
    <div id="area-dokumen" class="form-group" style="display:none;">
      <label>Pilih Dokumen</label>
      <select name="id_dokumen" class="form-control">
        <?php if (empty($daftar_dokumen)): ?>
          <option value="0">-- belum ada dokumen yang ter-index --</option>
        <?php else: ?>
          <?php foreach ($daftar_dokumen as $d): ?>
            <option value="<?= (int)$d['id'] ?>" <?= ($id_pilih === (int)$d['id']) ? 'selected' : '' ?>>
              [<?= htmlspecialchars($d['bidang']) ?>] <?= htmlspecialchars($d['nama_dokumen']) ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      <small class="text-muted">
        Isi seluruh sel Excel dibaca, lalu diproses. Ditampilkan maksimal 4.000 karakter pertama
        supaya halaman tetap ringan.
      </small>
    </div>

    <button type="submit" class="btn btn-primary">
      <i class="fa fa-play"></i> Proses
    </button>
  </form>
</div>

<?php if (!$service_aktif): ?>
  <div class="alert alert-warning">
    <h4><i class="fa fa-warning"></i> Service preprocessing belum aktif</h4>
    <p style="margin-bottom:6px;"><?= htmlspecialchars($status['error']) ?></p>
    <p style="margin-bottom:0;">
      Buka folder <code>nlp_service</code>, klik dua kali <code>start_service.bat</code>,
      biarkan jendelanya terbuka, lalu muat ulang halaman ini.
    </p>
  </div>
<?php endif; ?>

<?php if ($pesan_error): ?>
  <div class="alert alert-danger">
    <i class="fa fa-times-circle"></i> <?= htmlspecialchars($pesan_error) ?>
  </div>
<?php endif; ?>

<!-- ============ HASIL TIAP TAHAP ============ -->
<?php if ($hasil): $t = $hasil['tahapan']; $j = $t['jumlah']; ?>

<?php if (!empty($hasil['dipotong'])): ?>
  <div class="alert alert-info" style="padding:8px 14px;">
    <i class="fa fa-info-circle"></i>
    Teks dokumen ini panjang, jadi yang ditampilkan hanya 4.000 karakter pertama.
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-md-8">

    <!-- TEKS ASLI -->
    <div class="tahap" style="border-left-color:#777;">
      <div class="tahap-judul">
        Teks Asli
        <span class="badge-jml"><?= (int)$j['karakter_asli'] ?> karakter</span>
      </div>
      <div class="tahap-ket">
        <?= $hasil['sumber'] === 'dokumen'
            ? 'Diambil dari isi dokumen: <b>' . htmlspecialchars($hasil['nama_dokumen']) . '</b>'
            : 'Teks yang Anda ketik' ?>
      </div>
      <div class="tahap-isi"><?= htmlspecialchars($t['teks_asli']) ?></div>
    </div>
    <div class="panah"><i class="fa fa-arrow-down"></i></div>

    <!-- TAHAP 1 -->
    <div class="tahap">
      <div class="tahap-judul">Tahap 1 &mdash; Case Folding</div>
      <div class="tahap-ket">
        Semua huruf diubah jadi huruf kecil dengan <code>teks.lower()</code>,
        supaya "Kerja", "KERJA", dan "kerja" dianggap satu kata yang sama.
      </div>
      <div class="tahap-isi"><?= htmlspecialchars($t['case_folding']) ?></div>
    </div>
    <div class="panah"><i class="fa fa-arrow-down"></i></div>

    <!-- TAHAP 2 -->
    <div class="tahap">
      <div class="tahap-judul">
        Tahap 2 &mdash; Cleaning (Regular Expression)
        <span class="badge-jml"><?= (int)$j['karakter_cleaning'] ?> karakter</span>
      </div>
      <div class="tahap-ket">
        Dibuang berurutan memakai modul <code>re</code> Python:
        <span class="regex-code">https?://\S+</span> alamat web &middot;
        <span class="regex-code">\d+</span> angka &middot;
        <span class="regex-code">[^\w\s]</span> tanda baca &amp; simbol &middot;
        <span class="regex-code">_+</span> underscore &middot;
        <span class="regex-code">\s+</span> spasi berlebih
      </div>
      <div class="tahap-isi"><?= htmlspecialchars($t['cleaning']) ?></div>
    </div>
    <div class="panah"><i class="fa fa-arrow-down"></i></div>

    <!-- TAHAP 3 -->
    <div class="tahap">
      <div class="tahap-judul">
        Tahap 3 &mdash; Tokenizing
        <span class="badge-jml"><?= (int)$j['token_tokenizing'] ?> token</span>
      </div>
      <div class="tahap-ket">
        Kalimat dipotong menjadi kata-kata dengan <code>nltk.word_tokenize()</code>.
      </div>
      <div class="tahap-isi"><?php tampil_token($t['tokenizing'], 'abu'); ?></div>
    </div>
    <div class="panah"><i class="fa fa-arrow-down"></i></div>

    <!-- TAHAP 4 -->
    <div class="tahap">
      <div class="tahap-judul">
        Tahap 4 &mdash; Stopword Removal
        <span class="badge-jml"><?= (int)$j['token_setelah_stopword'] ?> token</span>
      </div>
      <div class="tahap-ket">
        Kata umum dibuang memakai <code>nltk.corpus.stopwords.words('indonesian')</code>
        ditambah daftar khusus dokumen kantor (mis. "no", "jumlah", "data", "tahun")
        di file <code>stopwords_tambahan.txt</code>.
        Terbuang: <b><?= (int)$j['token_tokenizing'] - (int)$j['token_setelah_stopword'] ?></b> kata.
      </div>
      <div class="tahap-isi"><?php tampil_token($t['stopword_removal'], 'biru'); ?></div>
    </div>
    <div class="panah"><i class="fa fa-arrow-down"></i></div>

    <!-- TAHAP 5 -->
    <div class="tahap" style="border-left-color:#1d7a3d;">
      <div class="tahap-judul" style="color:#1d7a3d;">
        Tahap 5 &mdash; Stemming (Sastrawi)
        <span class="badge-jml" style="background:#1d7a3d;"><?= (int)$j['token_setelah_stemming'] ?> token</span>
      </div>
      <div class="tahap-ket">
        Tiap kata dikembalikan ke bentuk dasarnya, mis. "pelatihan" &rarr; "latih",
        "pekerja" &rarr; "kerja". Dipakai Sastrawi karena NLTK tidak menyediakan
        stemmer bahasa Indonesia. Inilah hasil akhir yang disimpan ke index.
      </div>
      <div class="tahap-isi"><?php tampil_token($t['stemming_final'], 'hijau'); ?></div>
    </div>

  </div>

  <!-- ============ RINGKASAN ============ -->
  <div class="col-md-4">
    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title" style="font-size:15px;">Ringkasan Jumlah Token</h3>
      </div>
      <div class="box-body no-padding">
        <table class="ringkas-table" style="width:100%;">
          <tr><td>Karakter teks asli</td><td align="right"><b><?= (int)$j['karakter_asli'] ?></b></td></tr>
          <tr><td>Karakter setelah cleaning</td><td align="right"><b><?= (int)$j['karakter_cleaning'] ?></b></td></tr>
          <tr><td>Token hasil tokenizing</td><td align="right"><b><?= (int)$j['token_tokenizing'] ?></b></td></tr>
          <tr><td>Token setelah stopword</td><td align="right"><b><?= (int)$j['token_setelah_stopword'] ?></b></td></tr>
          <tr><td>Token setelah stemming</td><td align="right"><b><?= (int)$j['token_setelah_stemming'] ?></b></td></tr>
          <tr><td>Kata dasar unik</td><td align="right"><b><?= (int)$j['token_unik'] ?></b></td></tr>
        </table>
      </div>
    </div>

    <div class="box box-success">
      <div class="box-header with-border">
        <h3 class="box-title" style="font-size:15px;">Kata Paling Sering Muncul</h3>
      </div>
      <div class="box-body no-padding">
        <table class="ringkas-table" style="width:100%;">
          <?php if (empty($hasil['term_teratas'])): ?>
            <tr><td colspan="2" class="text-muted">(tidak ada)</td></tr>
          <?php else: ?>
            <?php foreach ($hasil['term_teratas'] as $tt): ?>
              <tr>
                <td><span class="tok tok-hijau"><?= htmlspecialchars($tt[0]) ?></span></td>
                <td align="right"><b><?= (int)$tt[1] ?>x</b></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </table>
      </div>
      <div class="box-footer" style="font-size:12px; color:#888;">
        Nilai ini yang disimpan sebagai <b>TF</b> (Term Frequency)
        di tabel <code>dokumen_term</code>.
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
  function gantiSumber() {
    var pilihan = document.querySelector('input[name="sumber"]:checked').value;
    document.getElementById('area-teks').style.display    = (pilihan === 'teks') ? 'block' : 'none';
    document.getElementById('area-dokumen').style.display = (pilihan === 'dokumen') ? 'block' : 'none';
  }
  gantiSumber();
</script>
