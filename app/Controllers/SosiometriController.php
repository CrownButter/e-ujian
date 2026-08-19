<?php

namespace App\Controllers;

use App\Models\SosiometriModel;

class SosiometriController extends BaseController
{
    protected $materiModel;
    public function __construct()
    {
        $this->materiModel = new SosiometriModel();
    }

    public function index()
    {
        $prefix = service('uri')->getSegment(1);
        $data = [
            'title'  => 'Sosio Metri',
            'materi' => $this->materiModel->findAll(),
            'prefix'  => $prefix
        ];
        return view('sosiometri/index', $data);
    }


    public function sosiobaca($slug = null)
    {

        $materi = $this->materiModel->where('slug', $slug)->first();
        $prefix = service('uri')->getSegment(1);
        if (!$materi) {
            return redirect()->to('/sosiometri')->with('error', 'Materi tidak ditemukan.');
        }

        $data = [
            'materi' => $materi,
            'isSiswa' => (session()->get('role_id') == 7),
            'prefix'  => $prefix
        ];

        return view('sosiometri/sosiobaca', $data);
    }

    public function store()
    {
        $materiModel = new SosiometriModel();

        // 1. Validasi Input (Server Side)
        $rules = [
            'judul'    => 'required|min_length[3]',
            'file_pdf' => 'uploaded[file_pdf]|ext_in[file_pdf,pdf]|max_size[file_pdf,20480]', // 20MB
        ];

        // Tambahkan validasi cover hanya jika ada file yang diupload
        if ($this->request->getFile('cover_img')->isValid()) {
            $rules['cover_img'] = 'is_image[cover_img]|ext_in[cover_img,jpg,jpeg,png]|max_size[cover_img,2048]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // 2. Proses Upload PDF
        $pdfFile = $this->request->getFile('file_pdf');
        $pdfName = $pdfFile->getRandomName();
        $pdfFile->move('assets/dist/pdf/', $pdfName);
        $judul = $this->request->getPost('judul');
        $slug = url_title($judul, '-', true);

        // 3. Proses Upload Cover
        $coverFile = $this->request->getFile('cover_img');
        if ($coverFile->isValid() && !$coverFile->hasMoved()) {
            $coverName = $coverFile->getRandomName();
            $coverFile->move('assets/dist/img/', $coverName);
        } else {
            $coverName = 'coverbook.png';
        }

        // 4. Simpan ke Database
        $materiModel->save([
            'judul'     => $judul,
            'slug'      => $slug,
            'file_pdf'  => $pdfName,
            'cover_img' => $coverName
        ]);

        return redirect()->to('/admin/sosiometri')->with('success', 'Materi berhasil diunggah!');
    }

    public function update($id)
    {
        $materiModel = new \App\Models\SosiometriModel();
        $dataLama = $materiModel->find($id);

        // 1. Validasi & Slug
        $judul = $this->request->getPost('judul');
        $slug = url_title($judul, '-', true);

        // 2. Proses File
        $filePdf = $this->request->getFile('file_pdf');
        $fileCover = $this->request->getFile('cover_img');

        $updateData = [
            'judul' => $judul,
            'slug'  => $slug
        ];

        // Cek jika ada file PDF baru
        if ($filePdf && $filePdf->isValid() && !$filePdf->hasMoved()) {
            unlink('assets/dist/pdf/' . $dataLama['file_pdf']); // Hapus file lama
            $newName = $filePdf->getRandomName();
            $filePdf->move('assets/dist/pdf/', $newName);
            $updateData['file_pdf'] = $newName;
        }

        // Cek jika ada file Cover baru
        if ($fileCover && $fileCover->isValid() && !$fileCover->hasMoved()) {
            unlink('assets/dist/img/' . $dataLama['cover_img']); // Hapus cover lama
            $newName = $fileCover->getRandomName();
            $fileCover->move('assets/dist/img/', $newName);
            $updateData['cover_img'] = $newName;
        }

        $materiModel->update($id, $updateData);

        // Kirim session untuk ditangkap SweetAlert
        return redirect()->to('/sosiometri')->with('status', 'success')->with('message', 'Materi berhasil diupdate!');
    }

    // Fungsi Delete
    public function delete($id)
    {
        // Opsional: Hapus file fisik dari folder assets/dist/img sebelum hapus dari DB
        $this->materiModel->delete($id);
        return redirect()->to('/sosiometri')->with('success', 'Materi berhasil dihapus.');
    }
}
