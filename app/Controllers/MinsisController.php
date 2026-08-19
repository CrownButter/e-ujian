<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\UserModel;

class MinsisController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $roleModel = new RoleModel();
    }

    public function monitoris(): string
    {
        $data = [
            'title' => 'Monitoris',
        ];
        return view('minsis/monitoris', $data);
    }

    public function e_fatma(): string
    {
        $data = [
            'title' => 'E-Fatma',
        ];
        return view('minsis/e_fatma', $data);
    }
}
