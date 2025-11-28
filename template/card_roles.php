<style>
  /* === STYLE UMUM (admin & lainnya) === */
  .dash-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    align-items: stretch;
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
  }

  .dash-card {
    width: 100%;
    box-sizing: border-box;
    border-radius: 10px;
    color: #fff;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 12px;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    min-height: 220px;
  }

  .dash-card img {
    width: 55px;
    height: 55px;
    margin-bottom: 10px;
    display: block;
  }

  .dash-card h4 {
    margin: 0;
    padding: 0;
    font-weight: 600;
    text-align: center;
  }

  .dash-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 18px rgba(0,0,0,.2);
  }

  /* === RESPONSIVE === */
  @media (max-width: 576px) {
    .dash-grid {
      grid-template-columns: 1fr;
    }
  }

  /* === STYLE KHUSUS UNTUK ROLE SATU CARD === */
  .single-card-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 180px);
  }

  .single-card {
    width: 100%;
    max-width: 420px;
    height: 360px;
    background: linear-gradient(135deg, rgba(255,255,255,0.12), rgba(0,0,0,0.15));
    border-radius: 18px;
    padding: 30px;
    text-align: center;
    color: #fff;
    text-decoration: none;
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    transition: all 0.25s ease-in-out;
    display: flex;
    flex-direction: column;
    justify-content: center;  /* tengah vertikal */
    align-items: center;      /* tengah horizontal */
    text-align: center;
    padding: 0;
  }

  .single-card:hover {
    transform: scale(1.05) translateY(-6px);
    box-shadow: 0 16px 30px rgba(0,0,0,0.3);
  }

  .single-card img {
    width: 95px;
    height: 95px;
    margin-bottom: 15px;
    transition: transform 0.3s ease;
  }

  .single-card:hover img {
    transform: rotate(4deg) scale(1.1);
  }

  .single-card h4 {
    margin: 0;
    font-size: 1.6rem;
    font-weight: 700;
    letter-spacing: 0.5px;
  }
</style>

<?php if ($role == 'admin') { ?>
  <div class="dash-grid">
    <a class="dash-card" href="index.php?page=sekretariat" style="background:#6E5CB1;">
      <img src="dist/img/Sign Document.png" alt="">
      <h4>BIDANG SEKRETARIAT</h4>
    </a>

    <a class="dash-card" href="index.php?page=industri" style="background:#00a65a;">
      <img src="dist/img/Factory.png" alt="">
      <h4>BIDANG INDUSTRI</h4>
    </a>

    <a class="dash-card" href="index.php?page=hubinsyaker" style="background:#C24E5C;">
      <img src="dist/img/Handshake.png" alt="">
      <h4>BIDANG HUBINSYAKER</h4>
    </a>

    <a class="dash-card" href="index.php?page=pptk" style="background:#E0A733;">
      <img src="dist/img/Open Book.png" alt="">
      <h4>BIDANG PPTK</h4>
    </a>
  </div>

<?php } else {
  $cardData = [
    'sekretariat' => ['index.php?page=sekretariat', '#6E5CB1', 'dist/img/Sign Document.png', 'BIDANG SEKRETARIAT'],
    'industri' => ['index.php?page=industri', '#00a65a', 'dist/img/Factory.png', 'BIDANG INDUSTRI'],
    'hubinsyaker' => ['index.php?page=hubinsyaker', '#C24E5C', 'dist/img/Handshake.png', 'BIDANG HUBINSYAKER'],
    'pptk' => ['index.php?page=pptk', '#E0A733', 'dist/img/Open Book.png', 'BIDANG PPTK']
  ];

  if (isset($cardData[$role])) {
    list($href, $bg, $img, $label) = $cardData[$role];
?>
    <div class="single-card-container">
      <a class="single-card" href="<?= $href ?>" style="background:<?= $bg ?>;">
        <img src="<?= $img ?>" alt="">
        <h4><?= $label ?></h4>
      </a>
    </div>
<?php } } ?>
