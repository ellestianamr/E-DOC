# -*- coding: utf-8 -*-
"""
Index ulang dokumen lewat baris perintah (tidak perlu lewat web).

Kapan dipakai?
    - pertama kali memasang fitur ini, untuk dokumen yang sudah
      terlanjur ada di folder uploads/
    - setelah mengubah stopwords_tambahan.txt atau pipeline preprocessing
    - kalau ada dokumen yang gagal ter-index saat diupload

Cara pakai:
    venv\\Scripts\\python reindex.py              -> index dokumen yang BELUM punya index
    venv\\Scripts\\python reindex.py --all        -> index ulang SEMUA dokumen
    venv\\Scripts\\python reindex.py --id 7       -> index ulang dokumen tertentu
    venv\\Scripts\\python reindex.py --bidang PPTK -> index ulang satu bidang saja

Script ini TIDAK memanggil service Flask, jadi boleh dijalankan
walaupun app.py sedang mati.
"""
import argparse
import sys

import db
from app import proses_index


def main():
    p = argparse.ArgumentParser(description='Index ulang dokumen E-DOC')
    p.add_argument('--all', action='store_true',
                   help='index ulang semua dokumen (termasuk yang sudah ter-index)')
    p.add_argument('--id', type=int, help='index ulang satu dokumen berdasarkan id')
    p.add_argument('--bidang', type=str,
                   help='hanya bidang tertentu, mis. PPTK / INDUSTRI')
    args = p.parse_args()

    if args.id:
        daftar = [d for d in db.ambil_semua_dokumen() if d['id'] == args.id]
        if not daftar:
            print('Dokumen id=%d tidak ditemukan.' % args.id)
            return 1
    else:
        daftar = db.ambil_semua_dokumen()

        if args.bidang:
            bid = args.bidang.strip().upper()
            daftar = [d for d in daftar if (d['bidang'] or '').upper() == bid]

        if not args.all:
            # hanya yang belum punya index
            belum = []
            for d in daftar:
                row = db.ambil_dokumen(d['id'])
                if not row or (row['total_term'] or 0) == 0:
                    belum.append(d)
            daftar = belum

    if not daftar:
        print('Tidak ada dokumen yang perlu di-index. '
              'Pakai --all kalau ingin memaksa index ulang.')
        return 0

    print('=' * 64)
    print(' REINDEX %d dokumen' % len(daftar))
    print('=' * 64)

    berhasil, gagal = 0, 0
    pesan_gagal = []

    for d in daftar:
        nama = (d['nama_dokumen'] or '')[:46]
        try:
            hasil = proses_index(d['id'])
            print('[OK    ] id=%-4s %-46s %3d term unik'
                  % (d['id'], nama, hasil['jumlah_term_unik']))
            berhasil += 1
        except Exception as e:
            print('[GAGAL ] id=%-4s %-46s -> %s' % (d['id'], nama, e))
            pesan_gagal.append('id=%s (%s): %s' % (d['id'], d['nama_dokumen'], e))
            gagal += 1

    print()
    print('Selesai. Berhasil: %d | Gagal: %d' % (berhasil, gagal))
    if pesan_gagal:
        print('\nDaftar yang gagal:')
        for m in pesan_gagal:
            print('  -', m)
        print('\nPenyebab tersering: file Excel-nya sudah tidak ada di folder '
              'uploads/, atau filenya rusak.')
    print('\nStatistik index:', db.statistik_index())
    return 1 if gagal else 0


if __name__ == '__main__':
    sys.exit(main())
