<?php

namespace App\Models;

use CodeIgniter\Model;

class BankSoalModel extends Model
{
    protected $table            = 'soal_obe'; // Nama tabel yang benar
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    // Sesuaikan dengan kolom yang ada di tabel `soal_obe`
    protected $allowedFields    = [
        'kelas_ujian_id',
        'mapel_id',
        'cpmk',
        'cpl',
        'tingkat_taksonomi',
        'bobot_soal',
        'pertanyaan',
        'rubrik_penilaian',
        'created_by'
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
