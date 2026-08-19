<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MonitoringLaporanModel;
use App\Models\PletonModel;
use App\Models\AngkatanModel;

class MonitoringLaporan extends BaseController
{
    protected $laporanModel;
    protected $pletonModel;
    protected $angkatanModel;

    public function __construct()
    {
        $this->laporanModel  = new MonitoringLaporanModel();
        $this->pletonModel   = new PletonModel();
        $this->angkatanModel = new AngkatanModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();

        // 1. Ambil filter dari request URL (GET)
        $periodeId = $this->request->getGet('periode_id');
        $pletonId  = $this->request->getGet('pleton_id');

        // 2. Mengambil daftar periode lengkap dengan nama angkatan untuk dropdown filter
        $listPeriode = $db->table('monitoring_periode')
            ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
            ->orderBy('monitoring_periode.id', 'DESC')
            ->get()->getResultArray();

        $listPleton = $this->pletonModel->findAll();

        // 3. Set default periode_id jika kosong di URL dan listPeriode ada datanya
        if (empty($periodeId) && !empty($listPeriode)) {
            $periodeId = $listPeriode[0]['id'];
        }

        // Set default pleton_id jika kosong
        if (empty($pletonId) && !empty($listPleton)) {
            $pletonId = $listPleton[0]['id'];
        }

        // 4. Pengambilan Data Angkatan & Periode Aktif (Dengan Proteksi Fallback)
        $periodeAktif = null;

        if (!empty($periodeId)) {
            $periodeAktif = $db->table('monitoring_periode')
                ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
                ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
                ->where('monitoring_periode.id', $periodeId)
                ->get()->getRowArray();
        }


        if (empty($periodeAktif)) {
            $angkatanAktif = $db->table('angkatan')
                ->where('status', 1)
                ->get()->getRowArray();

            if (!empty($angkatanAktif)) {
                $periodeAktif = [
                    'id'             => null,
                    'minggu_ke'      => '-',
                    'periode_awal'   => null,
                    'periode_akhir'  => null,
                    'nama_angkatan'  => $angkatanAktif['nama_angkatan'],
                    'tahun_angkatan' => $angkatanAktif['tahun_angkatan']
                ];
            }
        }

        // 5. Jalankan Query Data Dinamis Siswa & Nilai
        $indikatorList = $this->laporanModel->getIndikatorDinamis();
        $siswaList     = [];
        $matriksHasil  = [];
        $pengesahan    = null;

        if (!empty($periodeId) && !empty($pletonId)) {
            $siswaList    = $this->laporanModel->getSiswaByPleton($pletonId);
            $matriksHasil = $this->laporanModel->getMatriksHasil($periodeId, $pletonId);
            $pengesahan   = $this->laporanModel->getPengesahan($periodeId, $pletonId);
        }

        // 6. Kelompokkan indikator berdasarkan bidang untuk render table colspan
        $bidangDinamis = [];
        foreach ($indikatorList as $ind) {
            $bidangId = $ind['bidang_id'];
            if (!isset($bidangDinamis[$bidangId])) {
                $bidangDinamis[$bidangId] = [
                    'nama_bidang' => $ind['nama_bidang'],
                    'kode_bidang' => $ind['kode_bidang'],
                    'count'       => 0,
                    'indikator'   => []
                ];
            }
            $bidangDinamis[$bidangId]['count']++;
            $bidangDinamis[$bidangId]['indikator'][] = $ind;
        }

        // Send data ke view
        return view('minsis/laporan_monitoring', [
            'title'             => 'Laporan Monitoring Mingguan',
            'list_periode'      => $listPeriode,
            'list_pleton'       => $listPleton,
            'periode_id_aktif'  => $periodeId,
            'pleton_id_aktif'   => $pletonId,
            'periode_aktif'     => $periodeAktif,
            'bidang_dinamis'    => $bidangDinamis,
            'siswa_list'        => $siswaList,
            'matriks_hasil'     => $matriksHasil,
            'pengesahan'        => $pengesahan,
        ]);
    }
}
