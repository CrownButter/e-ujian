<?php

namespace App\Models; // Harus sesuai dengan folder

use CodeIgniter\Model;

class PerdupsisModel extends Model
{
    protected $table      = 'materi_perdupsis';
    protected $primaryKey = 'id';
    protected $allowedFields = ['judul', 'slug', 'deskripsi', 'file_pdf', 'cover_img'];
}
