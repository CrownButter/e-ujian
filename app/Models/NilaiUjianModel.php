<?php

namespace App\Models;

use CodeIgniter\Model;

class NilaiUjianModel extends Model
{
    protected $table            = 'nilai_ujian';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'kelas_ujian_id',
        'siswa_id',
        'nilai_akhir',
        'status_pengerjaan',
        'status_penilaian'
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
