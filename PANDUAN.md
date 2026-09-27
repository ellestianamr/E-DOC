# PANDUAN FITUR PENCARIAN DOKUMEN BERBASIS NLTK
### Aplikasi E-DOC — Dinas Perindustrian dan Tenaga Kerja

Dokumen ini menjelaskan fitur **pencarian dokumen berdasarkan isi file Excel**
yang ditambahkan ke aplikasi E-DOC, mulai dari cara memasang, cara kerja tiap
tahap preprocessing, sampai rumus perhitungan yang dipakai.

---

## DAFTAR ISI

1. [Gambaran Umum](#1-gambaran-umum)
2. [Cara Install](#2-cara-install)
3. [Cara Menjalankan Sehari-hari](#3-cara-menjalankan-sehari-hari)
4. [File yang Ditambahkan](#4-file-yang-ditambahkan)
5. [Alur Kerja Sistem](#5-alur-kerja-sistem)
6. [Preprocessing: Penjelasan Tiap Tahap](#6-preprocessing-penjelasan-tiap-tahap)
7. [Penjelasan Pola Regex](#7-penjelasan-pola-regex)
8. [TF-IDF dan Cosine Similarity](#8-tf-idf-dan-cosine-similarity)
9. [Struktur Database](#9-struktur-database)
10. [Daftar Endpoint Service Python](#10-daftar-endpoint-service-python)
11. [Data Contoh untuk Pengujian](#11-data-contoh-untuk-pengujian)
12. [Kalau Ada Masalah](#12-kalau-ada-masalah)
13. [Saran Alur Demo Presentasi](#13-saran-alur-demo-presentasi)

---

## 1. GAMBARAN UMUM

### Masalah sebelumnya

Pencarian di aplikasi E-DOC sebelumnya hanya mengandalkan fitur filter DataTables,
yang mencocokkan **huruf per huruf pada judul dokumen** saja. Akibatnya:

| Yang dicari | Hasil sebelumnya | Alasan |
|---|---|---|
| "melatih pekerja" | tidak ketemu apa-apa | judulnya "Rekap **Pelatihan Kerja** 2024", tulisannya beda |
| "menjahit" | tidak ketemu | kata itu ada di **dalam** Excel, bukan di judul |

### Yang ditambahkan

Sekarang isi file Excel ikut dibaca dan diproses dengan **NLTK**, sehingga:

- pencarian membaca **isi seluruh sel Excel**, bukan cuma judul
- kata dicocokkan dalam **bentuk dasar**, jadi "melatih" cocok dengan "pelatihan"
- hasil diurutkan berdasarkan **tingkat relevansi** (skor %), bukan urutan tanggal

### Teknologi yang dipakai

| Komponen | Kegunaan |
|---|---|
| **NLTK** | tokenizing (`word_tokenize`) dan daftar stopword bahasa Indonesia |
| **Sastrawi** | stemming bahasa Indonesia (NLTK tidak punya stemmer bahasa Indonesia) |
| **modul `re` Python** | cleaning teks dengan regular expression |
| **openpyxl / xlrd** | membaca isi file Excel `.xlsx` dan `.xls` |
| **Flask** | membungkus semuanya jadi service yang bisa dipanggil PHP |
| **PyMySQL** | menyimpan hasil index ke MySQL yang sama dengan aplikasi PHP |

### Kenapa harus ada service Python terpisah?

NLTK dan Sastrawi adalah library Python, sedangkan E-DOC ditulis dengan PHP.
Keduanya tidak bisa langsung saling memanggil. Solusinya: Python dijalankan
sebagai **service kecil** di `http://127.0.0.1:5000`, lalu PHP memanggilnya
lewat **cURL**. Keduanya membaca database MySQL yang sama (`e-doc_db`).

```
┌──────────────────┐   cURL (HTTP + JSON)   ┌─────────────────────┐
│  E-DOC (PHP)     │ ─────────────────────► │  Service Python     │
│  XAMPP / Apache  │ ◄───────────────────── │  Flask :5000        │
└────────┬─────────┘        hasil JSON      └──────────┬──────────┘
         │                                             │
         └──────────────► MySQL: e-doc_db ◄────────────┘
```

---

## 2. CARA INSTALL

### Syarat

| Kebutuhan | Keterangan |
|---|---|
| XAMPP | Apache + MySQL sudah jalan, project ada di `C:\xampp\htdocs\E-DOC` |
| Python | versi 3.10 atau lebih baru, unduh di <https://www.python.org/downloads/> |
| Koneksi internet | hanya saat install (mengunduh library dan data NLTK) |

> **Penting saat memasang Python:** centang **"Add Python to PATH"** di layar
> pertama installer. Kalau lupa, perintah `python` tidak akan dikenali.
> Cek dengan membuka Command Prompt lalu ketik `python --version`.

### Langkah 1 — Pasang service Python

Buka folder `C:\xampp\htdocs\E-DOC\nlp_service`, lalu **klik dua kali `setup.bat`**.

Script ini otomatis melakukan tiga hal:

1. membuat *virtual environment* (folder `venv`) — supaya library project ini
   tidak tercampur dengan project Python lain di komputer Anda
2. memasang library dari `requirements.txt`
3. mengunduh data NLTK: `punkt`, `punkt_tab`, dan `stopwords`

Tunggu sampai muncul tulisan **SELESAI**. Proses ini cukup dijalankan **sekali saja**.

<details>
<summary>Kalau lebih suka lewat Command Prompt manual</summary>

```bat
cd C:\xampp\htdocs\E-DOC\nlp_service
python -m venv venv
venv\Scripts\python.exe -m pip install --upgrade pip
venv\Scripts\python.exe -m pip install -r requirements.txt
venv\Scripts\python.exe setup_nltk.py
```
</details>

### Langkah 2 — Tambahkan tabel di database

Buka **phpMyAdmin** → pilih database `e-doc_db` → tab **Import** →
pilih file `nlp_service/schema_nlp.sql` → klik **Go**.

File itu berisi dua perintah:

- menambah 3 kolom di tabel `dokumen` (`teks_ekstraksi`, `total_term`, `indexed_at`)
- membuat tabel baru `dokumen_term`

### Langkah 3 — Nyalakan service

Klik dua kali **`nlp_service/start_service.bat`**.
Akan muncul jendela hitam berisi:

```
============================================================
 SERVICE NLP E-DOC
 Alamat  : http://127.0.0.1:5000
 Tekan CTRL+C untuk berhenti
============================================================
```

**Biarkan jendela ini tetap terbuka** selama aplikasi dipakai.

Untuk memastikan service benar-benar hidup, buka
<http://127.0.0.1:5000> di browser. Harusnya muncul tulisan JSON
`"message": "Service NLP aktif"`.

### Langkah 4 — Index dokumen yang sudah terlanjur ada

Dokumen yang diupload **sebelum** fitur ini dipasang belum punya index,
jadi belum bisa ditemukan. Jalankan sekali:

```bat
cd C:\xampp\htdocs\E-DOC\nlp_service
venv\Scripts\python.exe reindex.py
```

Pilihan perintah `reindex.py`:

| Perintah | Kegunaan |
|---|---|
| `reindex.py` | index hanya dokumen yang **belum** punya index |
| `reindex.py --all` | index ulang **semua** dokumen (pakai ini setelah mengubah stopword) |
| `reindex.py --id 7` | index ulang satu dokumen tertentu |
| `reindex.py --bidang PPTK` | index ulang satu bidang saja |

Script ini **tidak** memerlukan service Flask menyala, jadi boleh dijalankan kapan saja.

---

## 3. CARA MENJALANKAN SEHARI-HARI

Setiap kali mau memakai aplikasi:

1. buka **XAMPP Control Panel** → **Start** Apache dan MySQL
2. klik dua kali **`nlp_service/start_service.bat`** → biarkan jendelanya terbuka
3. buka <http://localhost/E-DOC> di browser

### Kalau service Python lupa dinyalakan

Aplikasi **tetap jalan normal** — ini memang dirancang begitu:

| Yang dilakukan | Akibatnya |
|---|---|
| upload dokumen | **tetap berhasil**, hanya muncul catatan bahwa indexing gagal |
| edit / hapus dokumen | tetap berhasil seperti biasa |
| buka menu Pencarian Dokumen | muncul kotak peringatan + cara menyalakannya |
| menu lain (Rekapan, Pengaturan) | tidak terpengaruh sama sekali |

Setelah service dinyalakan lagi, dokumen yang sempat gagal tinggal diperbaiki
dengan `venv\Scripts\python.exe reindex.py`.

---

## 4. FILE YANG DITAMBAHKAN

### Folder baru: `nlp_service/`

| File | Isi |
|---|---|
| `app.py` | service Flask + semua endpoint |
| `preprocessing.py` | **inti pipeline NLTK** — 5 tahap preprocessing |
| `excel_reader.py` | membaca isi seluruh sel file Excel |
| `db.py` | koneksi MySQL + perhitungan TF-IDF & cosine similarity |
| `config.py` | pengaturan database, port, folder uploads |
| `stopwords_tambahan.txt` | daftar stopword khusus dokumen kantor (bisa diedit) |
| `stemming_pengecualian.txt` | koreksi kata yang salah di-stem oleh Sastrawi |
| `reindex.py` | script CLI untuk index ulang dokumen |
| `sample_data.py` + `seed_sample_data.py` | membuat 15 dokumen Excel contoh |
| `bersihkan_contoh.py` | menyingkirkan / mengembalikan dokumen contoh |
| `schema_nlp.sql` | perintah SQL tabel `dokumen_term` |
| `setup_nltk.py` | mengunduh data NLTK |
| `setup.bat` / `start_service.bat` | pemasangan & menjalankan service |
| `requirements.txt` | daftar library Python |

### File PHP baru

| File | Isi |
|---|---|
| `pages/process/nlp_client.php` | jembatan PHP → Python lewat cURL |
| `pages/pencarian.php` | halaman **Pencarian Dokumen** |
| `pages/preprocessing.php` | halaman **Proses Preprocessing** (untuk demo) |

### File PHP lama yang diubah seperlunya

| File | Perubahan |
|---|---|
| `pages/process/upload_dokumen.php` | setelah upload berhasil → panggil `POST /index` |
| `pages/process/update_dokumen.php` | setelah file/nama diubah → index ulang |
| `pages/process/delete_dokumen.php` | sebelum hapus → panggil `DELETE /index/<id>` |
| `template/sidebar.php` | tambah 2 menu baru |
| `template/content_header.php` | tambah judul untuk 2 halaman baru |
| `template/table_documents.php` | tambah kolom **Kata Kunci** (5 kata dengan TF tertinggi) |

Fitur login, role, dan menu lama **tidak diubah sama sekali**.

---

## 5. ALUR KERJA SISTEM

### A. Saat dokumen diupload (indexing)

```
User upload Excel lewat tombol "Tambah Dokumen"
        │
        ▼
upload_dokumen.php  ─ simpan file ke uploads/<bidang>/
        │           ─ INSERT ke tabel dokumen
        │
        ▼ cURL: POST /index {"id_dokumen": 17}
Service Python
        │
        ├─ 1. baca semua sheet & sel Excel      (openpyxl)
        ├─ 2. gabungkan dengan nama_dokumen
        ├─ 3. PREPROCESSING 5 tahap             (NLTK + Sastrawi)
        ├─ 4. hitung TF tiap kata dasar
        └─ 5. simpan ke tabel dokumen_term
                                                → dokumen siap dicari
```

### B. Saat user mencari (searching)

```
User ketik "melatih pekerja" di menu Pencarian Dokumen
        │
        ▼
pencarian.php ─ tentukan hak akses:
        │        admin → semua bidang
        │        user bidang → bidangnya saja
        │
        ▼ cURL: POST /search {"query": "...", "bidang": "..."}
Service Python
        │
        ├─ 1. PREPROCESSING kata kunci   ← PIPELINE YANG SAMA PERSIS
        │      "melatih pekerja" → ["latih", "kerja"]
        ├─ 2. ambil dokumen yang punya kata itu dari dokumen_term
        ├─ 3. hitung bobot TF-IDF
        ├─ 4. hitung cosine similarity
        └─ 5. urutkan dari skor tertinggi
                                          → tabel hasil + skor %
```

> **Kunci utamanya:** dokumen dan kata kunci diproses dengan **pipeline yang
> sama persis**. Karena itu "melatih" dan "pelatihan" sama-sama berubah jadi
> "latih", sehingga bisa saling bertemu.

---

## 6. PREPROCESSING: PENJELASAN TIAP TAHAP

Semua kode ada di **`nlp_service/preprocessing.py`**, fungsi `preprocess()`.
Fungsi ini sengaja mengembalikan **hasil setiap tahap**, bukan hanya hasil akhir,
supaya bisa ditampilkan di halaman demo.

Contoh yang dipakai di bawah ini:

> **`Rekap Pelatihan Kerja Tahun 2024 (BLK Kota), peserta: 30 orang!`**

---

### TAHAP 1 — Case Folding

**Tujuan:** menyamakan huruf besar dan huruf kecil.

Tanpa tahap ini, komputer menganggap `Kerja`, `KERJA`, dan `kerja` sebagai tiga
kata yang berbeda, karena kode huruf besar dan kecil memang berbeda.

```python
def case_folding(teks):
    return teks.lower()
```

| | |
|---|---|
| **Input** | `Rekap Pelatihan Kerja Tahun 2024 (BLK Kota), peserta: 30 orang!` |
| **Output** | `rekap pelatihan kerja tahun 2024 (blk kota), peserta: 30 orang!` |

---

### TAHAP 2 — Cleaning (Regular Expression)

**Tujuan:** membuang karakter yang tidak membantu pencarian.

Dokumen Excel penuh dengan angka, tanda baca, dan simbol (`Rp`, `%`, `:`, `()`).
Semua itu tidak membantu membedakan satu dokumen dengan dokumen lain,
justru membuat hasil tokenizing jadi berantakan.

```python
def cleaning(teks):
    teks = RE_URL.sub(' ', teks)           # (a) alamat web
    teks = RE_ANGKA.sub(' ', teks)         # (b) angka
    teks = RE_TANDA_BACA.sub(' ', teks)    # (c) tanda baca & simbol
    teks = RE_GARIS_BAWAH.sub(' ', teks)   # (d) underscore
    teks = RE_SPASI.sub(' ', teks)         # (e) spasi berlebih
    return teks.strip()
```

| | |
|---|---|
| **Input** | `rekap pelatihan kerja tahun 2024 (blk kota), peserta: 30 orang!` |
| **Output** | `rekap pelatihan kerja tahun blk kota peserta orang` |

Yang terbuang: `2024`, `(`, `)`, `,`, `:`, `30`, `!`, dan spasi ganda.
Penjelasan tiap polanya ada di [bagian 7](#7-penjelasan-pola-regex).

---

### TAHAP 3 — Tokenizing

**Tujuan:** memotong kalimat menjadi daftar kata satuan (*token*).

Komputer perlu tahu batas antar kata sebelum bisa menghitung frekuensi.
Dipakai fungsi NLTK `word_tokenize()`.

```python
import nltk
token = nltk.word_tokenize(teks)
```

| | |
|---|---|
| **Input** | `rekap pelatihan kerja tahun blk kota peserta orang` |
| **Output** | `['rekap', 'pelatihan', 'kerja', 'tahun', 'blk', 'kota', 'peserta', 'orang']` |
| **Jumlah** | 8 token |

> Data `punkt` dan `punkt_tab` yang diunduh `setup_nltk.py` adalah model
> pemotong kata yang dipakai fungsi ini.

---

### TAHAP 4 — Stopword Removal

**Tujuan:** membuang kata umum yang muncul di hampir semua dokumen.

Kata seperti "yang", "dan", "di", "tahun" muncul di hampir semua dokumen,
jadi tidak berguna untuk membedakan dokumen mana yang paling relevan.
Membuangnya membuat index lebih kecil sekaligus hasil pencarian lebih tepat.

Sumbernya ada dua:

1. **NLTK** — `stopwords.words('indonesian')`, berisi **757 kata unik**
2. **Daftar tambahan** — `stopwords_tambahan.txt`, berisi **40 kata** khusus
   dokumen kantor, misalnya `no`, `jumlah`, `data`, `tahun`, `keterangan`,
   `rekap`, `laporan`, `rp`, `orang`

Setelah digabung dan duplikatnya dibuang, totalnya **790 stopword**.
Jumlah ini bisa dilihat kapan saja di <http://127.0.0.1:5000/status>.

Kata yang hanya terdiri dari 1 huruf juga dibuang.

```python
def stopword_removal(token_list):
    sw = daftar_stopword()
    return [t for t in token_list if t not in sw and len(t) > 1]
```

| | |
|---|---|
| **Input** | `['rekap', 'pelatihan', 'kerja', 'tahun', 'blk', 'kota', 'peserta', 'orang']` |
| **Output** | `['pelatihan', 'kerja', 'blk', 'kota', 'peserta']` |
| **Jumlah** | 8 token → **5 token** (terbuang: `rekap`, `tahun`, `orang`) |

> **Menambah stopword sendiri:** buka `nlp_service/stopwords_tambahan.txt`,
> tulis satu kata per baris dalam huruf kecil, simpan, lalu jalankan
> `venv\Scripts\python.exe reindex.py --all` agar index ikut diperbarui.

---

### TAHAP 5 — Stemming (Sastrawi)

**Tujuan:** mengembalikan kata ke bentuk dasarnya.

**Ini tahap terpenting untuk fitur ini.** Bahasa Indonesia punya banyak imbuhan
(`me-`, `pe-`, `-an`, `-kan`), sehingga satu akar kata bisa muncul dalam banyak
bentuk. Tanpa stemming, "melatih" tidak akan pernah bertemu "pelatihan".

```python
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
stemmer = StemmerFactory().create_stemmer()
stemmer.stem('pelatihan')   # -> 'latih'
```

| | |
|---|---|
| **Input** | `['pelatihan', 'kerja', 'blk', 'kota', 'peserta']` |
| **Output** | `['latih', 'kerja', 'blk', 'kota', 'peserta']` |

#### Kenapa Sastrawi, bukan NLTK?

NLTK menyediakan `PorterStemmer` dan `SnowballStemmer`, tapi keduanya hanya
untuk **bahasa Inggris** dan tidak mengenal imbuhan bahasa Indonesia.
Karena itu tahap stemming memakai **Sastrawi**, library Python khusus
bahasa Indonesia. Tahap 3 dan 4 tetap memakai NLTK.

#### Contoh hasil stemming pada data nyata

| Kata asli | Bentuk dasar | | Kata asli | Bentuk dasar |
|---|---|---|---|---|
| pelatihan | latih | | keuangan | uang |
| melatih | latih | | perselisihan | selisih |
| pekerja | kerja | | penempatan | tempat |
| bekerja | kerja | | pendidikan | didik |
| perusahaan | usaha | | pemasaran | pasar |
| pengusaha | usaha | | perizinan | izin |

#### Catatan: penanganan over-stemming

Sastrawi bekerja dengan aturan pemotongan imbuhan, jadi sesekali ada kata
yang terpotong terlalu jauh:

```
peserta  →  serta      (salah!)
```

Parahnya, "serta" justru termasuk stopword, sehingga kata "peserta" bisa
**hilang sama sekali** padahal itu kata penting di dokumen pelatihan.

Solusinya: file **`stemming_pengecualian.txt`** berisi daftar koreksi manual
dengan format `kata=bentuk_dasar`:

```
peserta=peserta
```

Setelah diubah, panggil `POST /reload-stopwords` atau restart service.

---

### Ringkasan lima tahap

| Tahap | Alat | Input contoh | Output contoh | Jumlah token |
|---|---|---|---|---|
| Teks asli | — | `Rekap Pelatihan Kerja Tahun 2024 (BLK Kota), peserta: 30 orang!` | — | 63 karakter |
| 1. Case folding | `.lower()` | teks asli | `rekap pelatihan kerja tahun 2024 (blk kota), peserta: 30 orang!` | — |
| 2. Cleaning | modul `re` | hasil tahap 1 | `rekap pelatihan kerja tahun blk kota peserta orang` | 50 karakter |
| 3. Tokenizing | `nltk.word_tokenize` | hasil tahap 2 | `['rekap','pelatihan','kerja','tahun','blk','kota','peserta','orang']` | 8 |
| 4. Stopword removal | `nltk.corpus.stopwords` | hasil tahap 3 | `['pelatihan','kerja','blk','kota','peserta']` | 5 |
| 5. Stemming | Sastrawi | hasil tahap 4 | `['latih','kerja','blk','kota','peserta']` | 5 |

Hasil tahap 5 inilah yang disimpan ke tabel `dokumen_term`.

---

## 7. PENJELASAN POLA REGEX

Semua pola ada di bagian atas `nlp_service/preprocessing.py`.
Semuanya bisa dites di **<https://regexr.com>** dengan menyalin polanya.

### Simbol dasar yang perlu diketahui lebih dulu

| Simbol | Artinya |
|---|---|
| `\d` | satu digit angka `0`–`9` |
| `\w` | satu karakter kata: huruf, angka, atau underscore `_` |
| `\s` | satu karakter spasi (termasuk tab dan baris baru) |
| `\S` | kebalikan `\s` — apa pun yang **bukan** spasi |
| `+` | pengulangan: "satu kali atau lebih" |
| `?` | opsional: "boleh ada, boleh tidak" |
| `[...]` | salah satu dari karakter di dalam kurung |
| `[^...]` | **negasi** — apa pun **kecuali** yang di dalam kurung |
| `\|` | atau |

---

### (a) `https?://\S+|www\.\S+` — membuang alamat web

```python
RE_URL = re.compile(r'https?://\S+|www\.\S+')
```

**Cara baca:** `http`, lalu `s?` artinya huruf `s` boleh ada boleh tidak
(jadi cocok untuk `http://` maupun `https://`), lalu `://`, lalu `\S+`
yaitu deretan karakter apa pun sampai ketemu spasi. Bagian setelah `|`
menangani alamat yang diawali `www.`

**Kenapa harus dibuang duluan?** Kalau URL dibiarkan, pola (c) akan membuang
`:`, `/`, dan `.` di dalamnya, sehingga `https://disnaker.go.id/data-2024`
pecah menjadi potongan tidak bermakna seperti `https`, `disnaker`, `go`, `id`.

| Input | Output |
|---|---|
| `info lengkap di https://disnaker.go.id/pelatihan ya` | `info lengkap di   ya` |

> Pada semua contoh di bagian ini, karakter yang dibuang **diganti satu spasi**,
> bukan dihapus begitu saja. Itu sengaja, supaya kata di kiri dan kanannya tidak
> menempel jadi satu. Spasi berlebih yang muncul akibatnya nanti dirapikan
> oleh pola (e) di langkah terakhir.

---

### (b) `\d+` — membuang angka

```python
RE_ANGKA = re.compile(r'\d+')
```

**Cara baca:** `\d` satu digit, `+` satu kali atau lebih.
Tanda `+` penting supaya `2024` diperlakukan sebagai **satu kesatuan**
dan diganti satu spasi, bukan empat spasi.

**Kenapa dibuang?** Angka tahun, jumlah peserta, dan nominal rupiah tidak
membantu menentukan dokumen mana yang relevan dengan kata kunci teks.

| Input | Output |
|---|---|
| `rekap pelatihan kerja tahun 2024` | `rekap pelatihan kerja tahun ` |
| `peserta 30 orang biaya 1500000` | `peserta   orang biaya ` |

---

### (c) `[^\w\s]` — membuang tanda baca dan simbol

```python
RE_TANDA_BACA = re.compile(r'[^\w\s]')
```

**Cara baca:** tanda `^` di dalam kurung siku berarti **"kecuali"**.
Jadi polanya berbunyi: *cocokkan satu karakter apa pun yang **bukan** huruf/angka
(`\w`) dan **bukan** spasi (`\s`)*.

Pendekatan "kecuali" ini lebih aman daripada menulis daftar tanda baca satu per
satu, karena otomatis menangkap simbol yang tidak terpikirkan sebelumnya —
termasuk `©`, `™`, atau emoji yang kadang ikut tersalin ke dalam sel Excel.

| Input | Output |
|---|---|
| `(blk kota), peserta: 30 orang!` | `blk kota  peserta  30 orang` |
| `anggaran: Rp1.500.000,-` | `anggaran  Rp1 500 000` |

Yang terbuang di baris pertama: `(`, `)`, `,`, `:`, dan `!`.
Angka `30` masih utuh karena contoh ini hanya menjalankan pola (c) saja —
di pipeline sebenarnya angka sudah dibuang lebih dulu oleh pola (b).

> **Catatan urutan:** pola ini dijalankan **setelah** pola (b),
> sehingga saat giliran pola ini angkanya sudah lenyap lebih dulu.

---

### (d) `_+` — membuang underscore

```python
RE_GARIS_BAWAH = re.compile(r'_+')
```

**Kenapa perlu pola sendiri?** Karena `\w` pada pola (c) **masih menganggap
underscore sebagai karakter kata**, jadi `_` lolos dari penyaringan sebelumnya.
Padahal nama file di aplikasi ini memakai pola
`1790431978_Rekap_Pelatihan_Kerja_2024.xlsx` yang penuh underscore.

| Input | Output |
|---|---|
| `rekap_pelatihan_kerja` | `rekap pelatihan kerja` |
| `1790431978_Rekap_Pelatihan_Kerja_2024` | `1790431978 Rekap Pelatihan Kerja 2024` |

---

### (e) `\s+` — merapikan spasi

```python
RE_SPASI = re.compile(r'\s+')
```

**Cara baca:** satu atau lebih karakter spasi, diganti dengan **satu** spasi biasa.

Pola ini dijalankan **paling akhir**, karena keempat pola sebelumnya mengganti
karakter yang dibuang dengan spasi — sehingga teks jadi penuh spasi berlebih.
Pola ini juga menyatukan tab dan baris baru (yang banyak muncul saat membaca
sel Excel) menjadi spasi biasa. Terakhir, `.strip()` membuang spasi
di awal dan akhir teks.

| Input | Output |
|---|---|
| `rekap   pelatihan     kerja  ` | `rekap pelatihan kerja` |

---

### Urutan lengkap dengan satu contoh

| Langkah | Hasil |
|---|---|
| Teks masuk (setelah case folding) | `rekap pelatihan kerja tahun 2024 (blk kota), peserta: 30 orang!` |
| setelah (a) URL | *tidak berubah — tidak ada alamat web* |
| setelah (b) `\d+` | `rekap pelatihan kerja tahun (blk kota), peserta: orang!` |
| setelah (c) `[^\w\s]` | `rekap pelatihan kerja tahun blk kota peserta orang` (masih banyak spasi ganda) |
| setelah (d) `_+` | *tidak berubah — tidak ada underscore* |
| setelah (e) `\s+` + `.strip()` | `rekap pelatihan kerja tahun blk kota peserta orang` |

Baru setelah itu teks masuk ke tahap 3 (tokenizing).

---

## 8. TF-IDF DAN COSINE SIMILARITY

Kode perhitungannya ada di **`nlp_service/db.py`**, fungsi `cari()`.

### Ide dasarnya

Setelah preprocessing, setiap dokumen berubah menjadi sekumpulan kata dasar.
Supaya bisa dibandingkan dengan kata kunci, tiap dokumen diubah menjadi
**deretan angka (vektor)**. Setiap kata dasar menjadi satu "sumbu", dan
nilainya adalah **seberapa penting kata itu bagi dokumen tersebut**.
Nilai penting itulah yang disebut **bobot TF-IDF**.

---

### A. TF — *Term Frequency* (seberapa sering)

> Makin sering sebuah kata muncul di suatu dokumen, makin penting kata itu
> **bagi dokumen tersebut**.

Rumus paling dasarnya adalah menghitung kemunculan apa adanya. Tapi dokumen
di aplikasi ini berbentuk **tabel Excel**, dan tabel punya sifat khusus:
satu nilai kolom terulang di setiap baris.

Contoh nyata dari sebuah file dataset: kata `laki` muncul **506 kali** hanya
karena ada 506 baris data, sedangkan `hadir` muncul **2 kali** di judul kolom.
Kalau dihitung apa adanya, `laki` dianggap **253 kali lebih penting** —
padahal itu semata-mata karena tabelnya panjang, bukan karena lebih bermakna.

Karena itu dipakai **sublinear TF scaling**:

```
TF = 1 + log(jumlah kemunculan kata di dokumen)
```

Logaritma meredam pengulangan. Bandingkan:

| Jumlah kemunculan | TF apa adanya | TF sublinear `1+log(n)` |
|---|---|---|
| 1 kali | 1 | **1,00** |
| 2 kali | 2 | **1,69** |
| 18 kali | 18 | **3,89** |
| 506 kali | 506 | **7,23** |

Dari 1 ke 506 kali, nilainya hanya naik dari 1 menjadi 7,2 — bukan 506.
Artinya: *"muncul berkali-kali memang lebih penting, tapi tidak sebanding lurus."*

**Contoh nyata:** pada dokumen *"Rekap Pelatihan Kerja 2024"*, kata `latih`
muncul **18 kali** dan `kerja` muncul **8 kali**:

```
TF(latih) = 1 + ln(18) = 3,8904
TF(kerja) = 1 + ln(8)  = 3,0794
```

Angka kemunculannya bisa Anda cek sendiri di phpMyAdmin:

```sql
SELECT term, tf FROM dokumen_term
WHERE id_dokumen = (SELECT id FROM dokumen
                    WHERE nama_dokumen = 'Rekap Pelatihan Kerja 2024');
```

> **Kenapa tidak dibagi total kata dokumen?** Karena pembagian dengan `|B|`
> pada rumus cosine di bagian D **sudah** menangani perbedaan panjang dokumen.
> Membaginya dua kali tidak mengubah hasil cosine sama sekali.

---

### B. IDF — *Inverse Document Frequency* (seberapa langka)

> Kata yang muncul di **hampir semua dokumen** hampir tidak berguna untuk
> membedakan. Kata yang muncul di **sedikit dokumen** justru sangat khas.

```
IDF = ln( (N + 1) ÷ (df + 1) ) + 1

N  = jumlah dokumen yang sudah di-index
df = jumlah dokumen yang memuat kata tersebut
ln = logaritma natural
```

- **`+1` di dalam pecahan** mencegah pembagian dengan nol untuk kata yang
  belum pernah muncul
- **`+1` di belakang** memastikan kata yang muncul di semua dokumen tetap
  bernilai 1, bukan 0 — jadi tidak hilang total

**Contoh nyata** saat panduan ini ditulis ada **N = 16** dokumen ter-index
(angka ini ikut berubah setiap ada dokumen baru):

| Kata | Muncul di (df) | Perhitungan | IDF | Artinya |
|---|---|---|---|---|
| `kerja` | 13 dokumen | `ln(17/14)+1` | **1,1942** | sangat umum, bobot kecil |
| `latih` | 4 dokumen | `ln(17/5)+1` | **2,2238** | cukup khas |
| `selisih` | 1 dokumen | `ln(17/2)+1` | **3,1401** | sangat khas, bobot besar |

Nilai df bisa dicek dengan:

```sql
SELECT term, COUNT(DISTINCT id_dokumen) AS df FROM dokumen_term
WHERE term IN ('kerja','latih','selisih') GROUP BY term;
```

---

### C. Bobot akhir

```
bobot = TF × IDF
      = (1 + log jumlah kemunculan) × (log((N+1)/(df+1)) + 1)
```

Jadi sebuah kata bernilai tinggi kalau **sering muncul di dokumen itu**
(TF besar) **tapi jarang muncul di dokumen lain** (IDF besar).

Kata seperti `kerja` yang muncul di mana-mana ditekan oleh IDF,
sedangkan kata khas seperti `selisih` justru diangkat.

---

### D. Cosine Similarity

Setelah dokumen dan kata kunci sama-sama jadi vektor bobot, kemiripannya
diukur dengan **sudut antara kedua vektor**, bukan jaraknya.

```
                    A · B
cosine(A, B) = ─────────────────
                  |A| × |B|
```

| Bagian | Artinya |
|---|---|
| `A · B` | *dot product*: kalikan bobot kata yang sama, lalu jumlahkan |
| `\|A\|` | panjang vektor A = akar dari jumlah kuadrat semua bobotnya |

**Kenapa memakai sudut, bukan jarak?** Karena sudut tidak terpengaruh panjang
dokumen. Dokumen 2 halaman dan 50 halaman yang membahas topik sama akan
menghasilkan arah vektor yang mirip, sehingga sudutnya tetap kecil.

Hasilnya selalu antara **0 dan 1**:

| Nilai | Artinya | Ditampilkan sebagai |
|---|---|---|
| 1 | arah vektor persis sama → sangat relevan | 100% |
| 0,5 | cukup mirip | 50% |
| 0 | tidak ada kata yang sama | tidak ditampilkan |

---

### E. Contoh perhitungan lengkap

Misal ada **3 dokumen** dan kata kunci **"melatih pekerja"**.

**Langkah 1 — preprocessing semuanya**

| | Teks | Kata dasar hasil preprocessing |
|---|---|---|
| **Q** (kata kunci) | "melatih pekerja" | `latih`, `kerja` |
| **D1** | "Rekap Pelatihan Kerja 2024" | `latih`, `kerja` |
| **D2** | "Data Serikat Pekerja" | `kerja`, `serikat` |
| **D3** | "Laporan Keuangan Belanja Pegawai" | `uang`, `belanja`, `pegawai` |

**Langkah 2 — hitung IDF** (N = 3 dokumen)

| Kata | df | Perhitungan | IDF |
|---|---|---|---|
| `latih` | 1 (hanya D1) | `ln(4/2) + 1` | **1,6931** |
| `kerja` | 2 (D1 dan D2) | `ln(4/3) + 1` | **1,2877** |
| `serikat` | 1 (hanya D2) | `ln(4/2) + 1` | **1,6931** |

**Langkah 3 — hitung bobot tiap vektor**

Semua kata di contoh ini muncul **1 kali**, jadi TF-nya = `1 + ln(1)` = **1**.
Dengan begitu bobot = 1 × IDF = IDF itu sendiri:

| | `latih` | `kerja` | `serikat` | Panjang vektor |
|---|---|---|---|---|
| **Q** | **1,6931** | **1,2877** | 0 | 2,1272 |
| **D1** | **1,6931** | **1,2877** | 0 | 2,1272 |
| **D2** | 0 | **1,2877** | **1,6931** | 2,1272 |
| **D3** | 0 | 0 | 0 | 2,9326 |

Contoh cara menghitung panjang vektor Q:

```
|Q| = √(1,6931² + 1,2877²) = √(2,8666 + 1,6582) = √4,5248 = 2,1272
```

**Langkah 4 — hitung cosine similarity**

**Q vs D1** — semua kata cocok:
```
Q · D1 = (1,6931 × 1,6931) + (1,2877 × 1,2877) = 2,8666 + 1,6582 = 4,5248

cosine = 4,5248 ÷ (2,1272 × 2,1272) = 4,5248 ÷ 4,5250 = 1,0000  →  100,00%
```

**Q vs D2** — hanya `kerja` yang cocok:
```
Q · D2 = (1,6931 × 0) + (1,2877 × 1,2877) = 1,6582

cosine = 1,6582 ÷ (2,1272 × 2,1272) = 1,6582 ÷ 4,5250 = 0,3664  →  36,64%
```

**Q vs D3** — tidak ada kata yang sama:
```
Q · D3 = 0   →   cosine = 0   →   tidak ditampilkan di hasil
```

**Hasil akhir yang tampil di halaman Pencarian Dokumen:**

| Peringkat | Dokumen | Skor |
|---|---|---|
| 1 | Rekap Pelatihan Kerja 2024 | 100,00% |
| 2 | Data Serikat Pekerja | 36,64% |
| — | Laporan Keuangan Belanja Pegawai | *tidak muncul* |

> **Poin penting untuk presentasi:** kata kunci "melatih pekerja" **tidak
> mengandung satu pun huruf yang sama persis** dengan judul "Rekap Pelatihan
> Kerja 2024" jika dicocokkan mentah-mentah. Dokumen itu bisa muncul di
> peringkat 1 **justru karena tahap stemming**, yang mengubah "melatih" dan
> "pelatihan" sama-sama menjadi "latih".

---

## 9. STRUKTUR DATABASE

### Kolom tambahan di tabel `dokumen`

| Kolom | Tipe | Isi |
|---|---|---|
| `teks_ekstraksi` | MEDIUMTEXT | seluruh teks hasil pembacaan Excel (dipakai halaman demo) |
| `total_term` | INT | jumlah kata dasar hasil preprocessing (penanda dokumen sudah di-index, sekaligus ukuran panjang dokumen) |
| `indexed_at` | DATETIME | kapan dokumen terakhir di-index |

`total_term = 0` berarti dokumen itu **belum pernah di-index**.

### Tabel `dokumen_term` (inverted index)

| Kolom | Tipe | Isi |
|---|---|---|
| `id` | INT AUTO_INCREMENT | kunci utama |
| `id_dokumen` | INT | FK ke `dokumen.id`, **ON DELETE CASCADE** |
| `term` | VARCHAR(100) | satu kata dasar hasil stemming |
| `tf` | INT | berapa kali kata itu muncul di dokumen tersebut |

Satu baris = **satu kata dasar di satu dokumen**. Contoh isinya:

| id_dokumen | term | tf |
|---|---|---|
| 2 | latih | 5 |
| 2 | kerja | 3 |
| 2 | jahit | 1 |
| 3 | tempat | 4 |

**Kenapa disebut *inverted index*?** Karena arah pencariannya dibalik.
Biasanya kita bertanya "dokumen 2 isinya apa saja?". Di sini kita bertanya
"kata `latih` ada di dokumen mana saja?" — dan karena kolom `term` diberi index
database, jawabannya didapat tanpa perlu membuka file Excel-nya sama sekali.

**`ON DELETE CASCADE`** berarti: kalau baris di tabel `dokumen` dihapus,
semua baris `dokumen_term` miliknya ikut terhapus otomatis oleh MySQL.
Jadi index tidak akan meninggalkan sampah, bahkan seandainya service Python
sedang mati saat dokumen dihapus.

---

## 10. DAFTAR ENDPOINT SERVICE PYTHON

Alamat dasar: `http://127.0.0.1:5000`

| Method | Endpoint | Body | Kegunaan |
|---|---|---|---|
| `GET` | `/` atau `/status` | — | cek service hidup + statistik index |
| `POST` | `/index` | `{"id_dokumen": 12}` | baca Excel → preprocessing → simpan index |
| `POST` | `/search` | `{"query":"...", "bidang":"PPTK", "limit":50}` | cari dokumen, hasil terurut skor |
| `POST` | `/preprocess` | `{"teks":"..."}` atau `{"id_dokumen":12}` | hasil tiap tahap preprocessing |
| `DELETE` | `/index/<id>` | — | hapus index sebuah dokumen |
| `POST` | `/reload-stopwords` | — | muat ulang file stopword & pengecualian |

Semua jawaban berbentuk JSON dengan pola:

```json
{ "success": true, "message": "...", "...data lainnya..." }
```

### Contoh mencoba manual lewat Command Prompt

```bat
curl -X POST http://127.0.0.1:5000/search ^
     -H "Content-Type: application/json" ^
     -d "{\"query\":\"melatih pekerja\"}"
```

### Sisi PHP

Semua pemanggilan dibungkus di `pages/process/nlp_client.php`:

```php
$hasil = nlp_search('melatih pekerja', 'PPTK');
if ($hasil['ok']) {
    // $hasil['data']['hasil'] berisi daftar dokumen
} else {
    // $hasil['error'] berisi pesan yang siap ditampilkan ke user
}
```

Fungsi-fungsi ini **tidak pernah membuat aplikasi error** walaupun service mati —
selalu mengembalikan array `['ok' => ..., 'data' => ..., 'error' => ...]`.

---

## 11. DATA CONTOH UNTUK PENGUJIAN

Tersedia **15 dokumen Excel contoh** dengan topik yang berbeda-beda:

| Bidang | Jumlah | Contoh judul |
|---|---|---|
| PPTK | 4 | Rekap Pelatihan Kerja 2024, Laporan Penempatan Tenaga Kerja |
| INDUSTRI | 4 | Data Perusahaan Industri Kecil Menengah, Laporan Produksi Industri Makanan |
| HUBINSYAKER | 4 | Rekap Perselisihan Hubungan Industrial, Data Serikat Pekerja Terdaftar |
| SEKRETARIAT | 3 | Laporan Keuangan Belanja Pegawai, Rekap Surat Masuk dan Surat Keluar |

Cara membuat ulang:

```bat
cd C:\xampp\htdocs\E-DOC\nlp_service
venv\Scripts\python.exe seed_sample_data.py           :: tambahkan (lewati yang sudah ada)
venv\Scripts\python.exe seed_sample_data.py --reset   :: hapus contoh lama, buat ulang
```

Script ini membuat file Excel di `uploads/<bidang>/`, memasukkannya ke tabel
`dokumen`, lalu langsung meng-index-nya. `--reset` hanya menghapus dokumen yang
judulnya persis sama dengan daftar contoh, jadi **dokumen asli Anda aman**.

### Menyingkirkan dokumen contoh untuk uji coba data asli

Kalau ingin menguji aplikasi dengan file Excel asli tanpa tercampur dokumen
contoh, dokumen contoh bisa disingkirkan **tanpa dihapus permanen**:

```bat
venv\Scripts\python.exe bersihkan_contoh.py            :: singkirkan
venv\Scripts\python.exe bersihkan_contoh.py --lihat    :: cek isi cadangan & database
venv\Scripts\python.exe bersihkan_contoh.py --pulihkan :: kembalikan lagi
```

Saat disingkirkan:

1. file Excel **dipindahkan** ke `nlp_service/contoh_cadangan/`, bukan dihapus
2. baris di tabel `dokumen` dihapus — baris `dokumen_term` ikut terhapus
   otomatis lewat `ON DELETE CASCADE`
3. catatannya disimpan di `contoh_cadangan/daftar.json` supaya bisa
   dikembalikan persis seperti semula

Sama seperti `--reset`, script ini hanya mengenali dokumen yang judulnya persis
sama dengan daftar contoh, jadi **dokumen asli Anda tidak tersentuh**.

### Contoh kata kunci untuk diuji

| Kata kunci | Yang seharusnya muncul di peringkat atas |
|---|---|
| `melatih pekerja` | Rekap Pelatihan Kerja 2024 |
| `peserta pelatihan menjahit` | Rekap Pelatihan Kerja 2024 |
| `perselisihan upah` | Rekap Perselisihan Hubungan Industrial 2024 |
| `industri makanan` | Data Perusahaan Industri Kecil Menengah 2024 |
| `surat keluar keuangan` | Rekap Surat Masuk dan Surat Keluar |

---

## 12. KALAU ADA MASALAH

### "Service pencarian belum aktif" di halaman Pencarian

Service Python belum dinyalakan. Klik dua kali
`nlp_service/start_service.bat` dan biarkan jendelanya terbuka.

### `python` tidak dikenali sebagai perintah

Python belum terpasang, atau saat memasang lupa mencentang
**"Add Python to PATH"**. Pasang ulang Python dan centang opsi tersebut.

### Upload berhasil tapi ada catatan "indexing isi dokumen gagal"

Ini **normal dan tidak berbahaya** — file sudah tersimpan dengan aman,
hanya belum masuk index. Nyalakan service, lalu jalankan:

```bat
cd C:\xampp\htdocs\E-DOC\nlp_service
venv\Scripts\python.exe reindex.py
```

### Dokumen tidak muncul padahal kata kuncinya ada di dalamnya

Cek berurutan:

1. **Sudah di-index?** Jalankan `venv\Scripts\python.exe reindex.py --all`
2. **Kata kuncinya kebuang jadi stopword?** Buka menu **Proses Preprocessing**,
   ketik kata kunci itu, lalu lihat apakah masih tersisa di tahap 5.
   Kalau hilang, kata itu ada di `stopwords_tambahan.txt` — hapus saja barisnya.
3. **Salah bidang?** User non-admin hanya bisa mencari di bidangnya sendiri.
4. **File Excel-nya berisi gambar/angka saja?** Yang dibaca hanya sel berisi teks.

### `Table 'e-doc_db.dokumen_term' doesn't exist`

File `schema_nlp.sql` belum diimport. Ulangi [Langkah 2](#langkah-2--tambahkan-tabel-di-database).

### Port 5000 sudah dipakai program lain

Buka `nlp_service/config.py`, ubah `SERVICE_PORT = 5000` menjadi misalnya `5050`.
Lalu buka `pages/process/nlp_client.php` dan samakan angkanya:

```php
define('NLP_BASE_URL', 'http://127.0.0.1:5050');
```

### Kolom "Kata Kunci" di tabel dokumen isinya "-" semua

Dokumen-dokumen itu belum di-index. Jalankan
`venv\Scripts\python.exe reindex.py`.

---

## 13. SARAN ALUR DEMO PRESENTASI

Urutan ini menunjukkan seluruh rangkaian dari teori sampai hasilnya, sekitar 7 menit.

**1. Tunjukkan masalahnya dulu (1 menit)**

Buka salah satu halaman bidang, tunjukkan bahwa tabel hanya bisa mencari judul.
Katakan: *"kalau saya cari 'melatih pekerja', tidak akan ketemu apa-apa,
padahal ada dokumen 'Rekap Pelatihan Kerja 2024'."*

**2. Halaman Proses Preprocessing — teori jalan di depan mata (3 menit)**

Menu **Proses Preprocessing** → biarkan teks contoh bawaan → klik **Proses**.
Jelaskan turun satu per satu:

| Yang ditunjuk | Yang dikatakan |
|---|---|
| Teks asli → Tahap 1 | "semua huruf disamakan jadi kecil" |
| Tahap 2 | "angka dan tanda baca dibuang pakai regex — ini polanya" |
| Tahap 3 | "kalimat dipotong jadi kata pakai NLTK, hasilnya 8 token" |
| Tahap 4 | "kata umum dibuang, tinggal 5 token" |
| Tahap 5 | **"'pelatihan' jadi 'latih' — ini kunci seluruh fiturnya"** |

**3. Buktikan regex-nya di regexr.com (1 menit)**

Buka <https://regexr.com>, tempel `[^\w\s]`, lalu tempel
`(blk kota), peserta: 30 orang!` di kotak teks. Tunjukkan tanda baca
yang langsung tersorot. Ulangi dengan `\d+` untuk angka.

**4. Halaman Pencarian Dokumen — hasilnya (2 menit)**

Menu **Pencarian Dokumen** → ketik **`melatih pekerja`** → **Cari**.

- buka panel *"Kata kunci Anda setelah diproses"*, tunjukkan
  `melatih pekerja` → **`latih`, `kerja`**
- tunjukkan "Rekap Pelatihan Kerja 2024" di peringkat 1 dengan skor tertinggi
- tunjuk kolom **Kata yang Cocok** dan bar skor relevansinya
- jelaskan skor itu berasal dari TF-IDF + cosine similarity
  (contoh hitungannya ada di [bagian 8](#8-tf-idf-dan-cosine-similarity))

**5. Tutup dengan hak akses (30 detik)**

Logout, login sebagai `pptk1`, cari kata umum seperti `kerja`.
Tunjukkan bahwa yang muncul **hanya dokumen bidang PPTK** — aturan role
yang lama tetap berlaku di fitur baru ini.

---

*Dokumen ini dibuat untuk aplikasi E-DOC — fitur pencarian dokumen berbasis NLTK.*
