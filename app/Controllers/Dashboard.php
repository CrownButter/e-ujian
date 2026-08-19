<?php

namespace App\Controllers;

use App\Models\SiswaModel;
use App\Models\PegawaiModel;
use App\Models\UserModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $role_id = session()->get('role_id');

        // Peta Role ke View
        $views = [
            1 => 'admin/dashboard',
            2 => 'operator/dashboard',
            3 => 'pengasuh/dashboard',
            4 => 'danton/dashboard',
            5 => 'danki/dashboard',
            6 => 'danyon/dashboard',
            7 => 'siswa/dashboard',
        ];

        // Jika role tidak ditemukan, redirect ke login
        if (!isset($views[$role_id])) {
            return redirect()->to('/login');
        }

        $siswaModel = new SiswaModel();
        $pegawaiModel = new PegawaiModel();
        $userModel = new UserModel();

        // Ambil semua pegawai dengan relasinya menggunakan fungsi yang Anda buat
        $all_pegawai = $pegawaiModel->getPegawaiWithRelations();

        // Hitung jumlah Danton (asumsi role_id untuk Danton adalah 4)
        $total_danton = count(array_filter($all_pegawai, function ($pegawai) {
            return isset($pegawai['role_id']) && $pegawai['role_id'] == 4;
        }));

        // Hitung jumlah Danki (asumsi role_id untuk Danki adalah 5)
        $total_danki = count(array_filter($all_pegawai, function ($pegawai) {
            return isset($pegawai['role_id']) && $pegawai['role_id'] == 5;
        }));


        $data = [
            'title'         => 'Dashboard',
            'role_name'     => session()->get('role_name') ?? 'User', // Perbaikan variabel role_name
            'total_siswa'   => $siswaModel->countAllResults(),
            'total_user'    => $userModel->countAllResults(),
            'total_pegawai' => count($all_pegawai), // Menggunakan count dari array agar lebih efisien
            'total_danton'  => $total_danton,
            'total_danki'   => $total_danki,
            'total_danton' => $pegawaiModel->countByRole(4),
            'total_danki'  => $pegawaiModel->countByRole(5),
        ];

        return view($views[$role_id], $data);
    }
}
