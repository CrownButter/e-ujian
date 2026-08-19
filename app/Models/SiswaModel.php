<?php

namespace App\Models;

use CodeIgniter\Model;

class SiswaModel extends Model
{
    protected $table            = 'siswa';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['user_id', 'nama', 'nosis', 'pleton_id', 'angkatan_id'];

    // Fungsi untuk mengambil data siswa beserta relasi pletonnya
    public function getSiswaWithPleton()
    {
        return $this->select('siswa.*, pleton.nama_pleton')
            ->join('pleton', 'pleton.id = siswa.pleton_id', 'left')
            ->findAll();
    }


    public function getSiswaByAngkatanAktif($angkatanId = null)
    {
        $query = $this->select('siswa.*, pleton.nama_pleton, angkatan.nama_angkatan')
            ->join('pleton', 'pleton.id = siswa.pleton_id', 'left')
            ->join('angkatan', 'angkatan.id = siswa.angkatan_id', 'left'); // Ubah ke left

        // Tambahkan log untuk debug (bisa dilihat di toolbar CodeIgniter)
        $query->where('angkatan.status', 1);

        if ($angkatanId) {
            $query->where('siswa.angkatan_id', $angkatanId);
        }

        return $query->findAll();
    }

    public function getProfilSiswa($user_id)
    {
        return $this->db->table('siswa')
            ->join('pleton', 'pleton.id = siswa.pleton_id')
            ->join('kompi', 'kompi.id = pleton.kompi_id')
            ->join('batalyon', 'batalyon.id = kompi.batalyon_id')
            ->join('danton', 'danton.id = pleton.danton_id')
            ->join('danki', 'danki.id = kompi.danki_id')
            ->where('siswa.user_id', $user_id)
            ->get()->getRowArray();
    }

    // Fungsi untuk menetapkan pleton kepada siswa
    public function assignPleton($siswaId, $pletonId)
    {
        return $this->update($siswaId, [
            'pleton_id' => $pletonId
        ]);
    }

    public function getSiswaDetailWithPejabat($id)
    {
        $builder = $this->db->table('siswa');
        $builder->select('
            siswa.*, 
            pleton.nama_pleton, 
            kompi.id as kompi_id, 
            kompi.nama_kompi, 
            batalyon.id as batalyon_id, 
            batalyon.nama_batalyon,
            
            p_danton.nama as danton_nama,
            pg_danton.nama_pangkat as danton_pangkat,
            p_danton.nomor_induk as danton_nrp,
            
            p_danki.nama as danki_nama,
            pg_danki.nama_pangkat as danki_pangkat,
            p_danki.nomor_induk as danki_nrp,
            
            p_danyon.nama as danyon_nama,
            pg_danyon.nama_pangkat as danyon_pangkat,
            p_danyon.nomor_induk as danyon_nrp
        ');

        // Relasi hierarki wilayah/organisasi
        $builder->join('pleton', 'pleton.id = siswa.pleton_id', 'left');
        $builder->join('kompi', 'kompi.id = pleton.kompi_id', 'left');
        $builder->join('batalyon', 'batalyon.id = kompi.batalyon_id', 'left');

        // Relasi Pejabat (Pegawai)
        $builder->join('pegawai as p_danton', 'p_danton.id = pleton.danton_id OR p_danton.nomor_induk = pleton.danton_id', 'left');
        $builder->join('pegawai as p_danki', 'p_danki.id = kompi.danki_id OR p_danki.nomor_induk = kompi.danki_id', 'left');
        $builder->join('pegawai as p_danyon', 'p_danyon.id = batalyon.danyon_id OR p_danyon.nomor_induk = batalyon.danyon_id', 'left');

        // Relasi Pangkat untuk masing-masing Pejabat (Pastikan alias tabel pegawai benar)
        $builder->join('pangkat as pg_danton', 'pg_danton.id = p_danton.pangkat_id', 'left');
        $builder->join('pangkat as pg_danki', 'pg_danki.id = p_danki.pangkat_id', 'left');
        $builder->join('pangkat as pg_danyon', 'pg_danyon.id = p_danyon.pangkat_id', 'left');

        $builder->where('siswa.id', $id);

        $row = $builder->get()->getRowArray();

        if ($row) {
            $row['danton_info'] = [
                'nama'        => $row['danton_nama'] ?? '-',
                'pangkat'     => $row['danton_pangkat'] ?? '-',
                'nrp'         => $row['danton_nrp'] ?? '-',
                'pangkat_nrp' => trim(($row['danton_pangkat'] ?? '') . ' ' . ($row['danton_nrp'] ?? '-'))
            ];

            $row['danki_info'] = [
                'nama'        => $row['danki_nama'] ?? '-',
                'pangkat'     => $row['danki_pangkat'] ?? '-',
                'nrp'         => $row['danki_nrp'] ?? '-',
                'pangkat_nrp' => trim(($row['danki_pangkat'] ?? '') . ' ' . ($row['danki_nrp'] ?? '-'))
            ];

            $row['danyon_info'] = [
                'nama'        => $row['danyon_nama'] ?? '-',
                'pangkat'     => $row['danyon_pangkat'] ?? '-',
                'nrp'         => $row['danyon_nrp'] ?? '-',
                'pangkat_nrp' => trim(($row['danyon_pangkat'] ?? '') . ' ' . ($row['danyon_nrp'] ?? '-'))
            ];
        }

        return $row;
    }
    /**
     * Mengambil seluruh data siswa beserta relasi lengkap (opsional jika dibutuhkan untuk list)
     */
    public function getSiswaRelasionAll($pletonId = null)
    {
        $builder = $this->db->table('siswa');
        $builder->select('
            siswa.*, 
            pleton.nama_pleton, 
            kompi.id as kompi_id, 
            kompi.nama_kompi, 
            batalyon.id as batalyon_id, 
            batalyon.nama_batalyon,
            pleton.danton_id,
            kompi.danki_id,
            batalyon.danyon_id
        ');
        $builder->join('pleton', 'pleton.id = siswa.pleton_id', 'left');
        $builder->join('kompi', 'kompi.id = pleton.kompi_id', 'left');
        $builder->join('batalyon', 'batalyon.id = kompi.batalyon_id', 'left');

        if (!empty($pletonId)) {
            $builder->where('siswa.pleton_id', $pletonId);
        }

        $result = $builder->get()->getResultArray();

        foreach ($result as &$row) {
            $row['danton_info'] = $this->getDetailPegawai($row['danton_id'] ?? null);
            $row['danki_info']  = $this->getDetailPegawai($row['danki_id'] ?? null);
            $row['danyon_info'] = $this->getDetailPegawai($row['danyon_id'] ?? null);
        }

        return $result;
    }

    /**
     * Helper privat untuk mengambil data pegawai (pangkat, nama, nrp)
     */
    private function getDetailPegawai($pegawaiId)
    {
        $default = ['nama' => '-', 'pangkat' => '-', 'nrp' => '-', 'pangkat_nrp' => '-'];
        if (empty($pegawaiId)) {
            return $default;
        }

        $pegawai = $this->db->table('pegawai')
            ->groupStart()
            ->where('id', $pegawaiId)
            ->orWhere('nomor_induk', $pegawaiId)
            ->groupEnd()
            ->get()
            ->getRowArray();

        if (!$pegawai) {
            return $default;
        }

        $pangkat = $pegawai['pangkat'] ?? $pegawai['pangkat_gol'] ?? '';
        $nrp = $pegawai['nomor_induk'] ?? $pegawai['nip'] ?? $pegawai['nrp'] ?? '-';
        $nama = $pegawai['nama'] ?? $pegawai['nama_pegawai'] ?? '-';

        return [
            'nama'        => $nama,
            'pangkat'     => $pangkat,
            'nrp'         => $nrp,
            'pangkat_nrp' => trim($pangkat . ' NRP ' . $nrp)
        ];
    }
}
