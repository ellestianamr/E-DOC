-- ============================================================
-- TABEL TAMBAHAN UNTUK FITUR PENCARIAN ISI DOKUMEN (NLTK)
-- Jalankan di phpMyAdmin pada database: e-doc_db
-- ============================================================

-- 1) Kolom tambahan di tabel dokumen
--    teks_ekstraksi -> hasil pembacaan semua sel Excel (untuk halaman demo)
--    total_term     -> jumlah kata dasar hasil preprocessing (untuk hitung TF-IDF)
--    indexed_at     -> kapan dokumen terakhir di-index
ALTER TABLE dokumen
  ADD COLUMN teks_ekstraksi MEDIUMTEXT NULL AFTER ukuran_file,
  ADD COLUMN total_term INT NOT NULL DEFAULT 0 AFTER teks_ekstraksi,
  ADD COLUMN indexed_at DATETIME NULL AFTER total_term;

-- 2) Tabel inverted index: 1 baris = 1 kata dasar di 1 dokumen
--    ON DELETE CASCADE -> kalau dokumen dihapus, term-nya ikut terhapus
CREATE TABLE IF NOT EXISTS dokumen_term (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_dokumen INT NOT NULL,
  term VARCHAR(100) NOT NULL,
  tf INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_term_dokumen FOREIGN KEY (id_dokumen)
    REFERENCES dokumen(id) ON DELETE CASCADE,
  UNIQUE KEY uq_dokumen_term (id_dokumen, term),
  KEY idx_term (term)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
