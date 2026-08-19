<?php

namespace App\Models;

use CodeIgniter\Model;

class PletonModel extends Model
{
    protected $table = 'pleton';
    protected $primaryKey = 'id';
    protected $allowedFields = ['kompi_id', 'nama_pleton', 'danton_id'];

    public function getPletonWithPegawai()
    {
        return $this->db->table('pleton')
            // Tambahkan pangkat.nama_pangkat ke dalam select
            ->select('pleton.*, pegawai.nama as nama_danton, pangkat.nama_pangkat, kompi.nama_kompi')

            // Join untuk nama Danton
            ->join('pegawai', 'pegawai.nomor_induk = pleton.danton_id', 'left')

            // Join ke tabel pangkat untuk mengambil pangkat si Danton
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')

            // Join untuk nama Kompi
            ->join('kompi', 'kompi.id = pleton.kompi_id', 'left')

            ->get()
            ->getResultArray();
    }
}
