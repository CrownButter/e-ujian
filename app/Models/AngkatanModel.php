<?php

namespace App\Models;

use CodeIgniter\Model;

class AngkatanModel extends Model
{
    protected $table = 'angkatan';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nama_angkatan', 'tahun_angkatan', 'tanggal_mulai', 'tanggal_berakhir'];
}
