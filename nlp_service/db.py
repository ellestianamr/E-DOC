# -*- coding: utf-8 -*-
"""
Akses database MySQL (database yang SAMA dengan aplikasi PHP).

Isi file ini:
    - koneksi ke MySQL lewat PyMySQL
    - simpan / hapus inverted index (tabel dokumen_term)
    - perhitungan ranking TF-IDF + cosine similarity
"""
import math

import pymysql

from config import DB_CONFIG


def koneksi():
    """Buka koneksi baru ke MySQL. Selalu ditutup lagi setelah dipakai."""
    return pymysql.connect(
        host=DB_CONFIG['host'],
        port=DB_CONFIG['port'],
        user=DB_CONFIG['user'],
        password=DB_CONFIG['password'],
        database=DB_CONFIG['database'],
        charset=DB_CONFIG['charset'],
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=False,
    )


# ==================================================================
# DOKUMEN
# ==================================================================
def ambil_dokumen(id_dokumen):
    """Ambil 1 baris dokumen berdasarkan id. None kalau tidak ada."""
    with koneksi() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id, id_user, nama_dokumen, bidang, nama_file, "
                "ukuran_file, total_term, indexed_at "
                "FROM dokumen WHERE id = %s",
                (id_dokumen,),
            )
            return cur.fetchone()


def ambil_semua_dokumen():
    """Semua dokumen, dipakai script reindex."""
    with koneksi() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id, nama_dokumen, bidang, nama_file "
                "FROM dokumen ORDER BY id ASC"
            )
            return cur.fetchall()


def ambil_teks_ekstraksi(id_dokumen):
    """Teks hasil ekstraksi Excel (untuk halaman demo preprocessing)."""
    with koneksi() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT nama_dokumen, bidang, teks_ekstraksi "
                "FROM dokumen WHERE id = %s",
                (id_dokumen,),
            )
            return cur.fetchone()


# ==================================================================
# INDEX
# ==================================================================
def simpan_index(id_dokumen, teks_ekstraksi, tf_dict, total_term):
    """Tulis ulang index sebuah dokumen.

    Index lama dihapus dulu, lalu term baru dimasukkan sekaligus
    (executemany) supaya cepat. Semua dalam satu transaksi: kalau ada
    error di tengah jalan, tidak ada perubahan yang tersimpan separuh.
    """
    with koneksi() as conn:
        try:
            with conn.cursor() as cur:
                cur.execute(
                    "DELETE FROM dokumen_term WHERE id_dokumen = %s",
                    (id_dokumen,),
                )

                if tf_dict:
                    baris = [
                        (id_dokumen, term[:100], int(tf))
                        for term, tf in tf_dict.items()
                    ]
                    cur.executemany(
                        "INSERT INTO dokumen_term (id_dokumen, term, tf) "
                        "VALUES (%s, %s, %s) "
                        "ON DUPLICATE KEY UPDATE tf = VALUES(tf)",
                        baris,
                    )

                # Simpan potongan teks (dibatasi supaya kolom tidak meledak)
                cur.execute(
                    "UPDATE dokumen SET teks_ekstraksi = %s, total_term = %s, "
                    "indexed_at = NOW() WHERE id = %s",
                    (teks_ekstraksi[:4000000], total_term, id_dokumen),
                )
            conn.commit()
        except Exception:
            conn.rollback()
            raise
    return len(tf_dict)


def hapus_index(id_dokumen):
    """Hapus index sebuah dokumen (dipakai saat dokumen dihapus/diganti)."""
    with koneksi() as conn:
        try:
            with conn.cursor() as cur:
                cur.execute(
                    "DELETE FROM dokumen_term WHERE id_dokumen = %s",
                    (id_dokumen,),
                )
                jml = cur.rowcount
                cur.execute(
                    "UPDATE dokumen SET teks_ekstraksi = NULL, total_term = 0, "
                    "indexed_at = NULL WHERE id = %s",
                    (id_dokumen,),
                )
            conn.commit()
        except Exception:
            conn.rollback()
            raise
    return jml


def statistik_index():
    """Ringkasan isi index, dipakai endpoint /status."""
    with koneksi() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT COUNT(*) AS n FROM dokumen")
            total_dokumen = cur.fetchone()['n']
            cur.execute("SELECT COUNT(*) AS n FROM dokumen WHERE total_term > 0")
            sudah_index = cur.fetchone()['n']
            cur.execute("SELECT COUNT(*) AS n FROM dokumen_term")
            total_baris_term = cur.fetchone()['n']
            cur.execute("SELECT COUNT(DISTINCT term) AS n FROM dokumen_term")
            term_unik = cur.fetchone()['n']
    return {
        'total_dokumen': total_dokumen,
        'dokumen_terindex': sudah_index,
        'total_baris_term': total_baris_term,
        'term_unik': term_unik,
    }


# ==================================================================
# PENCARIAN: TF-IDF + COSINE SIMILARITY
# ==================================================================
def cari(query_tf, bidang=None, limit=50):
    """Cari dokumen paling mirip dengan query.

    query_tf : dict {kata_dasar: berapa kali muncul di query}
    bidang   : None = semua bidang (admin), atau 'SEKRETARIAT' dst.
    limit    : jumlah maksimal hasil

    Rumus yang dipakai:
        tf     = 1 + log(jumlah kemunculan kata di dokumen)
                 Disebut "sublinear TF". Dipakai karena dokumen kita berupa
                 TABEL Excel: satu nilai kolom bisa terulang ratusan kali,
                 padahal bukan berarti 500x lebih penting daripada kata yang
                 muncul 1x. Logaritma meredam pengulangan itu.
        idf    = log( (N + 1) / (df + 1) ) + 1
                 N  = jumlah dokumen yang sudah di-index
                 df = jumlah dokumen yang memuat kata tersebut
        bobot  = tf * idf
        cosine = (A . B) / (|A| * |B|)

    Catatan: pembagian dengan panjang dokumen TIDAK diperlukan, karena
    pembagian dengan |B| pada rumus cosine sudah menangani perbedaan
    panjang dokumen.
    """
    kata_query = [k for k in query_tf.keys() if k]
    if not kata_query:
        return [], {}

    with koneksi() as conn:
        with conn.cursor() as cur:
            # --- N: jumlah dokumen yang sudah punya index ---
            cur.execute("SELECT COUNT(*) AS n FROM dokumen WHERE total_term > 0")
            N = cur.fetchone()['n']
            if N == 0:
                return [], {}

            # --- df tiap kata: berapa dokumen yang memuat kata itu ---
            cur.execute(
                "SELECT term, COUNT(DISTINCT id_dokumen) AS df "
                "FROM dokumen_term GROUP BY term"
            )
            df_map = {r['term']: r['df'] for r in cur.fetchall()}

            # --- dokumen kandidat: memuat MINIMAL satu kata dari query ---
            tanda = ','.join(['%s'] * len(kata_query))
            sql = (
                "SELECT DISTINCT d.id FROM dokumen d "
                "JOIN dokumen_term t ON t.id_dokumen = d.id "
                "WHERE t.term IN (%s) AND d.total_term > 0" % tanda
            )
            params = list(kata_query)
            if bidang:
                sql += " AND d.bidang = %s"
                params.append(bidang)

            cur.execute(sql, params)
            id_kandidat = [r['id'] for r in cur.fetchall()]
            if not id_kandidat:
                return [], {}

            # --- ambil SELURUH term milik dokumen kandidat ---
            # Perlu semua term (bukan cuma yang cocok) karena panjang vektor
            # dokumen (|B|) dihitung dari seluruh katanya.
            tanda_id = ','.join(['%s'] * len(id_kandidat))
            cur.execute(
                "SELECT t.id_dokumen, t.term, t.tf, d.nama_dokumen, d.bidang, "
                "d.nama_file, d.ukuran_file, d.total_term, d.created_at, "
                "d.updated_at "
                "FROM dokumen_term t JOIN dokumen d ON d.id = t.id_dokumen "
                "WHERE t.id_dokumen IN (%s)" % tanda_id,
                id_kandidat,
            )
            baris_term = cur.fetchall()

    # ---------------- IDF ----------------
    def idf(term):
        df = df_map.get(term, 0)
        return math.log((N + 1) / (df + 1)) + 1

    # ---------------- TF sublinear ----------------
    def bobot_tf(jumlah):
        """1 + log(jumlah). Kata yang muncul 1x bernilai 1,
        muncul 10x bernilai 3.3, muncul 500x bernilai 7.2."""
        return 1 + math.log(jumlah) if jumlah > 0 else 0

    # ---------------- Vektor QUERY ----------------
    bobot_query = {}
    for term, jumlah in query_tf.items():
        bobot_query[term] = bobot_tf(jumlah) * idf(term)
    norma_query = math.sqrt(sum(b * b for b in bobot_query.values()))
    if norma_query == 0:
        return [], {}

    # ---------------- Vektor tiap DOKUMEN ----------------
    dokumen = {}      # id -> info dokumen
    for r in baris_term:
        did = r['id_dokumen']
        if did not in dokumen:
            dokumen[did] = {
                'id': did,
                'nama_dokumen': r['nama_dokumen'],
                'bidang': r['bidang'],
                'nama_file': r['nama_file'],
                'ukuran_file': r['ukuran_file'],
                'created_at': r['created_at'],
                'updated_at': r['updated_at'],
                'total_term': r['total_term'] or 1,
                'bobot': {},
                'tf_mentah': {},
            }
        dokumen[did]['bobot'][r['term']] = bobot_tf(r['tf']) * idf(r['term'])
        dokumen[did]['tf_mentah'][r['term']] = r['tf']

    # ---------------- Cosine similarity ----------------
    hasil = []
    for did, d in dokumen.items():
        bobot = d['bobot']
        norma_dok = math.sqrt(sum(b * b for b in bobot.values()))
        if norma_dok == 0:
            continue

        dot = 0.0
        cocok = []
        for term, bq in bobot_query.items():
            bd = bobot.get(term)
            if bd:
                dot += bq * bd
                cocok.append({
                    'term': term,
                    'tf': int(d['tf_mentah'].get(term, 0)),
                    'bobot': round(bd, 6),
                })

        if dot <= 0:
            continue

        skor = dot / (norma_query * norma_dok)
        cocok.sort(key=lambda x: -x['bobot'])
        hasil.append({
            'id': did,
            'nama_dokumen': d['nama_dokumen'],
            'bidang': d['bidang'],
            'nama_file': d['nama_file'],
            'ukuran_file': d['ukuran_file'],
            'total_term': d['total_term'],
            'created_at': d['created_at'].strftime('%Y-%m-%d %H:%M:%S') if d['created_at'] else None,
            'updated_at': d['updated_at'].strftime('%Y-%m-%d %H:%M:%S') if d['updated_at'] else None,
            'skor': round(skor, 6),
            'persen': round(skor * 100, 2),
            'term_cocok': cocok,
        })

    hasil.sort(key=lambda x: -x['skor'])

    info = {
        'jumlah_dokumen_terindex': N,
        'idf_query': {t: round(idf(t), 4) for t in kata_query},
        'df_query': {t: df_map.get(t, 0) for t in kata_query},
    }
    return hasil[:limit], info


def term_teratas(id_dokumen, n=5):
    """5 kata dengan TF tertinggi -> dipakai fitur tag kata kunci."""
    with koneksi() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT term, tf FROM dokumen_term WHERE id_dokumen = %s "
                "ORDER BY tf DESC, term ASC LIMIT %s",
                (id_dokumen, int(n)),
            )
            return cur.fetchall()
