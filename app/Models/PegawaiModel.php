<?php

namespace App\Models;

use CodeIgniter\Model;

class PegawaiModel extends Model
{
    protected $table = 'pegawai';
    protected $primaryKey = 'id';
    // Hapus 'role_id' dari sini karena role disimpan di tabel users
    protected $allowedFields = ['user_id', 'nama', 'tipe_pegawai', 'nomor_induk', 'pangkat_id', 'role_id'];

    public function getPegawaiWithRelations()
    {
        return $this->select('pegawai.*, users.username, users.role_id, pangkat.nama_pangkat, roles.nama_role, 
                            batalyon.nama_batalyon, kompi.nama_kompi, pleton.nama_pleton')
            ->join('users', 'users.id = pegawai.user_id', 'left')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
            ->join('batalyon', 'batalyon.danyon_id = pegawai.nomor_induk', 'left')
            ->join('kompi', 'kompi.danki_id = pegawai.nomor_induk', 'left')
            ->join('pleton', 'pleton.danton_id = pegawai.nomor_induk', 'left')
            ->findAll();
    }

    public function get_pegawai_with_roles()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('pegawai');

        $builder->select('pegawai.nama as nama_pegawai, users.username, roles.nama_role');
        $builder->join('batalyon', 'pegawai.nomor_induk = batalyon.danyon_id', 'inner');
        $builder->join('users', 'pegawai.user_id = users.id', 'inner');
        $builder->join('roles', 'users.role_id = roles.id', 'inner');

        $query = $builder->get();
        return $query->getResultArray();
    }

    // Di dalam PegawaiModel.php
    public function countByRole($role_id)
    {
        return $this->select('pegawai.*')
            ->join('users', 'users.id = pegawai.user_id', 'left')
            ->where('users.role_id', $role_id)
            ->countAllResults();
    }

    public function getPegawaiByJabatan($jabatan)
    {
        return $this->select('pegawai.*, users.username, users.role_id, pangkat.nama_pangkat, roles.nama_role, 
                            batalyon.nama_batalyon, kompi.nama_kompi, pleton.nama_pleton')
            ->join('users', 'users.id = pegawai.user_id', 'left')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
            ->join('batalyon', 'batalyon.danyon_id = pegawai.nomor_induk', 'left')
            ->join('kompi', 'kompi.danki_id = pegawai.nomor_induk', 'left')
            ->join('pleton', 'pleton.danton_id = pegawai.nomor_induk', 'left')
            ->where('roles.nama_role', $jabatan)
            ->findAll();
    }
}
