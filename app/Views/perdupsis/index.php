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
            <?php if (!$isSiswa) : ?>
                <div class="mb-3">
                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#perdupsisModal">
                        <i class="fas fa-plus"></i> Tambah Modul
                    </button>
                </div>
            <?php else : ?>
                <p class="text-muted small"><i class="fas fa-info-circle"></i> Mode baca saja aktif.</p>
            <?php endif; ?>
            <div class="col-12">
                <div class="row">
                    <?php foreach ($materi as $mt): ?>
                        <div class="card mr-2" style="width: 12rem;">
                            <div class="dropdown" style="position: absolute; top: 5px; right: 5px; z-index: 10;">
                                <button class="btn btn-sm btn-light" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i> </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editModal<?= $mt['id']; ?>">Edit</a>

                                    <a class="dropdown-item text-danger" href="<?= base_url($prefix . '/perdupsis/delete/' . $mt['id']); ?>"
                                        onclick="return confirm('Hapus materi ini?')">Hapus</a>
                                </div>
                            </div>
                            <a href="<?= base_url($prefix . '/perdupsis/baca/' . $mt['slug']); ?>">
                                <img src="<?= base_url('assets/dist/img/' . $mt['cover_img']); ?>" class="card-img-top" alt="<?= $mt['judul']; ?>">
                            </a>
                            <div class="card-body">
                                <p class="card-text"><?= $mt['judul']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>


<!-- modal add -->

<div class="modal fade" id="perdupsisModal" tabindex="-1" role="dialog" aria-labelledby="perdupsisModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="perdupsisForm" action="<?= base_url('admin/perdupsis/store') ?>"
                method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="perdupsisModalLabel">Tambah Materi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Judul</label>
                        <input type="text" name="judul" class="form-control" required>
                        <div class="invalid-feedback">
                            Mohon isi judul materi terlebih dahulu.
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cover (Gambar)</label>
                        <input type="file" name="cover_img" id="coverInput" class="form-control" accept="image/*" onchange="previewImage(event)">

                        <div class="mt-2">
                            <img id="imgPreview"
                                src="<?= base_url('assets/dist/img/coverbook.png') ?>"
                                alt="Preview"
                                style="width: 150px; height: 150px; object-fit: cover; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Unggah Modul (PDF)</label>
                        <input type="file" name="file_pdf" class="form-control" accept="application/pdf" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($materi as $mt): ?>
    <div class="modal fade" id="editModal<?= $mt['id']; ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= base_url('perdupsis/update/' . $mt['id']) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Materi: <?= $mt['judul']; ?></h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Judul</label>
                            <input type="text" name="judul" class="form-control" value="<?= $mt['judul']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Cover (Gambar)</label>
                            <input type="file" name="cover_img" class="form-control"
                                accept="image/*" onchange="previewEditImage(event, '<?= $mt['id']; ?>')">

                            <div class="mt-2">
                                <img id="imgPreviewEdit<?= $mt['id']; ?>"
                                    src="<?= base_url('assets/dist/img/' . $mt['cover_img']) ?>"
                                    alt="Preview"
                                    style="width: 150px; height: 150px; object-fit: cover; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Ganti Modul (PDF)</label>
                            <input type="file" name="file_pdf" class="form-control" accept="application/pdf">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah file PDF.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach ?>
<?= $this->endsection(); ?>

<?= $this->section('script'); ?>
<script>
    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById('imgPreview');

        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result; // Ganti sumber gambar dengan file yang diupload
            }

            reader.readAsDataURL(input.files[0]); // Baca file sebagai URL
        } else {
            // Jika batal atau kosong, kembalikan ke default
            preview.src = "<?= base_url('assets/dist/img/coverbook.png') ?>";
        }
    }

    (function() {
        'use strict'

        // Ambil semua form yang ingin kita beri custom validation
        var forms = document.querySelectorAll('.needs-validation')

        // Loop melalui form dan cegah submit jika tidak valid
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>
<?= $this->endsection(); ?>