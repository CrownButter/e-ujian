<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-UJIAN SEPOLWAN MENYALA</title>
    <link rel="shortcut icon" href="<?= base_url(); ?>favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --navy: rgb(9, 147, 157);
            --gold: rgb(255, 54, 201);
        }

        .navbar {
            background-color: var(--navy) !important;
            border-bottom: 3px solid var(--gold);
        }

        /* Slider */
        .carousel-item {
            height: 85vh;
            background: #000;
        }

        .carousel-item img {
            height: 85vh;
            object-fit: cover;
            filter: brightness(80%);
            margin-top: 2px;
        }

        .carousel-caption {
            bottom: 30%;
        }

        /* Content & UI */
        .btn-gold {
            background-color: var(--gold);
            color: #000;
            font-weight: 700;
            border: none;
            padding: 12px 30px;
        }

        .section-title {
            color: var(--navy);
            font-weight: 800;
        }

        .card {
            border-top: 4px solid var(--navy);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Footer */
        footer {
            background-color: var(--navy);
            color: #fff;
            padding-top: 50px;
        }
    </style>
</head>

<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <img src="<?= base_url('assets/dist/img/logo_sepolwan.png'); ?>" alt="Logo" class="d-inline-block align-text-top me-2" style="height: 35px; width: auto;">
                E-UJIAN SEPOLWAN MENYALA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Program</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light ms-lg-3" href="<?= base_url('login'); ?>">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Slider -->
    <div id="heroSlider" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active position-relative">

                <img src="<?= base_url('assets/dist/img/slider/slide1.png'); ?>" class="d-block w-100" alt="Slide">

                <!-- Tombol Login -->
                <div class="position-absolute start-50 translate-middle-x"
                    style="bottom: 50px; z-index: 10;">
                    <a href="<?= base_url('login'); ?>"
                        class="btn btn-danger btn-lg px-3 py-2 fw-bold shadow">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <!-- <section class="py-5">
        <div class="container">
            <div class="row align-items-center mb-5">
                <div class="col-lg-6">
                    <h2 class="section-title">Dedikasi Untuk Negeri</h2>
                    <p class="text-muted">Sepolwan Lemdiklat Polri berkomitmen memberikan pendidikan terbaik bagi calon srikandi Polri melalui kurikulum berbasis teknologi dan karakter kepemimpinan yang tangguh.</p>
                </div>
                <div class="col-lg-6">
                    <img src="" class="img-fluid rounded shadow" alt="Dedikasi Untuk Negeri">
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <h4>Kepemimpinan</h4>
                        <p>Membentuk karakter pemimpin masa depan yang disiplin.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <h4>Teknologi</h4>
                        <p>Adaptasi sistem kepolisian modern dan digitalisasi.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100">
                        <h4>Kesamaptaan</h4>
                        <p>Pelatihan fisik intensif dan kesehatan jasmani.</p>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Footer -->
    <footer>
        <div class="container pb-4">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>E-UJIAN SEPOLWAN MENYALA</h5>
                    <p></p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Menu</h5>
                    <ul class="list-unstyled">
                        <li>Pendaftaran</li>
                        <li>Kurikulum</li>
                        <li>Galeri</li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Kontak</h5>
                    <p>Jl. Sawangan Residence Ideal E02, Bojongsari Baru, Bojongsari, Depok, Jawa Barat<br>Email: bayatmultijaya@gmail.com</p>
                </div>
            </div>
            <div class="text-center border-top pt-3 text-secondary"><small>&copy; 2026 Bayat MUlti Jaya</small></div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>