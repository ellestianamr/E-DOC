# -*- coding: utf-8 -*-
"""
Konfigurasi service NLP.
Semua pengaturan yang mungkin berubah (database, folder, port) dikumpulkan di sini
supaya tidak perlu mengubah file lain.
"""
import os

# Folder nlp_service/ itu sendiri
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# Folder project E-DOC (satu tingkat di atas nlp_service/)
PROJECT_DIR = os.path.dirname(BASE_DIR)

# Folder tempat file Excel disimpan: E-DOC/uploads/<bidang>/<nama_file>
UPLOADS_DIR = os.path.join(PROJECT_DIR, 'uploads')

# Koneksi MySQL -- harus sama dengan pages/process/db.php
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'root',
    'password': '',
    'database': 'e-doc_db',
    'charset': 'utf8mb4',
}

# Alamat service Flask
SERVICE_HOST = '127.0.0.1'
SERVICE_PORT = 5000

# Batas jumlah sel Excel yang dibaca per file (pengaman supaya tidak terlalu berat)
MAX_SEL_EXCEL = 200000
