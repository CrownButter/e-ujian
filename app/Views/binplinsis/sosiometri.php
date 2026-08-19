<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= $title; ?></h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active"><?= $title; ?></li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="mb-3">
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#userModal">
                    <i class="fas fa-plus"></i> Tambah Modul
                </button>
            </div>
            <div class="row">
                <div class="card" style="width: 10rem; position: relative;">
                    <div class="dropdown" style="position: absolute; top: 5px; right: 5px; z-index: 10;">
                        <button class="btn btn-sm btn-light" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i> </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="">Edit</a>
                            <a class="dropdown-item text-danger" href="#" onclick="return confirm('Hapus materi ini?')">Hapus</a>
                        </div>
                    </div>
                    <a href="#" class="text-decoration-none text-dark">
                        <img src="<?= base_url('assets/dist/img/coverbook.png'); ?>" class="card-img-top" alt="...">
                        <div class="card-body">
                            <p class="card-text">Judul Materi</p>
                        </div>
                    </a>
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<?= $this->endsection(); ?>