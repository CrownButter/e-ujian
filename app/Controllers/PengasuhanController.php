<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\UserModel;

class PengasuhanController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $roleModel = new RoleModel();
    }

    public function struktur(): string
    {
        $data = [
            'title' => 'Struktur',
        ];
        return view('pengasuhan/struktur', $data);
    }

    public function rengiat(): string
    {
        $data = [
            'title' => 'E-Rengiat',
        ];
        return view('pengasuhan/rengiat', $data);
    }
}
