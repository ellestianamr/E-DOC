# -*- coding: utf-8 -*-
"""
==================================================================
SERVICE NLP untuk E-DOC  (Flask)
==================================================================
Jalankan:  venv\\Scripts\\python app.py
Alamat  :  http://127.0.0.1:5000

Daftar endpoint
---------------
GET    /                 -> cek service hidup + ringkasan isi index
POST   /index            -> baca Excel, preprocessing, simpan ke index
POST   /search           -> cari dokumen (TF-IDF + cosine similarity)
POST   /preprocess       -> tampilkan hasil tiap tahap preprocessing
DELETE /index/<id>       -> hapus index sebuah dokumen
POST   /reload-stopwords -> muat ulang stopwords_tambahan.txt

Semua jawaban berbentuk JSON dengan pola:
    { "success": true/false, "message": "...", ...data... }
==================================================================
"""
import os
import traceback

from flask import Flask, jsonify, request

import db
import preprocessing as pre
from config import SERVICE_HOST, SERVICE_PORT, UPLOADS_DIR
from excel_reader import baca_excel

app = Flask(__name__)
app.config['JSON_AS_ASCII'] = False


# ==================================================================
# Bantuan
# ==================================================================
def _gagal(pesan, kode=400, **tambahan):
    data = {'success': False, 'message': pesan}
    data.update(tambahan)
    return jsonify(data), kode


def _path_dokumen(row, path_kiriman=None):
    """Tentukan lokasi file Excel di disk.

    Sumber utama: kolom bidang + nama_file di database.
    PHP boleh mengirim 'path', tapi tetap dicek supaya masih berada
    di dalam folder uploads/ (mencegah file di luar folder ikut dibaca).
    """
    if path_kiriman:
        kandidat = os.path.abspath(path_kiriman)
        akar = os.path.abspath(UPLOADS_DIR)
        if os.path.commonpath([kandidat, akar]) == akar and os.path.exists(kandidat):
            return kandidat

    if not row:
        return None
    return os.path.join(
        UPLOADS_DIR, (row['bidang'] or '').lower(), row['nama_file']
    )


def proses_index(id_dokumen, path_kiriman=None):
    """Inti proses indexing: baca Excel -> preprocessing -> simpan index.

    Dipakai endpoint /index DAN script reindex.py.
    """
    row = db.ambil_dokumen(id_dokumen)
    if not row:
        raise ValueError('Dokumen id=%s tidak ada di database' % id_dokumen)

    path = _path_dokumen(row, path_kiriman)
    if not path or not os.path.exists(path):
        raise FileNotFoundError('File Excel tidak ditemukan: %s' % path)

    teks_excel, info_excel = baca_excel(path)

    # Nama dokumen ikut diindex, supaya judul juga bisa ditemukan
    teks_lengkap = '%s %s' % (row['nama_dokumen'], teks_excel)

    hasil = pre.preprocess(teks_lengkap)
    token_final = hasil['stemming_final']
    tf = pre.hitung_tf(token_final)

    db.simpan_index(id_dokumen, teks_lengkap, tf, len(token_final))

    return {
        'id_dokumen': id_dokumen,
        'nama_dokumen': row['nama_dokumen'],
        'bidang': row['bidang'],
        'file': os.path.basename(path),
        'jumlah_token': len(token_final),
        'jumlah_term_unik': len(tf),
        'info_excel': info_excel,
        'contoh_term': sorted(tf.items(), key=lambda x: -x[1])[:10],
    }


# ==================================================================
# GET /  -> cek service
# ==================================================================
@app.route('/', methods=['GET'])
@app.route('/status', methods=['GET'])
def status():
    try:
        stat = db.statistik_index()
        return jsonify({
            'success': True,
            'message': 'Service NLP aktif',
            'database': 'terhubung',
            'statistik': stat,
            'jumlah_stopword': len(pre.daftar_stopword()),
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'message': 'Service hidup tapi database gagal: %s' % e,
            'database': 'gagal',
        }), 500


# ==================================================================
# POST /index
# body: {"id_dokumen": 12, "path": "(opsional)"}
# ==================================================================
@app.route('/index', methods=['POST'])
def endpoint_index():
    data = request.get_json(silent=True) or request.form
    id_dokumen = data.get('id_dokumen') or data.get('id')
    if not id_dokumen:
        return _gagal('Parameter id_dokumen wajib diisi')

    try:
        hasil = proses_index(int(id_dokumen), data.get('path'))
    except FileNotFoundError as e:
        return _gagal(str(e), 404)
    except ValueError as e:
        return _gagal(str(e), 404)
    except Exception as e:
        traceback.print_exc()
        return _gagal('Gagal indexing: %s' % e, 500)

    return jsonify({
        'success': True,
        'message': 'Dokumen berhasil di-index',
        **hasil,
    })


# ==================================================================
# DELETE /index/<id>
# ==================================================================
@app.route('/index/<int:id_dokumen>', methods=['DELETE'])
def endpoint_hapus_index(id_dokumen):
    try:
        jml = db.hapus_index(id_dokumen)
    except Exception as e:
        traceback.print_exc()
        return _gagal('Gagal menghapus index: %s' % e, 500)

    return jsonify({
        'success': True,
        'message': 'Index dokumen dihapus',
        'id_dokumen': id_dokumen,
        'term_dihapus': jml,
    })


# ==================================================================
# POST /search
# body: {"query": "melatih pekerja", "bidang": "PPTK"|null, "limit": 50}
# ==================================================================
@app.route('/search', methods=['POST'])
def endpoint_search():
    data = request.get_json(silent=True) or request.form
    query = (data.get('query') or '').strip()
    if not query:
        return _gagal('Kata kunci pencarian kosong')

    bidang = data.get('bidang') or None
    if bidang:
        bidang = str(bidang).strip().upper()

    try:
        limit = int(data.get('limit') or 50)
    except (TypeError, ValueError):
        limit = 50

    try:
        # Query diproses dengan pipeline yang SAMA PERSIS dengan dokumen
        tahap = pre.preprocess(query)
        tf_query = pre.hitung_tf(tahap['stemming_final'])

        if not tf_query:
            return jsonify({
                'success': True,
                'message': 'Kata kunci habis setelah preprocessing '
                           '(kemungkinan semuanya stopword atau angka)',
                'query': query,
                'preprocessing': tahap,
                'jumlah': 0,
                'hasil': [],
            })

        hasil, info = db.cari(tf_query, bidang=bidang, limit=limit)
    except Exception as e:
        traceback.print_exc()
        return _gagal('Gagal mencari: %s' % e, 500)

    return jsonify({
        'success': True,
        'message': 'Ditemukan %d dokumen' % len(hasil),
        'query': query,
        'preprocessing': tahap,
        'term_query': tahap['stemming_final'],
        'info_perhitungan': info,
        'jumlah': len(hasil),
        'hasil': hasil,
    })


# ==================================================================
# POST /preprocess
# body: {"teks": "..."}  ATAU  {"id_dokumen": 12}
# ==================================================================
@app.route('/preprocess', methods=['POST'])
def endpoint_preprocess():
    data = request.get_json(silent=True) or request.form
    teks = (data.get('teks') or '').strip()
    id_dokumen = data.get('id_dokumen')
    sumber = 'teks manual'
    nama_dokumen = None

    try:
        if not teks and id_dokumen:
            row = db.ambil_teks_ekstraksi(int(id_dokumen))
            if not row:
                return _gagal('Dokumen tidak ditemukan', 404)
            if not row['teks_ekstraksi']:
                return _gagal(
                    'Dokumen "%s" belum di-index. Jalankan reindex dulu.'
                    % row['nama_dokumen'], 404)
            teks = row['teks_ekstraksi']
            nama_dokumen = row['nama_dokumen']
            sumber = 'dokumen'

        if not teks:
            return _gagal('Isi teks atau pilih dokumen terlebih dahulu')

        # Dibatasi supaya halaman demo tidak menampilkan ribuan token
        batas = int(data.get('batas_karakter') or 4000)
        dipotong = len(teks) > batas
        if dipotong:
            teks = teks[:batas]

        hasil = pre.preprocess(teks)
        tf = pre.hitung_tf(hasil['stemming_final'])
    except Exception as e:
        traceback.print_exc()
        return _gagal('Gagal memproses teks: %s' % e, 500)

    return jsonify({
        'success': True,
        'message': 'Preprocessing selesai',
        'sumber': sumber,
        'nama_dokumen': nama_dokumen,
        'dipotong': dipotong,
        'tahapan': hasil,
        'term_teratas': sorted(tf.items(), key=lambda x: (-x[1], x[0]))[:10],
    })


# ==================================================================
# POST /reload-stopwords
# ==================================================================
@app.route('/reload-stopwords', methods=['POST'])
def endpoint_reload_stopwords():
    sw = pre.muat_ulang_stopword()
    return jsonify({
        'success': True,
        'message': 'Daftar stopword dimuat ulang',
        'jumlah_stopword': len(sw),
    })


@app.errorhandler(404)
def _404(e):
    return _gagal('Endpoint tidak ditemukan', 404)


if __name__ == '__main__':
    print('=' * 60)
    print(' SERVICE NLP E-DOC')
    print(' Alamat  : http://%s:%d' % (SERVICE_HOST, SERVICE_PORT))
    print(' Uploads : %s' % UPLOADS_DIR)
    print(' Tekan CTRL+C untuk berhenti')
    print('=' * 60)
    # Daftar stopword & stemmer disiapkan lebih dulu supaya request
    # pertama tidak terasa lambat.
    pre.daftar_stopword()
    app.run(host=SERVICE_HOST, port=SERVICE_PORT, debug=False)
