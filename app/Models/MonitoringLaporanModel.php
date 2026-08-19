<?php

namespace App\Models;

use CodeIgniter\Model;

class MonitoringLaporanModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Mengambil daftar periode minggu yang tersedia untuk filter dropdown
     */
    public function getPeriodeList()
    {
        return $this->db->table('monitoring_periode_view_or_table') // jika tidak ada view, join langsung ke angkatan
            ->select('monitoring_periode.*, angkatan.nama_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'left')
            ->orderBy('monitoring_periode.periode_awal', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Mengambil daftar bidang beserta indikator di dalamnya secara berurutan
     */
    public function getIndikatorDinamis()
    {
        return $this->db->table('monitoring_indikator')
            ->select('monitoring_indikator.*, monitoring_bidang.nama_bidang, monitoring_bidang.kode as kode_bidang')
            ->join('monitoring_bidang', 'monitoring_bidang.id = monitoring_indikator.bidang_id')
            ->where('monitoring_indikator.aktif', 1)
            ->orderBy('monitoring_bidang.urutan', 'ASC')
            ->orderBy('monitoring_indikator.urutan', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Mengambil daftar siswa berdasarkan filter Pleton
     */
    public function getSiswaByPleton($pletonId)
    {
        $builder = $this->db->table('siswa')
            ->select('siswa.id, siswa.nama, siswa.no_akademik, pleton.nama_pleton')
            ->join('pleton', 'pleton.id = siswa.pleton_id', 'left');

        if ($pletonId !== 'All' && !empty($pletonId)) {
            $builder->where('siswa.pleton_id', $pletonId);
        }

        return $builder->orderBy('siswa.nama', 'ASC')->get()->getResultArray();
    }


    public function getMatriksHasil($periodeId, $pletonId)
    {
        $builder = $this->db->table('monitoring_hasil')
            ->select('siswa_id, indikator_id, hasil_yang_dicapai, catatan_pengasuh, status')
            ->join('siswa', 'siswa.id = monitoring_hasil.siswa_id')
            ->where('monitoring_hasil.periode_id', $periodeId);

        if ($pletonId !== 'All' && !empty($pletonId)) {
            $builder->where('siswa.pleton_id', $pletonId);
        }

        $results = $builder->get()->getResultArray();

        $matriks = [];
        foreach ($results as $row) {
            $matriks[$row['siswa_id']][$row['indikator_id']] = [
                'hasil'   => $row['hasil_yang_dicapai'],
                'catatan' => $row['catatan_pengasuh'],
                'status'  => $row['status']
            ];
        }

        return $matriks;
    }

    /**
     * Mengambil data pengesahan/tanda tangan laporan dari Danton, Danki (Danpi), dan Danyon
     */
    public function getPengesahan($periodeId, $pletonId)
    {
        return $this->db->table('monitoring_pengesahan')
            ->select('monitoring_pengesahan.*, 
                      danton_peg.nama as nama_danton, 
                      danpi_peg.nama as nama_danpi, 
                      danyon_peg.nama as nama_danyon')
            ->join('pegawai as danton_peg', 'danton_peg.nomor_induk = monitoring_pengesahan.danton_id', 'left')
            // catatan: sesuaikan join pegawai jika danton_id/danpi_id merujuk ke tabel pegawai atau users
            ->join('pegawai as danpi_peg', 'danpi_peg.id = monitoring_pengesahan.danpi_id', 'left')
            ->where('monitoring_pengesahan.periode_id', $periodeId)
            ->where('monitoring_pengesahan.pleton_id', $pletonId)
            ->get()->getRowArray();
    }

    // ==========================================
    // METHOD TAMBAHAN UNTUK BUAT LAPORAN MINGGUAN
    // ==========================================

    // 1. Method untuk menampilkan form pengisian instrumen monitoring mingguan
    public function buat()
    {
        $periodeId = $this->request->getVar('periode_id');

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Silakan pilih periode laporan terlebih dahulu.');
        }

        $data['title'] = 'Buat Laporan Monitoring';
        $data['prefix'] = $this->prefix; // Menggunakan prefix dari constructor controller kamu
        $data['periode_id'] = $periodeId;

        // Ambil data detail periode & angkatan untuk header form
        $data['periode'] = $this->db->table('monitoring_periode')
            ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
            ->where('monitoring_periode.id', $periodeId)
            ->get()->getRowArray();

        // Daftar Pleton secara statis
        $data['list_pleton'] = ['Pleton A', 'Pleton B', 'Pleton C'];

        return view('minsis/monitoring_laporan_buat', $data);
    }

    // 2. Method untuk memproses penyimpanan data instrumen mingguan ke database
    public function simpan()
    {
        $periodeId = $this->request->getPost('periode_id');
        $pleton = $this->request->getPost('pleton');

        // Tangkap array data dari form input bidang-bidang
        $bidang = $this->request->getPost('bidang');
        $indikator = $this->request->getPost('indikator');
        $giat_serdik = $this->request->getPost('giat_serdik');
        $hasil_dicapai = $this->request->getPost('hasil_dicapai');
        $giat_pengasuh = $this->request->getPost('giat_pengasuh');

        $this->db->transStart();

        // Bersihkan data lama dengan periode_id & pleton yang sama agar tidak duplikat saat edit/simpan ulang
        $this->db->table('laporan_monitoring_detail')
            ->where('periode_id', $periodeId)
            ->where('pleton', $pleton)
            ->delete();

        // Looping dan simpan setiap baris bidang instrumen
        if (!empty($bidang)) {
            foreach ($bidang as $key => $val) {
                if (trim($val) != '') {
                    $insertData = [
                        'periode_id'    => $periodeId,
                        'pleton'        => $pleton,
                        'bidang'        => $val,
                        'indikator'     => $indikator[$key] ?? '',
                        'giat_serdik'   => $giat_serdik[$key] ?? '',
                        'hasil_dicapai' => $hasil_dicapai[$key] ?? '',
                        'giat_pengasuh' => $giat_pengasuh[$key] ?? '',
                        'created_at'    => date('Y-m-d H:i:s')
                    ];
                    $this->db->table('laporan_monitoring_detail')->insert($insertData);
                }
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Gagal menyimpan laporan monitoring.');
        }

        return redirect()->to(base_url($this->prefix . '/monitoringlaporan?periode_id=' . $periodeId . '&pleton=' . $pleton))
            ->with('success', 'Laporan Monitoring mingguan berhasil disimpan.');
    }
}
