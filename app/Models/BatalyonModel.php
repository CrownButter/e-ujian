<?php

namespace App\Models;

use CodeIgniter\Model;

class BatalyonModel extends Model
{
    protected $table      = 'batalyon';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nama_batalyon', 'danyon_id'];


    public function getBatalyonWithPegawai()
    {
        return $this->select('batalyon.*, pegawai.nama as nama_danyon, pangkat.nama_pangkat')
            ->join('pegawai', 'pegawai.nomor_induk = batalyon.danyon_id', 'left')
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left') // Sesuaikan namaforeign key tabel pangkat Anda
            ->findAll();
    }
}
