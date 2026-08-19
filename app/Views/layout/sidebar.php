<?php
// Ambil session role_id
$session = session();
$role_id = (int)$session->get('role_id');

$roleMap = [
    1 => 'admin',
    2 => 'operator',
    3 => 'gadik',
    4 => 'danton',
    5 => 'danki',
    6 => 'danyon',
    7 => 'siswa'
];

$prefix = $roleMap[$role_id] ?? 'admin';

/**
 * Helper untuk mengecek menu aktif secara akurat
 * Menggunakan segmen pertama atau path URL
 */
if (!function_exists('isActive')) {
    function isActive($targetPath, $strict = false)
    {
        $currentUri = uri_string();

        if ($strict) {
            // Pencocokan persis URL
            return ($currentUri === $targetPath) ? 'active' : '';
        } else {
            // Pencocokan parsial (segmen)
            return (strpos($currentUri, $targetPath) === 0) ? 'active' : '';
        }
    }
}

/**
 * Fungsi untuk membuka treeview secara dinamis
 * Memeriksa segmen pertama URI
 */
if (!function_exists('segSegSegMenuOpen')) {
    function segSegSegMenuOpen($childPaths = [])
    {
        $currentUri = uri_string();
        foreach ($childPaths as $path) {
            // Periksa apakah URI saat ini dimulai dengan segmen path anak
            if (strpos($currentUri, $path) === 0) {
                return 'menu-open';
            }
        }
        return '';
    }
}
?>

<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">

        <!-- DASHBOARD (Hanya Admin / role_id == 1) atau PROFIL SISWA -->
        <?php if ($role_id === 1): ?>
            <li class="nav-item">
                <a href="<?= base_url('dashboard'); ?>" class="nav-link <?= isActive('dashboard', true); ?>">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>DASHBOARD</p>
                </a>
            </li>
        <?php elseif ($role_id === 7): ?>
            <li class="nav-item">
                <a href="<?= base_url('siswa/users/profil'); ?>" class="nav-link <?= isActive('siswa/users/profil'); ?>">
                    <i class="nav-icon fas fa-user"></i>
                    <p>Profil</p>
                </a>
            </li>
        <?php endif; ?>

        <!-- ========================================================================================= -->
        <!-- PENYESUAIAN MENU UJIAN OBE SISI ADMIN & GADIK -->
        <!-- ========================================================================================= -->
        <?php
        // Cek apakah role adalah Admin, Operator, Gadik, Danton, Danki, atau Danyon
        if (in_array($role_id, [1, 2, 3, 4, 5, 6])):
            // Kumpulkan segmen path anak SESUAI dengan menu di bawahnya agar treeview tidak menutup sendiri
            $obeAdminSubMenus = [
                $prefix . '/obe/kelas_ujian',
                $prefix . '/obe/bobot-nilai',
                $prefix . '/obe/penilaian',
                $prefix . '/obe/riwayat-ujian'
            ];
        ?>
            <li class="nav-item">
                <a href="<?= base_url($prefix . '/mata_pelajaran'); ?>" class="nav-link <?= isActive($prefix . '/mata_pelajaran'); ?>">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Mata Pelajaran</p>
                </a>
            </li>
            <li class="nav-item has-treeview <?= segSegSegMenuOpen($obeAdminSubMenus); ?>">
                <a href="#" class="nav-link <?= segSegSegMenuOpen($obeAdminSubMenus) ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-file-signature"></i>
                    <p>UJIAN OBE <i class="fas fa-angle-left right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                    <!-- 1. KELAS UJIAN -->
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/obe/kelas_ujian'); ?>" class="nav-link <?= isActive($prefix . '/obe/kelas_ujian'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Kelas Ujian</p>
                        </a>
                    </li>
                    <!-- 2. BOBOT NILAI OBE -->
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/obe/bobot-nilai'); ?>" class="nav-link <?= isActive($prefix . '/obe/bobot-nilai'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Bobot Nilai OBE</p>
                        </a>
                    </li>
                    <!-- 3. PENILAIAN UJIAN -->
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/obe/penilaian'); ?>" class="nav-link <?= isActive($prefix . '/obe/penilaian'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Penilaian Ujian</p>
                        </a>
                    </li>
                    <!-- 4. RIWAYAT UJIAN -->
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/obe/riwayat-ujian'); ?>" class="nav-link <?= isActive($prefix . '/obe/riwayat-ujian'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Riwayat Ujian</p>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>

        <?php
        // Cek apakah role adalah Siswa
        if ($role_id === 7):
            // Kumpulkan segmen path anak untuk menu UJIAN OBE (Sisi Siswa)
            $obeSiswaSubMenus = [
                $prefix . '/ujian/daftar',
                $prefix . '/ujian/riwayat'
            ];
        ?>
            <li class="nav-item has-treeview <?= segSegSegMenuOpen($obeSiswaSubMenus); ?>">
                <a href="#" class="nav-link <?= segSegSegMenuOpen($obeSiswaSubMenus) ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-user-edit"></i>
                    <p>IKUTI UJIAN <i class="fas fa-angle-left right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                    <!-- 1. DAFTAR UJIAN AKTIF -->
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/ujian/daftar'); ?>" class="nav-link <?= isActive($prefix . '/ujian/daftar'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Daftar Ujian Aktif</p>
                        </a>
                    </li>
                    <!-- 2. RIWAYAT UJIAN -->
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/ujian/riwayat'); ?>" class="nav-link <?= isActive($prefix . '/ujian/riwayat'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Riwayat Ujian</p>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>
        <!-- ========================================================================================= -->


        <!-- KHUSUS ADMIN -->
        <?php if ($role_id === 1): ?>

            <!-- PENGGUNA -->
            <?php
            // Segmen path anak untuk menu PENGGUNA
            $userSubMenus = [
                $prefix . '/master/data_referensi',
                $prefix . '/master/manage_siswa_pleton',
                $prefix . '/pegawai',
                $prefix . '/siswa/nominatif'
            ];
            ?>
            <li class="nav-item has-treeview <?= segSegSegMenuOpen($userSubMenus); ?>">
                <a href="#" class="nav-link <?= segSegSegMenuOpen($userSubMenus) ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-users"></i>
                    <p>PENGGUNA <i class="fas fa-angle-left right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/master/data_referensi'); ?>" class="nav-link <?= isActive($prefix . '/master/data_referensi'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>PRODIKLAT</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/master/manage_siswa_pleton'); ?>" class="nav-link <?= isActive($prefix . '/master/manage_siswa_pleton'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Setting Siswa ke Pleton</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/pegawai') ?>" class="nav-link <?= isActive($prefix . '/pegawai'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>GADIK</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/siswa/nominatif') ?>" class="nav-link <?= isActive($prefix . '/siswa/nominatif'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>SISWA</p>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- PENGATURAN -->
            <?php
            // Segmen path anak untuk menu PENGATURAN
            $configSubMenus = [
                $prefix . '/pengaturan/profil',
                $prefix . '/pengaturan/slider'
            ];
            ?>
            <li class="nav-item has-treeview <?= segSegSegMenuOpen($configSubMenus); ?>">
                <a href="#" class="nav-link <?= segSegSegMenuOpen($configSubMenus) ? 'active' : ''; ?>">
                    <i class="fas fa-solid fa-snowflake"></i>
                    <p>PENGATURAN <i class="fas fa-angle-left right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/pengaturan/profil'); ?>" class="nav-link <?= isActive($prefix . '/pengaturan/profil'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Profil Sekolah</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url($prefix . '/pengaturan/slider'); ?>" class="nav-link <?= isActive($prefix . '/pengaturan/slider'); ?>">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Slider</p>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>


        <li class="nav-item mt-2">
            <a href="<?= base_url('logout'); ?>" class="nav-link btn btn-danger btn-sm text-white logout-btn">
                <i class="nav-icon fas fa-sign-out-alt"></i>
                <p>Logout</p>
            </a>
        </li>
    </ul>
</nav>