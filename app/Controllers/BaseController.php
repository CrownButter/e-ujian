<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // 1. Jalankan parent initController terlebih dahulu (WAJIB)
        parent::initController($request, $response, $logger);

        // 2. Ambil service renderer agar bisa membagikan data ke semua view
        $renderer = \Config\Services::renderer();

        // 3. Logika untuk membagikan variabel jumlah_notif ke semua view
        if (session()->get('role_id') == 5) { // Jika role Danki
            $db = \Config\Database::connect();
            $danki_id = session()->get('username');

            // Hitung notifikasi berdasarkan query yang Anda inginkan
            $notif = $db->table('penilaian_mental pm')
                ->join('siswa s', 's.id = pm.siswa_id')
                ->join('pleton p', 'p.id = s.pleton_id')
                ->join('kompi k', 'k.id = p.kompi_id')
                ->where('k.danki_id', $danki_id)
                ->where('pm.status_danton', '1')
                ->where('pm.status_danki', '0')
                ->countAllResults();

            // Bagikan variabel secara global
            $renderer->setVar('jumlah_notif', $notif);
        } else {
            // Default jika bukan Danki atau tidak ada notif
            $renderer->setVar('jumlah_notif', 0);
        }
    }
}
