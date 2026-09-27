<?php
/**
 * ============================================================
 * PENGHUBUNG PHP <-> SERVICE PYTHON (NLP)
 * ============================================================
 * Semua komunikasi ke service Flask (http://127.0.0.1:5000)
 * dikumpulkan di file ini supaya kalau alamatnya berubah,
 * cukup diubah di satu tempat.
 *
 * ATURAN PENTING:
 * Kalau service Python sedang mati, fungsi di sini TIDAK boleh
 * membuat aplikasi error. Fungsi selalu mengembalikan array:
 *     ['ok' => true/false, 'data' => array, 'error' => string]
 * Pemanggilnya yang memutuskan mau menampilkan peringatan atau tidak.
 */

// Alamat service Python. Ubah di sini kalau port-nya diganti.
if (!defined('NLP_BASE_URL')) {
    define('NLP_BASE_URL', 'http://127.0.0.1:5000');
}

/**
 * Kirim permintaan ke service Python.
 *
 * @param string $method  GET / POST / DELETE
 * @param string $path    contoh: '/search'
 * @param array  $body    data yang dikirim (akan diubah jadi JSON)
 * @param int    $timeout batas waktu tunggu dalam detik
 */
function nlp_request($method, $path, $body = null, $timeout = 20)
{
    $ch = curl_init(NLP_BASE_URL . $path);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // kalau service mati, cepat menyerah

    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json),
        ]);
    }

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    // Service tidak bisa dihubungi sama sekali
    if ($response === false) {
        return [
            'ok'     => false,
            'data'   => [],
            // Pesan singkat & mudah dimengerti untuk ditampilkan ke user...
            'error'  => 'Service NLP belum dinyalakan di ' . NLP_BASE_URL,
            // ...sedangkan pesan asli dari cURL disimpan untuk keperluan
            // pengecekan kalau suatu saat perlu ditelusuri.
            'detail' => $curl_err,
        ];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return [
            'ok'    => false,
            'data'  => [],
            'error' => 'Jawaban service tidak bisa dibaca (HTTP ' . $http_code . ')',
        ];
    }

    if ($http_code >= 400 || empty($data['success'])) {
        return [
            'ok'    => false,
            'data'  => $data,
            'error' => isset($data['message']) ? $data['message']
                                               : 'Gagal (HTTP ' . $http_code . ')',
        ];
    }

    return ['ok' => true, 'data' => $data, 'error' => ''];
}

/**
 * Cek apakah service NLP hidup. Dipakai halaman pencarian untuk
 * menampilkan peringatan kalau service belum dinyalakan.
 */
function nlp_status()
{
    return nlp_request('GET', '/status', null, 5);
}

/**
 * Minta service membaca file Excel lalu menyimpan index-nya.
 * Dipanggil setelah upload / ganti file berhasil.
 */
function nlp_index_dokumen($id_dokumen)
{
    // Indexing membaca seluruh isi Excel, jadi diberi waktu lebih panjang.
    return nlp_request('POST', '/index', ['id_dokumen' => (int) $id_dokumen], 60);
}

/**
 * Hapus index sebuah dokumen. Dipanggil sebelum baris dokumen dihapus.
 */
function nlp_hapus_index($id_dokumen)
{
    return nlp_request('DELETE', '/index/' . (int) $id_dokumen, null, 15);
}

/**
 * Cari dokumen berdasarkan isinya.
 *
 * @param string      $query  kata kunci dari user
 * @param string|null $bidang null = semua bidang (untuk admin)
 */
function nlp_search($query, $bidang = null, $limit = 50)
{
    return nlp_request('POST', '/search', [
        'query'  => $query,
        'bidang' => $bidang,
        'limit'  => (int) $limit,
    ], 30);
}

/**
 * Ambil hasil preprocessing tiap tahap (untuk halaman demo).
 * Isi salah satu: $teks ATAU $id_dokumen.
 */
function nlp_preprocess($teks = '', $id_dokumen = null, $batas_karakter = 4000)
{
    return nlp_request('POST', '/preprocess', [
        'teks'           => $teks,
        'id_dokumen'     => $id_dokumen ? (int) $id_dokumen : null,
        'batas_karakter' => (int) $batas_karakter,
    ], 30);
}
