<?php
// ==== COMPONENT: TABLE DOKUMEN ====
// Variabel yang bisa dikirim sebelum include:
// $data_dokumen  -> array data (harus isi minimal kolom: name, bidang, size, uploaded, path)

if (!isset($data_dokumen)) $data_dokumen = [];

$page = isset($_GET['page']) ? $_GET['page'] : '';
$role = $_SESSION['role_name'] ?? '';
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<style>
  .files-table thead th {
    background: #145885;
    color: #fff;
    vertical-align: middle;
  }
  .action-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 6px;
    text-decoration: none;
    color: #fff;
    margin: 0 4px;
    font-size: 14px;
  }
  .action-view { background: rgba(0,0,0,0.15); }
  .action-download{ background: rgba(20,88,133,0.9); }
  .action-copy{ background: rgba(0,128,0,0.85); }
  .action-delete{ background: rgba(220,53,69,0.95); }
  .files-table tbody td { vertical-align: middle; }

  .swal2-popup {
    font-size: 1.4rem !important;
    padding: 1.5em !important;
  }

  .swal2-title {
    font-size: 1.8rem !important;
  }

  .swal2-confirm, .swal2-cancel {
    font-size: 1.2rem !important;
    padding: 10px 20px !important;
  }

  #excel-viewer-container table {
    border-collapse: collapse;
    width: 100%;
    font-size: 14px;
    font-family: Arial, sans-serif;
  }

  #excel-viewer-container th,
  #excel-viewer-container td {
    border: 1px solid #ddd;
    padding: 6px 10px;
  }

  #excel-viewer-container th {
    background: #f1f1f1;
    font-weight: bold;
    text-align: left;
  }

  #excel-viewer-container tr:nth-child(even) {
    background: #fafafa;
  }

</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
  <div>
    <?php if ($page != 'rekapan_tahunan') { ?>
      <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalTambahDokumen">
        <i class="fa fa-plus"></i> Tambah Dokumen
      </button>
    <?php } ?>
  </div>

  <?php if ($page != 'rekapan_tahunan') { ?>
  <!-- 🔍 Filter tanggal di kanan -->
  <form method="GET" style="display: flex; align-items: center; gap: 5px;">
    <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">

    <input 
      type="text" 
      id="tanggal" 
      name="tanggal"
      class="form-control input-sm" 
      placeholder="dd/mm/yyyy"
      value="<?= isset($_GET['tanggal']) ? htmlspecialchars($_GET['tanggal']) : '' ?>"
      style="width: 130px;"
    >

    <button type="submit" class="btn btn-sm btn-success">
      <i class="fa fa-search"></i>
    </button>

    <?php if (isset($_GET['tanggal']) && $_GET['tanggal'] != ''): ?>
      <a href="index.php?page=<?= htmlspecialchars($page) ?>" class="btn btn-sm btn-default">
        <i class="fa fa-refresh"></i>
      </a>
    <?php endif; ?>
  </form>
  <?php } else { ?>
    <form method="GET" style="display: flex; align-items: center; gap: 5px;">
      <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">

      <?php if ($role == 'admin') { ?>
      <select name="bidang" class="form-control input-sm" style="width: 150px;">
        <option value="">Semua Bidang</option>
        <?php
        // Daftar bidang sesuai dengan case statement yang Anda miliki
        $listBidang = ['SEKRETARIAT', 'INDUSTRI', 'HUBINSYAKER', 'PPTK'];

        // Tambahkan opsi Bidang
        foreach ($listBidang as $bidang): ?>
            <option value="<?= htmlspecialchars($bidang) ?>" 
                <?= (isset($_GET['bidang']) && $_GET['bidang'] == $bidang) ? 'selected' : '' ?>>
                <?= htmlspecialchars($bidang) ?>
            </option>
        <?php endforeach; ?>
      </select>
      <?php } ?>

      <select name="tahun" class="form-control input-sm" style="width: 100px;">
        <option value="">Tahun</option>
        <?php
          $tahunSekarang = date('Y');
          for ($t = $tahunSekarang; $t >= 2020; $t--): ?>
            <option value="<?= $t ?>" <?= (isset($_GET['tahun']) && $_GET['tahun'] == $t) ? 'selected' : '' ?>>
              <?= $t ?>
            </option>
        <?php endfor; ?>
      </select>

      <button type="submit" class="btn btn-sm btn-success">
        <i class="fa fa-search"></i>
      </button>

      <?php if (!empty($_GET['tahun'])): ?>
        <a href="index.php?page=<?= htmlspecialchars($page) ?>" class="btn btn-sm btn-default">
          <i class="fa fa-refresh"></i>
        </a>
      <?php endif; ?>
    </form>
  <?php } ?>
</div>

<div class="box">
  <div class="box-body table-responsive no-padding">
    <table class="table table-hover files-table">
      <thead>
        <tr>
          <th>No</th>
          <th>Dokumen</th>
          <th>Bidang</th>
          <th>Ukuran</th>
          <th>Tgl Dibuat</th>
          <th>Tgl Diperbarui</th>
          <th style="text-align:center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($data_dokumen) > 0): ?>
          <?php $no=1; foreach ($data_dokumen as $f): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($f['nama_dokumen']) ?></td>
            <td><?= htmlspecialchars($f['bidang']) ?></td>
            <td><?= htmlspecialchars($f['ukuran_file']) ?></td>
            <td><?= date('d/m/Y', strtotime($f['created_at'])) ?></td>
            <td><?= date('d/m/Y', strtotime($f['updated_at'])) ?></td>
            <td style="text-align:center;">
              <a href="javascript:void(0)" onclick="downloadFile('<?= strtolower($f['bidang']) . '/' . $f['nama_file'] ?>'); return false;" class="action-icon action-download"><i class="fa fa-download"></i></a>
              <a href="javascript:void(0)" onclick="viewExcel('<?= strtolower($f['bidang']) . '/' . $f['nama_file'] ?>'); return false;" class="action-icon action-view"><i class="fa fa-eye"></i></a>
              <a href="javascript:void(0)" class="action-icon action-copy" 
                  data-id="<?= $f['id'] ?>" 
                  data-nama="<?= htmlspecialchars($f['nama_dokumen']) ?>" 
                  data-bidang="<?= htmlspecialchars($f['bidang']) ?>" 
                  data-file="<?= htmlspecialchars($f['nama_file']) ?>"
                  onclick="openEditModal(this)">
                <i class="fa fa-edit"></i>
              </a>
              <a href="javascript:void(0)" onclick="confirmDelete(<?= $f['id'] ?? 0 ?>)" class="action-icon action-delete"><i class="fa fa-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" style="text-align:center; color:#888;">Belum ada data</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="popupViewer" style="
  display:none;
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%, -50%);
  width:80%;
  height:80vh;
  background:white;
  border-radius:10px;
  box-shadow:0 8px 30px rgba(0,0,0,0.4);
  z-index:9999;
">
  <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;border-bottom:1px solid #ddd;">
    <h4 id="popupViewerTitle" style="margin:0;">📄 Pratinjau Dokumen</h4>
    <button onclick="closePopupViewer()" style="background:#dc3545;color:white;border:none;padding:6px 12px;border-radius:5px;cursor:pointer;">✕ Tutup</button>
  </div>
  <iframe id="popupViewerFrame" style="width:100%;height:calc(100% - 50px);border:none;border-radius:0 0 10px 10px;"></iframe>
</div>

<!-- Modal Tambah Dokumen -->
<div class="modal fade" id="modalTambahDokumen" tabindex="-1" role="dialog" aria-labelledby="modalTambahDokumenLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="uploadForm" action="pages/process/upload_dokumen.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h4 class="modal-title" id="modalTambahDokumenLabel">Tambah Dokumen</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span>&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label>Nama Dokumen</label>
            <input type="text" name="nama_dokumen" class="form-control" required>
          </div>

          <!-- Bidang otomatis dari GET -->
          <?php
            // Ubah jadi huruf besar semua
            $bidang = strtoupper($page);
          ?>
          <div class="form-group">
            <label>Bidang</label>
            <input 
              type="text" 
              class="form-control" 
              value="<?= $bidang ?>" 
              disabled>
            <!-- kirim juga hidden input biar tetap bisa diproses -->
            <input type="hidden" name="bidang" value="<?= $bidang ?>">
          </div>

          <div class="form-group">
            <label>Upload File (Excel)</label>
            <input 
              type="file" 
              name="file_dokumen" 
              class="form-control" 
              accept=".xls,.xlsx" 
              required>
            <small class="text-muted">Hanya file Excel (.xls, .xlsx). File Max 5MB!</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Dokumen -->
<div class="modal fade" id="modalEditDokumen" tabindex="-1" role="dialog" aria-labelledby="modalEditDokumenLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="uploadForm" action="pages/process/update_dokumen.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h4 class="modal-title" id="modalEditDokumenLabel">Edit Dokumen</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span>&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">

          <div class="form-group">
            <label>Nama Dokumen</label>
            <input type="text" name="nama_dokumen" id="edit_nama" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Bidang</label>
            <input type="text" id="edit_bidang" class="form-control" disabled>
            <input type="hidden" name="bidang" id="edit_bidang_hidden">
          </div>

          <div class="form-group">
            <label>File Sekarang</label>
            <div id="edit_file_old" class="text-muted small"></div>
          </div>

          <div class="form-group">
            <label>Ganti File (opsional)</label>
            <input type="file" name="file_dokumen" class="form-control" accept=".xls,.xlsx">
            <small class="text-muted">Hanya file Excel (.xls, .xlsx). File Max 5MB!</small></br>
            <small class="text-muted d-block">Kosongkan jika tidak ingin mengganti file.</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.getElementById('uploadForm').addEventListener('submit', function() {
    Swal.fire({
      title: 'Sedang mengunggah...',
      text: 'Mohon tunggu sebentar',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });
  });
</script>

<script>
async function viewExcel(fileName) {
  try {
    const namaDokumen = fileName.split('/').pop();
    const fileUrl = 'uploads/' + fileName;

    document.getElementById('popupViewerTitle').innerText = '📊 ' + namaDokumen;
    document.getElementById('popupViewerContent').innerHTML = '<p>Sedang memuat...</p>';
    document.getElementById('popupViewerExcel').style.display = 'block';

    // Ambil file Excel dari server
    const response = await fetch(fileUrl);
    if (!response.ok) throw new Error('Gagal mengambil file Excel');
    const arrayBuffer = await response.arrayBuffer();

    // Baca workbook
    const workbook = XLSX.read(arrayBuffer, { type: 'array' });
    const firstSheetName = workbook.SheetNames[0];
    const sheet = workbook.Sheets[firstSheetName];
    const html = XLSX.utils.sheet_to_html(sheet);

    // Tampilkan ke viewer container
    document.getElementById('popupViewerContent').innerHTML = `
      <div id="excel-viewer-container" style="max-height:100%;overflow:auto;">
        ${html}
      </div>
    `;

  } catch (error) {
    console.error(error);
    document.getElementById('popupViewerContent').innerHTML =
      '<p style="color:red;">Gagal menampilkan file Excel</p>';
  }
}

function closePopupViewerExcel() {
  document.getElementById('popupViewerExcel').style.display = 'none';
}
</script>
<!-- VIEWER POPUP -->
<div id="popupViewerExcel" style="
  display:none;
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%, -50%);
  width:80%;
  height:80vh;
  background:white;
  border-radius:10px;
  box-shadow:0 8px 30px rgba(0,0,0,0.4);
  z-index:9999;
">
  <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;border-bottom:1px solid #ddd;">
    <h4 id="popupViewerTitle" style="margin:0;">📄 Pratinjau Dokumen</h4>
    <button onclick="closePopupViewerExcel()" style="background:#dc3545;color:white;border:none;padding:6px 12px;border-radius:5px;cursor:pointer;">✕ Tutup</button>
  </div>
  <div id="popupViewerContent" style="width:100%;height:calc(100% - 50px);overflow:auto;padding:10px;"></div>
</div>

<script>
  $('#tanggal').datepicker({
    format: 'dd/mm/yyyy',
    autoclose: true,
    todayHighlight: true
  });
</script>

<script>
  function base_url(path = '') {
    const { protocol, host, pathname } = window.location;
    const base = protocol + '//' + host + pathname.substring(0, pathname.lastIndexOf('/'));
    return base.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
  }

  function downloadFile(fileName) {
    const namaDokumen = fileName.split('/').pop(); // ambil nama file saja

    // Langkah 1 — Tampilkan konfirmasi sebelum download
    Swal.fire({
      title: 'Download dokumen?',
      text: 'Klik OK untuk mulai mengunduh ' + namaDokumen,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'OK',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
    }).then((result) => {
      if (result.isConfirmed) {

        // Langkah 2 — Jalankan download
        const link = document.createElement('a');
        link.href = 'uploads/' + fileName;
        link.download = namaDokumen;
        document.body.appendChild(link);
        link.click();
        link.remove();

        // Langkah 3 — Setelah file mulai diunduh, tampilkan alert sukses
        Swal.fire({
          title: 'Berhasil!',
          text: namaDokumen + ' berhasil diunduh!',
          icon: 'success',
          confirmButtonText: 'OK',
          confirmButtonColor: '#3085d6'
        });
      }
    });
  }

  function viewFile(fileName) {
    const namaDokumen = fileName.split('/').pop(); // ambil nama file saja

    const fileUrl = base_url('uploads/' + fileName);
    const encodedUrl = encodeURIComponent(fileUrl);

    // tampilkan nama dokumen
    document.getElementById('popupViewerTitle').innerText = '📄 ' + namaDokumen;

    // tampilkan file di Google Docs Viewer
    document.getElementById('popupViewerFrame').src =
      'https://docs.google.com/gview?url=' + encodedUrl + '&embedded=true';

    // munculkan area viewer
    document.getElementById('popupViewer').style.display = 'block';
  }

  function closePopupViewer() {
    document.getElementById('popupViewer').style.display = 'none';
  }

  function openEditModal(el) {
    const id = el.dataset.id;
    const nama = el.dataset.nama;
    const bidang = el.dataset.bidang;
    const file = el.dataset.file;

    // isi field di modal
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_bidang').value = bidang;
    document.getElementById('edit_bidang_hidden').value = bidang;
    document.getElementById('edit_file_old').innerText = 'File lama: ' + file;

    // buka modal
    $('#modalEditDokumen').modal('show');
  }

  // delete confirmation (simple). Replace with AJAX call to server-side delete if needed.
  function confirmDelete(id) {
    Swal.fire({
      title: 'Hapus dokumen ini?',
      text: 'Tindakan ini tidak bisa dibatalkan!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal',
      customClass: {
        title: 'swal-title-lg',
        htmlContainer: 'swal-text-lg',
        confirmButton: 'swal-btn-lg',
        cancelButton: 'swal-btn-lg'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`pages/process/delete_dokumen.php?id=${id}`, { method: 'GET' })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const row = document.getElementById('row-' + id);
              if (row) row.remove();
              Swal.fire({
                icon: 'success',
                title: 'Dihapus!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: data.message
              });
            }
          })
          .catch(err => {
            Swal.fire({
              icon: 'error',
              title: 'Error!',
              text: 'Terjadi kesalahan saat menghapus.'
            });
          });
      }
    });
  }
</script>

<?php
function base_url($path = '') {
  $base = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
  return $base . '/' . ltrim($path, '/');
}
?>