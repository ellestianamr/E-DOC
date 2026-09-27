# -*- coding: utf-8 -*-
"""
Membaca SEMUA teks di dalam file Excel.

Yang diambil:
    - nama tiap sheet
    - isi tiap sel yang berupa teks (angka murni dilewati, karena
      nanti juga akan dibuang di tahap cleaning)

Dipakai library:
    .xlsx -> openpyxl (mode read_only supaya hemat memori)
    .xls  -> xlrd (format Excel lama). Kalau gagal, dicoba pakai pandas.
"""
import os

from config import MAX_SEL_EXCEL


def _bersih(nilai):
    """Ubah isi sel jadi teks. Sel kosong / angka murni dikembalikan ''."""
    if nilai is None:
        return ''
    if isinstance(nilai, str):
        return nilai.strip()
    if isinstance(nilai, bool):
        return ''
    if isinstance(nilai, (int, float)):
        # Angka dilewati: tidak menambah makna untuk pencarian teks
        return ''
    # Tanggal (datetime) dan tipe lain -> jadikan teks apa adanya
    return str(nilai).strip()


def _baca_xlsx(path):
    from openpyxl import load_workbook

    potongan = []
    jumlah_sel = 0
    wb = load_workbook(path, read_only=True, data_only=True)
    try:
        for ws in wb.worksheets:
            # Nama sheet sering berisi kata penting, mis. "Pelatihan Las"
            potongan.append(str(ws.title))
            for baris in ws.iter_rows(values_only=True):
                for sel in baris:
                    jumlah_sel += 1
                    if jumlah_sel > MAX_SEL_EXCEL:
                        return potongan, True
                    teks = _bersih(sel)
                    if teks:
                        potongan.append(teks)
    finally:
        wb.close()
    return potongan, False


def _baca_xls(path):
    potongan = []
    jumlah_sel = 0
    try:
        import xlrd

        wb = xlrd.open_workbook(path)
        for ws in wb.sheets():
            potongan.append(str(ws.name))
            for r in range(ws.nrows):
                for c in range(ws.ncols):
                    jumlah_sel += 1
                    if jumlah_sel > MAX_SEL_EXCEL:
                        return potongan, True
                    teks = _bersih(ws.cell_value(r, c))
                    if teks:
                        potongan.append(teks)
        return potongan, False
    except Exception:
        # Cadangan: sebagian file .xls sebenarnya berformat lain
        import pandas as pd

        sheets = pd.read_excel(path, sheet_name=None, header=None, dtype=str)
        for nama_sheet, df in sheets.items():
            potongan.append(str(nama_sheet))
            for nilai in df.values.ravel():
                jumlah_sel += 1
                if jumlah_sel > MAX_SEL_EXCEL:
                    return potongan, True
                teks = _bersih(nilai)
                if teks:
                    potongan.append(teks)
        return potongan, False


def baca_excel(path):
    """Baca file Excel -> (teks_gabungan, info).

    info = {'jumlah_potongan': int, 'terpotong': bool, 'ekstensi': str}
    Melempar FileNotFoundError / ValueError kalau file bermasalah.
    """
    if not os.path.exists(path):
        raise FileNotFoundError('File tidak ditemukan: %s' % path)

    ext = os.path.splitext(path)[1].lower()
    if ext == '.xlsx':
        potongan, terpotong = _baca_xlsx(path)
    elif ext == '.xls':
        potongan, terpotong = _baca_xls(path)
    else:
        raise ValueError('Ekstensi tidak didukung: %s (hanya .xls / .xlsx)' % ext)

    return ' '.join(potongan), {
        'jumlah_potongan': len(potongan),
        'terpotong': terpotong,
        'ekstensi': ext,
    }
