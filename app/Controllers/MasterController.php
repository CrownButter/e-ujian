<?php

namespace App\Controllers;

use App\Models\AngkatanModel;
use App\Models\PletonModel;
use App\Models\RoleModel;
use App\Models\KompiModel;
use App\Models\BatalyonModel;

class MasterController extends BaseController
{

    public function data_referensi()
    {
        $db = \Config\Database::connect();

        $batalyonModel = new BatalyonModel();
        $angkatanModel = new AngkatanModel();
        $pletonModel   = new PletonModel();
        $roleModel     = new RoleModel();
        $pegawaiModel  = new \App\Models\PegawaiModel();
        $kompiModel    = new \App\Models\KompiModel();

        // DIPERBAIKI: Menambahkan parameter role_id untuk Danyon (misal: 3, sesuaikan dengan database Anda)
        $danyonTersedia = $pegawaiModel->select('pegawai.nama, pegawai.nomor_induk')
            ->where('role_id', 3)
            ->whereNotIn('nomor_induk', function ($builder) {
                return $builder->select('danyon_id')->from('batalyon')->where('danyon_id IS NOT NULL');
            })
            ->findAll();

        $dankiTersedia = $pegawaiModel->select('pegawai.nama, pegawai.nomor_induk, pangkat.nama_pangkat')
            ->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left')
            ->where('pegawai.role_id', 5)
            ->whereNotIn('nomor_induk', function ($builder) {
                return $builder->select('danki_id')->from('kompi')->where('danki_id IS NOT NULL');
            })
            ->findAll();

        // 3. DANTON: Pegawai role 4, belum menjabat di tabel pleton (danton_id)
        $dantonTersedia = $pegawaiModel->select('pegawai.nama, pegawai.nomor_induk')
            ->where('role_id', 4)
            ->whereNotIn('nomor_induk', function ($builder) {
                return $builder->select('danton_id')->from('pleton')->where('danton_id IS NOT NULL');
            })
            ->findAll();

        $batalyonTersedia = $batalyonModel->select('batalyon.*, pegawai.nama as nama_danyon')
            ->join('pegawai', 'pegawai.nomor_induk = batalyon.danyon_id', 'left')
            ->findAll();

        $pegwaiAll = $pegawaiModel->findAll();

        $data = [
            'title'         => 'Master Data Referensi',
            'angkatan'      => $angkatanModel->findAll(),
            'pleton'        => $pletonModel->getPletonWithPegawai(),
            'roles'         => $roleModel->findAll(),
            'kompi'         => $kompiModel->getKompiWithPegawai(),
            'pegawai'       => $pegawaiModel->get_pegawai_with_roles(),
            'batalyon'      => $batalyonModel->getBatalyonWithPegawai(),
            'batalyonSedia' => $batalyonTersedia,
            'danyon'        => $danyonTersedia,
            'danki'         => $dankiTersedia,
            'danton'        => $dantonTersedia,
            'pegawaiAll'    => $pegwaiAll
        ];

        return view('master/data_referensi', $data);
    }

    public function storeAngkatan()
    {
        $namaAngkatan    = $this->request->getPost('nama_angkatan');
        $tahunAngkatan   = $this->request->getPost('tahun_angkatan');
        $tanggalMulai    = $this->request->getPost('tanggal_mulai');
        $tanggalBerakhir = $this->request->getPost('tanggal_berakhir');
        $status          = $this->request->getPost('status');

        $angkatanModel = new \App\Models\AngkatanModel();

        $cek = $angkatanModel->where('nama_angkatan', $namaAngkatan)->first();

        if ($cek) {
            return redirect()->back()->with('error', 'Angkatan dengan nama tersebut sudah ada!');
        }

        $angkatanModel->save([
            'nama_angkatan'    => $namaAngkatan,
            'tahun_angkatan'   => $tahunAngkatan,
            'tanggal_mulai'    => !empty($tanggalMulai) ? $tanggalMulai : null,
            'tanggal_berakhir' => !empty($tanggalBerakhir) ? $tanggalBerakhir : null,
            'status'           => $status ?? 1
        ]);

        return redirect()->to('/admin/master/data_referensi')->with('success', 'Angkatan berhasil ditambah');
    }

    public function toggleAngkatan($id)
    {
        $db = \Config\Database::connect();

        $angkatan = $db->table('angkatan')->where('id', $id)->get()->getRowArray();

        if ($angkatan) {
            $newStatus = ($angkatan['status'] == 1) ? 0 : 1;

            $db->table('angkatan')->where('id', $id)->update(['status' => $newStatus]);

            return redirect()->back()->with('success', 'Status angkatan berhasil diubah!');
        }

        return redirect()->back()->with('error', 'Data tidak ditemukan!');
    }

    public function updateAngkatan($id)
    {
        $namaAngkatan    = $this->request->getPost('nama_angkatan');
        $tahunAngkatan   = $this->request->getPost('tahun_angkatan');
        $tanggalMulai    = $this->request->getPost('tanggal_mulai');
        $tanggalBerakhir = $this->request->getPost('tanggal_berakhir');
        $status          = $this->request->getPost('status');

        $angkatanModel = new \App\Models\AngkatanModel();

        $cek = $angkatanModel->where('nama_angkatan', $namaAngkatan)
            ->where('id !=', $id)
            ->first();

        if ($cek) {
            return redirect()->back()->with('error', 'Nama Angkatan sudah terpakai!');
        }

        $angkatanModel->save([
            'id'               => $id,
            'nama_angkatan'    => $namaAngkatan,
            'tahun_angkatan'   => $tahunAngkatan,
            'tanggal_mulai'    => !empty($tanggalMulai) ? $tanggalMulai : null,
            'tanggal_berakhir' => !empty($tanggalBerakhir) ? $tanggalBerakhir : null,
            'status'           => $status
        ]);

        return redirect()->to('/admin/master/data_referensi')->with('success', 'Angkatan berhasil diupdate!');
    }

    public function storeBatalyon()
    {
        // 1. Ambil semua input dari form
        $namaBatalyon = $this->request->getPost('nama_batalyon');
        $danyon     = $this->request->getPost('danyon_id');

        $batalyonModel = new \App\Models\BatalyonModel();

        // 2. Cek apakah nama sudah ada
        $cek = $batalyonModel->where('nama_batalyon', $namaBatalyon)->first();

        if ($cek) {
            return redirect()->back()->with('error', 'Batalyon dengan nama tersebut sudah ada!');
        }

        // 3. Simpan data lengkap beserta danyon_id
        $batalyonModel->save([
            'nama_batalyon' => $namaBatalyon,
            'danyon_id'     => $danyon
        ]);

        return redirect()->to('/admin/master/data_referensi')->with('success', 'Batalyon berhasil ditambah');
    }

    public function updateBatalyon($id)
    {
        $batalyonModel = new \App\Models\BatalyonModel();
        $rules = [
            'nama_batalyon' => 'required',
            'danyon_id'     => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Data tidak valid!');
        }

        $data = [
            'nama_batalyon' => $this->request->getPost('nama_batalyon'),
            'danyon_id'     => $this->request->getPost('danyon_id')
        ];

        $update = $batalyonModel->update($id, $data);

        if ($update) {
            return redirect()->to('/admin/master/data_referensi')->with('success', 'Batalyon berhasil diupdate.');
        } else {
            return redirect()->back()->with('error', 'Gagal mengupdate data.');
        }
    }

    public function deleteBatalyon($id)
    {
        $batalyonModel = new \App\Models\BatalyonModel();

        // Cek apakah data ada
        $batalyon = $batalyonModel->find($id);
        if (!$batalyon) {
            return redirect()->back()->with('error', 'Batalyon tidak ditemukan.');
        }

        // Proses hapus
        if ($batalyonModel->delete($id)) {
            return redirect()->to('/admin/master/data_referensi')->with('success', 'Batalyon berhasil dihapus.');
        } else {
            return redirect()->back()->with('error', 'Gagal menghapus batalyon.');
        }
    }

    public function storeKompi()
    {
        (new KompiModel())->save([
            'batalyon_id'  => $this->request->getPost('batalyon_id'),
            'nama_kompi'   => $this->request->getPost('nama_kompi'),
            'danki_id'   => $this->request->getPost('danki_id')
        ]);
        return redirect()->to('/admin/master/data_referensi')->with('success', 'Kompi berhasil ditambah');
    }

    public function updateKompi($id)
    {
        $kompiModel = new \App\Models\KompiModel();

        // Pastikan data yang diterima sesuai dengan form Anda
        $data = [
            'batalyon_id'  => $this->request->getPost('batalyon_id'),
            'nama_kompi'   => $this->request->getPost('nama_kompi'),
            'danki_id'     => $this->request->getPost('danki_id')
        ];

        // Melakukan update berdasarkan ID
        $kompiModel->update($id, $data);

        return redirect()->to('/admin/master/data_referensi')->with('success', 'Kompi berhasil diperbarui');
    }

    public function deleteKompi($id)
    {
        $kompiModel = new \App\Models\KompiModel();

        $kompi = $kompiModel->find($id);

        if (!$kompi) {
            return redirect()->to('/admin/master/kompi')
                ->with('error', 'Data Kompi tidak ditemukan!');
        }

        try {
            $kompiModel->delete($id);

            return redirect()->to('/admin/master/data_referensi')
                ->with('pesan', 'Kompi berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->to('/admin/master/data_referensi')
                ->with('error', 'Gagal menghapus! Pastikan tidak ada Pleton yang terdaftar di Kompi ini.');
        }
    }

    public function tambahPleton()
    {
        (new PletonModel())->save([
            'kompi_id'    => $this->request->getPost('kompi_id'),
            'nama_pleton' => $this->request->getPost('nama_pleton'),
            'danton_id'   => $this->request->getPost('danton_id')
        ]);
        return redirect()->to('admin/master/data_referensi')->with('success', 'Pleton berhasil ditambah');
    }

    public function updatePleton($id)
    {
        // Menggunakan ID yang dikirim dari URL
        (new PletonModel())->update($id, [
            'kompi_id'    => $this->request->getPost('kompi_id'),
            'nama_pleton' => $this->request->getPost('nama_pleton'),
            'danton_id'   => $this->request->getPost('danton_id')
        ]);

        return redirect()->to('admin/master/data_referensi')->with('pesan', 'Pleton berhasil diupdate');
    }

    // Di PletonController.php (fungsi delete)
    public function deletePleton($id)
    {
        $pletonModel = new \App\Models\PletonModel();
        if ($pletonModel->delete($id)) {
            return redirect()->to('/admin/master/data_referensi')->with('pesan', 'Pleton berhasil dihapus!');
        }
    }


    public function manage_siswa_pleton()
    {
        $siswaModel = new \App\Models\SiswaModel();
        $pletonModel = new \App\Models\PletonModel();

        $data = [
            'title'       => 'Manage siswa pleton',
            // Filter: hanya ambil siswa yang pleton_id-nya null atau 0
            'siswa_list'  => $siswaModel->where('pleton_id IS NULL')
                ->orWhere('pleton_id', 0)
                ->findAll(),
            'pleton_list' => $pletonModel->findAll(),
            // Ambil hanya yang sudah punya pleton
            'siswaInPleton' => $siswaModel->getSiswaWithPleton()
        ];

        return view('master/assign_siswa', $data);
    }
}
