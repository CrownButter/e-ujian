<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AngkatanModel;

class MonitoringPeriode extends BaseController
{
    protected $db;
    protected $angkatanModel;
    protected $prefix;

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

    public function buat_laporan()
    {
        $periodeId = $this->request->getVar('periode_id');
        $pletonSelected = $this->request->getVar('pleton'); // Ambil filter pilihan pleton dari tab/URL jika ada

        if (!$periodeId) {
            return redirect()->back()->with('error', 'Silakan pilih periode laporan terlebih dahulu.');
        }

        $data['title'] = 'Buat & Edit Laporan Monitoring';
        $data['prefix'] = $this->prefix;
        $data['periode_id'] = $periodeId;

        // Ambil detail info periode & angkatan untuk kop/header form
        $data['periode'] = $this->db->table('monitoring_periode')
            ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
            ->where('monitoring_periode.id', $periodeId)
            ->get()->getRowArray();

        // Ambil daftar pleton untuk navigasi pilihan tab di form atas
        $data['list_pleton'] = $this->db->table('pleton')
            ->orderBy('nama_pleton', 'ASC')
            ->get()->getResultArray();

        // Tetapkan default pleton aktif jika belum ada parameter yang dipilih
        if (empty($pletonSelected) && !empty($data['list_pleton'])) {
            $data['current_pleton'] = $data['list_pleton'][0]['id']; // Menggunakan ID pleton sebagai parameter
            $data['current_pleton_name'] = $data['list_pleton'][0]['nama_pleton'];
        } else {
            // Jika ada parameter, cari nama pletonnya
            $data['current_pleton'] = $pletonSelected;
            $pRow = $this->db->table('pleton')->where('id', $pletonSelected)->get()->getRowArray();
            $data['current_pleton_name'] = $pRow['nama_pleton'] ?? 'Pleton Tidak Diketahui';
        }

        /**
         * Mengambil data master instrumen/indikator yang sudah ada isinya untuk diedit,
         * atau data template kosong jika pleton ini belum pernah mengisi di periode terkait.
         */
        $rows = $this->db->table('laporan_monitoring_detail')
            ->where('periode_id', $periodeId)
            ->where('pleton', $data['current_pleton'])
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        if (empty($rows)) {
            // Mengambil master template structure dari data terdekat yang ada sebagai template form isian
            $rows = $this->db->table('laporan_monitoring_detail')
                ->select('bidang, sub_bidang, indikator, "" as giat_serdik, "" as hasil_dicapai, "" as giat_pengasuh')
                ->groupBy(['bidang', 'sub_bidang', 'indikator'])
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();
        }

        // Kelompokkan data berdasarkan Bidang Utama agar mempermudah rendering Rowspan di View
        $laporanData = [];
        foreach ($rows as $row) {
            $bidangName = $row['bidang'] ?? '-';
            $laporanData[$bidangName][] = $row;
        }

        $data['laporan_data'] = $laporanData;

        return view('minsis/monitoring_laporan_buat', $data);
    }


    public function edit_laporan()
    {
        $periodeId = $this->request->getGet('periode_id');
        $pletonName = $this->request->getGet('pleton_name');

        if (empty($periodeId) || empty($pletonName)) {
            return redirect()->back()->with('error', 'Parameter peleton atau periode tidak valid.');
        }

        // Instansiasi model
        $monitoringModel = new \App\Models\MonitoringLaporanModel();

        // 1. Ambil data detail periode & angkatan untuk header form
        $periode = $monitoringModel->db->table('monitoring_periode')
            ->select('monitoring_periode.*, angkatan.nama_angkatan, angkatan.tahun_angkatan')
            ->join('angkatan', 'angkatan.id = monitoring_periode.angkatan_id', 'inner')
            ->where('monitoring_periode.id', $periodeId)
            ->get()->getRowArray();

        if (!$periode) {
            return redirect()->back()->with('error', 'Data periode tidak ditemukan.');
        }

        // 2. Ambil list peleton dari tabel master peleton (untuk dropdown header & pencarian ID)
        $listPleton = $monitoringModel->db->table('pleton')->get()->getResultArray();

        // 3. Cari ID Pleton berdasarkan nama peleton yang dikirim dari URL parameter
        $currentPletonId = '';
        foreach ($listPleton as $plt) {
            if (trim($plt['nama_pleton']) == trim($pletonName)) {
                $currentPletonId = $plt['id'];
                break;
            }
        }

        // 4. Ambil data laporan existing menggunakan ID Peleton maupun Nama Peleton
        // Langkah aman ini menangani database jika terisi angka ID ataupun string nama teks
        $rawLaporan = $monitoringModel->db->table('laporan_monitoring_detail')
            ->where('periode_id', $periodeId)
            ->groupStart()
            ->where('pleton', $pletonName)
            ->orWhere('pleton', $currentPletonId)
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        // 5. MAPPING ARRAY: Kelompokkan data detail berdasarkan Bidang Utama untuk looping view
        $laporanData = [];
        if (!empty($rawLaporan)) {
            foreach ($rawLaporan as $row) {
                $bidangKey = $row['bidang'];

                $laporanData[$bidangKey][] = [
                    'sub_bidang'    => $row['sub_bidang'] ?? '',
                    'indikator'     => $row['indikator'] ?? '',
                    'giat_serdik'   => $row['giat_serdik'] ?? '',
                    'hasil_dicapai' => $row['hasil_dicapai'] ?? '',
                    'giat_pengasuh' => $row['giat_pengasuh'] ?? ''
                ];
            }
        }

        // Mengambil segment prefix pertama secara dinamis (misal: 'admin')
        $currentPrefix = service('request')->getUri()->getSegment(1);

        $data = [
            'title'               => 'Edit Laporan Monitoring',
            'prefix'              => $currentPrefix,
            'periode_id'          => $periodeId,
            'periode'             => $periode,
            'list_pleton'         => $listPleton,
            'current_pleton'      => $currentPletonId,
            'current_pleton_name' => $pletonName,
            'pleton_id'           => $currentPletonId,
            'laporan_data'        => $laporanData // Mengirimkan array terstruktur untuk foreach di view
        ];

        return view('minsis/monitoring_laporan_edit', $data);
    }


    public function simpan_laporan()
    {
        $monitoringModel = new \App\Models\MonitoringLaporanModel();

        $periodeId = $this->request->getPost('periode_id');
        $pletonInput = $this->request->getPost('pleton'); // Ini berisi ID angka dari form (misal: 1, 2)

        // Tangkap array multidimensi dari form input
        $bidangArr = $this->request->getPost('bidang');
        $subBidangArr = $this->request->getPost('sub_bidang');
        $indikatorArr = $this->request->getPost('indikator');
        $giatSerdikArr = $this->request->getPost('giat_serdik');
        $hasilDicapaiArr = $this->request->getPost('hasil_dicapai');
        $giatPengasuhArr = $this->request->getPost('giat_pengasuh');

        // Gunakan Database Transaction agar data aman
        $monitoringModel->db->transStart();

        // Lakukan pembersihan data lama (Bekerja sebagai fungsi Reset sebelum Update / Bypass saat Create)
        $monitoringModel->db->table('laporan_monitoring_detail')
            ->where('periode_id', $periodeId)
            ->where('pleton', $pletonInput)
            ->delete();

        // Proses looping data input bertingkat untuk menyimpan data aktual terbaru
        if (!empty($bidangArr)) {
            foreach ($bidangArr as $bIdx => $namaBidang) {
                if (trim($namaBidang) !== '' && isset($subBidangArr[$bIdx])) {
                    foreach ($subBidangArr[$bIdx] as $sIdx => $namaSubBidang) {

                        if (trim($namaSubBidang) !== '') {
                            $insertData = [
                                'periode_id'    => $periodeId,
                                'pleton'        => $pletonInput, // Menyimpan ID Pleton ke database
                                'bidang'        => $namaBidang,
                                'sub_bidang'    => $namaSubBidang,
                                'indikator'     => $indikatorArr[$bIdx][$sIdx] ?? '',
                                'giat_serdik'   => $giatSerdikArr[$bIdx][$sIdx] ?? '',
                                'hasil_dicapai' => $hasilDicapaiArr[$bIdx][$sIdx] ?? '',
                                'giat_pengasuh' => $giatPengasuhArr[$bIdx][$sIdx] ?? '',
                                'created_at'    => date('Y-m-d H:i:s')
                            ];
                            $monitoringModel->db->table('laporan_monitoring_detail')->insert($insertData);
                        }
                    }
                }
            }
        }

        $monitoringModel->db->transComplete();

        if ($monitoringModel->db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Gagal memproses data laporan monitoring.');
        }

        // --- PROSES REDIRECT CUSTOM KEMBALI KE LIHAT LAPORAN ---

        // 1. Ambil Nama Pleton asli dari tabel master pleton berdasarkan ID input
        $pletonRow = $monitoringModel->db->table('pleton')
            ->where('id', $pletonInput)
            ->get()->getRowArray();

        // Jika data peleton ditemukan, pakai namanya. Jika tidak (atau input sudah string nama), fallback ke input asal.
        $pletonParam = $pletonRow ? $pletonRow['nama_pleton'] : $pletonInput;

        // 2. Mengambil segment prefix pertama secara dinamis (misal: 'admin')
        $currentPrefix = service('request')->getUri()->getSegment(1);

        // 3. Alihkan langsung ke halaman lihat_laporan dengan parameter URL terisi lengkap
        return redirect()->to(base_url($currentPrefix . '/monitoringperiode/lihat_laporan?periode_id=' . $periodeId . '&pleton_name=' . urlencode($pletonParam)))
            ->with('success', 'Laporan Monitoring berhasil diperbarui.');
    }


    // 5. Menampilkan rekapitulasi data laporan untuk dilihat (Read)
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

    // 6. Cetak/Export data ke file PDF
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

        // Jika user memfilter pleton tertentu (misal TON B), batasi query hanya untuk Pleton tersebut
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

    // 7. Method untuk Export Laporan Monitoring ke MS Word (.doc)
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
