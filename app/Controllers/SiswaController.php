<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\SiswaModel;
use App\Controllers\BaseController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;


class SiswaController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $roleModel = new RoleModel();
    }

    public function nominatif()
    {
        // Inisialisasi Model
        $siswaModel    = new \App\Models\SiswaModel();
        $pletonModel   = new \App\Models\PletonModel();
        $angkatanModel = new \App\Models\AngkatanModel();
        $roleModel     = new \App\Models\RoleModel();

        // Ambil parameter filter dari URL
        $angkatanId = $this->request->getGet('angkatan_id');
        $pletonId   = $this->request->getGet('pleton_id');
        $angkatanAktif = $angkatanModel->where('status', 1)->first();

        // Daftar angkatan aktif
        $listAngkatanAktif = $angkatanModel
            ->where('status', 1)
            ->findAll();

        // Daftar pleton
        $pletonList = $pletonModel->findAll();

        // ==========================
        // Ambil Data Siswa
        // ==========================
        if (!empty($pletonId) && $pletonId != 'all') {

            $siswaData = $siswaModel
                ->select('siswa.*, pleton.nama_pleton')
                ->join('pleton', 'pleton.id = siswa.pleton_id', 'left')
                ->where('siswa.angkatan_id', $angkatanId)
                ->where('siswa.pleton_id', $pletonId)
                ->findAll();
        } else {

            $siswaData = $siswaModel->getSiswaByAngkatanAktif($angkatanId);
        }

        // ==========================
        // Hitung jumlah siswa per pleton
        // ==========================
        $counts = [];

        foreach ($pletonList as $p) {
            $counts[$p['nama_pleton']] = 0;
        }

        $counts['Belum di set'] = 0;

        foreach ($siswaData as $row) {

            $namaPleton = empty($row['nama_pleton'])
                ? 'Belum di set'
                : $row['nama_pleton'];

            if (!isset($counts[$namaPleton])) {
                $counts[$namaPleton] = 0;
            }

            $counts[$namaPleton]++;
        }

        // ==========================
        // Data ke View
        // ==========================
        $data = [
            'title'            => 'Data Siswa',
            'siswa'            => $siswaData,
            'pleton_list'      => $pletonList,
            'angkatan_list'    => $listAngkatanAktif,
            'counts'           => $counts,
            'roles'            => $roleModel->findAll(),

            // Filter yang sedang dipilih
            'angkatan_id'      => $angkatanId,
            'pleton_id'        => $pletonId,
            'current_angkatan' => $angkatanId,
            'angkatanAktif'    => $angkatanAktif,
        ];

        return view('siswa/nominatif', $data);
    }

    public function export_pdf()
    {
        try {
            $siswaModel = new \App\Models\SiswaModel();
            $angkatanModel = new \App\Models\AngkatanModel();
            $pletonModel = new \App\Models\PletonModel(); // Tambahkan model pleton

            $angkatanId = $this->request->getGet('angkatan_id');
            $pletonId   = $this->request->getGet('pleton_id');

            // 1. Validasi & Ambil data angkatan
            if (empty($angkatanId)) {
                $angkatanAktif = $angkatanModel->where('status', 1)->first();
                $angkatanId = $angkatanAktif ? $angkatanAktif['id'] : null;
            }

            if (empty($angkatanId)) {
                throw new \Exception("Tidak ada angkatan aktif yang ditemukan.");
            }

            // 2. Ambil data nama angkatan untuk judul PDF
            $angkatanData = $angkatanModel->find($angkatanId);
            $namaAngkatan = $angkatanData['nama_angkatan'] ?? 'Semua Angkatan';

            // 3. Ambil data nama pleton untuk judul PDF
            $namaPleton = 'Semua Pleton';
            if (!empty($pletonId) && $pletonId !== 'all') {
                $pData = $pletonModel->find($pletonId);
                $namaPleton = $pData['nama_pleton'] ?? 'Pleton Tidak Ditemukan';
            }

            // 4. Query data siswa dengan JOIN
            $builder = $siswaModel->select('siswa.*, pleton.nama_pleton')
                ->join('pleton', 'pleton.id = siswa.pleton_id', 'left')
                ->where('siswa.angkatan_id', $angkatanId);

            if (!empty($pletonId) && $pletonId !== 'all') {
                $builder->where('siswa.pleton_id', $pletonId);
            }

            $siswaData = $builder->findAll();

            // 5. Render ke PDF
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new \Dompdf\Dompdf($options);

            $html = view('siswa/nominatif_pdf', [
                'siswa'        => $siswaData,
                'tanggal'      => date('d-m-Y'),
                'nama_angkatan' => $namaAngkatan,
                'nama_pleton'  => $namaPleton
            ]);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->stream('Laporan_Nominatif.pdf', ["Attachment" => true]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate PDF: ' . $e->getMessage());
        }
    }

    public function saveAssignSiswa()
    {
        $siswaIds = $this->request->getPost('siswa_ids');
        $pletonId = $this->request->getPost('pleton_id');

        if (empty($siswaIds) || empty($pletonId)) {
            return redirect()->back()->with('error', 'Pilih siswa dan pleton terlebih dahulu.');
        }

        $model = new SiswaModel();

        if ($model->assignPleton($siswaIds, (int)$pletonId)) {
            return redirect()->back()->with('success', 'Siswa berhasil ditempatkan ke pleton.');
        }

        return redirect()->back()->with('error', 'Gagal menyimpan data.');
    }

    public function deleteBatch()
    {
        $ids = $this->request->getPost('ids'); // Array ID dari tabel siswa

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada data dipilih.');
        }

        $siswaModel = new \App\Models\SiswaModel(); // Sesuaikan dengan nama model Anda
        $userModel  = new \App\Models\UserModel();
        $db         = \Config\Database::connect();

        // 1. Ambil data siswa yang akan dihapus untuk mendapatkan user_id
        $siswaList = $siswaModel->whereIn('id', $ids)->findAll();
        $userIds = array_column($siswaList, 'user_id'); // Mengambil semua user_id ke dalam array

        // 2. Mulai Transaksi Database
        $db->transStart();

        // 3. Hapus dari tabel SISWA
        $siswaModel->whereIn('id', $ids)->delete();

        // 4. Hapus dari tabel USERS (jika userIds tidak kosong)
        if (!empty($userIds)) {
            $userModel->whereIn('id', $userIds)->delete();
        }

        // 5. Selesaikan Transaksi
        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menghapus data.');
        }

        return redirect()->back()->with('success', 'Data siswa dan akun user berhasil dihapus.');
    }

    public function edit($id)
    {
        $siswaModel = new \App\Models\SiswaModel();

        // Ambil data siswa berdasarkan ID lengkap dengan relasi struktur & pejabatnya
        $siswa = $siswaModel->getSiswaDetailWithPejabat($id);

        if (empty($siswa)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Data siswa dengan ID $id tidak ditemukan.");
        }

        $data = [
            'title' => 'Profil Siswa',
            'siswa' => $siswa
        ];

        // dd($data['siswa']);

        return view('siswa/edit', $data);
    }

    public function update($id)
    {
        $siswaModel = new \App\Models\SiswaModel();

        $data = [
            'nama'      => $this->request->getPost('nama'),
            'nosis'     => $this->request->getPost('nosis'),
            'pleton_id' => $this->request->getPost('pleton_id'),
        ];

        $siswaModel->update($id, $data);
        return redirect()->to('/admin/siswa/nominatif')->with('success', 'Data berhasil diupdate');
    }

    public function importSiswa()
    {
        // 1. Tambahkan durasi eksekusi dan memori untuk proses besar
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        $file = $this->request->getFile('file_excel');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid!');
        }

        $angkatanModel = new \App\Models\AngkatanModel();
        $userModel     = new \App\Models\UserModel();
        $siswaModel    = new \App\Models\SiswaModel();

        $angkatanAktif = $angkatanModel->where('status', 1)->first();
        if (!$angkatanAktif) {
            return redirect()->back()->with('error', 'Tidak ada angkatan aktif!');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $data = $spreadsheet->getActiveSheet()->toArray();

        // 2. Optimasi: Ambil semua nosis yang sudah ada sekali saja di awal
        $existingNosis = $siswaModel->findColumn('nosis') ?? [];

        $userBatch = [];
        $siswaBatch = [];
        $skippedSiswa = [];

        foreach ($data as $index => $row) {
            if ($index == 0) continue; // Lewati header

            $nosis    = $row[0];
            $password = (string)$row[1];
            $nama     = $row[3];

            // Cek duplikat dari array yang sudah diambil (jauh lebih cepat dari query per baris)
            if (in_array($nosis, $existingNosis)) {
                $skippedSiswa[] = ['nosis' => $nosis, 'nama' => $nama];
                continue;
            }

            // Tambahkan ke array batch
            $userBatch[] = [
                'username' => $nosis,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role_id'  => 7
            ];

            // Simpan referensi nosis agar tidak terjadi duplikat dalam 1 file yang sama
            $existingNosis[] = $nosis;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 3. Insert Users secara batch
        if (!empty($userBatch)) {
            $userModel->insertBatch($userBatch);

            // Ambil ID user yang baru saja diinsert
            // Karena insertBatch tidak mengembalikan ID, kita ambil berdasarkan username
            $insertedUsers = $userModel->whereIn('username', array_column($userBatch, 'username'))->findAll();

            foreach ($insertedUsers as $u) {
                $siswaBatch[] = [
                    'user_id'     => $u['id'],
                    'nama'        => $data[array_search($u['username'], array_column($data, 0))][3],
                    'nosis'       => $u['username'],
                    'angkatan_id' => $angkatanAktif['id'],
                    'role_id'     => 7
                ];
            }

            // 4. Insert Siswa secara batch
            $siswaModel->insertBatch($siswaBatch);
        }

        $db->transComplete();

        return redirect()->to('/admin/siswa/nominatif')
            ->with('success', count($siswaBatch) . " siswa berhasil diimpor.")
            ->with('skipped_data', $skippedSiswa);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Mengatur Header sesuai urutan file Anda
        $sheet->setCellValue('A1', 'Username');
        $sheet->setCellValue('B1', 'Password');
        $sheet->setCellValue('C1', 'Role');
        $sheet->setCellValue('D1', 'Nama');
        $sheet->setCellValue('E1', 'Nomor Induk');

        // Memberi warna latar belakang pada header agar menarik
        $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFF00'); // Kuning

        // Mengatur lebar kolom agar rapi
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Template_Import_Siswa.xlsx';

        // Header untuk memaksa download di browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function deleteSiswa($id)
    {
        // 1. Inisialisasi Model & Database
        $siswaModel = new \App\Models\SiswaModel(); // Pastikan nama model sesuai
        $userModel  = new \App\Models\UserModel();
        $db         = \Config\Database::connect();

        // 2. Ambil data siswa untuk mendapatkan user_id sebelum dihapus
        $siswa = $siswaModel->find($id);

        if (!$siswa) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        // 3. Mulai Transaksi Database
        $db->transStart();

        // 4. Hapus dari tabel SISWA
        $siswaModel->delete($id);

        // 5. Hapus dari tabel USERS menggunakan user_id yang didapat dari data siswa
        if (!empty($siswa['user_id'])) {
            $userModel->delete($siswa['user_id']);
        }

        // 6. Selesaikan Transaksi
        $db->transComplete();

        // 7. Cek apakah transaksi sukses
        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menghapus data.');
        }

        return redirect()->to('/admin/siswa/nominatif')->with('success', 'Data siswa dan akun user berhasil dihapus.');
    }

    public function profil()
    {
        $userId = session()->get('user_id');
        $roleId = session()->get('role_id');
        $db = \Config\Database::connect();

        $data['user'] = null;

        if ($roleId == 7) {
            // Query untuk Siswa dengan JOIN ke tabel relasi
            $data['user'] = $db->table('siswa s')
                ->select('s.*, p.nama_pleton, k.nama_kompi, b.nama_batalyon, dt.nama as nama_danton, dk.nama as nama_danki')
                ->join('pleton p', 'p.id = s.pleton_id', 'left')
                ->join('kompi k', 'k.id = p.kompi_id', 'left')
                ->join('batalyon b', 'b.id = k.batalyon_id', 'left')
                // Join ke tabel pegawai untuk Danton dan Danki
                ->join('pegawai dt', 'dt.nomor_induk = p.danton_id', 'left')
                ->join('pegawai dk', 'dk.nomor_induk = k.danki_id', 'left')
                ->where('s.user_id', $userId)
                ->get()->getRowArray();
        } else {
            // --- LOGIKA PEGAWAI/ADMIN/DLL ---
            $data['user'] = $db->table('pegawai')->where('user_id', $userId)->get()->getRowArray();

            // Fallback jika tidak ada di tabel pegawai
            if (!$data['user']) {
                $data['user'] = $db->table('profiles')->where('user_id', $userId)->get()->getRowArray();
            }
        }

        // Jika data tetap kosong, beri default
        if (!$data['user']) {
            $data['user'] = [
                'nama' => 'User',
                'nomor_induk' => '-',
                'nosis' => '-',
                'foto' => 'default.png'
            ];
        }

        return view('users/profil', $data);
    }

    public function getAllSiswaJson()
    {
        $siswaModel = new \App\Models\SiswaModel();
        $dataSiswa = $siswaModel->findAll(); // atau sesuaikan query-nya

        return $this->response->setJSON($dataSiswa);
    }
}
