<?= $this->extend('layout/main'); ?>

<?= $this->section('content'); ?>
<!-- Tambahkan div pembungkus content-wrapper di sini -->
<div class="content-wrapper">

    <!-- Bagian Content-Header untuk Judul Halaman agar rapi -->
    <div class="content-header">
        <div class="container-fluid">
            <!-- Anda bisa menaruh judul halaman utama di sini jika diperlukan -->
        </div>
    </div>

    <!-- Konten Utama Anda -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0 font-weight-bold text-primary"><?= esc($materi['judul']); ?></h5>

                            <a href="<?= base_url($prefix . '/perdupsis'); ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>

                        <div class="card-body">
                            <div id="pdf-viewer-wrapper" class="overflow-x-auto" style="position: relative; min-height: 500px; background: #eee; border: 1px solid #ccc;">

                                <div id="watermark">
                                    <?= session()->get('username') ?? 'SISWA' ?> - PUSDIK
                                </div>

                                <div id="pdf-container" class="d-flex flex-column align-items-center">
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-right">
                            <?php if (!$isSiswa) : ?>
                                <a href="<?= base_url('assets/dist/pdf/' . $materi['file_pdf']) ?>" class="btn btn-primary" download>
                                    <i class="fas fa-download"></i> Unduh Dokumen
                                </a>
                            <?php else : ?>
                                <p class="text-muted small"><i class="fas fa-info-circle"></i> Mode baca saja aktif.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div> <!-- Penutup content-wrapper -->

<style>
    /* ... kode style Anda tetap sama ... */
    #watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        font-size: 40px;
        color: rgba(0, 0, 0, 0.05);
        pointer-events: none;
        z-index: 10;
        white-space: nowrap;
    }

    #pdf-container canvas {
        margin-bottom: 10px;
        max-width: 100%;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    }
</style>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    const url = '<?= base_url('assets/dist/pdf/' . $materi['file_pdf']) ?>';
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js';

    pdfjsLib.getDocument(url).promise.then(pdf => {
        for (let i = 1; i <= pdf.numPages; i++) {
            pdf.getPage(i).then(page => {
                const canvas = document.createElement('canvas');
                document.getElementById('pdf-container').appendChild(canvas);
                const context = canvas.getContext('2d');
                const viewport = page.getViewport({
                    scale: 1.5
                });
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                page.render({
                    canvasContext: context,
                    viewport: viewport
                });
            });
        }
    });

    // Proteksi: Disable Klik Kanan
    document.addEventListener('contextmenu', event => event.preventDefault());
</script>
<?= $this->endSection(); ?>