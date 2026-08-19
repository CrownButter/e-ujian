<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AngkatanModel;

class MonitoringPeriode extends BaseController
{
    protected $db;
    protected $angkatanModel;
    protected $prefix; // <-- Deklarasikan property untuk menampung prefix role

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->angkatanModel = new AngkatanModel();

        // Ambil segment pertama dari URL secara dinamis sebagai prefix role di constructor
        $uri = service('request')->getUri();
        $this->prefix = $uri->getSegment(1);
    }

    // 1. Menampilkan daftar periode (Index)
    public function index()
    {
        $data['title'] = 'Manajemen Periode Monitoring';
        $data['prefix'] = $this->prefix; // <-- Ambil langsung dari property class

        // Ambil data periode dengan melakukan JOIN ke tabel angkatan
        $data['list_periode'] = $this->db->table('monitoring_periode')
            ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
            ->orderBy('monitoring_periode.id', 'DESC')
            ->get()->getResultArray();

        return view('minsis/periode_list', $data);
    }

    // 2. Menampilkan halaman form tambah periode (Create)
    public function create()
    {
        $data['title'] = 'Tambah Periode Monitoring';
        $data['prefix'] = $this->prefix; // <-- Ambil langsung dari property class

        // Ambil daftar angkatan untuk dropdown filter di form input
        $data['list_angkatan'] = $this->angkatanModel->where('status', 1)->findAll();

        return view('minsis/periode_create', $data);
    }

    // 3. Memproses penyimpanan data ke database (Store)
    public function store()
    {
        // Aturan Validasi Inputan Form
        $rules = [
            'angkatan_id'   => 'required',
            'minggu_ke'     => 'required|numeric',
            'periode_awal'  => 'required|valid_date',
            'periode_akhir' => 'required|valid_date',
            'status'        => 'required|in_list[Draft,Final]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Siapkan array data untuk disimpan ke tabel monitoring_periode
        $insertData = [
            'angkatan_id'   => $this->request->getPost('angkatan_id'),
            'minggu_ke'     => $this->request->getPost('minggu_ke'),
            'periode_awal'  => $this->request->getPost('periode_awal'),
            'periode_akhir' => $this->request->getPost('periode_akhir'),
            'status'        => $this->request->getPost('status'),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s')
        ];

        // Jalankan query insert ke tabel monitoring_periode
        $this->db->table('monitoring_periode')->insert($insertData);

        // Redirect kembali menggunakan property class $this->prefix
        return redirect()->to(base_url($this->prefix . '/monitoringperiode'))->with('success', 'Periode monitoring baru berhasil ditambahkan.');
    }

    // =========================================================================
    // BARU: Method untuk Menampilkan Form Isian Laporan Mingguan per Pleton
    // =========================================================================
    public function buat_laporan()
    {
        $periodeId = $this->request->getVar('periode_id');

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Silakan pilih periode laporan terlebih dahulu.');
        }

        $data['title'] = 'Buat Laporan Monitoring';
        $data['prefix'] = $this->prefix;
        $data['periode_id'] = $periodeId;

        // Ambil detail info periode & angkatan untuk kop/header form menggunakan $this->db
        $data['periode'] = $this->db->table('monitoring_periode')
            ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
            ->where('monitoring_periode.id', $periodeId)
            ->get()->getRowArray();

        // PERBAIKAN: Menggunakan $this->db->table('pleton') agar konsisten dan tidak error lagi
        $data['list_pleton'] = $this->db->table('pleton')
            ->orderBy('nama_pleton', 'ASC')
            ->get()->getResultArray();

        return view('minsis/monitoring_laporan_buat', $data);
    }

    public function simpan_laporan()
    {
        $periodeId = $this->request->getPost('periode_id');
        $pleton = $this->request->getPost('pleton');

        // Tangkap data array bertingkat dari form input
        $bidangArr = $this->request->getPost('bidang');
        $subBidangArr = $this->request->getPost('sub_bidang');
        $indikatorArr = $this->request->getPost('indikator');
        $giatSerdikArr = $this->request->getPost('giat_serdik');
        $hasilDicapaiArr = $this->request->getPost('hasil_dicapai');
        $giatPengasuhArr = $this->request->getPost('giat_pengasuh');

        // Mulai Transaksi Database dengan mode strict agar mempermudah debugging
        $this->db->transException(true)->transStart();

        try {
            // 1. Hapus data lama untuk kombinasi periode & pleton yang sama agar tidak duplikat
            $this->db->table('laporan_monitoring_detail')
                ->where('periode_id', $periodeId)
                ->where('pleton', $pleton)
                ->delete();

            // 2. Lakukan looping bertingkat untuk menyimpan data baru
            if (!empty($bidangArr)) {
                foreach ($bidangArr as $bIdx => $namaBidang) {
                    if (trim($namaBidang) != '' && isset($subBidangArr[$bIdx])) {

                        // Looping sub-bidang di bawah bidang utama saat ini
                        foreach ($subBidangArr[$bIdx] as $sIdx => $namaSubBidang) {
                            if (trim($namaSubBidang) != '') {
                                $insertData = [
                                    'periode_id'    => $periodeId,
                                    'pleton'        => $pleton,
                                    'bidang'        => $namaBidang,
                                    'sub_bidang'    => $namaSubBidang,
                                    'indikator'     => $indikatorArr[$bIdx][$sIdx] ?? '',
                                    'giat_serdik'   => $giatSerdikArr[$bIdx][$sIdx] ?? '',
                                    'hasil_dicapai' => $hasilDicapaiArr[$bIdx][$sIdx] ?? '',
                                    'giat_pengasuh' => $giatPengasuhArr[$bIdx][$sIdx] ?? '',
                                    'created_at'    => date('Y-m-d H:i:s')
                                ];

                                $this->db->table('laporan_monitoring_detail')->insert($insertData);
                            }
                        }
                    }
                }
            }

            // Selesaikan Transaksi jika sukses semua
            $this->db->transComplete();
        } catch (\Exception $e) {
            // Rollback otomatis jika ada query gagal
            $this->db->transRollback();

            // Kembalikan ke form dan tampilkan pesan error yang sebenarnya terjadi di SQL
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan database: ' . $e->getMessage());
        }

        return redirect()->to(base_url($this->prefix . '/monitoringperiode'))
            ->with('success', 'Laporan Monitoring mingguan untuk ' . $pleton . ' berhasil disimpan.');
    }

    public function lihat_laporan()
    {
        $periodeId = $this->request->getVar('periode_id');
        $pletonId = $this->request->getVar('pleton_id'); // Tangkap filter pleton aktif jika ada di view

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Silakan pilih periode laporan terlebih dahulu.');
        }

        $data['title'] = 'Laporan Monitoring Mingguan';
        $data['prefix'] = $this->prefix;
        $data['periode_id'] = $periodeId;
        $data['pleton_id'] = $pletonId; // Teruskan ke view agar tombol export tahu pleton mana yang aktif

        // 1. Ambil detail info periode & angkatan
        $data['periode'] = $this->db->table('monitoring_periode')
            ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
            ->where('monitoring_periode.id', $periodeId)
            ->get()->getRowArray();

        if (!$data['periode']) {
            return redirect()->back()->with('error', 'Data periode tidak ditemukan.');
        }

        // 2. Ambil data pleton dengan filter jika user sedang membuka tab tertentu
        $dbBuilder = $this->db->table('pleton p')
            ->select('p.nama_pleton, lmd.*')
            ->join('laporan_monitoring_detail lmd', 'lmd.pleton = p.id AND lmd.periode_id = ' . $this->db->escape($periodeId), 'left');

        if (!empty($pletonId)) {
            $dbBuilder->where('p.id', $pletonId);
        }

        $rows = $dbBuilder->orderBy('p.nama_pleton', 'ASC')
            ->orderBy('lmd.id', 'ASC')
            ->get()->getResultArray();

        // 3. Kelompokkan data secara aman ke array multi-dimensi
        $laporanData = [];
        foreach ($rows as $row) {
            $pletonName = $row['nama_pleton'];
            $bidang = $row['bidang'] ?? '-';

            $laporanData[$pletonName][$bidang][] = $row;
        }

        $data['laporan_data'] = $laporanData;

        return view('minsis/monitoring_laporan_lihat', $data);
    }

    public function edit_laporan()
    {
        return $this->buat_laporan();
    }

    public function export_pdf()
    {
        $periodeId = $this->request->getVar('periode_id');
        $pletonNameFilter = $this->request->getVar('pleton_name'); // MENANGKAP KIRIMAN JAVASCRIPT

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Silakan pilih periode laporan terlebih dahulu.');
        }

        // 1. Ambil detail info periode & angkatan
        $periode = $this->db->table('monitoring_periode')
            ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
            ->where('monitoring_periode.id', $periodeId)
            ->get()->getRowArray();

        if (!$periode) {
            return redirect()->back()->with('error', 'Data periode tidak ditemukan.');
        }

        // 2. Ambil semua data pleton dan LEFT JOIN ke tabel detail laporan
        $dbBuilder = $this->db->table('pleton p')
            ->select('p.nama_pleton, lmd.*')
            ->join('laporan_monitoring_detail lmd', 'lmd.pleton = p.id AND lmd.periode_id = ' . $this->db->escape($periodeId), 'left');

        // PERBAIKAN UTAMA: Jika user memfilter pleton tertentu (misal TON B), batasi query hanya untuk Pleton tersebut
        if (!empty($pletonNameFilter)) {
            $dbBuilder->where('p.nama_pleton', $pletonNameFilter);
        }

        $rows = $dbBuilder->orderBy('p.nama_pleton', 'ASC')
            ->orderBy('lmd.id', 'ASC')
            ->get()->getResultArray();

        // 3. Kelompokkan data: [Nama Pleton][Nama Bidang][]
        $laporanData = [];
        foreach ($rows as $row) {
            $pletonName = $row['nama_pleton'];
            $bidang = $row['bidang'] ?? '-';
            $laporanData[$pletonName][$bidang][] = $row;
        }

        // 4. Proses Rendering menggunakan Dompdf
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);

        // Load html dari view khusus PDF
        $html = view('minsis/monitoring_laporan_pdf', [
            'periode'      => $periode,
            'laporan_data' => $laporanData
        ]);

        $dompdf->loadHtml($html);

        // Set ukuran kertas (A4) dan orientasi (Landscape karena tabelnya lebar)
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Buat nama file dinamis sesuai dengan pleton yang dicetak
        $suffixPleton = !empty($pletonNameFilter) ? "_" . url_title($pletonNameFilter, '_', true) : "_semua_pleton";
        $fileName = "Laporan_Monitoring_Minggu_Ke_" . ($periode['minggu_ke'] ?? '-') . $suffixPleton . "_" . url_title($periode['nama_angkatan'] ?? 'angkatan', '_', true) . ".pdf";

        // Stream hasil ke browser untuk langsung download
        $dompdf->stream($fileName, ["Attachment" => true]);
        exit();
    }

    // =========================================================================
    // BARU: Method untuk Export Laporan Monitoring ke MS Word (.doc)
    // =========================================================================
    public function export_word()
    {
        $db = \Config\Database::connect();
        $periodeId = $this->request->getVar('periode_id');

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Silakan pilih periode laporan terlebih dahulu.');
        }

        // 1. Ambil detail info periode & angkatan
        $periode = $db->table('monitoring_periode')
            ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
            ->where('monitoring_periode.id', $periodeId)
            ->get()->getRowArray();

        if (!$periode) {
            return redirect()->back()->with('error', 'Data periode tidak ditemukan.');
        }

        // 2. Ambil data rekapitulasi monitoring dengan query JOIN ke tabel aslinya
        // Query ini otomatis mengelompokkan data berdasarkan Pleton, Bidang, Sub-bidang (Indikator)
        $rows = $db->table('monitoring_hasil h')
            ->select('
                p.nama_pleton as pleton,
                b.nama_bidang as bidang,
                ind.judul as sub_bidang,
                ind.indikator,
                ind.giat_serdik,
                h.hasil_yang_dicapai as hasil_dicapai,
                h.catatan_pengasuh as giat_pengasuh
            ')
            ->join('siswa s', 's.id = h.siswa_id', 'inner')
            ->join('pleton p', 'p.id = s.pleton_id', 'inner')
            ->join('monitoring_indikator ind', 'ind.id = h.indikator_id', 'inner')
            ->join('monitoring_bidang b', 'b.id = ind.bidang_id', 'inner')
            ->where('h.periode_id', $periodeId)
            ->orderBy('p.nama_pleton', 'ASC')
            ->orderBy('b.urutan', 'ASC')
            ->orderBy('ind.urutan', 'ASC')
            ->get()->getResultArray();

        // 3. Strukturkan data agar sesuai dengan layout tabel view (dikelompokkan per Pleton -> Bidang)
        $laporanData = [];
        foreach ($rows as $row) {
            $pleton = $row['pleton'];
            $bidang = $row['bidang'];
            $laporanData[$pleton][$bidang][] = $row;
        }

        // Buat nama file dokumen dinamis
        $fileName = "Laporan_Monitoring_Minggu_Ke_" . ($periode['minggu_ke'] ?? '-') . "_" . url_title($periode['nama_angkatan'] ?? 'angkatan', '_', true) . ".doc";

        // Kirim header khusus agar browser mendownload sebagai MS Word (.doc)
        header("Content-Type: application/vnd.ms-word");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-disposition: attachment; filename=" . $fileName);

        // Kirim data ke view khusus Word
        return view('minsis/monitoring_laporan_word', [
            'periode'      => $periode,
            'laporan_data' => $laporanData
        ]);
    }
}
