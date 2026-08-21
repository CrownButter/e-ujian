<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BankSoalModel;

class ObeController extends BaseController
{
    protected $bankSoalModel;

    // Pemetaan Role
    protected $roleMap = [
        1 => 'admin',
        2 => 'operator',
        3 => 'gadik',
        4 => 'danton',
        5 => 'danki',
        6 => 'danyon',
        7 => 'siswa'
    ];

    public function __construct()
    {
        $this->bankSoalModel = model(BankSoalModel::class);
    }

    protected function getRolePrefix()
    {
        $roleId = (int) session()->get('role_id');
        return $this->roleMap[$roleId] ?? 'admin';
    }

    // ========================================================================
    // MANAJEMEN BANK SOAL (C1 s/d C6)
    // ========================================================================
    public function kelas_ujian()
    {
        $rolePrefix = $this->getRolePrefix();

        $data = [
            'title'       => 'Manajemen Kelas Ujian OBE',
            'page_title'  => 'Kelas Ujian',
            'role_prefix' => $rolePrefix,
        ];

        // Pastikan file view 'obe/kelas_ujian' sudah ada di folder app/Views/obe/
        return view('obe/kelas_ujian', $data);
    }

    // ========================================================================
    // API UNTUK KELAS UJIAN
    // ========================================================================

    public function kelasUjianGetData()
    {
        try {
            $db = \Config\Database::connect();
            $rolePrefix = $this->getRolePrefix();
            $userId = session()->get('user_id');

            $builder = $db->table('kelas_ujian')
                ->select('kelas_ujian.*, mata_pelajaran.nama_mapel as mata_pelajaran, pegawai.nama as nama_gadik, pangkat.nama_pangkat')
                ->select('(SELECT COUNT(kelas_ujian_peserta.siswa_id) FROM kelas_ujian_peserta WHERE kelas_ujian_peserta.kelas_ujian_id = kelas_ujian.id) as jumlah_siswa', false)
                // GUNAKAN REPLACE UNTUK MEMBERSIHKAN TEKS "Pleton ID: " SEHINGGA HANYA TERSISA ANGKA DAN KOMANYA SAJA
                ->select('(SELECT GROUP_CONCAT(pleton.nama_pleton SEPARATOR ", ") FROM pleton WHERE FIND_IN_SET(pleton.id, REPLACE(REPLACE(kelas_ujian.deskripsi, "Pleton ID:", ""), " ", "")) ) as nama_pleton', false)
                ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
                ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left');

            // Jika yang login adalah gadik, cari dulu id pegawai berdasarkan user_id yang login
            if ($rolePrefix === 'gadik' && !empty($userId)) {
                $pegawai = $db->table('pegawai')
                    ->where('id', $userId)
                    ->orWhere('user_id', $userId)
                    ->get()
                    ->getRow();

                if ($pegawai) {
                    $builder->groupStart()
                        ->where('kelas_ujian.penguji_id', $pegawai->id)
                        ->orWhere('kelas_ujian.penguji_id', $pegawai->nomor_induk)
                        ->groupEnd();
                } else {
                    $builder->where('kelas_ujian.id', 0);
                }
            }

            // ==========================================
            // TAMBAHKAN PENGURUTAN JADWAL TERBARU DI SINI
            // ==========================================
            // Mengurutkan berdasarkan tanggal terbaru, lalu jam mulai terbaru
            $builder->orderBy('kelas_ujian.tanggal', 'DESC');
            $builder->orderBy('kelas_ujian.jam_mulai', 'DESC');
            // Atau jika ingin berdasarkan data yang paling baru diinput ke database:
            // $builder->orderBy('kelas_ujian.id', 'DESC');

            $data = $builder->get()->getResultArray();

            return $this->response->setJSON([
                'status' => true,
                'data'   => $data
            ]);
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => false,
                'message' => $th->getMessage()
            ]);
        }
    }


    public function pletonGetData()
    {
        $db = \Config\Database::connect();

        // Gunakan groupBy agar nama pleton hanya muncul sekali meski siswanya banyak
        $data = $db->table('siswa')
            ->select('pleton.id as id, pleton.nama_pleton as nama_pleton')
            ->join('pleton', 'pleton.id = siswa.pleton_id', 'inner')
            ->groupBy('pleton.id')
            ->where('siswa.pleton_id IS NOT NULL')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status' => true,
            'data' => $data
        ]);
    }

    public function siswaGetData()
    {
        $db = \Config\Database::connect();
        $data = $db->table('siswa')
            ->select('id, nama_siswa, nosis')
            ->orderBy('nosis', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON($data);
    }


    public function kelasUjianStore()
    {
        $db = \Config\Database::connect();

        $id             = $this->request->getPost('kelas_id') ?: $this->request->getPost('id');
        $namaKelas      = trim($this->request->getPost('nama_kelas'));
        $mataPelajaranId = $this->request->getPost('mata_pelajaran_id');
        $pengujiId      = $this->request->getPost('penguji_id');
        $tanggal        = $this->request->getPost('tanggal');
        $jamMulai       = $this->request->getPost('jam_mulai');
        $jamSelesai     = $this->request->getPost('jam_selesai');
        $statusUjian    = $this->request->getPost('status_ujian') ?? 'draf';

        // Tangkap input metode, pleton_ids, dan siswa_ids
        $metodePilih    = $this->request->getPost('metode_pilih');
        $pletonIds      = $this->request->getPost('pleton_ids');
        $siswaIds       = $this->request->getPost('siswa_ids');

        // Buat deskripsi berdasarkan metode yang dipilih
        if ($metodePilih === 'pleton' && !empty($pletonIds) && is_array($pletonIds)) {
            $deskripsiPleton = 'Pleton ID: ' . implode(',', $pletonIds);
        } elseif ($metodePilih === 'satuan' && !empty($siswaIds) && is_array($siswaIds)) {
            $deskripsiPleton = 'Satuan / Siswa Tertentu';
        } else {
            $deskripsiPleton = 'Semua Siswa';
        }

        // 1. Cek Bentrok Jadwal Penguji
        if (!empty($pengujiId)) {
            $builder = $db->table('kelas_ujian');
            $builder->where('penguji_id', $pengujiId);
            $builder->where('tanggal', $tanggal);
            if (!empty($id)) {
                $builder->where('id !=', $id);
            }
            $builder->groupStart();
            $builder->where("('$jamMulai' < jam_selesai AND '$jamSelesai' > jam_mulai)");
            $builder->groupEnd();
            $cekBentrok = $builder->get()->getRow();

            if ($cekBentrok) {
                return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Gagal! Penguji/Gadik tersebut sudah memiliki jadwal ujian lain pada rentang jam yang sama.',
                    'csrf_token' => csrf_hash()
                ]);
            }
        }

        $data = [
            'nama_kelas'        => $namaKelas,
            'mata_pelajaran_id' => !empty($mataPelajaranId) ? $mataPelajaranId : null,
            'penguji_id'        => !empty($pengujiId) ? $pengujiId : null,
            'tanggal'           => $tanggal,
            'jam_mulai'         => $jamMulai,
            'jam_selesai'       => $jamSelesai,
            'status_ujian'      => $statusUjian,
            'deskripsi'         => $deskripsiPleton,
        ];

        $db->transStart();

        try {
            if (!empty($id)) {
                $db->table('kelas_ujian')->where('id', $id)->update($data);
                $kelasUjianId = $id;

                // Hapus peserta lama sebelum di-insert ulang
                $db->table('kelas_ujian_peserta')->where('kelas_ujian_id', $kelasUjianId)->delete();
                $message = 'Kelas ujian berhasil diperbarui.';
            } else {
                $db->table('kelas_ujian')->insert($data);
                $kelasUjianId = $db->insertID();
                $message = 'Kelas ujian berhasil ditambahkan.';
            }

            // 2. Ambil daftar siswa berdasarkan metode pemilihan yang dikirim
            $daftarSiswa = [];

            if ($metodePilih === 'pleton' && !empty($pletonIds) && is_array($pletonIds)) {
                // Berdasarkan Pleton
                $daftarSiswa = $db->table('siswa')
                    ->select('id')
                    ->whereIn('pleton_id', $pletonIds)
                    ->get()
                    ->getResultArray();
            } elseif ($metodePilih === 'satuan' && !empty($siswaIds) && is_array($siswaIds)) {
                // Berdasarkan Satuan / Siswa Tertentu
                $daftarSiswa = $db->table('siswa')
                    ->select('id')
                    ->whereIn('id', $siswaIds)
                    ->get()
                    ->getResultArray();
            } else {
                // Default: Semua Siswa
                $daftarSiswa = $db->table('siswa')
                    ->select('id')
                    ->get()
                    ->getResultArray();
            }

            // 3. Masukkan ke tabel pivot kelas_ujian_peserta
            if (!empty($daftarSiswa)) {
                $batchData = [];
                foreach ($daftarSiswa as $siswa) {
                    $batchData[] = [
                        'kelas_ujian_id' => $kelasUjianId,
                        'siswa_id'       => $siswa['id']
                    ];
                }
                $db->table('kelas_ujian_peserta')->insertBatch($batchData);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Gagal melakukan transaksi database.');
            }

            return $this->response->setJSON([
                'status'     => true,
                'message'    => $message,
                'csrf_token' => csrf_hash()
            ]);
        } catch (\Throwable $th) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'status'     => false,
                'message'    => 'Gagal menyimpan: ' . $th->getMessage(),
                'csrf_token' => csrf_hash()
            ]);
        }
    }

    public function kelasUjianGet($id)
    {
        try {
            $db = \Config\Database::connect();
            $data = $db->table('kelas_ujian')->where('id', $id)->get()->getRow();

            if ($data) {
                return $this->response->setJSON([
                    'status' => true,
                    'data'   => $data
                ]);
            } else {
                return $this->response->setStatusCode(404)->setJSON([
                    'status'  => false,
                    'message' => 'Data tidak ditemukan'
                ]);
            }
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => false,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function kelasUjianDelete($id)
    {
        try {
            $db = \Config\Database::connect();
            $db->table('kelas_ujian')->where('id', $id)->delete();

            return $this->response->setJSON([
                'status'     => true,
                'message'    => 'Kelas ujian berhasil dihapus!',
                'csrf_token' => csrf_hash()
            ]);
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'     => false,
                'message'    => 'Gagal menghapus: ' . $th->getMessage(),
                'csrf_token' => csrf_hash()
            ]);
        }
    }

    public function mataPelajaranGetData()
    {
        try {
            $db = \Config\Database::connect();
            // Sesuaikan nama tabel mata pelajaran di database Anda (misal: 'mata_pelajaran' atau 'mapel')
            $data = $db->table('mata_pelajaran')->get()->getResultArray();

            return $this->response->setJSON([
                'status' => true,
                'data'   => $data
            ]);
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => false,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function pegawaiGetData()
    {
        // Sesuaikan dengan model Pegawai / Gadik yang Anda gunakan
        $pegawaiModel = new \App\Models\PegawaiModel();

        // Ambil data pegawai/gadik dari database
        $data = $pegawaiModel->findAll();

        return $this->response->setJSON([
            'status' => true,
            'data' => $data
        ]);
    }

    public function bankSoalIndex()
    {
        $rolePrefix = $this->getRolePrefix();

        // Ambil data detail ujian/kelas aktif yang sedang dikelola dari session
        $detailUjian = session()->get('active_kelas_ujian');

        // Jika session active_kelas_ujian belum ada, kita bisa fallback ambil dari session user yang login
        if (!$detailUjian) {
            $db = \Config\Database::connect();
            $userId = session()->get('user_id') ?? session()->get('pegawai_id');

            // Ambil data pegawai/gadik yang sedang login
            $pegawai = $db->table('pegawai')
                ->select('pegawai.nama as nama_gadik, pangkat.nama_pangkat as pangkat_gadik')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('pegawai.id', $userId)
                ->orWhere('pegawai.user_id', $userId)
                ->get()
                ->getRowArray();

            $detailUjian = [
                'pangkat_gadik' => $pegawai['pangkat_gadik'] ?? '',
                'nama_gadik'    => $pegawai['nama_gadik'] ?? session()->get('nama') ?? session()->get('username') ?? '-'
            ];
        }

        $data = [
            'title'        => 'Bank Soal OBE (C1 - C6)',
            'page_title'   => 'Manajemen Bank Soal',
            'role_prefix'  => $rolePrefix,
            'detail_ujian' => $detailUjian
        ];

        return view('obe/bank_soal', $data);
    }


    public function bankSoalStore()
    {


        $rules = [
            'level_soal' => 'required|in_list[C1,C2,C3,C4,C5,C6]',
            'pertanyaan' => 'required',
            'jawaban'    => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status'     => false,
                'message'    => 'Level soal, pertanyaan, dan kunci jawaban wajib diisi dengan benar!',
                'errors'     => $this->validator->getErrors(),
                'csrf_token' => csrf_hash()
            ]);
        }

        try {
            $id    = $this->request->getPost('id_soal');
            $level = strtoupper(trim($this->request->getPost('level_soal')));

            // Ambil data session active_kelas_ujian
            $activeUjian = session()->get('active_kelas_ujian');

            // AMBIL ID KELAS UJIAN DARI POST (AJAX) ATAU DARI SESSION
            $kelasUjianId = $this->request->getPost('kelas_ujian_id')
                ?? $this->request->getPost('id_ujian')
                ?? ($activeUjian['id'] ?? $activeUujian['kelas_ujian_id'] ?? null);

            // Ambil mapel_id dari kiriman AJAX atau dari session
            $mapelId = $this->request->getPost('mapel_id') ?: ($activeUjian['mata_pelajaran_id'] ?? null);

            // Ambil ID user/pegawai yang sedang login
            $userId = session()->get('id') ?? session()->get('pegawai_id') ?? null;

            $data = [
                'kelas_ujian_id'    => $this->request->getPost('kelas_ujian_id'),
                'tingkat_taksonomi' => $level,
                'pertanyaan'        => $this->request->getPost('pertanyaan'),
                'rubrik_penilaian'  => $this->request->getPost('jawaban'),
                'mapel_id'          => $mapelId,
                'cpmk'              => $this->request->getPost('cpmk') ?: 'CPMK-' . $level,
                'cpl'               => $this->request->getPost('cpl') ?: 'CPL-DEFAULT',
                'bobot_soal'        => $this->request->getPost('bobot_soal') !== '' ? $this->request->getPost('bobot_soal') : 0.00,
                'created_by'        => $userId
            ];

            if (!empty($id)) {
                $this->bankSoalModel->update($id, $data);
                $message = 'Soal tingkat ' . $level . ' berhasil diperbarui.';
            } else {
                $this->bankSoalModel->insert($data);
                $message = 'Soal tingkat ' . $level . ' berhasil ditambahkan.';
            }

            return $this->response->setJSON([
                'status'     => true,
                'message'    => $message,
                'csrf_token' => csrf_hash()
            ]);
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'     => false,
                'message'    => 'Gagal menyimpan: ' . $th->getMessage(),
                'csrf_token' => csrf_hash()
            ]);
        }
    }

    /**
     * Set active_kelas_ujian ke dalam session saat Gadik/Admin memilih kelas ujian tertentu
     */
    public function setActiveKelasUjian()
    {
        $json = $this->request->getJSON(true);
        $kelasId = $json['kelas_ujian_id'] ?? $this->request->getPost('kelas_ujian_id');

        if (empty($kelasId)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => false,
                'message' => 'ID Kelas Ujian tidak boleh kosong.'
            ]);
        }

        $db = \Config\Database::connect();

        // Ambil detail kelas ujian beserta nama mapel dan informasi penguji
        $kelasUjian = $db->table('kelas_ujian')
            ->select('kelas_ujian.*, mata_pelajaran.nama_mapel, pegawai.nama as nama_gadik, pangkat.nama_pangkat as pangkat_gadik')
            ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
            ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
            ->where('kelas_ujian.id', $kelasId)
            ->get()
            ->getRowArray();

        if (!$kelasUjian) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => false,
                'message' => 'Data kelas ujian tidak ditemukan.'
            ]);
        }

        // Simpan ke session
        session()->set('active_kelas_ujian', $kelasUjian);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Kelas ujian aktif berhasil diset.',
            'data' => $kelasUjian,
            'csrf_token' => csrf_hash()
        ]);
    }

    public function pesertaPleton($id = null)
    {
        try {
            $model = new \App\Models\KelasUjianPletonModel();
            $data = $model->where('kelas_ujian_id', $id)->findAll();

            return $this->response->setJSON([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function bankSoalGetByLevel($level)
    {
        $db = \Config\Database::connect();

        $activeUjian = session()->get('active_kelas_ujian');
        $kelasUjianId = $activeUjian['id'] ?? null;

        if (empty($kelasUjianId)) {
            return $this->response->setJSON([
                'status'     => false,
                'message'    => 'ID Kelas Ujian / Sesi tidak ditemukan.',
                'data'       => [],
                'csrf_token' => csrf_hash()
            ]);
        }

        $soal = $db->table('soal_obe')
            ->where('tingkat_taksonomi', strtoupper($level))
            ->where('kelas_ujian_id', $kelasUjianId) // Filter berdasarkan relasi baru
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'     => true,
            'data'       => $soal,
            'csrf_token' => csrf_hash()
        ]);
    }

    public function bankSoalGetDetail($id)
    {
        try {
            $soal = $this->bankSoalModel->find($id);

            if (!$soal) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status'  => false,
                    'message' => 'Data soal tidak ditemukan.'
                ]);
            }

            return $this->response->setJSON([
                'status' => true,
                'data'   => $soal
            ]);
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => false,
                'message' => 'Gagal mengambil data: ' . $th->getMessage()
            ]);
        }
    }

    public function bankSoalDelete($id)
    {
        try {
            $soal = $this->bankSoalModel->find($id);
            if ($soal) {
                $this->bankSoalModel->delete($id);
                return $this->response->setJSON([
                    'status'     => true,
                    'message'    => 'Soal berhasil dihapus.',
                    'csrf_token' => csrf_hash() // <--- TAMBAHKAN INI
                ]);
            }

            return $this->response->setJSON([
                'status'     => false,
                'message'    => 'Data soal tidak ditemukan.',
                'csrf_token' => csrf_hash() // <--- TAMBAHKAN INI JUGA (JAGA-JAGA)
            ]);
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'     => false,
                'message'    => 'Gagal menghapus: ' . $th->getMessage(),
                'csrf_token' => csrf_hash()
            ]);
        }
    }

    // ========================================================================
    // FITUR UJIAN LAINNYA
    // ========================================================================

    public function bobotNilaiIndex()
    {
        $rolePrefix = $this->getRolePrefix();
        return view('obe/bobot_nilai', [
            'title'       => 'Konfigurasi Bobot Nilai OBE',
            'page_title'  => 'Bobot Nilai Tingkat Kognitif (C1-C6)',
            'role_prefix' => $rolePrefix
        ]);
    }

    /**
     * Menangani proses simpan pengaturan bobot via AJAX
     */
    public function bobotNilaiStore()
    {
        // Tangkap data yang dikirim dari AJAX JS
        $postData = $this->request->getPost();

        // TODO: Silakan tambahkan logika penyimpanan ke Database di sini jika sudah ada tabelnya.
        // Contoh: $this->bobotModel->save($postData);

        // Kirim respons JSON ke AJAX beserta token CSRF baru
        return $this->response->setJSON([
            'status'     => true,
            'message'    => 'Pengaturan bobot dimensi berhasil disimpan!',
            'csrf_token' => csrf_hash()
        ]);
    }

    public function jadwalUjianIndex()
    {
        $rolePrefix = $this->getRolePrefix();

        // Ambil data mata pelajaran dari database
        $db = \Config\Database::connect();
        $mataPelajaran = $db->table('mata_pelajaran')->get()->getResultArray();

        return view('obe/jadwal_ujian', [
            'title'          => 'Jadwal & Waktu Ujian OBE',
            'page_title'     => 'Set Jadwal & Deadline Ujian',
            'role_prefix'    => $rolePrefix,
            'mata_pelajaran' => $mataPelajaran // Masukkan ke sini
        ]);
    }

    public function siswaDaftarUjian()
    {
        $db = \Config\Database::connect();

        $userId = session()->get('user_id');
        $siswa = $db->table('siswa')->where('user_id', $userId)->get()->getRow();
        $siswaId = $siswa ? $siswa->id : null;

        $builder = $db->table('kelas_ujian');

        // Ambil data termasuk nama pangkat dari tabel pangkat
        $builder->select('kelas_ujian.*, mata_pelajaran.nama_mapel, pegawai.nama as nama_pegawai, pangkat.nama_pangkat');
        $builder->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left');
        $builder->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left');
        $builder->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left');
        $builder->join('kelas_ujian_peserta', 'kelas_ujian_peserta.kelas_ujian_id = kelas_ujian.id', 'inner');

        $builder->where('kelas_ujian.status_ujian', 'publis');
        $builder->where('kelas_ujian_peserta.siswa_id', $siswaId);

        // EKSEKUSI QUERY KE DATABASE (INI YANG KEMARIN TERLEWAT)
        $dataUjian = $builder->get()->getResultArray();

        $now = date('Y-m-d H:i:s');

        return view('siswa/obe/daftar_ujian', [
            'title'      => 'Daftar Ujian OBE Aktif',
            'page_title' => 'Ikuti Ujian',
            'listUjian'  => $dataUjian,
            'now'        => $now
        ]);
    }

    public function kerjakanUjian($kelasUjianId)
    {
        $db = \Config\Database::connect();

        // 1. Validasi apakah siswa yang login benar-benar peserta ujian ini
        $userId = session()->get('user_id');
        $siswa = $db->table('siswa')->where('user_id', $userId)->get()->getRow();
        $siswaId = $siswa ? $siswa->id : null;

        $peserta = $db->table('kelas_ujian_peserta')
            ->where('kelas_ujian_id', $kelasUjianId)
            ->where('siswa_id', $siswaId)
            ->get()
            ->getRow();

        if (!$peserta) {
            return redirect()->to('/siswa/ujian/daftar')->with('error', 'Anda tidak memiliki akses ke ujian ini.');
        }

        // 2. Ambil detail informasi ujian, mapel, dan penguji
        $ujian = $db->table('kelas_ujian')
            ->select('kelas_ujian.*, mata_pelajaran.nama_mapel, pegawai.nama as nama_pegawai, pangkat.nama_pangkat')
            ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
            ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
            ->where('kelas_ujian.id', $kelasUjianId)
            ->where('kelas_ujian.status_ujian', 'publis')
            ->get()
            ->getRowArray();

        if (!$ujian) {
            return redirect()->to('/siswa/ujian/daftar')->with('error', 'Data ujian tidak ditemukan atau belum dipublikasikan.');
        }

        // 3. AMBIL SEMUA SOAL BERDASARKAN MATA PELAJARAN UJIAN
        $soal = [];
        if (!empty($ujian['mata_pelajaran_id'])) {
            $soal = $db->table('soal_obe')
                ->where('kelas_ujian_id', $kelasUjianId)
                ->where('mapel_id', $ujian['mata_pelajaran_id'])
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }

        // 4. BUAT VARIABEL WAKTU SELESAI UNTUK TIMER MELAYANG
        // Sesuaikan nama kolom jika di database Anda berbeda (misal: tanggal & jam_selesai)
        $waktuSelesaiUjian = ($ujian['tanggal'] ?? date('Y-m-d')) . ' ' . ($ujian['jam_selesai'] ?? '23:59:59');

        return view('siswa/obe/kerjakan_ujian', [
            'title'             => 'Mengerjakan Ujian',
            'page_title'        => 'Ujian: ' . ($ujian['nama_mapel'] ?? ''),
            'ujian'             => $ujian,
            'listSoal'          => $soal,
            'waktuSelesaiUjian' => $waktuSelesaiUjian // <--- TAMBAHKAN INI AGAR TIDAK ERROR
        ]);
    }

    public function selesaiUjian($kelasUjianId)
    {
        $jawabanSiswa = $this->request->getPost('jawaban');

        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $siswa = $db->table('siswa')->where('user_id', $userId)->get()->getRow();
        $siswaId = $siswa ? $siswa->id : null;

        if (!$siswaId) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Sesi siswa tidak valid.'
            ]);
        }

        $db->transStart();

        try {
            if (!empty($jawabanSiswa) && is_array($jawabanSiswa)) {
                foreach ($jawabanSiswa as $soalId => $teksJawaban) {
                    // Cek berdasarkan soal_id, siswa_id, dan kelas_ujian_id
                    $existing = $db->table('jawaban_siswa')
                        ->where('soal_id', $soalId)
                        ->where('siswa_id', $siswaId)
                        ->where('kelas_ujian_id', $kelasUjianId)
                        ->get()
                        ->getRow();

                    if ($existing) {
                        // Update jawaban jika sudah ada
                        $db->table('jawaban_siswa')
                            ->where('id', $existing->id)
                            ->update([
                                'jawaban_teks' => $teksJawaban,
                                'updated_at'   => date('Y-m-d H:i:s')
                            ]);
                    } else {
                        // Insert baru dengan menyertakan kelas_ujian_id
                        $db->table('jawaban_siswa')->insert([
                            'kelas_ujian_id' => $kelasUjianId,
                            'soal_id'        => $soalId,
                            'siswa_id'       => $siswaId,
                            'jawaban_teks'   => $teksJawaban,
                            'created_at'     => date('Y-m-d H:i:s'),
                            'updated_at'     => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }

            // Update status peserta ujian jadi selesai
            $db->table('kelas_ujian_peserta')
                ->where('kelas_ujian_id', $kelasUjianId)
                ->where('siswa_id', $siswaId)
                ->update([
                    'status' => 'selesai',
                    'status_pengerjaan' => 'selesai'
                ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'status' => false,
                    'message' => 'Gagal menyimpan jawaban.'
                ]);
            }

            return $this->response->setJSON([
                'status' => true,
                'message' => 'Ujian berhasil diselesaikan!',
                'redirect' => base_url('siswa/ujian/riwayat')
            ]);
        } catch (\Throwable $th) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'status' => false,
                'message' => 'Error DB: ' . $th->getMessage()
            ]);
        }
    }

    public function siswaRiwayatUjian()
    {
        $db = \Config\Database::connect();

        // Ambil data siswa yang sedang login berdasarkan user_id session
        $userId = session()->get('user_id');
        $siswa = $db->table('siswa')->where('user_id', $userId)->get()->getRow();

        // Ambil data riwayat ujian siswa dari database (sesuaikan nama tabel Anda)
        $riwayatUjian = [];
        if ($siswa) {
            $riwayatUjian = $db->table('nilai_ujian') // atau tabel hasil ujian Anda
                ->select('nilai_ujian.*, kelas_ujian.nama_ujian, mata_pelajaran.nama_mapel')
                ->join('kelas_ujian', 'kelas_ujian.id = nilai_ujian.kelas_ujian_id')
                ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mapel_id', 'left')
                ->where('nilai_ujian.siswa_id', $siswa->id)
                ->get()
                ->getResultArray();
        }

        $data = [
            'title' => 'Riwayat Ujian',
            'page_title' => 'Riwayat Ujian Saya',
            'riwayat' => $riwayatUjian
        ];

        // Tampilkan ke view riwayat ujian siswa
        return view('siswa/riwayat_ujian', $data);
    }

    public function riwayatUjian()
    {
        $db = \Config\Database::connect();
        $session = session();
        $roleId = (int) $session->get('role_id');
        $userId = $session->get('user_id');

        // Jika yang login SISWA (role_id = 7)
        if ($roleId === 7) {
            $siswa = $db->table('siswa')->where('user_id', $userId)->get()->getRow();

            if (!$siswa) {
                return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
            }

            // Query khusus Siswa (termasuk status pengerjaan personal dan pangkat penguji)
            $riwayatUjian = $db->table('kelas_ujian_peserta cup')
                ->select('cup.*, cup.status as status, kelas_ujian.nama_kelas, kelas_ujian.tanggal, kelas_ujian.jam_mulai, kelas_ujian.jam_selesai, mata_pelajaran.nama_mapel as nama_mapel, pegawai.nama as nama_pegawai, pangkat.nama_pangkat as pangkat')
                ->join('kelas_ujian', 'kelas_ujian.id = cup.kelas_ujian_id', 'left')
                ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
                ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->where('cup.siswa_id', $siswa->id)
                ->orderBy('kelas_ujian.tanggal', 'DESC')
                ->get()
                ->getResultArray();

            $viewPath = 'siswa/obe/riwayat_ujian';
        } else {
            // Query khusus Admin / Gadik (menampilkan daftar kelas ujian secara umum tanpa tabel peserta)
            $riwayatUjian = $db->table('kelas_ujian')
                ->select('kelas_ujian.*, mata_pelajaran.nama_mapel as nama_mapel, pegawai.nama as nama_pegawai, pangkat.nama_pangkat as pangkat')
                ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
                ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
                ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
                ->orderBy('kelas_ujian.tanggal', 'DESC')
                ->get()
                ->getResultArray();

            $viewPath = 'obe/riwayat_ujian';
        }

        $data = [
            'title'      => 'Riwayat Ujian OBE',
            'page_title' => 'Riwayat Ujian',
            'riwayat'    => $riwayatUjian
        ];

        return view($viewPath, $data);
    }

    public function detail($id = null)
    {
        $db = \Config\Database::connect();

        // 1. Ambil data kelas ujian beserta mata pelajaran, penguji, dan pangkatnya
        $kelasUjian = $db->table('kelas_ujian')
            ->select('kelas_ujian.*, mata_pelajaran.nama_mapel as nama_mapel, pegawai.nama as nama_pegawai, pangkat.nama_pangkat as pangkat')
            ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
            ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
            ->where('kelas_ujian.id', $id)
            ->get()
            ->getRowArray();

        if (!$kelasUjian) {
            return redirect()->back()->with('error', 'Data ujian tidak ditemukan.');
        }

        // 2. Ambil SEMUA data peserta untuk ujian ini
        $peserta = $db->table('kelas_ujian_peserta cup')
            ->select('cup.*, siswa.nama as nama_siswa, siswa.nosis as nosis')
            ->join('siswa', 'siswa.id = cup.siswa_id', 'left')
            ->where('cup.kelas_ujian_id', $id)
            ->get()
            ->getResultArray();

        $data = [
            'title'      => 'Detail Ujian OBE',
            'page_title' => 'Detail Ujian: ' . ($kelasUjian['nama_kelas'] ?? 'Ujian'),
            'ujian'      => $kelasUjian,
            'peserta'    => $peserta
        ];

        return view('obe/detail_ujian', $data);
    }

    public function jadwalUjianDelete($id)
    {
        $model = model('JadwalUjianModel');
        $model->delete($id);

        return $this->response->setJSON([
            'status'     => true,
            'message'    => 'Data jadwal berhasil dihapus!',
            'csrf_token' => csrf_hash()
        ]);
    }

    // ========================================================================
    // PENILAIAN HASIL UJIAN (GADIK & ADMIN)
    // ========================================================================
    public function indexPenilaian()
    {
        $db = \Config\Database::connect();
        $rolePrefix = $this->getRolePrefix();

        // Ambil nama user/gadik yang sedang login dari session (misal: 'nama' atau 'username')
        $namaGadikLogin = session()->get('nama') ?? 'VERAWATY THAIB, S.I.K., M.Si.';

        $builder = $db->table('kelas_ujian')
            ->select('kelas_ujian.*, mata_pelajaran.nama_mapel, pegawai.nama as nama_gadik, pangkat.nama_pangkat as pangkat')
            ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
            ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left'); // Tambahkan join tabel pangkat

        // Jika role gadik, kita filter langsung berdasarkan nama pegawai yang login agar aman dari perbedaan ID session
        if ($rolePrefix === 'gadik') {
            $builder->where('pegawai.nama', $namaGadikLogin);
        }

        // TAMBAHKAN URUTAN TERBARU DI SINI (DESC = dari yang terbaru / besar ke kecil)
        $builder->orderBy('tanggal', 'DESC')
            ->orderBy('jam_mulai', 'DESC');

        $dataKelasRaw = $builder->get()->getResultArray();

        // Gabungkan pangkat dan nama gadik agar siap ditampilkan di view
        $dataKelas = [];
        foreach ($dataKelasRaw as $row) {
            $pangkat = $row['pangkat'] ?? '';
            $nama = $row['nama_gadik'] ?? '-';

            // Format: [Pangkat] [Nama] (Contoh: AKBP VERAWATY THAIB, S.I.K., M.Si.)
            $row['nama_gadik'] = trim($pangkat . ' ' . $nama);
            $dataKelas[] = $row;
        }

        return view('obe/index_penilaian', [
            'title'       => 'Daftar Penilaian Ujian OBE',
            'page_title'  => 'Pilih Kelas Ujian untuk Dinilai',
            'role_prefix' => $rolePrefix,
            'kelas_ujian' => $dataKelas
        ]);
    }

    public function daftarPenilaianIndex($kelasUjianId)
    {
        $db = \Config\Database::connect();
        $rolePrefix = $this->getRolePrefix();

        // Ambil parameter sort dari URL (default 'nosis' jika kosong)
        $sort = $this->request->getGet('sort');

        // Tentukan aturan pengurutan berdasarkan pilihan filter
        if ($sort == 'nilai') {
            $orderByColumn = 'nilai_ujian.nilai_akhir';
            $orderByDirection = 'DESC'; // Peringkat: Tertinggi ke terendah
        } else {
            $orderByColumn = 'siswa.nosis';
            $orderByDirection = 'ASC';  // Default: Nosis terendah ke tertinggi
        }

        // Ambil detail kelas ujian
        $ujian = $db->table('kelas_ujian')
            ->select('kelas_ujian.*, mata_pelajaran.nama_mapel, pegawai.nama as nama_gadik')
            ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
            ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
            ->where('kelas_ujian.id', $kelasUjianId)
            ->get()
            ->getRowArray();

        // Ambil daftar siswa/peserta di kelas ini dengan urutan dinamis
        $peserta = $db->table('kelas_ujian_peserta cup')
            ->select('cup.*, siswa.nama, siswa.nosis, nilai_ujian.nilai_akhir')
            ->join('siswa', 'siswa.id = cup.siswa_id', 'left')
            ->join('nilai_ujian', 'nilai_ujian.kelas_ujian_id = cup.kelas_ujian_id AND nilai_ujian.siswa_id = cup.siswa_id', 'left')
            ->where('cup.kelas_ujian_id', $kelasUjianId)
            ->orderBy($orderByColumn, $orderByDirection)
            ->get()
            ->getResultArray();

        return view('obe/penilaian_ujian', [
            'title'       => 'Penilaian Hasil Ujian OBE',
            'page_title'  => 'Kelola & Lihat Nilai Ujian',
            'role_prefix' => $rolePrefix,
            'ujian'       => $ujian,
            'peserta'     => $peserta
        ]);
    }

    /**
     * API / Proses Simpan Nilai oleh Gadik
     */
    public function simpanPenilaianUjian()
    {
        $rolePrefix = $this->getRolePrefix();

        // Validasi: Hanya Gadik yang boleh melakukan penilaian (atau sesuaikan jika admin juga boleh)
        if ($rolePrefix !== 'gadik' && $rolePrefix !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melakukan penilaian.',
                'csrf_token' => csrf_hash()
            ]);
        }

        $db = \Config\Database::connect();
        $kelasUjianId = $this->request->getPost('kelas_ujian_id');
        $siswaId      = $this->request->getPost('siswa_id');
        $nilaiAkhir   = $this->request->getPost('nilai_akhir');
        $statusPenilaian = $this->request->getPost('status_penilaian');
        $statusPengerjaan = $this->request->getPost('status_pengerjaan');

        if (empty($kelasUjianId) || empty($siswaId)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => false,
                'message' => 'Parameter kelas ujian atau siswa tidak valid.',
                'csrf_token' => csrf_hash()
            ]);
        }

        try {
            // Cek apakah data nilai sudah pernah ada
            $existing = $db->table('nilai_ujian')
                ->where('kelas_ujian_id', $kelasUjianId)
                ->where('siswa_id', $siswaId)
                ->get()
                ->getRow();

            $dataSimpan = [
                'kelas_ujian_id'    => $kelasUjianId,
                'siswa_id'          => $siswaId,
                'nilai_akhir'       => $nilaiAkhir,
                'status_pengerjaan' => $statusPengerjaan ?? 'selesai',
                'status_penilaian'  => $statusPenilaian ?? 'sudah',
                'updated_at'        => date('Y-m-d H:i:s')
            ];

            if ($existing) {
                // Update nilai
                $db->table('nilai_ujian')
                    ->where('kelas_ujian_id', $kelasUjianId)
                    ->where('siswa_id', $siswaId)
                    ->update($dataSimpan);
            } else {
                // Insert nilai baru
                $dataSimpan['created_at'] = date('Y-m-d H:i:s');
                $db->table('nilai_ujian')->insert($dataSimpan);
            }

            return $this->response->setJSON([
                'status'     => true,
                'message'    => 'Nilai hasil ujian berhasil disimpan.',
                'csrf_token' => csrf_hash()
            ]);
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'     => false,
                'message'    => 'Gagal menyimpan nilai: ' . $th->getMessage(),
                'csrf_token' => csrf_hash()
            ]);
        }
    }

    /**
     * Mengambil detail jawaban/status ujian siswa tertentu untuk keperluan penilaian oleh Gadik/Penguji
     */
    public function detailJawabanSiswa($kelasUjianId, $siswaId)
    {
        $db = \Config\Database::connect();

        // 1. Ambil detail peserta & ujian lengkap beserta data nilai_ujian
        $detail = $db->table('kelas_ujian_peserta cup')
            ->select('cup.*, siswa.nama AS nama_siswa, siswa.nosis, pleton.nama_pleton, kelas_ujian.nama_kelas, kelas_ujian.mata_pelajaran_id, kelas_ujian.tanggal, kelas_ujian.jam_mulai, kelas_ujian.jam_selesai, mata_pelajaran.nama_mapel, pegawai.nama AS nama_gadik, nilai_ujian.nilai_akhir, nilai_ujian.status_penilaian, nilai_ujian.status_pengerjaan')
            ->join('siswa', 'siswa.id = cup.siswa_id', 'left')
            ->join('pleton', 'pleton.id = siswa.pleton_id', 'left')
            ->join('kelas_ujian', 'kelas_ujian.id = cup.kelas_ujian_id', 'left')
            ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
            ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
            ->join('nilai_ujian', 'nilai_ujian.kelas_ujian_id = cup.kelas_ujian_id AND nilai_ujian.siswa_id = cup.siswa_id', 'left')
            ->where('cup.kelas_ujian_id', $kelasUjianId)
            ->where('cup.siswa_id', $siswaId)
            ->get()
            ->getRowArray();

        if (!$detail) {
            return redirect()->back()->with('error', 'Data peserta tidak ditemukan.');
        }

        // 2. Ambil data Soal OBE
        $soal = [];
        if (!empty($detail['mata_pelajaran_id'])) {
            $soal = $db->table('soal_obe')
                ->where('mapel_id', $detail['mata_pelajaran_id'])
                ->where('kelas_ujian_id', $kelasUjianId)
                ->get()
                ->getResultArray();
        }

        // 3. Ambil jawaban siswa dari tabel jawaban_siswa
        $jawaban_peserta = $db->table('jawaban_siswa')
            ->where('kelas_ujian_id', $kelasUjianId)
            ->where('siswa_id', $siswaId)
            ->get()
            ->getResultArray();

        $data = [
            'title'           => 'Penilaian Jawaban Siswa',
            'page_title'      => 'Detail Jawaban & Penilaian',
            'kelas_ujian_id'  => $kelasUjianId,
            'siswa_id'        => $siswaId,
            'detail'          => $detail,
            'soal'            => $soal,
            'jawaban_peserta' => $jawaban_peserta
        ];

        // Sesuaikan dengan nama view form penilaian Anda (misal: 'obe/detail_jawaban_siswa' atau 'obe/form_penilaian')
        return view('obe/detail_jawaban_siswa', $data);
    }
    /**
     * Menampilkan halaman view baru untuk penilaian siswa beserta data lengkapnya
     */
    public function halamanPenilaian($kelasUjianId, $siswaId)
    {
        $db = \Config\Database::connect();

        // 1. Ambil detail peserta & ujian
        $detail = $db->table('kelas_ujian_peserta')
            ->select('kelas_ujian_peserta.*, siswa.nama AS nama_siswa, siswa.nosis, pleton.nama_pleton, kelas_ujian.nama_kelas, kelas_ujian.mata_pelajaran_id, kelas_ujian.tanggal, kelas_ujian.jam_mulai, kelas_ujian.jam_selesai, mata_pelajaran.nama_mapel, pegawai.nama AS nama_gadik')
            ->join('siswa', 'siswa.id = kelas_ujian_peserta.siswa_id', 'left')
            ->join('pleton', 'pleton.id = siswa.pleton_id', 'left')
            ->join('kelas_ujian', 'kelas_ujian.id = kelas_ujian_peserta.kelas_ujian_id', 'left')
            ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
            ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
            ->where('kelas_ujian_peserta.kelas_ujian_id', $kelasUjianId)
            ->where('kelas_ujian_peserta.siswa_id', $siswaId)
            ->get()
            ->getRowArray();

        if (!$detail) {
            echo "Data tidak ditemukan.";
            exit;
        }

        // 2. Ambil data Soal
        $soal = [];
        if (!empty($detail['mata_pelajaran_id'])) {
            $soal = $db->table('soal_obe')
                ->where('mapel_id', $detail['mata_pelajaran_id'])
                ->get()
                ->getResultArray();
        }

        // 3. TAMBAHKAN INI: Ambil jawaban siswa dari tabel jawaban_siswa
        $jawaban_peserta = $db->table('jawaban_siswa')
            ->where('kelas_ujian_id', $kelasUjianId)
            ->where('siswa_id', $siswaId)
            ->get()
            ->getResultArray();

        $data = [
            'kelas_ujian_id'   => $kelasUjianId,
            'siswa_id'         => $siswaId,
            'detail'           => $detail,
            'soal'             => $soal,
            'jawaban_peserta'  => $jawaban_peserta // <-- MASUKKAN KE SINI AGAR DIKIRIM KE VIEW
        ];

        return view('obe/form_penilaian', $data);
    }

    /**
     * Menyimpan atau memperbarui nilai ujian siswa
     */
    public function simpanNilaiUjian()
    {
        try {
            $db = \Config\Database::connect();

            // Ambil data dari request (misalnya dari POST Form / AJAX)
            $kelasUjianId     = $this->request->getPost('kelas_ujian_id');
            $siswaId          = $this->request->getPost('siswa_id');
            $nilaiAkhir       = $this->request->getPost('nilai_akhir');
            $statusPengerjaan = $this->request->getPost('status_pengerjaan'); // contoh: 'selesai'
            $statusPenilaian  = $this->request->getPost('status_penilaian');  // contoh: 'sudah' atau 'belum'

            // Cek apakah data nilai untuk siswa pada kelas ujian tersebut sudah pernah ada
            $existing = $db->table('nilai_ujian')
                ->where('kelas_ujian_id', $kelasUjianId)
                ->where('siswa_id', $siswaId)
                ->get()
                ->getRowArray();

            $dataSimpan = [
                'kelas_ujian_id'    => $kelasUjianId,
                'siswa_id'          => $siswaId,
                'nilai_akhir'       => $nilaiAkhir,
                'status_pengerjaan' => $statusPengerjaan,
                'status_penilaian'  => $statusPenilaian,
                'updated_at'        => date('Y-m-d H:i:s')
            ];

            if ($existing) {
                // Jika sudah ada, lakukan UPDATE berdasarkan ID
                $db->table('nilai_ujian')
                    ->where('id', $existing['id'])
                    ->update($dataSimpan);
            } else {
                // Jika belum ada, lakukan INSERT baru
                $dataSimpan['created_at'] = date('Y-m-d H:i:s');
                $db->table('nilai_ujian')->insert($dataSimpan);
            }

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Nilai ujian berhasil disimpan.'
            ]);
        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => false,
                'message' => 'Gagal menyimpan nilai: ' . $th->getMessage()
            ]);
        }
    }

    // soal 
    public function soal($id = null)
    {
        if (!$id) {
            return redirect()->back()->with('error', 'ID Kelas Ujian tidak valid.');
        }

        $rolePrefix = $this->request->getUri()->getSegment(1) ?? 'admin';

        $db = \Config\Database::connect();

        // Menggunakan 'penguji_id' sesuai dengan struktur asli tabel kelas_ujian
        $detailUjian = $db->table('kelas_ujian')
            ->select('kelas_ujian.*, mata_pelajaran.nama_mapel as mata_pelajaran, pegawai.nama as nama_gadik, pangkat.nama_pangkat as pangkat_gadik')
            ->join('mata_pelajaran', 'mata_pelajaran.id = kelas_ujian.mata_pelajaran_id', 'left')
            ->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left')
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
            ->where('kelas_ujian.id', $id)
            ->get()
            ->getRowArray();

        if ($detailUjian) {
            // SIMPAN KE SESSION AGAR BISA DIBACA SAAT bankSoalStore() DIJALANKAN
            session()->set('active_kelas_ujian', $detailUjian);
        }

        $data = [
            'title' => 'Kelola Soal Ujian',
            'kelas_ujian_id' => $id,
            'role_prefix' => $rolePrefix,
            'detail_ujian' => $detailUjian
        ];

        return view('obe/soal_index', $data);
    }
    // ==================== 1. EXPORT EXCEL ====================
    public function exportExcel($kelasUjianId)
    {
        $db = \Config\Database::connect();
        $sort = $this->request->getGet('sort');
        $orderBy = ($sort == 'nilai') ? 'nu.nilai_akhir DESC' : 's.nosis ASC';

        $ujian = $db->table('kelas_ujian ku')
            ->select('ku.*, mp.nama_mapel, p.nama as nama_gadik, pg.nama_pangkat')
            ->join('mata_pelajaran mp', 'mp.id = ku.mata_pelajaran_id', 'left')
            ->join('pegawai p', 'p.id = ku.penguji_id', 'left')
            ->join('pangkat pg', 'pg.id = p.pangkat_id', 'left')
            ->where('ku.id', $kelasUjianId)
            ->get()->getRowArray();

        $angkatan = $db->table('kelas_ujian_peserta cup')
            ->select('a.nama_angkatan, a.tahun_angkatan')
            ->join('siswa s', 's.id = cup.siswa_id', 'left')
            ->join('angkatan a', 'a.id = s.angkatan_id', 'left')
            ->where('cup.kelas_ujian_id', $kelasUjianId)
            ->get()->getRowArray();

        $peserta = $db->table('kelas_ujian_peserta cup')
            ->select('cup.*, s.nama AS nama_siswa, s.nosis, nu.nilai_akhir')
            ->join('siswa s', 's.id = cup.siswa_id', 'left')
            ->join('nilai_ujian nu', 'nu.kelas_ujian_id = cup.kelas_ujian_id AND nu.siswa_id = cup.siswa_id', 'left')
            ->where('cup.kelas_ujian_id', $kelasUjianId)
            ->orderBy($orderBy)
            ->get()->getResultArray();

        // Hitung Jumlah Peserta Ujian
        $jumlahPeserta = count($peserta);

        // Format Bulan & Hari Bahasa Indonesia
        $namaBulan = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];
        $hariIndo = [
            'Sun' => 'Minggu',
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu'
        ];

        $hariUjian = '-';
        $tanggalUjian = '-';
        if (!empty($ujian['tanggal'])) {
            $tglKey = date('d', strtotime($ujian['tanggal']));
            $blnKey = date('m', strtotime($ujian['tanggal']));
            $thnKey = date('Y', strtotime($ujian['tanggal']));
            $hariKey = date('D', strtotime($ujian['tanggal']));

            $hariUjian = $hariIndo[$hariKey] ?? '-';
            $namaBlnFull = $namaBulan[$blnKey] ?? '';
            $tanggalUjian = "{$tglKey} {$namaBlnFull} {$thnKey}";
        }

        $jamMulai = !empty($ujian['jam_mulai']) ? date('H:i', strtotime($ujian['jam_mulai'])) : '-';
        $jamSelesai = !empty($ujian['jam_selesai']) ? date('H:i', strtotime($ujian['jam_selesai'])) : '-';
        $waktuUjian = "Pukul {$jamMulai} - {$jamSelesai} WIB";
        $gadikPenguji = (!empty($ujian['nama_pangkat']) ? $ujian['nama_pangkat'] . ' ' : '') . ($ujian['nama_gadik'] ?? '-');

        // Header File Excel
        $filename = "Daftar_Nilai_" . url_title($ujian['nama_kelas'] ?? 'Ujian', '_', true) . ".xls";
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Cache-Control: max-age=0");

        // Judul Laporan (2 Baris)
        echo "<h2 style='text-align:center;'>REKAPITULASI NILAI UJIAN OBE</h2>";
        echo "<p style='text-align:center;'><b>SISWA DIKTUK BINTARA POLWAN " . ($angkatan['nama_angkatan'] ?? '-') . " TAHUN ANGKATAN " . ($angkatan['tahun_angkatan'] ?? '-') . "/" . (($angkatan['tahun_angkatan'] ?? 0) + 1) . "</b></p>";

        // Informasi Detail (Tabel 2 Kolom Tanpa Border untuk Excel)
        echo "<table style='width:100%; border:none; margin-bottom: 15px;'>";
        echo "<tr>";

        // Kolom Kiri
        echo "<td style='vertical-align: top; border:none;'>";
        echo "<b>Kelas Ujian</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . ($ujian['nama_kelas'] ?? '-') . "<br>";
        echo "<b>Mata Pelajaran</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . ($ujian['nama_mapel'] ?? '-') . "<br>";
        echo "<b>Jumlah Peserta</b> &nbsp;&nbsp;&nbsp;: " . $jumlahPeserta . " Siswa";
        echo "</td>";

        // Kolom Kanan
        echo "<td style='vertical-align: top; border:none;'>";
        echo "<b>Hari</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $hariUjian . "<br>";
        echo "<b>Tanggal</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $tanggalUjian . "<br>";
        echo "<b>Waktu</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $waktuUjian . "<br>";
        echo "<b>Gadik Penguji</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $gadikPenguji;
        echo "</td>";

        echo "</tr>";
        echo "</table>";

        // Tabel Data Siswa
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<thead>
          <tr style='background-color: #d9d9d9; text-align: center;'>
              <th style='width: 40px;'>No</th>
              <th style='width: 10px;'>Nosis</th>
              <th style='width: 250px;'>Nama Siswa</th>
              <th style='width: 90px;'>Nilai Akhir</th>
              <th style='width: 110px;'>Status</th>
          </tr>
        </thead><tbody>";

        $no = 1;
        foreach ($peserta as $p) {
            $status = !empty($p['nilai_akhir']) ? 'Sudah Dinilai' : 'Belum Dinilai';
            $nilai = !empty($p['nilai_akhir']) ? number_format($p['nilai_akhir'], 2) : '-';

            echo "<tr>";
            echo "<td align='center'>" . $no++ . "</td>";
            echo "<td align='center'>'" . esc($p['nosis']) . "</td>";
            echo "<td>" . esc($p['nama_siswa']) . "</td>";
            echo "<td align='center'>" . $nilai . "</td>";
            echo "<td align='center'>" . $status . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        exit;
    }

    // ==================== 2. EXPORT WORD ====================
    public function exportWord($kelasUjianId)
    {
        $db = \Config\Database::connect();
        $sort = $this->request->getGet('sort');
        $orderBy = ($sort == 'nilai') ? 'nu.nilai_akhir DESC' : 's.nosis ASC';

        $ujian = $db->table('kelas_ujian ku')
            ->select('ku.*, mp.nama_mapel, p.nama as nama_gadik, pg.nama_pangkat')
            ->join('mata_pelajaran mp', 'mp.id = ku.mata_pelajaran_id', 'left')
            ->join('pegawai p', 'p.id = ku.penguji_id', 'left')
            ->join('pangkat pg', 'pg.id = p.pangkat_id', 'left')
            ->where('ku.id', $kelasUjianId)
            ->get()->getRowArray();

        $angkatan = $db->table('kelas_ujian_peserta cup')
            ->select('a.nama_angkatan, a.tahun_angkatan')
            ->join('siswa s', 's.id = cup.siswa_id', 'left')
            ->join('angkatan a', 'a.id = s.angkatan_id', 'left')
            ->where('cup.kelas_ujian_id', $kelasUjianId)
            ->get()->getRowArray();

        $peserta = $db->table('kelas_ujian_peserta cup')
            ->select('cup.*, s.nama AS nama_siswa, s.nosis, nu.nilai_akhir')
            ->join('siswa s', 's.id = cup.siswa_id', 'left')
            ->join('nilai_ujian nu', 'nu.kelas_ujian_id = cup.kelas_ujian_id AND nu.siswa_id = cup.siswa_id', 'left')
            ->where('cup.kelas_ujian_id', $kelasUjianId)
            ->orderBy($orderBy)
            ->get()->getResultArray();

        // Format Bulan & Hari Lengkap Bahasa Indonesia
        $namaBulan = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];

        $hariIndo = [
            'Sun' => 'Minggu',
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu'
        ];

        $hariUjian = '-';
        $tanggalUjian = '-';
        if (!empty($ujian['tanggal'])) {
            $tglKey = date('d', strtotime($ujian['tanggal']));
            $blnKey = date('m', strtotime($ujian['tanggal']));
            $thnKey = date('Y', strtotime($ujian['tanggal']));
            $hariKey = date('D', strtotime($ujian['tanggal']));

            $hariUjian = $hariIndo[$hariKey] ?? '-';
            $namaBlnFull = $namaBulan[$blnKey] ?? '';
            $tanggalUjian = "{$tglKey} {$namaBlnFull} {$thnKey}";
        }

        // Format Jam Ujian
        $jamMulai = !empty($ujian['jam_mulai']) ? date('H:i', strtotime($ujian['jam_mulai'])) : '-';
        $jamSelesai = !empty($ujian['jam_selesai']) ? date('H:i', strtotime($ujian['jam_selesai'])) : '-';
        $waktuUjian = "Pukul {$jamMulai} - {$jamSelesai} WIB";

        // Format Gadik Penguji & Pangkat
        $gadikPenguji = (!empty($ujian['nama_pangkat']) ? $ujian['nama_pangkat'] . ' ' : '') . ($ujian['nama_gadik'] ?? '-');

        $filename = "Daftar_Nilai_" . url_title($ujian['nama_kelas'] ?? 'Ujian', '_', true) . ".doc";
        header("Content-Type: application/vnd.ms-word");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Cache-Control: max-age=0");

        echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
        echo "<head><title>Export Word</title></head><body>";

        // Judul Laporan
        echo "<h2 style='text-align:center; margin-bottom: 5px;'>REKAPITULASI NILAI UJIAN OBE</h2>";
        echo "<p style='text-align:center; margin-top: 0; margin-bottom: 20px;'><b>SISWA DIKTUK BINTARA POLWAN " . ($angkatan['nama_angkatan'] ?? '-') . " TAHUN ANGKATAN " . ($angkatan['tahun_angkatan'] ?? '-') . "/" . (($angkatan['tahun_angkatan'] ?? 0) + 1) . "</b></p>";

        // Informasi Detail (2 Kolom Kanan-Kiri Terpisah Rapi)
        echo "<table style='width:100%; border:none; border-collapse:collapse; margin-bottom: 20px;'>";
        echo "<tr>";

        // Kolom Kiri
        echo "<td style='width: 45%; vertical-align: top; border:none;'>";
        echo "<b>Kelas Ujian</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . ($ujian['nama_kelas'] ?? '-') . "<br>";
        echo "<b>Mata Pelajaran</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . ($ujian['nama_mapel'] ?? '-');
        echo "</td>";

        // Kolom Kanan (Terpisah per baris)
        echo "<td style='width: 55%; vertical-align: top; border:none;'>";
        echo "<b>Hari</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $hariUjian . "<br>";
        echo "<b>Tanggal</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $tanggalUjian . "<br>";
        echo "<b>Waktu</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $waktuUjian . "<br>";
        echo "<b>Gadik Penguji</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $gadikPenguji;
        echo "</td>";

        echo "</tr>";
        echo "</table>";

        // Tabel Nilai Siswa
        echo "<table border='1' style='border-collapse:collapse; width:100%;' cellpadding='5'>";
        echo "<thead>
       <tr style='background-color:#d9d9d9;'>
           <th style='width: 5%;'>No</th>
           <th style='width: 15%;'>Nosis</th>
           <th style='width: 45%;'>Nama Siswa</th>
           <th style='width: 15%;'>Nilai Akhir</th>
           <th style='width: 20%;'>Status</th>
       </tr>
     </thead><tbody>";

        $no = 1;
        foreach ($peserta as $p) {
            $status = !empty($p['nilai_akhir']) ? 'Sudah Dinilai' : 'Belum Dinilai';
            $nilai = !empty($p['nilai_akhir']) ? number_format($p['nilai_akhir'], 2) : '-';

            echo "<tr>";
            echo "<td style='text-align:center;'>" . $no++ . "</td>";
            echo "<td style='text-align:center;'>" . esc($p['nosis']) . "</td>";
            echo "<td>" . esc($p['nama_siswa']) . "</td>";
            echo "<td style='text-align:center;'>" . $nilai . "</td>";
            echo "<td style='text-align:center;'>" . $status . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</body></html>";
        exit;
    }

    public function exportPdf($kelasUjianId)
    {
        $db = \Config\Database::connect();
        $sort = $this->request->getGet('sort');
        $orderBy = ($sort == 'nilai') ? 'nu.nilai_akhir DESC' : 's.nosis ASC';

        $ujian = $db->table('kelas_ujian ku')
            ->select('ku.*, mp.nama_mapel, p.nama as nama_gadik, pg.nama_pangkat')
            ->join('mata_pelajaran mp', 'mp.id = ku.mata_pelajaran_id', 'left')
            ->join('pegawai p', 'p.id = ku.penguji_id', 'left')
            ->join('pangkat pg', 'pg.id = p.pangkat_id', 'left')
            ->where('ku.id', $kelasUjianId)
            ->get()->getRowArray();

        $angkatan = $db->table('kelas_ujian_peserta cup')
            ->select('a.nama_angkatan, a.tahun_angkatan')
            ->join('siswa s', 's.id = cup.siswa_id', 'left')
            ->join('angkatan a', 'a.id = s.angkatan_id', 'left')
            ->where('cup.kelas_ujian_id', $kelasUjianId)
            ->get()->getRowArray();

        $peserta = $db->table('kelas_ujian_peserta cup')
            ->select('cup.*, s.nama AS nama_siswa, s.nosis, nu.nilai_akhir')
            ->join('siswa s', 's.id = cup.siswa_id', 'left')
            ->join('nilai_ujian nu', 'nu.kelas_ujian_id = cup.kelas_ujian_id AND nu.siswa_id = cup.siswa_id', 'left')
            ->where('cup.kelas_ujian_id', $kelasUjianId)
            ->orderBy($orderBy)
            ->get()->getResultArray();

        // Hitung Jumlah Peserta Ujian secara otomatis
        $jumlahPeserta = count($peserta);

        // Format Bulan & Hari Bahasa Indonesia
        $namaBulan = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];
        $hariIndo = [
            'Sun' => 'Minggu',
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu'
        ];

        $hariUjian = '-';
        $tanggalUjian = '-';
        if (!empty($ujian['tanggal'])) {
            $tglKey = date('d', strtotime($ujian['tanggal']));
            $blnKey = date('m', strtotime($ujian['tanggal']));
            $thnKey = date('Y', strtotime($ujian['tanggal']));
            $hariKey = date('D', strtotime($ujian['tanggal']));

            $hariUjian = $hariIndo[$hariKey] ?? '-';
            $namaBlnFull = $namaBulan[$blnKey] ?? '';
            $tanggalUjian = "{$tglKey} {$namaBlnFull} {$thnKey}";
        }

        $jamMulai = !empty($ujian['jam_mulai']) ? date('H:i', strtotime($ujian['jam_mulai'])) : '-';
        $jamSelesai = !empty($ujian['jam_selesai']) ? date('H:i', strtotime($ujian['jam_selesai'])) : '-';
        $waktuUjian = "Pukul {$jamMulai} - {$jamSelesai} WIB";
        $gadikPenguji = (!empty($ujian['nama_pangkat']) ? $ujian['nama_pangkat'] . ' ' : '') . ($ujian['nama_gadik'] ?? '-');

        // Render HTML untuk PDF
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: sans-serif; font-size: 11pt; }
                h2 { text-align: center; margin-bottom: 5px; }
                .subtitle { text-align: center; margin-top: 0; margin-bottom: 20px; font-weight: bold; }
                table.info { width: 100%; border: none; border-collapse: collapse; margin-bottom: 15px; }
                table.info td { border: none; vertical-align: top; padding: 2px 0; }
                table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
                table.data th, table.data td { border: 1px solid #000; padding: 6px; font-size: 10pt; }
                table.data th { background-color: #d9d9d9; text-align: center; }
            </style>
        </head>
        <body>
            <h2>REKAPITULASI NILAI UJIAN OBE</h2>
            <div class='subtitle'>SISWA DIKTUK BINTARA POLWAN " . ($angkatan['nama_angkatan'] ?? '-') . " TAHUN ANGKATAN " . ($angkatan['tahun_angkatan'] ?? '-') . "/" . (($angkatan['tahun_angkatan'] ?? 0) + 1) . "</div>

            <table class='info'>
                <tr>
                    <td style='width: 45%;'>
                        <b>Kelas Ujian</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . ($ujian['nama_kelas'] ?? '-') . "<br>
                        <b>Mata Pelajaran</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . ($ujian['nama_mapel'] ?? '-') . "<br>
                        <b>Jumlah Peserta</b> &nbsp;&nbsp;&nbsp;: " . $jumlahPeserta . " Siswa
                    </td>
                    <td style='width: 55%;'>
                        <b>Hari</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $hariUjian . "<br>
                        <b>Tanggal</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $tanggalUjian . "<br>
                        <b>Waktu</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $waktuUjian . "<br>
                        <b>Gadik Penguji</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: " . $gadikPenguji . "
                    </td>
                </tr>
            </table>

            <table class='data'>
                <thead>
                    <tr>
                        <th style='width: 5%;'>No</th>
                        <th style='width: 15%;'>Nosis</th>
                        <th style='width: 45%;'>Nama Siswa</th>
                        <th style='width: 15%;'>Nilai Akhir</th>
                        <th style='width: 20%;'>Status</th>
                    </tr>
                </thead>
                <tbody>";

        $no = 1;
        foreach ($peserta as $p) {
            $status = !empty($p['nilai_akhir']) ? 'Sudah Dinilai' : 'Belum Dinilai';
            $nilai = !empty($p['nilai_akhir']) ? number_format($p['nilai_akhir'], 2) : '-';

            $html .= "<tr>
                <td style='text-align:center;'>" . $no++ . "</td>
                <td style='text-align:center;'>" . esc($p['nosis']) . "</td>
                <td>" . esc($p['nama_siswa']) . "</td>
                <td style='text-align:center;'>" . $nilai . "</td>
                <td style='text-align:center;'>" . $status . "</td>
            </tr>";
        }

        $html .= "</tbody></table></body></html>";

        // Output ke PDF (sesuaikan dengan library PDF yang Anda pakai, contoh menggunakan Dompdf)
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Daftar_Nilai_" . url_title($ujian['nama_kelas'] ?? 'Ujian', '_', true) . ".pdf", ["Attachment" => 0]);
        exit();
    }
}
