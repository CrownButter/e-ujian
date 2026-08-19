<?php

namespace App\Models; // Harus sesuai dengan folder

use CodeIgniter\Model;

class SosiometriModel extends Model
{
    protected $table      = 'materi_sosiometri';
    protected $primaryKey = 'id';
    protected $allowedFields = ['judul', 'slug', 'deskripsi', 'file_pdf', 'cover_img'];
}
