<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('home');
    }

    public function tentang()
    {
        $data = [
            'title' => 'Tentang Kami'
        ];
        return view('tengtang_kami', $data);
    }
}
