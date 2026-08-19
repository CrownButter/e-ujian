<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\UserModel;
use Config\Database;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class BinplinsisController extends BaseController
{
    protected $userModel;
    protected $db;
    public function __construct()
    {
        $this->db = Database::connect();
        $this->userModel = new UserModel();
        $roleModel = new RoleModel();
        helper('tanggal');
    }

    public function perdupsis(): string
    {
        $data = [
            'title' => 'Perdupsis',
        ];
        return view('binplinsis/perdupsis', $data);
    }

    public function nilaimental(): string
    {
        $db = \Config\Database::connect();
        $request = service('request');

        $userId = session()->get('user_id');
        $userLevel = strtolower(session()->get('level'));
        $roleId = session()->get('role_id');

        if (empty($userLevel)) {
            $user = $db->table('users')
                ->join('roles', 'roles.id = users.role_id')
                ->where('users.id', $userId)
                ->get()
                ->getRow();

            $userLevel = strtolower($user->nama_role ?? '');
            session()->set('level', $userLevel);
        }

        $model = new \App\Models\PenilaianMentalModel();

        // ---------------------------------------------------
        // AMBIL DATA ANGKATAN AKTIF & HITUNG TANGGAL OTOMATIS
        // ---------------------------------------------------
        $angkatanAktif = $db->table('angkatan')->where('status', 1)->get()->getRowArray();

        $minggu_req = $request->getGet('minggu');
        $hari_req   = $request->getGet('hari');

        // Jika user baru buka halaman (belum ada filter di URL)
        if ($minggu_req === null && $hari_req === null && !empty($angkatanAktif['tanggal_mulai'])) {
            $tglMulai    = new \DateTime($angkatanAktif['tanggal_mulai']);
            $tglSekarang = new \DateTime(); // Tanggal hari ini

            if ($tglSekarang >= $tglMulai) {
                $selisihHari = $tglMulai->diff($tglSekarang)->days;
                $minggu_aktif = floor($selisihHari / 7) + 1; // 1 minggu = 7 hari
                $hari_aktif   = (int) $tglSekarang->format('N'); // 1 (Senin) s.d 7 (Minggu)
            } else {
                $minggu_aktif = 1;
                $hari_aktif   = 1;
            }
        } else {
            // Jika ada filter URL (User ganti dari dropdown)
            $minggu_aktif = (int) ($minggu_req ?? 1);
            $hari_aktif   = (int) ($hari_req ?? 1);
        }

        // Hitung string tanggal pasti untuk ditampilkan ke view
        $tglMulaiVal = $angkatanAktif['tanggal_mulai'] ?? date('Y-m-d');
        $jumlahHariTambahan = (($minggu_aktif - 1) * 7) + ($hari_aktif - 1);
        $tanggal_terhitung  = date('Y-m-d', strtotime("+$jumlahHariTambahan days", strtotime($tglMulaiVal)));
        // ---------------------------------------------------

        $nama_pleton_aktif = $request->getGet('pleton');

        // Ambil nomor_induk pegawai yang sedang login
        $me = $db->table('pegawai')->where('user_id', $userId)->get()->getRowArray();
        $myNomorInduk = $me['nomor_induk'] ?? '';

        //---------------------------------------------------
        // 1. DAFTAR PLETON (DINAMIS & AKURAT)
        //---------------------------------------------------
        switch ($userLevel) {
            case 'danton':
                $pletonList = $db->table('pleton')
                    ->select('pleton.*, pegawai.nama as nama_danton, pegawai.nomor_induk as nrp_danton, pegawai.ttd as ttd_danton')
                    ->join('pegawai', 'pegawai.nomor_induk = pleton.danton_id', 'left')
                    ->where('pleton.danton_id', $myNomorInduk)
                    ->orderBy('pleton.nama_pleton', 'ASC')
                    ->get()->getResultArray();
                break;

            case 'danki':
                $pletonList = $db->table('pleton')
                    ->select('pleton.*, pegawai.nama as nama_danton, pegawai.nomor_induk as nrp_danton, pegawai.ttd as ttd_danton')
                    ->join('kompi', 'kompi.id = pleton.kompi_id', 'left')
                    ->join('pegawai', 'pegawai.nomor_induk = pleton.danton_id', 'left')
                    ->where('kompi.danki_id', $myNomorInduk)
                    ->orderBy('pleton.nama_pleton', 'ASC')
                    ->get()->getResultArray();
                break;

            case 'danyon':
                $pletonList = $db->table('pleton')
                    ->select('pleton.*, pegawai.nama as nama_danton, pegawai.nomor_induk as nrp_danton, pegawai.ttd as ttd_danton')
                    ->join('kompi', 'kompi.id = pleton.kompi_id', 'left')
                    ->join('batalyon', 'batalyon.id = kompi.batalyon_id', 'left')
                    ->join('pegawai', 'pegawai.nomor_induk = pleton.danton_id', 'left')
                    ->where('batalyon.danyon_id', $myNomorInduk)
                    ->orderBy('pleton.nama_pleton', 'ASC')
                    ->get()->getResultArray();
                break;

            case 'siswa':
                $pletonList = [];
                break;

            default: // Admin / Lainnya
                $pletonList = $db->table('pleton')
                    ->select('pleton.*, pegawai.nama as nama_danton, pegawai.nomor_induk as nrp_danton, pegawai.ttd as ttd_danton')
                    ->join('pegawai', 'pegawai.nomor_induk = pleton.danton_id', 'left')
                    ->orderBy('pleton.nama_pleton', 'ASC')
                    ->get()->getResultArray();
                break;
        }

        //---------------------------------------------------
        // 2. SINKRONISASI INITIAL TAB
        //---------------------------------------------------
        if (empty($nama_pleton_aktif)) {
            if ($roleId == 1 || $userLevel == 'admin') {
                $nama_pleton_aktif = 'All';
            } else {
                if (!empty($pletonList)) {
                    $nama_pleton_aktif = $pletonList[0]['nama_pleton'] ?? 'All';
                } else {
                    $nama_pleton_aktif = 'All';
                }
            }
        }

        //---------------------------------------------------
        // 3. BADGE JUMLAH SISWA
        //---------------------------------------------------
        $counts = [];
        if ($userLevel != 'siswa') {
            foreach ($pletonList as $p) {
                $counts[$p['nama_pleton']] = $db->table('siswa')
                    ->join('pleton', 'pleton.id = siswa.pleton_id')
                    ->where('pleton.nama_pleton', $p['nama_pleton'])
                    ->countAllResults();
            }
        }

        //---------------------------------------------------
        // 4. PAGINATION & DATA SISWA (DIOPTIMALKAN)
        //---------------------------------------------------
        $perPage = 26;
        $page = max(1, (int)($request->getGet('page') ?? 1));

        $builderForTotal = $model->getSiswaBuilder($userId, $userLevel, $nama_pleton_aktif);
        $total = $builderForTotal->countAllResults(false);

        $builderForData = $model->getSiswaBuilder($userId, $userLevel, $nama_pleton_aktif);
        $builderForData->limit($perPage, ($page - 1) * $perPage);
        $siswa = $builderForData->get()->getResultArray();

        $pager = service('pager');
        $pager->makeLinks($page, $perPage, $total);

        //---------------------------------------------------
        // 5. MAP DATA NILAI MENTAL (DIFILTER MINGGU & HARI)
        //---------------------------------------------------
        $map_nilai = [];
        $status_simpan = [];

        if (!empty($siswa)) {
            $ids = array_column($siswa, 'id');

            $queryNilai = $db->table('penilaian_mental')
                ->where('minggu_ke', $minggu_aktif)
                ->where('hari_ke', $hari_aktif)
                ->whereIn('siswa_id', $ids);

            // Jika yang login adalah Danki atau Danyon, kunci agar hanya bisa melihat 
            // data yang status_danton nya sudah '1' (sudah dikirim oleh Danton)
            if ($userLevel === 'danki' || $userLevel === 'danyon') {
                $queryNilai->where('status_danton', '1');
            }

            $dataNilai = $queryNilai->get()->getResultArray();

            foreach ($dataNilai as $n) {
                $map_nilai[$n['siswa_id']] = $n;
                $status_simpan[$n['siswa_id']] = true;
            }
        }

        //---------------------------------------------------
        // 6. PEJABAT STRUKTUR TANDA TANGAN 
        //---------------------------------------------------
        $danton = null;
        $danki  = null;
        $danyon = null;

        $pletonDeteksi = $nama_pleton_aktif;
        if (($pletonDeteksi === 'All' || empty($pletonDeteksi)) && !empty($pletonList)) {
            $pletonDeteksi = $pletonList[0]['nama_pleton'] ?? '';
        }

        // A. PENCARIAN DATA DANTON
        if (!empty($pletonDeteksi) && $pletonDeteksi !== 'All') {
            $danton = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('pleton', 'pleton.danton_id = pegawai.nomor_induk')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('pleton.nama_pleton', $pletonDeteksi)
                ->get()
                ->getRowArray();
        }

        if (empty($danton) && $userLevel === 'danton') {
            $danton = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('pegawai.nomor_induk', $myNomorInduk)
                ->get()
                ->getRowArray();
        }

        if (empty($danton) && $userLevel === 'danki') {
            $danton = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('pleton', 'pleton.danton_id = pegawai.nomor_induk')
                ->join('kompi', 'kompi.id = pleton.kompi_id')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('kompi.danki_id', $myNomorInduk)
                ->get()
                ->getRowArray();
        }

        if (empty($danton) && !empty($siswa)) {
            $siswaPertama = $siswa[0];
            $danton = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('pleton', 'pleton.danton_id = pegawai.nomor_induk')
                ->join('siswa', 'siswa.pleton_id = pleton.id')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('siswa.id', $siswaPertama['id'])
                ->get()
                ->getRowArray();
        }

        // B. PENCARIAN DATA DANKI
        if ($userLevel === 'danki') {
            $danki = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('pegawai.nomor_induk', $myNomorInduk)
                ->get()
                ->getRowArray();
        } else if (!empty($pletonDeteksi) && $pletonDeteksi !== 'All') {
            $danki = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('kompi', 'kompi.danki_id = pegawai.nomor_induk')
                ->join('pleton', 'pleton.kompi_id = kompi.id')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('pleton.nama_pleton', $pletonDeteksi)
                ->get()
                ->getRowArray();
        }

        if (empty($danki) && !empty($danton)) {
            $danki = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('kompi', 'kompi.danki_id = pegawai.nomor_induk')
                ->join('pleton', 'pleton.kompi_id = kompi.id')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('pleton.danton_id', $danton['nomor_induk'] ?? '')
                ->get()
                ->getRowArray();
        }

        // C. PENCARIAN DATA DANYON
        if ($userLevel === 'danyon') {
            $danyon = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('pegawai.nomor_induk', $myNomorInduk)
                ->get()
                ->getRowArray();
        } else if ($userLevel === 'danki') {
            $danyon = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('batalyon', 'batalyon.danyon_id = pegawai.nomor_induk')
                ->join('kompi', 'kompi.batalyon_id = batalyon.id')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('kompi.danki_id', $myNomorInduk)
                ->get()
                ->getRowArray();
        } else if (!empty($pletonDeteksi) && $pletonDeteksi !== 'All') {
            $danyon = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('batalyon', 'batalyon.danyon_id = pegawai.nomor_induk')
                ->join('kompi', 'kompi.batalyon_id = batalyon.id')
                ->join('pleton', 'pleton.kompi_id = kompi.id')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('pleton.nama_pleton', $pletonDeteksi)
                ->get()
                ->getRowArray();
        }

        if (empty($danyon)) {
            $danyon = $db->table('pegawai')
                ->select('pegawai.*, pangkat.nama_pangkat')
                ->join('batalyon', 'batalyon.danyon_id = pegawai.nomor_induk')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->get()
                ->getRowArray();
        }

        //---------------------------------------------------
        // 7. STATUS APPROVAL TANDA TANGAN BERJENJANG
        //---------------------------------------------------
        $status_approval = [
            'status_danton' => '0',
            'status_danki'  => '0',
            'status_danyon' => '0',
        ];

        if (!empty($map_nilai)) {
            $first_nilai = reset($map_nilai);
            $status_approval['status_danton'] = $first_nilai['status_danton'] ?? '0';
            $status_approval['status_danki']  = $first_nilai['status_danki'] ?? '0';
            $status_approval['status_danyon'] = $first_nilai['status_danyon'] ?? '0';
        }

        //---------------------------------------------------
        // 8. RETURN DATA KE VIEW (Sudah membawa $tanggal_terhitung)
        //---------------------------------------------------
        return view('binplinsis/nilaimental', [
            'title'             => 'Nilai Mental',
            'prefix'            => $userLevel,
            'siswa'             => $siswa,
            'pager'             => $pager,
            'perPage'           => $perPage,
            'currentPage'       => $page,
            'map_nilai'         => $map_nilai,
            'status_simpan'     => $status_simpan,
            'minggu_aktif'      => $minggu_aktif,
            'hari_aktif'        => $hari_aktif,
            'tanggal_terhitung' => $tanggal_terhitung, // <--- INI KUNCI UTAMANYA
            'pleton_list'       => $pletonList,
            'counts'            => $counts,
            'nama_pleton_aktif' => $nama_pleton_aktif,
            'jumlah_notif'      => ($userLevel != 'siswa') ? (method_exists($this, 'getJumlahNotifDanki') ? $this->getJumlahNotifDanki() : 0) : 0,

            'danton'            => $danton,
            'danki'             => $danki,
            'danyon'            => $danyon,
            'status_approval'   => $status_approval
        ]);
    }

    public function simpanNilaiMental()
    {
        try {
            if (session()->get('role_id') != 4) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tidak memiliki hak akses'
                ]);
            }

            $json = $this->request->getJSON(true);

            if (!$json) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'JSON kosong'
                ]);
            }

            log_message('error', print_r($json, true));

            $skor      = $json['nilai'] ?? [];
            $siswa_id  = $json['siswa_id'] ?? null;
            $minggu_ke = $json['minggu_ke'] ?? null;
            $hari_ke   = $json['hari_ke'] ?? 1; // Ambil data hari, default ke 1 (Senin) jika kosong

            // Validasi data request termasuk hari_ke
            if (!$siswa_id || !$minggu_ke || !$hari_ke || !is_array($skor)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Data request tidak lengkap',
                    'json' => $json
                ]);
            }

            $total_skor = array_sum(array_map('intval', $skor));

            $jml_hsl_pengamatan = round($total_skor / 22, 1);
            $nilai_konversi = ($jml_hsl_pengamatan * 5) + 55;

            $tMinus = (float)($json['tind_minus'] ?? 0);
            $tPlus  = (float)($json['tind_plus'] ?? 0);

            $nilai_akhir = $nilai_konversi - $tMinus + $tPlus;

            $data = [
                'siswa_id'           => $siswa_id,
                'angkatan_id'        => $json['angkatan_id'] ?? null,
                'minggu_ke'          => $minggu_ke,
                'hari_ke'            => $hari_ke, // Disimpan ke database
                'jml_skor'           => $total_skor,
                'jml_hsl_pengamatan' => $jml_hsl_pengamatan,
                'nilai_konversi'     => $nilai_konversi,
                'tind_diluar_minus'  => $tMinus,
                'tind_diluar_plus'   => $tPlus,
                'nilai_akhir'        => $nilai_akhir,
                'danton_id'          => session()->get('user_id'),
                'skor_spiritual'     => json_encode(array_slice($skor, 0, 3)),
                'skor_ideologi'      => json_encode(array_slice($skor, 3, 3)),
                'skor_kejuangan'     => json_encode(array_slice($skor, 6, 4)),
                'skor_watak'         => json_encode(array_slice($skor, 10, 4)),
                'skor_kepemimpinan'  => json_encode(array_slice($skor, 14, 8)),
                'status_danton'      => '0'
            ];

            $db = db_connect();
            $builder = $db->table('penilaian_mental');

            // Cek data berdasarkan siswa, minggu, DAN hari
            $cek = $builder->where('siswa_id', $siswa_id)
                ->where('minggu_ke', $minggu_ke)
                ->where('hari_ke', $hari_ke)
                ->get()
                ->getRow();

            if ($cek) {
                $builder->where('id', $cek->id)->update($data);
            } else {
                $builder->insert($data);
            }

            if ($db->error()['code'] != 0) {
                return $this->response->setJSON($db->error());
            }

            return $this->response->setJSON([
                'success' => true,
                'token'   => csrf_hash()
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile()
            ]);
        }
    }

    public function simpanNilaiMentalMassal()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak.']);
        }

        $json = $this->request->getJSON(true);
        $namaPleton = $json['nama_pleton'] ?? '';
        $mingguKe   = $json['minggu_ke'] ?? '';
        $aksiType   = $json['aksi_type'] ?? '';

        if (empty($namaPleton) || empty($mingguKe)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Parameter tidak lengkap.']);
        }
        $db = \Config\Database::connect();
        $builder = $db->table('nilai_mental'); // sesuaikan nama tabel nilai Anda

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Seluruh data peleton ' . $namaPleton . ' berhasil diproses.',
            'token'   => csrf_hash() // kirim balik token baru jika CSRF regenerasi aktif
        ]);
    }

    // 1. METHOD KHUSUS DANTON: KIRIM KE DANKI (Mengubah status_danton = '1')
    public function kirimMassalKeDanki()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $json = $this->request->getJSON();
        $nama_pleton = $json->nama_pleton ?? '';
        $minggu_ke = $json->minggu_ke ?? '';

        if (empty($nama_pleton) || empty($minggu_ke)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap']);
        }

        $db = \Config\Database::connect();

        try {
            // Tambahkan status_danki = '0' dan catatan_danki = NULL agar tombol di Danki kembali aktif
            $sql = "UPDATE penilaian_mental 
                SET status_danton = '1', 
                    status_danki = '0', 
                    catatan_danki = NULL 
                WHERE id IN (
                    SELECT id FROM (
                        SELECT pm.id 
                        FROM penilaian_mental pm
                        JOIN siswa s ON s.id = pm.siswa_id
                        JOIN pleton p ON p.id = s.pleton_id
                        WHERE p.nama_pleton = ? 
                        AND pm.minggu_ke = ?
                    ) AS temp_table
                )";

            $db->query($sql, [$nama_pleton, $minggu_ke]);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Sukses! Data Peleton berhasil dikirim ke Danki.',
                'token'   => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', '[KirimMassalKeDanki] ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'token' => csrf_hash()]);
        }
    }

    // 2. METHOD KHUSUS DANKI: KIRIM KE DANYON (Mengubah status_danki = '1')
    public function kirimMassalKeDanyon()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $json = $this->request->getJSON();
        $minggu_ke = $json->minggu_ke ?? null;
        $nama_pleton = $json->nama_pleton ?? 'All';

        if (!$minggu_ke) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data minggu tidak lengkap']);
        }

        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $userLevel = strtolower(session()->get('level'));

        try {
            // Gunakan builder model yang sama persis seperti saat meload data siswa di halaman utama
            $model = new \App\Models\PenilaianMentalModel();

            // Ambil data siswa berdasarkan filter pleton aktif/all yang dikirim dari tombol
            $builderForData = $model->getSiswaBuilder($userId, $userLevel, $nama_pleton);
            $siswaList = $builderForData->get()->getResultArray();

            if (empty($siswaList)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada data siswa yang ditemukan untuk dikirim.']);
            }

            $siswaIds = array_column($siswaList, 'id');

            // Lakukan update status massal ke tabel penilaian_mental untuk siswa-siswa tersebut di minggu aktif
            $db->table('penilaian_mental')
                ->where('minggu_ke', $minggu_ke)
                ->whereIn('siswa_id', $siswaIds)
                ->update([
                    'status_danki' => '1' // Sesuaikan jika field status untuk kirim ke danyon berbeda
                ]);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Rekap nilai mental berhasil dikirim ke Danyon.',
                'token'   => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', '[KirimMassalKeDanyon] ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    public function verifikasiMassalDanyon()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $json = $this->request->getJSON();
        $minggu_ke = $json->minggu_ke ?? null;
        $nama_pleton = $json->nama_pleton ?? 'All';

        if (!$minggu_ke) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data minggu tidak lengkap']);
        }

        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $userLevel = strtolower(session()->get('level'));

        try {
            // Menggunakan builder yang sama untuk konsistensi data siswa
            $model = new \App\Models\PenilaianMentalModel();
            $builderForData = $model->getSiswaBuilder($userId, $userLevel, $nama_pleton);
            $siswaList = $builderForData->get()->getResultArray();

            if (empty($siswaList)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada data siswa yang ditemukan untuk diverifikasi.']);
            }

            $siswaIds = array_column($siswaList, 'id');

            // Lakukan update status verifikasi oleh Danyon pada tabel penilaian_mental
            $db->table('penilaian_mental')
                ->where('minggu_ke', $minggu_ke)
                ->whereIn('siswa_id', $siswaIds)
                ->update([
                    'status_danyon' => '1' // Sesuaikan nama kolom status verifikasi danyon di database Anda jika berbeda
                ]);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Nilai mental berhasil diverifikasi oleh Danyon.',
                'token'   => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', '[VerifikasiMassalDanyon] ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    public function exportPdf()
    {
        $userId = session()->get('user_id');
        $filterPleton = session()->get('active_pleton'); // Ambil dari session

        $model = new \App\Models\PenilaianMentalModel();
        $siswa = $model->getSiswaByDanton($userId);

        // Filter data
        if (!empty($filterPleton)) {
            $siswa = array_filter($siswa, function ($s) use ($filterPleton) {
                return isset($s['nama_pleton']) && trim($s['nama_pleton']) === trim($filterPleton);
            });
        }

        $db = \Config\Database::connect();
        $data_nilai = $db->table('penilaian_mental')->get()->getResultArray();
        $map_nilai = [];
        foreach ($data_nilai as $n) {
            $map_nilai[$n['siswa_id']] = $n;
        }

        $data = [
            'siswa'     => $siswa,
            'map_nilai' => $map_nilai,
            'title'     => 'Laporan Nilai Mental ' . (!empty($filterPleton) ? $filterPleton : 'Semua Pleton')
        ];

        // Clear session setelah export agar tidak nyangkut terus
        session()->remove('active_pleton');

        $html = view('binplinsis/print_pdf', $data);
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('Laporan_Nilai.pdf', ['Attachment' => false]);
    }

    public function savePletonSession()
    {
        $pleton = $this->request->getPost('pleton');
        session()->set('active_pleton', $pleton);
        return json_encode(['status' => 'success']);
    }

    public function nilaijasmani(): string
    {
        $data = [
            'title' => 'Nilai Jasmani',
        ];
        return view('binplinsis/nilaijasmani', $data);
    }

    public function sosiometri(): string
    {
        $data = [
            'title' => 'Sosiometri',
        ];
        return view('binplinsis/sosiometri', $data);
    }


    private function getJumlahNotifDanki()
    {
        // Hanya hitung jika user adalah Danki (role_id 5)
        if (session()->get('role_id') != 5) {
            return 0;
        }

        $danki_id = session()->get('username'); // Asumsi username = nomor induk
        return $this->db->table('penilaian_mental pm')
            ->join('siswa s', 's.id = pm.siswa_id')
            ->join('pleton p', 'p.id = s.pleton_id')
            ->join('kompi k', 'k.id = p.kompi_id')
            ->where('k.danki_id', $danki_id)
            ->where('pm.status_danton', '1')
            ->where('pm.status_danki', '0')
            ->countAllResults();
    }

    public function verifikasiNilaiMental()
    {
        // Pastikan hanya menerima request AJAX
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Akses ditolak'
            ]);
        }

        // Tangkap data menggunakan getPost karena AJAX mengirim data form-urlencoded standar
        $id_siswa  = $this->request->getPost('id_siswa');
        $aksi      = $this->request->getPost('aksi');
        $minggu_ke = $this->request->getPost('minggu_ke') ?? 1;

        // Validasi data siswa
        if (!$id_siswa) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan',
                'token' => csrf_hash()
            ]);
        }

        $db = \Config\Database::connect();

        // 1. Cek dulu apakah baris nilai untuk siswa tersebut di minggu ini sudah ada
        $cekNilai = $db->table('penilaian_mental')
            ->where('siswa_id', $id_siswa)
            ->where('minggu_ke', $minggu_ke)
            ->get()
            ->getRow();

        if (!$cekNilai) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nilai siswa belum diinput oleh Danton, tidak dapat diverifikasi.',
                'token' => csrf_hash()
            ]);
        }

        // 2. Tentukan kolom status yang akan di-update berdasarkan kiriman tombol JavaScript
        $updateData = [];
        if ($aksi === 'approve_danki') {
            $updateData = ['status_danki' => '1'];
        } elseif ($aksi === 'tolak_danki') {
            $updateData = ['status_danki' => '2'];
        } elseif ($aksi === 'approve_danyon') {
            $updateData = ['status_danyon' => '1'];
        } elseif ($aksi === 'tolak_danyon') {
            $updateData = ['status_danyon' => '2'];
        }

        if (empty($updateData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Aksi verifikasi tidak valid.',
                'token' => csrf_hash()
            ]);
        }

        try {
            // 3. Eksekusi update berdasarkan ID baris penilaian_mental
            $db->table('penilaian_mental')
                ->where('id', $cekNilai->id)
                ->update($updateData);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Berhasil memperbarui status verifikasi!',
                'token' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
                'token' => csrf_hash()
            ]);
        }
    }

    public function exportPdfByRole($roleId)
    {
        $db = \Config\Database::connect();

        $users = $db->table('users')
            ->select('users.*, roles.nama_role')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.role_id', $roleId)
            ->get()
            ->getResultArray();

        $roleModel = new \App\Models\RoleModel();
        $roleInfo = $roleModel->find($roleId);

        $data = [
            'title' => 'Laporan Data Berdasarkan Role: ' . ($roleInfo['nama_role'] ?? 'User'),
            'data_list' => $users,
            'kategori' => 'Role: ' . ($roleInfo['nama_role'] ?? 'User')
        ];

        return $this->generatePdf('binplinsis/role/pdf_template', $data);
    }

    // Export PDF Berdasarkan Pleton dengan prefix binplinsis
    // Export PDF Berdasarkan Pleton dengan prefix binplinsis
    public function exportPdfByPleton($pletonId)
    {
        $db = \Config\Database::connect();

        // 1. Ambil informasi pleton beserta relasi ke kompi & batalyon dalam 1 query efisien
        $pletonInfo = $db->table('pleton')
            ->select('pleton.*, kompi.id as kompi_id, kompi.batalyon_id, kompi.danki_id, batalyon.danyon_id')
            ->join('kompi', 'kompi.id = pleton.kompi_id', 'left')
            ->join('batalyon', 'batalyon.id = kompi.batalyon_id', 'left')
            ->where('pleton.id', $pletonId)
            ->get()
            ->getRowArray();

        $namaPleton = $pletonInfo['nama_pleton'] ?? '-';

        // 2. Ambil data siswa & pastikan nama_pleton terbawa
        $siswa = $db->table('siswa')
            ->select('siswa.*, pleton.nama_pleton, pleton.kompi_id')
            ->join('pleton', 'pleton.id = siswa.pleton_id', 'left')
            ->where('siswa.pleton_id', $pletonId)
            ->get()
            ->getResultArray();

        // 3. Ambil data angkatan yang berstatus aktif (status = 1) dari tabel angkatan[cite: 3]
        $angkatanAktif = $db->table('angkatan')
            ->where('status', 1)
            ->get()
            ->getRowArray();

        $namaAngkatan = $angkatanAktif['nama_angkatan'] ?? 'ANGKATAN 58';
        $tahunAnggaran = $angkatanAktif['tahun_angkatan'] ?? '2025-2026';

        // Format susunan subtitle dinamis sesuai data aktif
        $subtitleDinamis = 'PESERTA DIDIK DIKTUK BINTARA POLWAN ' . strtoupper($namaAngkatan) . ' TAHUN ANGGARAN ' . strtoupper($tahunAnggaran);

        // 4. Tanggal pengamatan & format bahasa Indonesia
        $tanggalPengamatan = $this->request->getGet('tanggal') ?? date('Y-m-d');

        $formatter = new \IntlDateFormatter('id_ID', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);

        $formatter->setPattern('EEEE');
        $hari = strtoupper($formatter->format(strtotime($tanggalPengamatan)));

        $formatter->setPattern('d MMMM yyyy');
        $tanggalFormatted = strtoupper($formatter->format(strtotime($tanggalPengamatan)));

        // 5. Helper function kecil untuk mapping data pejabat agar tidak redundant
        $getPejabatData = function ($key) use ($db) {
            $default = ['nama' => '-', 'pangkat_nrp' => '-'];
            if (empty($key)) return $default;

            $pegawai = $db->table('pegawai')
                ->groupStart()
                ->where('id', $key)
                ->orWhere('nomor_induk', $key)
                ->groupEnd()
                ->get()
                ->getRowArray();

            if (!$pegawai) return $default;

            $pangkat = $pegawai['pangkat'] ?? $pegawai['pangkat_gol'] ?? '';
            $nrp = $pegawai['nomor_induk'] ?? $pegawai['nip'] ?? $pegawai['nrp'] ?? '-';

            return [
                'nama' => $pegawai['nama'] ?? $pegawai['nama_pegawai'] ?? '-',
                'pangkat_nrp' => trim($pangkat . ' NRP ' . $nrp)
            ];
        };

        // Ekstraksi data pejabat berdasarkan key dari tabel pleton, kompi, & batalyon
        $danton = $getPejabatData($pletonInfo['danton_id'] ?? $pletonInfo['pegawai_id'] ?? null);
        $danki  = $getPejabatData($pletonInfo['danki_id'] ?? null);
        $danyon = $getPejabatData($pletonInfo['danyon_id'] ?? null);

        // 6. Susun data untuk dikirim ke template PDF
        $data = [
            'title'             => 'DAFTAR NILAI MENTAL HASIL PENGAMATAN',
            'subtitle'          => $subtitleDinamis,
            'data_list'         => $siswa,
            'nama_pleton'       => $namaPleton,
            'hari'              => $hari,
            'tanggal_format'    => $tanggalFormatted,
            'pejabat'           => [
                'danton' => $danton,
                'danki'  => $danki,
                'danyon' => $danyon
            ]
        ];

        return $this->generatePdf('binplinsis/pleton/pdf_template', $data);
    }
    // Fungsi Helper privat untuk render Dompdf
    private function generatePdf($viewHtml, $data)
    {
        $dompdf = new Dompdf();

        // Aktifkan opsi jika diperlukan (misal untuk load asset gambar/css eksternal)
        $options = $dompdf->getOptions();
        $options->setIsRemoteEnabled(true);
        $dompdf->setOptions($options);

        $html = view($viewHtml, $data);

        $dompdf->loadHtml($html);


        $dompdf->setPaper('A4', 'landscape');

        $dompdf->render();

        return $dompdf->stream('Daftar_Nilai_Mental_' . date('Y-m-d') . '.pdf', ['Attachment' => 0]);
    }

    public function verifikasi()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $json = $this->request->getJSON();
        $siswa_id  = $json->siswa_id ?? null;
        $aksi      = $json->aksi ?? '';
        $minggu_ke = $json->minggu_ke ?? null;

        if (!$siswa_id || !$minggu_ke || empty($aksi)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Parameter verifikasi tidak lengkap']);
        }

        $db = \Config\Database::connect();

        try {
            $dataUpdate = [];

            // Tentukan field status yang akan di-update berdasarkan aksi tombol
            switch ($aksi) {
                case 'approve_danki':
                    $dataUpdate = ['status_danki' => '1'];
                    break;
                case 'reject_danki':
                    $dataUpdate = ['status_danki' => '2'];
                    break;
                case 'approve_danyon':
                    $dataUpdate = ['status_danyon' => '1'];
                    break;
                case 'reject_danyon':
                    $dataUpdate = ['status_danyon' => '2'];
                    break;
            }

            if (empty($dataUpdate)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Jenis aksi verifikasi tidak dikenali']);
            }

            // Lakukan update ke tabel penilaian_mental
            $db->table('penilaian_mental')
                ->where('siswa_id', $siswa_id)
                ->where('minggu_ke', $minggu_ke)
                ->update($dataUpdate);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Status verifikasi siswa berhasil diperbarui.',
                'token'   => csrf_hash()
            ]);
        } catch (\Exception $e) {
            log_message('error', '[VerifikasiSiswa] ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    public function proses_tolak_danki()
    {
        $db = \Config\Database::connect();
        $request = $this->request;

        $siswaId  = $request->getPost('siswa_id');
        $mingguKe = $request->getPost('minggu_ke');
        $catatan  = $request->getPost('catatan');

        // Ambil ID Danki yang sedang login (sesuaikan session di aplikasi Anda)
        $dankiId  = session()->get('id_user'); // atau session()->get('user_id')

        // Update status_danki menjadi '2' (Ditolak) dan masukkan catatan_danki
        $db->table('penilaian_mental')
            ->where('siswa_id', $siswaId)
            ->where('minggu_ke', $mingguKe)
            ->update([
                'status_danki'         => '2',
                'catatan_danki'        => $catatan,
                'status_danton'        => '0',
                'approved_by_danki_id' => $dankiId,
                'approved_by_danki_at' => date('Y-m-d H:i:s')
            ]);

        return $this->response->setJSON(['status' => 'success']);
    }


    public function exportExcel()
    {

        $db = \Config\Database::connect();
        $request = service('request');

        $minggu = (int) ($request->getGet('minggu') ?? 1);
        $hari   = (int) ($request->getGet('hari') ?? 1);
        $pleton = $request->getGet('pleton') ?? 'All';

        $angkatanAktif = $db->table('angkatan')->where('status', 1)->get()->getRowArray();

        // Hitung Tanggal Berdasarkan Minggu & Hari
        $tglMulaiVal = $angkatanAktif['tanggal_mulai'] ?? date('Y-m-d');
        $jumlahHariTambahan = (($minggu - 1) * 7) + ($hari - 1);
        $tanggal_terhitung  = date('Y-m-d', strtotime("+$jumlahHariTambahan days", strtotime($tglMulaiVal)));

        $listHariIndo = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $namaHariAktif = $listHariIndo[$hari] ?? 'Senin';

        // Ambil Data Siswa
        $builder = $db->table('siswa')
            ->select('siswa.*, pleton.nama_pleton')
            ->join('pleton', 'pleton.id = siswa.pleton_id', 'left');

        if ($pleton !== 'All') {
            $builder->where('pleton.nama_pleton', $pleton);
        }
        $builder->orderBy('siswa.nosis', 'ASC');
        $siswaList = $builder->get()->getResultArray();

        // Ambil Data Nilai Mental
        $map_nilai = [];
        if (!empty($siswaList)) {
            $ids = array_column($siswaList, 'id');
            $dataNilai = $db->table('penilaian_mental')
                ->where('minggu_ke', $minggu)
                ->where('hari_ke', $hari)
                ->whereIn('siswa_id', $ids)
                ->get()
                ->getResultArray();

            foreach ($dataNilai as $n) {
                $map_nilai[$n['siswa_id']] = $n;
            }
        }

        // Ambil Pejabat Penandatangan dengan Aman (mencegah error jika data kosong)
        $danton = null;
        $danki = null;
        $danyon = null;
        if ($pleton !== 'All') {
            $pletonRow = $db->table('pleton')->where('nama_pleton', $pleton)->get()->getRowArray();
            if ($pletonRow) {
                // Danton
                if (!empty($pletonRow['danton_id'])) {
                    $danton = $db->table('pegawai')
                        ->select('pegawai.*, pangkat.nama_pangkat')
                        ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                        ->where('pegawai.nomor_induk', $pletonRow['danton_id'])
                        ->get()->getRowArray();
                }
                // Danki via Kompi
                if (!empty($pletonRow['kompi_id'])) {
                    $kompiRow = $db->table('kompi')->where('id', $pletonRow['kompi_id'])->get()->getRowArray();
                    if ($kompiRow && !empty($kompiRow['danki_id'])) {
                        $danki = $db->table('pegawai')
                            ->select('pegawai.*, pangkat.nama_pangkat')
                            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                            ->where('pegawai.nomor_induk', $kompiRow['danki_id'])
                            ->get()->getRowArray();
                    }
                    // Danyon via Batalyon
                    if ($kompiRow && !empty($kompiRow['batalyon_id'])) {
                        $batalyonRow = $db->table('batalyon')->where('id', $kompiRow['batalyon_id'])->get()->getRowArray();
                        if ($batalyonRow && !empty($batalyonRow['danyon_id'])) {
                            $danyon = $db->table('pegawai')
                                ->select('pegawai.*, pangkat.nama_pangkat')
                                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                                ->where('pegawai.nomor_induk', $batalyonRow['danyon_id'])
                                ->get()->getRowArray();
                        }
                    }
                }
            }
        }

        return view('binplinsis/export_nilaimental_excel', [
            'minggu'            => $minggu,
            'hari_nama'         => $namaHariAktif,
            'tanggal_terhitung' => $tanggal_terhitung,
            'pleton'            => $pleton,
            'siswaList'         => $siswaList,
            'map_nilai'         => $map_nilai,
            'danton'            => $danton,
            'danki'             => $danki,
            'danyon'            => $danyon
        ]);
    }
}
