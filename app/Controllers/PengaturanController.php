<?php

namespace App\Controllers;

class PengaturanController extends BaseController
{
    public function profil(): string
    {
        $data = [
            'title' => 'Profil Sekolah'
        ];
        return view('pengaturan/profil', $data);
    }

    public function slider()
    {
        $data = [
            'title' => 'Slider'
        ];
        return view('slider', $data);
    }
}
