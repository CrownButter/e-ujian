<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">

    Header

    <section class="content">

        <div class="container-fluid">

            <form action="<?= base_url('monitoring/simpan') ?>" method="post">

                <!-- FILTER -->

                <div class="card">
                    ....
                </div>

                <!-- BIDANG A -->

                <div class="card">
                    ....
                </div>

                <!-- BIDANG B -->

                <div class="card">
                    ....
                </div>

                <!-- BIDANG C -->

                <div class="card">
                    ....
                </div>

                <!-- BIDANG D -->

                <div class="card">
                    ....
                </div>

                <!-- BIDANG E -->

                <div class="card">
                    ....
                </div>

                <!-- PENGESAHAN -->

                <div class="card">
                    ....
                </div>

                <!-- TOMBOL -->

                <button class="btn btn-success">
                    Simpan
                </button>

            </form>

        </div>

    </section>

</div>

<?= $this->endSection() ?>