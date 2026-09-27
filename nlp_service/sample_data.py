# -*- coding: utf-8 -*-
"""
Isi 15 dokumen Excel contoh untuk pengujian & demo.

File ini HANYA berisi datanya. Pembuatan file Excel + memasukkan ke
aplikasi dilakukan oleh seed_sample_data.py.

Format tiap dokumen:
    nama    -> nama dokumen yang tampil di aplikasi
    bidang  -> SEKRETARIAT / INDUSTRI / HUBINSYAKER / PPTK
    sheet   -> nama sheet di Excel
    header  -> judul kolom
    baris   -> isi tabel
"""

DOKUMEN_CONTOH = [
    # ==========================================================
    # PPTK - Pelatihan & Penempatan Tenaga Kerja
    # ==========================================================
    {
        'nama': 'Rekap Pelatihan Kerja 2024',
        'bidang': 'PPTK',
        'sheet': 'Pelatihan Kerja',
        'header': ['No', 'Nama Pelatihan', 'Instruktur', 'Lokasi', 'Jumlah Peserta', 'Keterangan'],
        'baris': [
            [1, 'Pelatihan Menjahit Dasar', 'Siti Aminah', 'BLK Kota', 30, 'Peserta lulus seluruhnya'],
            [2, 'Pelatihan Las Listrik', 'Bambang Wijaya', 'BLK Kota', 25, 'Sertifikat kompetensi diberikan'],
            [3, 'Pelatihan Otomotif Sepeda Motor', 'Agus Santoso', 'BLK Cabang Timur', 28, 'Praktik bengkel mitra'],
            [4, 'Pelatihan Tata Boga', 'Ratna Dewi', 'BLK Kota', 22, 'Kerja sama hotel lokal'],
            [5, 'Pelatihan Menjahit Lanjutan', 'Siti Aminah', 'BLK Kota', 18, 'Lanjutan angkatan pertama'],
            [6, 'Pelatihan Las Argon', 'Bambang Wijaya', 'BLK Cabang Barat', 20, 'Peserta magang di industri'],
            [7, 'Pelatihan Instalasi Listrik Rumah', 'Hendra Kurnia', 'BLK Kota', 26, 'Uji kompetensi lulus'],
            [8, 'Pelatihan Pertanian Hidroponik', 'Dwi Lestari', 'Balai Desa Sukamaju', 24, 'Peserta kelompok tani'],
            [9, 'Pelatihan Menjahit Konveksi', 'Nur Halimah', 'BLK Cabang Timur', 27, 'Penempatan ke konveksi mitra'],
            [10, 'Pelatihan Perbaikan Alat Elektronik', 'Yusuf Maulana', 'BLK Kota', 21, 'Praktik servis televisi kulkas'],
        ],
        'catatan': [
            'Rekapitulasi kegiatan pelatihan kerja bagi pencari kerja tahun anggaran 2024.',
            'Seluruh peserta pelatihan mendapat sertifikat kompetensi kerja dari lembaga sertifikasi profesi.',
            'Instruktur pelatihan berasal dari balai latihan kerja dan praktisi industri.',
        ],
    },
    {
        'nama': 'Laporan Penempatan Tenaga Kerja Semester I 2024',
        'bidang': 'PPTK',
        'sheet': 'Penempatan',
        'header': ['No', 'Nama Pencari Kerja', 'Keahlian', 'Perusahaan Penempatan', 'Status', 'Bulan'],
        'baris': [
            [1, 'Rudi Hartono', 'Operator Mesin Jahit', 'PT Sandang Makmur', 'Diterima', 'Januari'],
            [2, 'Dewi Anggraini', 'Administrasi Perkantoran', 'PT Cahaya Abadi', 'Diterima', 'Januari'],
            [3, 'Slamet Riyadi', 'Teknisi Las', 'PT Baja Perkasa', 'Diterima', 'Februari'],
            [4, 'Lina Marlina', 'Juru Masak', 'Hotel Permata Indah', 'Diterima', 'Februari'],
            [5, 'Anton Nugroho', 'Mekanik Sepeda Motor', 'Bengkel Sejahtera Motor', 'Diterima', 'Maret'],
            [6, 'Sri Wahyuni', 'Operator Produksi', 'PT Pangan Sentosa', 'Diterima', 'Maret'],
            [7, 'Joko Susilo', 'Teknisi Listrik', 'PT Energi Nusantara', 'Diterima', 'April'],
            [8, 'Maya Puspita', 'Pramuniaga', 'Toko Serba Ada Rejeki', 'Diterima', 'April'],
            [9, 'Firman Syah', 'Sopir Angkutan Barang', 'CV Logistik Cepat', 'Proses', 'Mei'],
            [10, 'Indah Permatasari', 'Penjahit Konveksi', 'CV Busana Indah', 'Diterima', 'Juni'],
        ],
        'catatan': [
            'Laporan penempatan tenaga kerja hasil kegiatan penyaluran pencari kerja semester pertama.',
            'Penempatan dilakukan melalui kerja sama dengan perusahaan pengguna tenaga kerja di wilayah kabupaten.',
        ],
    },
    {
        'nama': 'Data Peserta Pelatihan Komputer dan Desain Grafis',
        'bidang': 'PPTK',
        'sheet': 'Peserta Komputer',
        'header': ['No', 'Nama Peserta', 'Pendidikan', 'Materi', 'Nilai Akhir', 'Predikat'],
        'baris': [
            [1, 'Ahmad Fauzi', 'SMK', 'Microsoft Office dan Pengolah Angka', 88, 'Baik Sekali'],
            [2, 'Nabila Rahma', 'SMA', 'Desain Grafis Corel dan Photoshop', 91, 'Baik Sekali'],
            [3, 'Bayu Pratama', 'SMK', 'Pemrograman Web Dasar', 79, 'Baik'],
            [4, 'Citra Melati', 'SMA', 'Desain Grafis Poster dan Brosur', 85, 'Baik Sekali'],
            [5, 'Dimas Prayoga', 'D3', 'Jaringan Komputer Dasar', 82, 'Baik'],
            [6, 'Erlina Sari', 'SMK', 'Microsoft Office dan Presentasi', 77, 'Baik'],
            [7, 'Fajar Ramadhan', 'SMA', 'Editing Video Promosi', 86, 'Baik Sekali'],
            [8, 'Gita Anjani', 'SMK', 'Desain Grafis Kemasan Produk', 89, 'Baik Sekali'],
        ],
        'catatan': [
            'Pelatihan keterampilan komputer dan desain grafis untuk meningkatkan daya saing pencari kerja muda.',
            'Peserta pelatihan diarahkan membuka usaha mandiri di bidang jasa desain dan percetakan.',
        ],
    },
    {
        'nama': 'Rekap Bursa Kerja dan Lowongan Perusahaan 2024',
        'bidang': 'PPTK',
        'sheet': 'Bursa Kerja',
        'header': ['No', 'Perusahaan', 'Posisi Lowongan', 'Kebutuhan', 'Pendidikan Minimal', 'Pelamar'],
        'baris': [
            [1, 'PT Sandang Makmur', 'Operator Jahit', 50, 'SMP', 132],
            [2, 'PT Baja Perkasa', 'Juru Las', 20, 'SMK', 68],
            [3, 'PT Pangan Sentosa', 'Operator Produksi', 40, 'SMA', 155],
            [4, 'Hotel Permata Indah', 'Juru Masak', 10, 'SMK', 44],
            [5, 'CV Logistik Cepat', 'Sopir Truk', 15, 'SMP', 51],
            [6, 'PT Energi Nusantara', 'Teknisi Listrik', 8, 'D3', 37],
            [7, 'PT Cahaya Abadi', 'Staf Administrasi', 12, 'SMA', 96],
            [8, 'CV Busana Indah', 'Penjahit', 25, 'SMP', 73],
        ],
        'catatan': [
            'Kegiatan bursa kerja mempertemukan pencari kerja dengan perusahaan pengguna tenaga kerja.',
            'Informasi lowongan pekerjaan disebarkan melalui papan pengumuman dan media sosial dinas.',
        ],
    },

    # ==========================================================
    # INDUSTRI
    # ==========================================================
    {
        'nama': 'Data Perusahaan Industri Kecil Menengah 2024',
        'bidang': 'INDUSTRI',
        'sheet': 'IKM',
        'header': ['No', 'Nama Usaha', 'Jenis Industri', 'Alamat', 'Tenaga Kerja', 'Modal Usaha'],
        'baris': [
            [1, 'Kerupuk Sari Rasa', 'Industri Makanan Ringan', 'Desa Sukamaju', 12, 85000000],
            [2, 'Batik Tulis Nusantara', 'Industri Tekstil dan Batik', 'Kelurahan Mekarsari', 18, 140000000],
            [3, 'Mebel Jati Indah', 'Industri Furnitur Kayu', 'Desa Karangasem', 22, 210000000],
            [4, 'Sepatu Kulit Mandiri', 'Industri Alas Kaki', 'Kelurahan Cibadak', 15, 120000000],
            [5, 'Keripik Singkong Barokah', 'Industri Makanan Ringan', 'Desa Sukamaju', 9, 60000000],
            [6, 'Anyaman Bambu Lestari', 'Industri Kerajinan', 'Desa Cilame', 14, 75000000],
            [7, 'Konveksi Busana Muslim', 'Industri Pakaian Jadi', 'Kelurahan Mekarsari', 30, 260000000],
            [8, 'Roti Manis Sejahtera', 'Industri Roti dan Kue', 'Kelurahan Cibadak', 11, 90000000],
            [9, 'Las Karoseri Perkasa', 'Industri Logam', 'Desa Karangasem', 17, 175000000],
            [10, 'Tahu Tempe Sumber Rejeki', 'Industri Pengolahan Kedelai', 'Desa Cilame', 8, 55000000],
        ],
        'catatan': [
            'Pendataan industri kecil menengah untuk pembinaan dan bantuan peralatan produksi.',
            'Sebagian pelaku industri kecil telah memiliki izin usaha industri dan sertifikat halal.',
        ],
    },
    {
        'nama': 'Laporan Produksi Industri Makanan dan Minuman',
        'bidang': 'INDUSTRI',
        'sheet': 'Produksi',
        'header': ['No', 'Perusahaan', 'Produk', 'Kapasitas Produksi', 'Satuan', 'Pemasaran'],
        'baris': [
            [1, 'PT Pangan Sentosa', 'Mie Instan', 12000, 'Karton per bulan', 'Regional dan Nasional'],
            [2, 'Kerupuk Sari Rasa', 'Kerupuk Udang', 3500, 'Kilogram per bulan', 'Lokal'],
            [3, 'Roti Manis Sejahtera', 'Roti Tawar dan Roti Isi', 9000, 'Bungkus per bulan', 'Lokal'],
            [4, 'CV Minuman Segar', 'Air Minum Dalam Kemasan', 25000, 'Dus per bulan', 'Regional'],
            [5, 'Tahu Tempe Sumber Rejeki', 'Tahu dan Tempe', 6000, 'Kilogram per bulan', 'Pasar tradisional'],
            [6, 'Keripik Singkong Barokah', 'Keripik Singkong Pedas', 2800, 'Kilogram per bulan', 'Lokal dan daring'],
            [7, 'CV Kopi Gunung', 'Kopi Bubuk Kemasan', 1800, 'Kilogram per bulan', 'Regional dan ekspor'],
        ],
        'catatan': [
            'Laporan kapasitas produksi industri pengolahan makanan dan minuman wilayah kabupaten.',
            'Peningkatan produksi didukung bantuan mesin pengolahan dari program pembinaan industri.',
        ],
    },
    {
        'nama': 'Rekap Izin Usaha Industri Tahun 2024',
        'bidang': 'INDUSTRI',
        'sheet': 'Izin Usaha',
        'header': ['No', 'Nama Perusahaan', 'Nomor Izin', 'Jenis Izin', 'Tanggal Terbit', 'Status'],
        'baris': [
            [1, 'PT Baja Perkasa', 'IUI/2024/0011', 'Izin Usaha Industri Besar', '2024-01-15', 'Aktif'],
            [2, 'Batik Tulis Nusantara', 'IUI/2024/0027', 'Izin Usaha Industri Kecil', '2024-02-02', 'Aktif'],
            [3, 'Mebel Jati Indah', 'IUI/2024/0033', 'Izin Usaha Industri Menengah', '2024-02-20', 'Aktif'],
            [4, 'Konveksi Busana Muslim', 'IUI/2024/0048', 'Izin Usaha Industri Kecil', '2024-03-11', 'Aktif'],
            [5, 'CV Minuman Segar', 'IUI/2024/0055', 'Izin Usaha Industri Menengah', '2024-04-05', 'Aktif'],
            [6, 'Las Karoseri Perkasa', 'IUI/2024/0062', 'Izin Usaha Industri Kecil', '2024-05-19', 'Perpanjangan'],
            [7, 'CV Kopi Gunung', 'IUI/2024/0074', 'Izin Usaha Industri Kecil', '2024-06-27', 'Aktif'],
        ],
        'catatan': [
            'Rekapitulasi penerbitan izin usaha industri sebagai dasar pembinaan dan pengawasan.',
            'Perusahaan industri wajib menyampaikan laporan kegiatan usaha secara berkala kepada dinas.',
        ],
    },
    {
        'nama': 'Data Kawasan Industri dan Tenaga Kerja Terserap',
        'bidang': 'INDUSTRI',
        'sheet': 'Kawasan Industri',
        'header': ['No', 'Kawasan', 'Jumlah Perusahaan', 'Luas Lahan', 'Tenaga Kerja Terserap', 'Sektor Dominan'],
        'baris': [
            [1, 'Kawasan Industri Utara', 24, '85 hektare', 4200, 'Tekstil dan Pakaian Jadi'],
            [2, 'Kawasan Industri Selatan', 17, '60 hektare', 2800, 'Makanan dan Minuman'],
            [3, 'Kawasan Industri Timur', 12, '45 hektare', 1900, 'Logam dan Komponen Otomotif'],
            [4, 'Sentra Industri Kerajinan', 38, '12 hektare', 620, 'Kerajinan Bambu dan Kayu'],
            [5, 'Sentra Industri Alas Kaki', 21, '8 hektare', 480, 'Sepatu dan Sandal Kulit'],
        ],
        'catatan': [
            'Data sebaran kawasan industri beserta penyerapan tenaga kerja pada masing-masing kawasan.',
            'Kawasan industri utara menjadi penyerap tenaga kerja terbesar di sektor tekstil.',
        ],
    },

    # ==========================================================
    # HUBINSYAKER - Hubungan Industrial & Syarat Kerja
    # ==========================================================
    {
        'nama': 'Rekap Perselisihan Hubungan Industrial 2024',
        'bidang': 'HUBINSYAKER',
        'sheet': 'Perselisihan',
        'header': ['No', 'Perusahaan', 'Jenis Perselisihan', 'Pokok Masalah', 'Mediator', 'Hasil'],
        'baris': [
            [1, 'PT Sandang Makmur', 'Perselisihan Hak', 'Keterlambatan pembayaran upah lembur', 'Suryadi', 'Perjanjian Bersama'],
            [2, 'PT Baja Perkasa', 'Perselisihan Pemutusan Hubungan Kerja', 'Pemutusan hubungan kerja sepihak', 'Marlina', 'Anjuran Mediator'],
            [3, 'CV Logistik Cepat', 'Perselisihan Kepentingan', 'Tuntutan kenaikan upah tahunan', 'Suryadi', 'Perjanjian Bersama'],
            [4, 'PT Pangan Sentosa', 'Perselisihan Hak', 'Pembayaran tunjangan hari raya', 'Hendrawan', 'Selesai di tingkat bipartit'],
            [5, 'CV Busana Indah', 'Perselisihan Pemutusan Hubungan Kerja', 'Pekerja mengundurkan diri tanpa pesangon', 'Marlina', 'Perjanjian Bersama'],
            [6, 'PT Energi Nusantara', 'Perselisihan Antar Serikat Pekerja', 'Perbedaan keanggotaan serikat pekerja', 'Hendrawan', 'Anjuran Mediator'],
        ],
        'catatan': [
            'Penyelesaian perselisihan hubungan industrial ditempuh melalui perundingan bipartit lalu mediasi.',
            'Mediator hubungan industrial memfasilitasi pekerja dan pengusaha mencapai perjanjian bersama.',
        ],
    },
    {
        'nama': 'Data Perjanjian Kerja Bersama Perusahaan',
        'bidang': 'HUBINSYAKER',
        'sheet': 'PKB',
        'header': ['No', 'Perusahaan', 'Nomor Pendaftaran', 'Masa Berlaku', 'Jumlah Pekerja', 'Serikat Pekerja'],
        'baris': [
            [1, 'PT Sandang Makmur', 'PKB/2023/007', '2023 sampai 2025', 820, 'Serikat Pekerja Tekstil'],
            [2, 'PT Baja Perkasa', 'PKB/2024/002', '2024 sampai 2026', 540, 'Serikat Pekerja Logam'],
            [3, 'PT Pangan Sentosa', 'PKB/2024/009', '2024 sampai 2026', 960, 'Serikat Pekerja Makanan'],
            [4, 'PT Energi Nusantara', 'PKB/2023/015', '2023 sampai 2025', 310, 'Serikat Pekerja Energi'],
            [5, 'PT Cahaya Abadi', 'PKB/2024/012', '2024 sampai 2026', 240, 'Serikat Pekerja Mandiri'],
        ],
        'catatan': [
            'Perjanjian kerja bersama memuat hak dan kewajiban pekerja serta pengusaha.',
            'Pendaftaran perjanjian kerja bersama dilakukan di dinas tenaga kerja setempat.',
        ],
    },
    {
        'nama': 'Laporan Upah Minimum dan Kepatuhan Perusahaan',
        'bidang': 'HUBINSYAKER',
        'sheet': 'Upah Minimum',
        'header': ['No', 'Perusahaan', 'Sektor', 'Upah Terendah', 'Upah Minimum Kabupaten', 'Status Kepatuhan'],
        'baris': [
            [1, 'PT Sandang Makmur', 'Tekstil', 3150000, 3100000, 'Patuh'],
            [2, 'PT Baja Perkasa', 'Logam', 3400000, 3100000, 'Patuh'],
            [3, 'CV Busana Indah', 'Konveksi', 2950000, 3100000, 'Penangguhan'],
            [4, 'PT Pangan Sentosa', 'Makanan', 3250000, 3100000, 'Patuh'],
            [5, 'CV Logistik Cepat', 'Transportasi', 3100000, 3100000, 'Patuh'],
            [6, 'Bengkel Sejahtera Motor', 'Jasa Perbengkelan', 2800000, 3100000, 'Penangguhan'],
            [7, 'Hotel Permata Indah', 'Perhotelan', 3120000, 3100000, 'Patuh'],
        ],
        'catatan': [
            'Pengawasan pelaksanaan upah minimum kabupaten terhadap perusahaan pengguna tenaga kerja.',
            'Perusahaan yang mengajukan penangguhan upah wajib melampirkan laporan keuangan.',
        ],
    },
    {
        'nama': 'Data Serikat Pekerja Terdaftar 2024',
        'bidang': 'HUBINSYAKER',
        'sheet': 'Serikat Pekerja',
        'header': ['No', 'Nama Serikat Pekerja', 'Perusahaan Induk', 'Nomor Bukti Catat', 'Jumlah Anggota', 'Ketua'],
        'baris': [
            [1, 'Serikat Pekerja Tekstil', 'PT Sandang Makmur', 'SP/2022/004', 410, 'Wahyu Utomo'],
            [2, 'Serikat Pekerja Logam', 'PT Baja Perkasa', 'SP/2023/006', 280, 'Rizal Effendi'],
            [3, 'Serikat Pekerja Makanan', 'PT Pangan Sentosa', 'SP/2021/011', 520, 'Nining Kartika'],
            [4, 'Serikat Pekerja Energi', 'PT Energi Nusantara', 'SP/2023/013', 160, 'Doni Saputra'],
            [5, 'Serikat Pekerja Mandiri', 'PT Cahaya Abadi', 'SP/2024/003', 95, 'Lestari Ningsih'],
        ],
        'catatan': [
            'Pencatatan serikat pekerja sebagai sarana penyalur aspirasi pekerja dalam hubungan industrial.',
            'Serikat pekerja berperan dalam perundingan perjanjian kerja bersama dengan pengusaha.',
        ],
    },

    # ==========================================================
    # SEKRETARIAT
    # ==========================================================
    {
        'nama': 'Laporan Keuangan Belanja Pegawai Tahun 2024',
        'bidang': 'SEKRETARIAT',
        'sheet': 'Belanja Pegawai',
        'header': ['No', 'Uraian Belanja', 'Anggaran', 'Realisasi', 'Sisa Anggaran', 'Persentase'],
        'baris': [
            [1, 'Gaji Pokok Pegawai Negeri Sipil', 4200000000, 4110000000, 90000000, '97.86%'],
            [2, 'Tunjangan Keluarga', 380000000, 372000000, 8000000, '97.89%'],
            [3, 'Tunjangan Jabatan Struktural', 540000000, 528000000, 12000000, '97.78%'],
            [4, 'Tambahan Penghasilan Pegawai', 1250000000, 1198000000, 52000000, '95.84%'],
            [5, 'Honorarium Tenaga Kontrak', 860000000, 845000000, 15000000, '98.26%'],
            [6, 'Iuran Jaminan Kesehatan', 210000000, 206000000, 4000000, '98.10%'],
            [7, 'Iuran Jaminan Kecelakaan Kerja', 95000000, 93000000, 2000000, '97.89%'],
        ],
        'catatan': [
            'Laporan realisasi anggaran belanja pegawai pada sekretariat dinas tenaga kerja.',
            'Penyusunan laporan keuangan mengacu pada standar akuntansi pemerintahan.',
        ],
    },
    {
        'nama': 'Rekap Surat Masuk dan Surat Keluar',
        'bidang': 'SEKRETARIAT',
        'sheet': 'Persuratan',
        'header': ['No', 'Nomor Surat', 'Jenis', 'Perihal', 'Asal atau Tujuan', 'Disposisi'],
        'baris': [
            [1, '005/145/2024', 'Surat Masuk', 'Undangan rapat koordinasi ketenagakerjaan', 'Pemerintah Provinsi', 'Sekretaris'],
            [2, '800/201/2024', 'Surat Keluar', 'Permohonan penambahan anggaran pelatihan', 'Badan Keuangan Daerah', 'Kepala Dinas'],
            [3, '560/312/2024', 'Surat Masuk', 'Pengaduan pekerja terkait upah lembur', 'Serikat Pekerja Tekstil', 'Bidang Hubungan Industrial'],
            [4, '027/088/2024', 'Surat Keluar', 'Pengadaan peralatan latihan kerja', 'Penyedia Barang dan Jasa', 'Sekretaris'],
            [5, '005/166/2024', 'Surat Masuk', 'Undangan sosialisasi program magang nasional', 'Kementerian Ketenagakerjaan', 'Kepala Dinas'],
            [6, '440/095/2024', 'Surat Keluar', 'Laporan pelaksanaan bursa kerja daerah', 'Pemerintah Provinsi', 'Bidang Penempatan'],
            [7, '900/233/2024', 'Surat Masuk', 'Pemberitahuan audit laporan keuangan', 'Inspektorat Daerah', 'Sekretaris'],
        ],
        'catatan': [
            'Administrasi persuratan dikelola sekretariat untuk menunjang kelancaran tugas dinas.',
            'Setiap surat masuk dicatat dalam buku agenda lalu diteruskan sesuai disposisi pimpinan.',
        ],
    },
    {
        'nama': 'Data Inventaris Barang Milik Daerah',
        'bidang': 'SEKRETARIAT',
        'sheet': 'Inventaris',
        'header': ['No', 'Nama Barang', 'Merek', 'Tahun Perolehan', 'Jumlah', 'Kondisi'],
        'baris': [
            [1, 'Komputer Meja', 'Lenovo', 2021, 24, 'Baik'],
            [2, 'Laptop Kerja', 'Asus', 2022, 15, 'Baik'],
            [3, 'Mesin Jahit Latihan', 'Singer', 2020, 30, 'Baik'],
            [4, 'Mesin Las Listrik', 'Rhino', 2021, 12, 'Rusak ringan'],
            [5, 'Meja Kerja Pegawai', 'Olympic', 2019, 48, 'Baik'],
            [6, 'Kursi Rapat', 'Chitose', 2020, 120, 'Baik'],
            [7, 'Kendaraan Dinas Roda Empat', 'Toyota', 2018, 4, 'Baik'],
            [8, 'Kendaraan Dinas Roda Dua', 'Honda', 2019, 9, 'Rusak ringan'],
            [9, 'Proyektor Ruang Pelatihan', 'Epson', 2022, 6, 'Baik'],
            [10, 'Lemari Arsip Besi', 'Brother', 2019, 18, 'Baik'],
        ],
        'catatan': [
            'Pencatatan barang milik daerah pada sekretariat meliputi peralatan kantor dan peralatan latihan kerja.',
            'Barang dengan kondisi rusak diusulkan untuk perbaikan atau penghapusan aset.',
        ],
    },
]
