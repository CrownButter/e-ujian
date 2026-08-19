<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // 1. Cek apakah sudah login
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        // 2. Cek apakah role user ada di dalam daftar akses yang diizinkan
        $userRole = session()->get('role_id');

        // $arguments ini didapat dari route (misal: 'role:1,2')
        if (!in_array($userRole, $arguments)) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
