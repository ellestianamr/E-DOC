# -*- coding: utf-8 -*-
"""
Membuat 15 file Excel contoh, menaruhnya di uploads/<bidang>/,
memasukkan datanya ke tabel dokumen, lalu langsung meng-index-nya.

Jalankan:
    venv\\Scripts\\python seed_sample_data.py           (tambah data contoh)
    venv\\Scripts\\python seed_sample_data.py --reset   (hapus dulu yang lama)

Catatan: dokumen contoh dikenali dari kolom nama_dokumen, jadi --reset
TIDAK akan menghapus dokumen asli yang Anda upload sendiri.
"""
import os
import sys
import time

from openpyxl import Workbook
from openpyxl.styles import Alignment, Font, PatternFill

import db
from app import proses_index
from config import UPLOADS_DIR
from sample_data import DOKUMEN_CONTOH

NAMA_CONTOH = [d['nama'] for d in DOKUMEN_CONTOH]


def buat_file_excel(doc, path):
    """Tulis satu dokumen contoh menjadi file .xlsx."""
    wb = Workbook()
    ws = wb.active
    ws.title = doc['sheet'][:31]        # Excel membatasi nama sheet 31 huruf

    judul_font = Font(bold=True, color='FFFFFF')
    judul_isi = PatternFill('solid', fgColor='145885')

    # Baris 1: judul dokumen
    ws.append([doc['nama']])
    ws['A1'].font = Font(bold=True, size=14)

    # Baris 2: kosong sebagai pemisah
    ws.append([])

    # Baris 3: header tabel
    ws.append(doc['header'])
    for sel in ws[3]:
        sel.font = judul_font
        sel.fill = judul_isi
        sel.alignment = Alignment(horizontal='center')

    # Baris data
    for baris in doc['baris']:
        ws.append(baris)

    # Bagian catatan di bawah tabel
    ws.append([])
    ws.append(['Catatan:'])
    for c in doc.get('catatan', []):
        ws.append([c])

    # Lebar kolom dibuat secukupnya biar enak dilihat
    for i, judul in enumerate(doc['header'], start=1):
        lebar = max(12, min(40, len(str(judul)) + 12))
        ws.column_dimensions[ws.cell(row=3, column=i).column_letter].width = lebar

    wb.save(path)
    wb.close()


def ambil_id_user(bidang):
    """Cari user pemilik dokumen: user bidang terkait, kalau tidak ada pakai admin."""
    with db.koneksi() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id "
                "WHERE LOWER(r.role_name) = %s ORDER BY u.id LIMIT 1",
                (bidang.lower(),),
            )
            row = cur.fetchone()
            if row:
                return row['id']
            cur.execute("SELECT id FROM users ORDER BY id LIMIT 1")
            row = cur.fetchone()
            return row['id'] if row else None


def hapus_contoh_lama():
    """Hapus dokumen contoh yang sudah ada (beserta file fisiknya)."""
    tanda = ','.join(['%s'] * len(NAMA_CONTOH))
    dihapus = 0
    with db.koneksi() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id, bidang, nama_file FROM dokumen "
                "WHERE nama_dokumen IN (%s)" % tanda,
                NAMA_CONTOH,
            )
            baris = cur.fetchall()
            for r in baris:
                path = os.path.join(
                    UPLOADS_DIR, (r['bidang'] or '').lower(), r['nama_file'])
                if os.path.exists(path):
                    os.remove(path)
                # dokumen_term ikut terhapus otomatis (ON DELETE CASCADE)
                cur.execute("DELETE FROM dokumen WHERE id = %s", (r['id'],))
                dihapus += 1
        conn.commit()
    return dihapus


def sudah_ada(nama_dokumen):
    with db.koneksi() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id FROM dokumen WHERE nama_dokumen = %s LIMIT 1",
                (nama_dokumen,),
            )
            return cur.fetchone()


def main():
    reset = '--reset' in sys.argv

    print('=' * 64)
    print(' SEED DATA CONTOH E-DOC  (%d dokumen)' % len(DOKUMEN_CONTOH))
    print('=' * 64)

    if reset:
        n = hapus_contoh_lama()
        print('Dokumen contoh lama dihapus: %d\n' % n)

    berhasil, dilewati, gagal = 0, 0, 0

    for doc in DOKUMEN_CONTOH:
        nama = doc['nama']
        bidang = doc['bidang']

        if sudah_ada(nama):
            print('[LEWAT ] %-52s (sudah ada)' % nama[:52])
            dilewati += 1
            continue

        try:
            # 1) buat file Excel dengan pola nama yang sama seperti hasil upload
            folder = os.path.join(UPLOADS_DIR, bidang.lower())
            os.makedirs(folder, exist_ok=True)

            nama_file = '%d_%s.xlsx' % (
                int(time.time()), nama.replace(' ', '_'))
            path = os.path.join(folder, nama_file)
            buat_file_excel(doc, path)
            ukuran = '%s KB' % round(os.path.getsize(path) / 1024, 2)

            # 2) masukkan ke tabel dokumen
            id_user = ambil_id_user(bidang)
            with db.koneksi() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "INSERT INTO dokumen "
                        "(id_user, nama_dokumen, bidang, nama_file, ukuran_file) "
                        "VALUES (%s, %s, %s, %s, %s)",
                        (id_user, nama, bidang, nama_file, ukuran),
                    )
                    id_dokumen = cur.lastrowid
                conn.commit()

            # 3) index-kan
            hasil = proses_index(id_dokumen)

            print('[OK    ] %-52s id=%-4s %3d term unik'
                  % (nama[:52], id_dokumen, hasil['jumlah_term_unik']))
            berhasil += 1
            time.sleep(1)   # supaya prefix waktu pada nama file tidak kembar

        except Exception as e:
            print('[GAGAL ] %-52s -> %s' % (nama[:52], e))
            gagal += 1

    print()
    print('Selesai. Berhasil: %d | Dilewati: %d | Gagal: %d'
          % (berhasil, dilewati, gagal))
    print('Statistik index:', db.statistik_index())
    return 1 if gagal else 0


if __name__ == '__main__':
    sys.exit(main())
