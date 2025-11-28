<h1>
    <?php
    switch ($page) {
        case 'sekretariat':
        echo "Daftar Arsip <b>Sekretariat</b>";
        break;
        case 'industri':
        echo "Daftar Arsip <b>Industri</b>";
        break;
        case 'hubinsyaker':
        echo "Daftar Arsip <b>Hubinsyaker</b>";
        break;
        case 'pptk':
        echo "Daftar Arsip <b>PPTK</b>";
        break;
        case 'rekapan_tahunan':
        echo "";
        break;
        case 'settings_users':
        echo "Pengaturan User";
        break;
        case 'settings_profile':
        echo "Pengaturan Profil";
        break;
        default:
        echo "DINAS PERINDUSTRIAN DAN TENAGA KERJA";
    }
    ?>
</h1>