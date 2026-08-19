<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-UJIAN SEPOLWAN</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url('assets/'); ?>/plugins/fontawesome-free/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="<?= base_url('assets/'); ?>/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url('assets/'); ?>/dist/css/adminlte.min.css">

    <!-- Custom CSS untuk Tampilan Background Image & Form Login -->
    <style>
        body.login-page {
            /* 
              Pengaturan Background Image + Overlay Transparan 
              Ganti 'assets/dist/img/bg-sepolwan.jpg' sesuai dengan nama/lokasi file gambar Anda.
            */
            background: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)),
                url("<?= base_url('assets/dist/img/background.png'); ?>") no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        .login-box {
            width: 400px;
        }

        .card-outline.card-info {
            border-top: 4px solid #17a2b8;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            /* Sedikit transparan modern */
            backdrop-filter: blur(5px);
            /* Efek blur halus di belakang card */
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 25px 15px 15px 15px;
        }

        .card-header .h3 {
            font-size: 1.5rem;
            color: #222;
            margin-top: 10px;
            line-height: 1.2;
        }

        .login-box-msg {
            padding: 10px 20px 20px 20px;
            color: #666;
        }

        .form-control {
            border-radius: 6px;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-color: #ced4da;
            color: #666;
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        /* Styling Tombol Sign In */
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
        }

        .icheck-info label {
            font-weight: normal !important;
            color: #555;
        }

        /* Styling Link Lupa Password */
        .login-box a.forgot-link {
            color: #17a2b8;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .login-box a.forgot-link:hover {
            color: #117a8b;
            text-decoration: underline;
        }

        /* Responsif untuk layar HP */
        @media (max-width: 576px) {
            .login-box {
                width: 90%;
                margin-top: 20px;
            }
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="card card-outline card-info">
            <div class="card-header text-center">
                <img src="<?= base_url('assets/dist/img/logo_sepolwan.png'); ?>" alt="Logo Sepolwan" style="width: 70px; height: auto;">
                <div class="h3"><b>E-UJIAN</b><br> <span style="font-weight: 300;">SEPOLWAN MENYALA</span></div>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Sign in to start your session</p>

                <!-- Alert Flashdata Error Login -->
                <?php if (session()->getFlashdata('msg')): ?>
                    <div class="alert alert-danger text-center p-2 mb-3" style="font-size: 90%; border-radius: 6px;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <?= session()->getFlashdata('msg'); ?>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('auth') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <div class="input-group">
                            <input type="text" name="username" class="form-control <?= validation_show_error('username') ? 'is-invalid' : '' ?>" value="<?= set_value('username'); ?>" placeholder="Username" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-user"></span>
                                </div>
                            </div>
                        </div>
                        <?php if (validation_show_error('username')): ?>
                            <div class="text-danger mt-1" style="font-size: 85%;">
                                <i class="fas fa-exclamation-circle mr-1"></i><?= validation_show_error('username'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <div class="input-group">
                            <input type="password" name="password" class="form-control <?= validation_show_error('password') ? 'is-invalid' : '' ?>" placeholder="Password" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                        <?php if (validation_show_error('password')): ?>
                            <div class="text-danger mt-1" style="font-size: 85%;">
                                <i class="fas fa-exclamation-circle mr-1"></i><?= validation_show_error('password'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row pt-2 pb-3">
                        <div class="col-8 d-flex align-items-center">
                            <div class="icheck-info">
                                <input type="checkbox" id="remember">
                                <label for="remember">Remember Me</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-info btn-block">Sign In</button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-2">
                    <a href="#" class="forgot-link" type="button" data-toggle="modal" data-target="#lupaPasswordModal">
                        I forgot my password
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Lupa Password -->
    <div class="modal fade" id="lupaPasswordModal" tabindex="-1" role="dialog" aria-labelledby="lupaPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 10px;">
                <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #eee; border-top-left-radius: 10px; border-top-right-radius: 10px;">
                    <h5 class="modal-title" id="lupaPasswordModalLabel" style="color: #333; font-weight: bold;">
                        <i class="fas fa-key mr-2 text-info"></i>Lupa Password
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center p-4">
                    <i class="fas fa-user-shield fa-3x text-warning mb-3"></i>
                    <p style="font-size: 1.1rem; color: #555;">Untuk mereset password Anda, silahkan hubungi <b>Admin Sistem</b>.</p>
                    <p class="text-muted small">Pastikan Anda menyiapkan NIK atau Nomor Anggota untuk verifikasi.</p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #eee;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 6px;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="<?= base_url('assets/'); ?>/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= base_url('assets/'); ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= base_url('assets/'); ?>/dist/js/adminlte.min.js"></script>
</body>

</html>