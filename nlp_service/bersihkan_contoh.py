# -*- coding: utf-8 -*-
"""
Menyingkirkan / mengembalikan 15 dokumen contoh buatan seed_sample_data.py.

Gunanya: supaya aplikasi bisa diuji dengan data asli (file Excel milik
sendiri) tanpa tercampur dokumen contoh.

Cara pakai:
    venv\\Scripts\\python bersihkan_contoh.py            -> singkirkan dokumen contoh
    venv\\Scripts\\python bersihkan_contoh.py --pulihkan -> kembalikan lagi
    venv\\Scripts\\python bersihkan_contoh.py --lihat    -> lihat isi folder cadangan

Yang terjadi saat disingkirkan:
    1. file Excel DIPINDAHKAN (bukan dihapus) ke nlp_service/contoh_cadangan/
    2. baris di tabel dokumen dihapus
       -> baris dokumen_term ikut terhapus otomatis (ON DELETE CASCADE)
    3. catatan lengkapnya disimpan di contoh_cadangan/daftar.json
       supaya nanti bisa dikembalikan persis seperti semula

Dokumen milik Anda sendiri TIDAK tersentuh: script ini hanya mengenali
dokumen yang judulnya persis sama dengan daftar di sample_data.py.
"""
import json
import os
import shutil
import sys

import db
from app import proses_index
from config import BASE_DIR, UPLOADS_DIR
from sample_data import DOKUMEN_CONTOH

FOLDER_CADANGAN = os.path.join(BASE_DIR, 'contoh_cadangan')
FILE_DAFTAR = os.path.join(FOLDER_CADANGAN, 'daftar.json')

NAMA_CONTOH = [d['nama'] for d in DOKUMEN_CONTOH]


def baca_daftar():
    if not os.path.exists(FILE_DAFTAR):
        return []
    with open(FILE_DAFTAR, 'r', encoding='utf-8') as f:
        return json.load(f)


def tulis_daftar(data):
    os.makedirs(FOLDER_CADANGAN, exist_ok=True)
    with open(FILE_DAFTAR, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2, ensure_ascii=False)


# ==================================================================
# SINGKIRKAN
# ==================================================================
def singkirkan():
    tanda = ','.join(['%s'] * len(NAMA_CONTOH))
    with db.koneksi() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id, id_user, nama_dokumen, bidang, nama_file, ukuran_file "
                "FROM dokumen WHERE nama_dokumen IN (%s) ORDER BY id" % tanda,
                NAMA_CONTOH,
            )
            baris = cur.fetchall()

    if not baris:
        print('Tidak ada dokumen contoh di database. Tidak ada yang perlu disingkirkan.')
        return 0

    print('=' * 66)
    print(' MENYINGKIRKAN %d DOKUMEN CONTOH' % len(baris))
    print('=' * 66)

    catatan = baca_daftar()
    dipindah, gagal = 0, 0

    for r in baris:
        bidang = (r['bidang'] or '').lower()
        asal = os.path.join(UPLOADS_DIR, bidang, r['nama_file'])
        tujuan_folder = os.path.join(FOLDER_CADANGAN, bidang)
        tujuan = os.path.join(tujuan_folder, r['nama_file'])

        try:
            # 1) pindahkan file (kalau filenya memang ada)
            if os.path.exists(asal):
                os.makedirs(tujuan_folder, exist_ok=True)
                shutil.move(asal, tujuan)
                status_file = 'dipindah'
            else:
                status_file = 'file tidak ada'

            # 2) hapus baris database (dokumen_term ikut lewat CASCADE)
            with db.koneksi() as conn:
                with conn.cursor() as cur:
                    cur.execute("DELETE FROM dokumen WHERE id = %s", (r['id'],))
                conn.commit()

            # 3) simpan catatan untuk pemulihan
            catatan = [c for c in catatan if c['nama_file'] != r['nama_file']]
            catatan.append({
                'id_user': r['id_user'],
                'nama_dokumen': r['nama_dokumen'],
                'bidang': r['bidang'],
                'nama_file': r['nama_file'],
                'ukuran_file': r['ukuran_file'],
            })

            print('[OK    ] id=%-3s %-48s (%s)'
                  % (r['id'], r['nama_dokumen'][:48], status_file))
            dipindah += 1

        except Exception as e:
            print('[GAGAL ] id=%-3s %-48s -> %s' % (r['id'], r['nama_dokumen'][:48], e))
            gagal += 1

    tulis_daftar(catatan)

    print()
    print('Selesai. Disingkirkan: %d | Gagal: %d' % (dipindah, gagal))
    print('File Excel dipindahkan ke: %s' % FOLDER_CADANGAN)
    print('Statistik index sekarang :', db.statistik_index())
    print()
    print('Untuk mengembalikan: venv\\Scripts\\python bersihkan_contoh.py --pulihkan')
    return 1 if gagal else 0


# ==================================================================
# PULIHKAN
# ==================================================================
def pulihkan():
    catatan = baca_daftar()
    if not catatan:
        print('Folder cadangan kosong. Tidak ada yang bisa dipulihkan.')
        print('Kalau ingin membuat ulang dari nol: '
              'venv\\Scripts\\python seed_sample_data.py')
        return 0

    print('=' * 66)
    print(' MEMULIHKAN %d DOKUMEN CONTOH' % len(catatan))
    print('=' * 66)

    sisa, berhasil, gagal = [], 0, 0

    for c in catatan:
        bidang = (c['bidang'] or '').lower()
        asal = os.path.join(FOLDER_CADANGAN, bidang, c['nama_file'])
        tujuan_folder = os.path.join(UPLOADS_DIR, bidang)
        tujuan = os.path.join(tujuan_folder, c['nama_file'])

        try:
            if not os.path.exists(asal):
                print('[LEWAT ] %-50s (file cadangan tidak ada)'
                      % c['nama_dokumen'][:50])
                gagal += 1
                sisa.append(c)
                continue

            # 1) kembalikan file ke uploads/
            os.makedirs(tujuan_folder, exist_ok=True)
            shutil.move(asal, tujuan)

            # 2) masukkan lagi ke tabel dokumen
            with db.koneksi() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "INSERT INTO dokumen "
                        "(id_user, nama_dokumen, bidang, nama_file, ukuran_file) "
                        "VALUES (%s, %s, %s, %s, %s)",
                        (c['id_user'], c['nama_dokumen'], c['bidang'],
                         c['nama_file'], c['ukuran_file']),
                    )
                    id_baru = cur.lastrowid
                conn.commit()

            # 3) index ulang
            hasil = proses_index(id_baru)
            print('[OK    ] id=%-3s %-48s %3d term unik'
                  % (id_baru, c['nama_dokumen'][:48], hasil['jumlah_term_unik']))
            berhasil += 1

        except Exception as e:
            print('[GAGAL ] %-50s -> %s' % (c['nama_dokumen'][:50], e))
            gagal += 1
            sisa.append(c)

    tulis_daftar(sisa)

    print()
    print('Selesai. Dipulihkan: %d | Gagal: %d' % (berhasil, gagal))
    print('Statistik index sekarang:', db.statistik_index())
    return 1 if gagal else 0


# ==================================================================
# LIHAT
# ==================================================================
def lihat():
    catatan = baca_daftar()
    print('Folder cadangan : %s' % FOLDER_CADANGAN)
    print('Jumlah dokumen  : %d' % len(catatan))
    print()
    if catatan:
        for c in catatan:
            print('  [%-11s] %s' % (c['bidang'], c['nama_dokumen']))
        print()
        print('Kembalikan dengan: venv\\Scripts\\python bersihkan_contoh.py --pulihkan')
    print()
    print('Isi database sekarang:')
    for d in db.ambil_semua_dokumen():
        print('  id=%-3s [%-11s] %s' % (d['id'], d['bidang'], d['nama_dokumen']))
    return 0


if __name__ == '__main__':
    if '--pulihkan' in sys.argv:
        sys.exit(pulihkan())
    if '--lihat' in sys.argv:
        sys.exit(lihat())
    sys.exit(singkirkan())
