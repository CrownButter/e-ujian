<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalUjianModel extends Model
{
    protected $table            = 'jadwal_ujian'; // Sesuaikan dengan nama tabel Anda di database
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'mapel',
        'tingkat_kognitif',
        'tanggal_ujian',
        'waktu_mulai',
        'waktu_selesai',
        'durasi_menit',
        'status'
    ];
}
