<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-hash" content="<?= csrf_hash() ?>">
    <title>E-UJIAN SEPOLWAN MENYALA</title>
    <link rel="shortcut icon" href="<?= base_url(); ?>favicon.ico" type="image/x-icon">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url('assets/'); ?>plugins/fontawesome-free/css/all.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= base_url('assets/'); ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url('assets/'); ?>dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="<?= base_url('assets/'); ?>plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/'); ?>plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/'); ?>plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
</head>

<!-- PERUBAHAN DI SINI: Menambahkan class 'layout-navbar-fixed' -->

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-danger navbar-dark">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item">
                    <h4>
                        <span class="badge bg-primary">
                            Akses:
                            <?php
                            $roles = [
                                1 => 'Admin',
                                2 => 'Operator',
                                3 => 'Gadik',
                                4 => 'Danton',
                                5 => 'Danki',
                                6 => 'Danyon',
                                7 => 'Siswa'
                            ];

                            $roleId = session()->get('role_id');

                            // Menampilkan nama role
                            echo $roles[$roleId] ?? 'User';

                            // Menampilkan tambahan detail berdasarkan role masing-masing:
                            if (($roleId == 4 || $roleId == 7) && session()->get('nama_pleton')) {
                                echo ' - ' . session()->get('nama_pleton');
                            } elseif ($roleId == 5 && session()->get('nama_kompi')) {
                                echo ' - ' . session()->get('nama_kompi');
                            } elseif ($roleId == 6 && session()->get('nama_batalyon')) {
                                echo ' - ' . session()->get('nama_batalyon');
                            }
                            ?>
                        </span>
                    </h4>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-bell"></i>
                        <?php
                        $jumlah = isset($jumlah_notif) ? $jumlah_notif : 0;
                        $role_map = [
                            1 => 'admin',
                            2 => 'admin',
                            3 => 'gadik',
                            4 => 'danton',
                            5 => 'danki',
                            6 => 'danyon',
                            7 => 'siswa'
                        ];
                        $prefix = $role_map[session()->get('role_id')] ?? 'admin';
                        ?>
                        <?php if ($jumlah > 0): ?>
                            <span class="badge badge-warning navbar-badge"><?= $jumlah ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header"><?= $jumlah ?> Penilaian Menunggu</span>

                        <div class="dropdown-divider"></div>

                        <?php if ($jumlah > 0): ?>
                            <a href="<?= site_url($prefix . '/binplinsis/nilaimental') ?>" class="dropdown-item">
                                <i class="fas fa-clipboard-check mr-2"></i>
                                <?= $jumlah ?> laporan baru dari Danton
                                <span class="float-right text-muted text-sm">Baru</span>
                            </a>
                        <?php else: ?>
                            <a href="#" class="dropdown-item text-muted">
                                <i class="fas fa-info-circle mr-2"></i> Tidak ada notifikasi
                            </a>
                        <?php endif; ?>

                        <div class="dropdown-divider"></div>
                        <a href="<?= site_url($prefix . '/binplinsis/nilaimental/list') ?>" class="dropdown-item dropdown-footer">
                            Lihat Semua Penilaian
                        </a>
                    </div>
                </li>
                <ul class="navbar-nav ml-auto mr-2">
                    <li class="nav-item dropdown">
                        <div class="nav-link d-flex align-items-center" data-toggle="dropdown" href="#" style="cursor: pointer;">
                            <?php
                            $db = \Config\Database::connect();

                            $userId = session()->get('user_id');
                            $roleId = session()->get('role_id');

                            $userNama = 'User';

                            if ($userId) {
                                if ($roleId == 7) {
                                    $query = $db->table('siswa')->where('user_id', $userId)->get()->getRow();
                                    $userNama = $query ? $query->nama : 'Siswa';
                                } else {
                                    $query = $db->table('pegawai')->where('user_id', $userId)->get()->getRow();
                                    $userNama = $query ? $query->nama : 'Admin';
                                }
                            }
                            ?>

                            <span class="mr-2"><?= $userNama ?></span>

                            <img src="<?= base_url('assets/dist/img/avatar.png') ?>" class="img-circle elevation-1" alt="User Image" width="25">
                        </div>

                        <?php
                        $session = session();
                        $role_id = $session->get('role_id');

                        // Buat pemetaan (mapping) ID ke prefix folder
                        $role_map = [
                            1 => 'admin',
                            2 => 'operator',
                            3 => 'gadik',
                            4 => 'danton',
                            5 => 'danki',
                            6 => 'danyon',
                            7 => 'siswa'
                        ];

                        // Ambil prefix berdasarkan role_id, default ke 'admin' jika tidak ditemukan
                        $prefix = $role_map[$role_id] ?? 'admin';
                        ?>

                        <div class="dropdown-menu dropdown-menu-right">
                            <a href="<?= base_url($prefix . '/users/profil') ?>" class="dropdown-item">
                                <i class="fas fa-user mr-2"></i> Profil
                            </a>
                            <a href="<?= base_url($prefix . '/users/ubah-password') ?>" class="dropdown-item">
                                <i class="fas fa-lock mr-2"></i> Ubah Password
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?= base_url('logout') ?>" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </ul>
        </nav>
        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="#" class="brand-link">
                <img src="<?= base_url('assets/'); ?>dist/img/logo_sepolwan.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">E-UJIAN SEPOLWAN</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <?php echo view('layout/sidebar'); ?>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <?= $this->renderSection('content') ?>
        <!-- /.content-wrapper -->

        <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <b>Version</b> 1.0
            </div>
            <strong>Copyright &copy; <?= date('Y'); ?> <a href="https://dwincomputer.com" target="_blank">Developer</a>. Ristanto</strong> All rights reserved.
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="<?= base_url('assets/'); ?>plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= base_url('assets/'); ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="<?= base_url('assets/'); ?>plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= base_url('assets/'); ?>dist/js/adminlte.min.js"></script>
    <script src="<?= base_url('assets/'); ?>plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url('assets/'); ?>plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="<?= base_url('assets/'); ?>plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="<?= base_url('assets/'); ?>plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <script src="<?= base_url('assets/'); ?>plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
    <script src="<?= base_url('assets/'); ?>plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>

    <script src="<?= base_url('assets/'); ?>plugins/datatables-buttons/js/buttons.html5.min.js"></script>
    <script src="<?= base_url('assets/'); ?>plugins/datatables-buttons/js/buttons.print.min.js"></script>
    <script src="<?= base_url('assets/'); ?>plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <?= $this->renderSection('script'); ?>

</body>

</html>