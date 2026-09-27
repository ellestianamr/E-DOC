# -*- coding: utf-8 -*-
"""
==================================================================
PIPELINE PREPROCESSING TEKS BAHASA INDONESIA
==================================================================
Urutan tahapan (sesuai materi NLTK):

    1. Case folding       -> semua huruf jadi huruf kecil
    2. Cleaning (regex)   -> buang angka, tanda baca, simbol, rapikan spasi
    3. Tokenizing         -> potong kalimat jadi daftar kata (NLTK)
    4. Stopword removal   -> buang kata umum (NLTK + daftar tambahan)
    5. Stemming           -> ubah kata ke bentuk dasar (Sastrawi)

Fungsi utama: preprocess(teks) -> dict berisi HASIL TIAP TAHAP,
bukan cuma hasil akhir. Ini dipakai halaman demo "Proses Preprocessing".
==================================================================
"""
import os
import re

import nltk
from nltk.corpus import stopwords
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
FILE_STOPWORD_TAMBAHAN = os.path.join(BASE_DIR, 'stopwords_tambahan.txt')
FILE_PENGECUALIAN = os.path.join(BASE_DIR, 'stemming_pengecualian.txt')


# ==================================================================
# POLA REGEX untuk TAHAP 2 (cleaning)
# ------------------------------------------------------------------
# Tiap pola diberi nama + komentar supaya mudah dijelaskan saat
# presentasi dan gampang dites ulang di https://regexr.com
# ==================================================================

# (a) Buang alamat web lebih dulu, supaya "http://situs.com/hal-1"
#     tidak pecah jadi potongan huruf aneh waktu tanda baca dibuang.
#     https?://  -> "http://" atau "https://" (tanda ? = huruf "s" boleh ada boleh tidak)
#     \S+        -> satu karakter atau lebih yang BUKAN spasi
RE_URL = re.compile(r'https?://\S+|www\.\S+')

# (b) Buang SEMUA angka.
#     \d  -> satu digit angka 0-9
#     +   -> satu kali atau lebih, jadi "2024" dianggap satu kesatuan
#     Alasan: angka tahun/jumlah tidak membantu pencarian isi dokumen.
RE_ANGKA = re.compile(r'\d+')

# (c) Buang tanda baca & simbol.
#     [^...] -> "kecuali" (negasi). \w = huruf/angka/underscore, \s = spasi.
#     Jadi [^\w\s] berarti: apa pun yang BUKAN huruf dan BUKAN spasi
#     -> titik, koma, titik dua, tanda kurung, %, Rp., emoji, dll.
RE_TANDA_BACA = re.compile(r'[^\w\s]')

# (d) Buang underscore.
#     Perlu pola sendiri karena \w di atas MASIH menganggap "_" sebagai
#     karakter kata, padahal nama file kita banyak yang pakai "_".
RE_GARIS_BAWAH = re.compile(r'_+')

# (e) Rapikan spasi.
#     \s -> spasi, tab, atau baris baru. \s+ -> satu atau lebih.
#     Diganti dengan SATU spasi biasa, lalu .strip() membuang spasi
#     di awal & akhir teks.
RE_SPASI = re.compile(r'\s+')


# ==================================================================
# Objek berat (stemmer & daftar stopword) dibuat SEKALI saja
# lalu dipakai berulang, supaya service tidak lambat.
# ==================================================================
_stemmer = StemmerFactory().create_stemmer()
_cache_stem = {}          # ingat hasil stemming kata yang sudah pernah diproses
_stopwords = None         # diisi saat pertama kali dibutuhkan
_pengecualian = None      # diisi saat pertama kali dibutuhkan


def _muat_stopword_tambahan():
    """Baca stopwords_tambahan.txt. Baris kosong & baris '#' diabaikan."""
    hasil = set()
    if not os.path.exists(FILE_STOPWORD_TAMBAHAN):
        return hasil
    with open(FILE_STOPWORD_TAMBAHAN, 'r', encoding='utf-8') as f:
        for baris in f:
            kata = baris.strip().lower()
            if kata and not kata.startswith('#'):
                hasil.add(kata)
    return hasil


def daftar_stopword():
    """Gabungan stopword NLTK bahasa Indonesia + stopword tambahan domain."""
    global _stopwords
    if _stopwords is None:
        try:
            bawaan = set(stopwords.words('indonesian'))
        except LookupError:
            # Data NLTK belum diunduh -> jalankan: python setup_nltk.py
            bawaan = set()
        _stopwords = bawaan | _muat_stopword_tambahan()
    return _stopwords


def daftar_pengecualian():
    """Kata-kata yang hasil stemming-nya ditentukan manual.

    Lihat stemming_pengecualian.txt untuk penjelasannya.
    """
    global _pengecualian
    if _pengecualian is None:
        hasil = {}
        if os.path.exists(FILE_PENGECUALIAN):
            with open(FILE_PENGECUALIAN, 'r', encoding='utf-8') as f:
                for baris in f:
                    baris = baris.strip()
                    if not baris or baris.startswith('#') or '=' not in baris:
                        continue
                    asal, dasar = baris.split('=', 1)
                    hasil[asal.strip().lower()] = dasar.strip().lower()
        _pengecualian = hasil
    return _pengecualian


def muat_ulang_stopword():
    """Dipanggil kalau stopwords_tambahan.txt / stemming_pengecualian.txt diubah."""
    global _stopwords, _pengecualian
    _stopwords = None
    _pengecualian = None
    _cache_stem.clear()
    daftar_pengecualian()
    return daftar_stopword()


# ==================================================================
# TAHAP 1 - CASE FOLDING
# ==================================================================
def case_folding(teks):
    """Samakan semua huruf jadi kecil, supaya 'Kerja', 'KERJA', 'kerja'
    dianggap kata yang sama."""
    return (teks or '').lower()


# ==================================================================
# TAHAP 2 - CLEANING (pakai modul re)
# ==================================================================
def cleaning(teks):
    """Buang URL, angka, tanda baca, underscore, lalu rapikan spasi."""
    teks = RE_URL.sub(' ', teks)            # (a) alamat web
    teks = RE_ANGKA.sub(' ', teks)          # (b) angka
    teks = RE_TANDA_BACA.sub(' ', teks)     # (c) tanda baca & simbol
    teks = RE_GARIS_BAWAH.sub(' ', teks)    # (d) underscore
    teks = RE_SPASI.sub(' ', teks)          # (e) spasi berlebih -> satu spasi
    return teks.strip()


# ==================================================================
# TAHAP 3 - TOKENIZING
# ==================================================================
def tokenizing(teks):
    """Potong teks jadi daftar kata memakai NLTK word_tokenize."""
    if not teks:
        return []
    try:
        return nltk.word_tokenize(teks)
    except LookupError:
        # Kalau data 'punkt' belum diunduh, jatuhkan ke pemisah spasi biasa
        # supaya service tetap jalan (jangan lupa jalankan setup_nltk.py).
        return teks.split()


# ==================================================================
# TAHAP 4 - STOPWORD REMOVAL
# ==================================================================
def stopword_removal(token_list):
    """Buang kata umum dan kata yang cuma 1 huruf."""
    sw = daftar_stopword()
    return [t for t in token_list if t not in sw and len(t) > 1]


# ==================================================================
# TAHAP 5 - STEMMING (Sastrawi)
# ==================================================================
def stemming(token_list):
    """Ubah tiap kata ke bentuk dasarnya.

    NLTK tidak punya stemmer bahasa Indonesia (PorterStemmer hanya untuk
    bahasa Inggris), jadi dipakai Sastrawi.
        'pelatihan' -> 'latih'      'melatih'  -> 'latih'
        'pekerja'   -> 'kerja'      'bekerja'  -> 'kerja'
    Inilah yang membuat query "melatih pekerja" bisa menemukan dokumen
    "Rekap Pelatihan Kerja 2024".

    Beberapa kata dipotong terlalu jauh oleh Sastrawi (mis. "peserta"
    jadi "serta"), jadi dicek dulu ke daftar pengecualian.
    """
    kecuali = daftar_pengecualian()
    hasil = []
    for kata in token_list:
        if kata not in _cache_stem:
            _cache_stem[kata] = kecuali.get(kata) or _stemmer.stem(kata)
        dasar = _cache_stem[kata]
        if dasar:
            hasil.append(dasar)
    return hasil


# ==================================================================
# PIPELINE LENGKAP
# ==================================================================
def preprocess(teks):
    """Jalankan tahap 1-5 dan kembalikan HASIL TIAP TAHAP.

    Return dict:
        teks_asli, case_folding, cleaning,
        tokenizing (list), stopword_removal (list),
        stemming (list), stemming_final (list, setelah stopword dicek ulang),
        jumlah (dict berisi jumlah token tiap tahap)
    """
    teks_asli = teks or ''

    t1 = case_folding(teks_asli)
    t2 = cleaning(t1)
    t3 = tokenizing(t2)
    t4 = stopword_removal(t3)
    t5 = stemming(t4)

    # Penyaringan ulang setelah stemming: kadang kata berimbuhan lolos di
    # tahap 4, tapi bentuk dasarnya ternyata stopword.
    # Contoh: "tahunan" -> "tahun" (ada di stopword tambahan).
    sw = daftar_stopword()
    t5_final = [k for k in t5 if k not in sw and len(k) > 1]

    return {
        'teks_asli': teks_asli,
        'case_folding': t1,
        'cleaning': t2,
        'tokenizing': t3,
        'stopword_removal': t4,
        'stemming': t5,
        'stemming_final': t5_final,
        'jumlah': {
            'karakter_asli': len(teks_asli),
            'karakter_cleaning': len(t2),
            'token_tokenizing': len(t3),
            'token_setelah_stopword': len(t4),
            'token_setelah_stemming': len(t5_final),
            'token_unik': len(set(t5_final)),
        },
    }


def hitung_tf(token_list):
    """Hitung Term Frequency: berapa kali tiap kata dasar muncul."""
    tf = {}
    for kata in token_list:
        tf[kata] = tf.get(kata, 0) + 1
    return tf
