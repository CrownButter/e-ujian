<?php



use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --- Jalur Publik & Auth ---
$routes->get('/', 'Home::index');
$routes->get('login', 'Auth::login');
$routes->post('auth', 'Auth::auth');
$routes->post('benchmark/password-verify', 'Auth::benchmarkPasswordVerify');
$routes->get('logout', 'Auth::logout');

// Login admission control. These endpoints intentionally stay public so
// clients can queue before the expensive /auth request reaches PHP-FPM.
$routes->post('waiting-room/enter', 'WaitingRoom::enter');
$routes->get('waiting-room/status', 'WaitingRoom::status');

// Dashboard Utama (filter 'auth' standar)
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);


// DEFINISI VARIABEL ROLE
$roles = [
    1 => 'admin',
    2 => 'operator',
    3 => 'gadik',
    4 => 'danton',
    5 => 'danki',
    6 => 'danyon',
    7 => 'siswa'
];


// ====================================================================
// LOOPING UNTUK SEMUA ROLE (ADMIN s/d SISWA)
// ====================================================================
// ====================================================================
// LOOPING UNTUK SEMUA ROLE (ADMIN s/d SISWA)
// ====================================================================
foreach ($roles as $id => $prefix) {
    // Filter keamanan pakai filter role dinamis
    $routes->group($prefix, ['filter' => 'role:' . $id], function ($routes) use ($prefix, $id) {

        // 1. MODUL PERDUPSIS
        $routes->get('perdupsis', 'PerdupsisController::index');
        $routes->post('perdupsis/store', 'PerdupsisController::store');
        $routes->get('perdupsis/baca/(:any)', 'PerdupsisController::baca/$1');
        $routes->get('perdupsis/delete/(:any)', 'PerdupsisController::delete/$1');

        // 2. MODUL SOSIOMETRI (Persiapan/Config)
        $routes->get('sosiometri', 'SosiometriController::index');
        $routes->post('sosiometri/store', 'SosiometriController::store');
        $routes->get('sosiometri/sosiobaca/(:any)', 'SosiometriController::sosiobaca/$1');

        // 3. MODUL BINPLINSIS (Nilai Mental & Jasmani, Monitoring)
        $routes->get('binplinsis/nilaimental', 'BinplinsisController::nilaimental');
        $routes->post('binplinsis/simpanNilaiMental', 'BinplinsisController::simpanNilaiMental');
        $routes->post('binplinsis/updateNilaiMental', 'BinplinsisController::updateNilaiMental');
        $routes->post('binplinsis/simpanNilaiMentalMassal', 'BinplinsisController::simpanNilaiMentalMassal');
        $routes->post('binplinsis/verifikasiNilaiMental', 'BinplinsisController::verifikasiNilaiMental');
        $routes->post('binplinsis/kirimMassalKeDanki', 'BinplinsisController::kirimMassalKeDanki');
        $routes->post('binplinsis/proses_tolak_danki', 'BinplinsisController::proses_tolak_danki');
        $routes->post('binplinsis/verifikasi', 'BinplinsisController::verifikasi');
        $routes->post('binplinsis/kirimMassalKeDanyon', 'BinplinsisController::kirimMassalKeDanyon');
        $routes->post('binplinsis/verifikasiMassalDanyon', 'BinplinsisController::verifikasiMassalDanyon');
        $routes->get('binplinsis/nilaimental/exportPdf', 'BinplinsisController::exportPdf');
        $routes->get('binplinsis/nilaimental/exportExcel', 'BinplinsisController::exportExcel');
        $routes->get('binplinsis/role/(:num)', 'BinplinsisController::exportPdfByRole/$1');
        $routes->get('binplinsis/pleton/(:num)', 'BinplinsisController::exportPdfByPleton/$1');
        $routes->get('binplinsis/nilaijasmani', 'BinplinsisController::nilaijasmani');

        // 4. MODUL MINSIS & MONITORING
        $routes->get('minsis/monitoris', 'MinsisController::monitoris');
        $routes->get('minsis/e_fatma', 'MinsisController::e_fatma');
        $routes->get('monitoringlaporan', 'MonitoringLaporan::index');
        $routes->get('monitoringperiode', 'MonitoringPeriode::index');
        $routes->get('monitoringperiode/create', 'MonitoringPeriode::create');
        $routes->post('monitoringperiode/store', 'MonitoringPeriode::store');
        $routes->get('monitoringperiode/buat_laporan', 'MonitoringPeriode::buat_laporan');
        $routes->post('monitoringperiode/simpan_laporan', 'MonitoringPeriode::simpan_laporan');
        $routes->get('monitoringperiode/lihat_laporan', 'MonitoringPeriode::lihat_laporan');
        $routes->get('monitoringperiode/export_word', 'MonitoringPeriode::export_word');
        $routes->get('monitoringperiode/export_pdf', 'MonitoringPeriode::export_pdf');
        $routes->get('monitoringperiode/edit_laporan', 'MonitoringPeriode::edit_laporan');

        // 5. MODUL PENGASUHAN
        $routes->get('pengasuhan/struktur', 'PengasuhanController::struktur');
        $routes->get('pengasuhan/rengiat', 'PengasuhanController::rengiat');

        // 6. PROFIL & PASSWORD
        $routes->get('users/profil', 'UserController::profil');
        $routes->post('users/update/(:num)', 'UserController::update/$1');
        $routes->post('users/simpanProfil', 'UserController::simpanProfil');
        $routes->get('users/ubah-password', 'UserController::ubahPasswordView');
        $routes->post('users/changePassword', 'UserController::changePassword');

        // 7. DATA SISWA
        $routes->get('siswa/nominatif', 'SiswaController::nominatif');
        $routes->get('siswa/export_pdf', 'SiswaController::export_pdf');
        $routes->get('siswa/exportExcel', 'SiswaController::exportExcel');
        $routes->get('api/siswa/all', 'SiswaController::getAllSiswaJson');

        // nata pelajaran
        $routes->get('mata_pelajaran', "MataPelajaran::mataPelajaran");
        $routes->post('mata_pelajaran/tambah_mapel', 'MataPelajaran::tambahMapel');
        $routes->post('mata_pelajaran/update_mapel/(:num)', 'MataPelajaran::updateMapel/$1');
        $routes->get('mata_pelajaran/delete_mapel/(:num)', 'MataPelajaran::deleteMapel/$1');

        $routes->group('obe', function ($routes) use ($id) {
            if ($id >= 1 && $id <= 6) {

                $routes->get('kelas-ujian/peserta-pleton/(:num)', 'ObeController::pesertaPleton/$1');
                $routes->get('kelas-ujian/peserta/(:num)', 'ObeController::pesertaSiswa/$1');
                // Rute Kelas Ujian
                $routes->get('mata-pelajaran/get-data', 'ObeController::mataPelajaranGetData');
                $routes->get('mata_pelajaran/get-data', 'ObeController::mataPelajaranGetData');
                $routes->get('kelas_ujian', 'ObeController::kelas_ujian');
                $routes->get('kelas-ujian/get-data', 'ObeController::kelasUjianGetData');
                $routes->post('kelas-ujian/store', 'ObeController::kelasUjianStore');
                $routes->get('kelas-ujian/get/(:num)', 'ObeController::kelasUjianGet/$1');
                $routes->post('kelas-ujian/delete/(:num)', 'ObeController::kelasUjianDelete/$1');
                $routes->get('kelas-ujian/peserta-pleton/(:num)', 'KelasUjianController::pesertaPleton/$1');

                $routes->get('mataPelajaranGetData', 'ObeController::mataPelajaranGetData');
                $routes->get('pegawaiGetData', 'ObeController::pegawaiGetData');
                $routes->get('pletonGetData', 'ObeController::pletonGetData');
                $routes->get('siswaGetData', 'ObeController::siswaGetData');
                // Bank Soal
                $routes->get('bank-soal', 'ObeController::bankSoalIndex');
                $routes->get('bank-soal/get/(:segment)', 'ObeController::bankSoalGetByLevel/$1');
                $routes->post('bank-soal/store', 'ObeController::bankSoalStore');
                $routes->post('bank-soal/delete/(:num)', 'ObeController::bankSoalDelete/$1');
                $routes->get('riwayat-ujian', 'ObeController::riwayatUjian');
                $routes->post('set-active-kelas', 'ObeController::setActiveKelasUjian');
                $routes->get('detail/(:num)', 'ObeController::detail/$1');
                // Bobot Nilai
                $routes->get('bobot-nilai', 'ObeController::bobotNilaiIndex');
                $routes->post('bobot-nilai/store', 'ObeController::bobotNilaiStore');

                // Jadwal Ujian
                $routes->get('jadwal-ujian', 'ObeController::jadwalUjianIndex');
                $routes->get('jadwal-ujian/get-data', 'ObeController::jadwalUjianGetData');
                $routes->get('jadwal-ujian/get/(:num)', 'ObeController::jadwalUjianGetDetail/$1');
                $routes->post('jadwal-ujian/store', 'ObeController::jadwalUjianStore');
                $routes->post('jadwal-ujian/delete/(:num)', 'ObeController::jadwalUjianDelete/$1');

                $routes->post('penilaian/simpan', 'ObeController::simpanPenilaianUjian');
                $routes->get('penilaian/kelas/(:num)', 'ObeController::daftarPenilaianIndex/$1');
                $routes->get('penilaian', 'ObeController::indexPenilaian');
                $routes->get('kelola-nilai', 'ObeController::indexKelolaNilai');
                $routes->post('penilaian/simpan', 'ObeController::simpanPenilaianUjian');
                $routes->get('penilaian/detail/(:num)/(:num)', 'ObeController::detailJawabanSiswa/$1/$2');
                $routes->get('penilaian/form/(:num)/(:num)', 'ObeController::halamanPenilaian/$1/$2');
                $routes->get('penilaian/form/(:num)/(:num)', 'ObeController::halamanPenilaian/$1/$2');
                $routes->get('penilaian/detail/(:num)/(:num)', 'ObeController::detailJawabanSiswa/$1/$2');

                // soal
                $routes->get('soal/(:num)', 'ObeController::soal/$1');

                // Sesuaikan grup prefix Anda (misal di bawah grup 'gadik' atau rute OBE Anda)
                $routes->get('penilaian/exportExcel/(:num)', 'ObeController::exportExcel/$1');
                $routes->get('penilaian/exportWord/(:num)', 'ObeController::exportWord/$1');
                $routes->get('penilaian/exportPdf/(:num)', 'ObeController::exportPdf/$1');
            }

            if ($id == 7) {
                $routes->get('ujian/daftar', 'ObeController::siswaDaftarUjian');
                $routes->get('daftar-ujian', 'ObeController::siswaDaftarUjian');
                $routes->get('kerjakan-ujian/(:num)', 'ObeController::kerjakanUjian/$1');
                $routes->post('ujian/selesai/(:num)', 'ObeController::selesaiUjian/$1');
            }
        });

        // ====================================================================

        // KHUSUS ROLE SISWA ($id == 7) DI LUAR GROUP OBE
        if ($id == 7) {
            $routes->get('ujian/daftar', 'ObeController::siswaDaftarUjian');
            $routes->get('daftar-ujian', 'ObeController::siswaDaftarUjian');
            $routes->get('kerjakan-ujian/(:num)', 'ObeController::kerjakanUjian/$1');
            $routes->post('ujian/selesai/(:num)', 'ObeController::selesaiUjian/$1');
        }
    });
}
