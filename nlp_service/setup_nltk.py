# -*- coding: utf-8 -*-
"""
Unduh data NLTK yang dibutuhkan pipeline preprocessing.
Jalankan SEKALI setelah pip install:

    venv\\Scripts\\python setup_nltk.py

Data yang diunduh:
    punkt / punkt_tab -> model pemotong kata (tokenizer)
    stopwords         -> daftar kata umum, termasuk bahasa Indonesia
"""
import sys

import nltk

PAKET = ['punkt', 'punkt_tab', 'stopwords']


def main():
    gagal = []
    for paket in PAKET:
        print('Mengunduh data NLTK: %s ...' % paket)
        try:
            ok = nltk.download(paket, quiet=False)
            if not ok:
                gagal.append(paket)
        except Exception as e:
            print('  ERROR: %s' % e)
            gagal.append(paket)

    print()
    if gagal:
        print('GAGAL mengunduh: %s' % ', '.join(gagal))
        print('Cek koneksi internet lalu jalankan ulang file ini.')
        return 1

    # Cek langsung apakah datanya benar-benar bisa dipakai
    print('Mengecek hasil unduhan...')
    from nltk.corpus import stopwords
    sw = stopwords.words('indonesian')
    token = nltk.word_tokenize('Rekap Pelatihan Kerja Tahun 2024.')

    print('  stopword bahasa Indonesia : %d kata' % len(sw))
    print('  contoh tokenizing         : %s' % token)
    print()
    print('SELESAI. Data NLTK siap dipakai.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
