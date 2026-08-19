<?php

namespace App\Models;

use CodeIgniter\Model;

class PenilaianMentalModel extends Model
{
    protected $table      = 'penilaian_mental';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'siswa_id',
        'angkatan_id',
        'minggu_ke',
        'skor_spiritual',
        'skor_ideologi',
        'skor_kejuangan',
        'skor_watak',
        'skor_kepemimpinan',
        'jml_skor',
        'nilai_akhir',
        'danton_id',
        'status_danton',
    ];

    /**
     * =====================================================
     * DATA PEGAWAI LOGIN
     * =====================================================
     */
    private function getPegawai($userId)
    {
        return $this->db->table('pegawai')
            ->where('user_id', $userId)
            ->get()
            ->getRow();
    }

    /**
     * =====================================================
     * SELURUH SISWA
     * =====================================================
     */
    public function getAllSiswa()
    {
        return $this->db->table('siswa')
            ->select('
                siswa.*,
                pleton.nama_pleton,
                kompi.nama_kompi,
                angkatan.nama_angkatan
            ')
            ->join('pleton', 'pleton.id=siswa.pleton_id', 'left')
            ->join('kompi', 'kompi.id=pleton.kompi_id', 'left')
            ->join('angkatan', 'angkatan.id=siswa.angkatan_id', 'left')
            ->get()
            ->getResultArray();
    }

    /**
     * =====================================================
     * DAFTAR PLETON DANTON
     * =====================================================
     */
    public function getPletonByDanton($userId)
    {
        $pegawai = $this->getPegawai($userId);

        if (!$pegawai) {
            return [];
        }

        return $this->db->table('pleton')
            ->where('danton_id', $pegawai->nomor_induk)
            ->orderBy('nama_pleton')
            ->get()
            ->getResultArray();
    }

    /**
     * =====================================================
     * DAFTAR PLETON DANKI
     * =====================================================
     */
    public function getPletonByDanki($userId)
    {
        $pegawai = $this->getPegawai($userId);

        if (!$pegawai) {
            return [];
        }

        return $this->db->table('pleton')
            ->select('pleton.*')
            ->join('kompi', 'kompi.id=pleton.kompi_id')
            ->where('kompi.danki_id', $pegawai->nomor_induk)
            ->orderBy('pleton.nama_pleton')
            ->get()
            ->getResultArray();
    }

    /**
     * =====================================================
     * DAFTAR PLETON DANYON
     * =====================================================
     */
    public function getPletonByDanyon($userId)
    {
        $pegawai = $this->getPegawai($userId);

        if (!$pegawai) {
            return [];
        }

        return $this->db->table('pleton')
            ->select('pleton.*')
            ->join('kompi', 'kompi.id=pleton.kompi_id')
            ->join('batalyon', 'batalyon.id=kompi.batalyon_id')
            ->where('batalyon.danyon_id', $pegawai->nomor_induk)
            ->orderBy('pleton.nama_pleton')
            ->get()
            ->getResultArray();
    }

    /**
     * =====================================================
     * BUILDER SISWA
     * =====================================================
     */
    public function getSiswaBuilder($userId, $level, $namaPleton = 'All')
    {
        // Tetap gunakan explicit table agar tidak terjadi error Unknown table
        $builder = $this->db->table('siswa');

        $builder->select("
            siswa.*,
            pleton.nama_pleton,
            kompi.nama_kompi,
            angkatan.nama_angkatan
        ");

        $builder->join('pleton', 'pleton.id=siswa.pleton_id', 'left');
        $builder->join('kompi', 'kompi.id=pleton.kompi_id', 'left');
        $builder->join('angkatan', 'angkatan.id=siswa.angkatan_id', 'left');

        switch (strtolower($level)) {

            case 'danton':
                $pegawai = $this->getPegawai($userId);
                if (!$pegawai) {
                    $builder->where('1=0');
                    return $builder;
                }
                $builder->where('pleton.danton_id', $pegawai->nomor_induk);
                break;

            case 'danki':
                $pegawai = $this->getPegawai($userId);
                if (!$pegawai) {
                    $builder->where('1=0');
                    return $builder;
                }
                $builder->where('kompi.danki_id', $pegawai->nomor_induk);
                break;

            case 'danyon':
                $pegawai = $this->getPegawai($userId);
                if (!$pegawai) {
                    $builder->where('1=0');
                    return $builder;
                }
                $builder->join('batalyon', 'batalyon.id=kompi.batalyon_id');
                $builder->where('batalyon.danyon_id', $pegawai->nomor_induk);
                break;

            case 'siswa':
                $builder->where('siswa.user_id', $userId);
                break;

            default:
                // Admin tampil semua
                break;
        }

        if (!empty($namaPleton) && $namaPleton != 'All') {
            $builder->where('pleton.nama_pleton', $namaPleton);
        }

        $builder->orderBy('pleton.nama_pleton', 'ASC');
        $builder->orderBy('siswa.nosis', 'ASC');

        return $builder;
    }

    /**
     * =====================================================
     * SISWA DANTON
     * =====================================================
     */
    public function getSiswaByDanton($userId)
    {
        return $this->getSiswaBuilder($userId, 'danton')->get()->getResultArray();
    }

    /**
     * =====================================================
     * SISWA DANKI
     * =====================================================
     */
    public function getSiswaByDanki($userId)
    {
        return $this->getSiswaBuilder($userId, 'danki')->get()->getResultArray();
    }

    /**
     * =====================================================
     * SISWA DANYON
     * =====================================================
     */
    public function getSiswaByDanyon($userId)
    {
        return $this->getSiswaBuilder($userId, 'danyon')->get()->getResultArray();
    }
}
