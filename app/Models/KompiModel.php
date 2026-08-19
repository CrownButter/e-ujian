<?php

namespace App\Models;

use CodeIgniter\Model;

class KompiModel extends Model
{
    protected $table      = 'kompi';
    protected $primaryKey = 'id';

    // Sesuaikan kolom yang diizinkan untuk diisi
    protected $allowedFields = ['batalyon_id', 'nama_kompi', 'danki_id'];


    public function getKompiWithPegawai()
    {
        return $this->db->table('kompi')
            // Tambahkan pangkat.nama_pangkat ke dalam select
            ->select('kompi.*, pegawai.nama as nama_danki, pangkat.nama_pangkat, batalyon.nama_batalyon')

            // Join ke tabel pegawai untuk nama Danki
            ->join('pegawai', 'pegawai.nomor_induk = kompi.danki_id', 'left')

            // Join ke tabel pangkat untuk mengambil pangkat si Danki
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')

            // Join ke tabel batalyon untuk nama Batalyon
            ->join('batalyon', 'batalyon.id = kompi.batalyon_id', 'left')

            ->get()
            ->getResultArray();
    }
}
