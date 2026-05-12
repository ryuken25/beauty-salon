<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LayananModel;

class LayananController extends BaseController
{
    public function index()
    {
        $rows = (new LayananModel())->orderBy('kategori')->orderBy('nama')->find();
        return view('admin/layanan/index', ['rows' => $rows]);
    }

    public function store()
    {
        $rules = [
            'nama' => 'required|min_length[2]|max_length[100]',
            'kategori' => 'required',
            'durasi_menit' => 'required|in_list[30,60,90,120,150,180,210,240]',
            'harga' => 'required|is_natural',
        ];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        (new LayananModel())->insert([
            'nama' => $this->request->getPost('nama'),
            'kategori' => $this->request->getPost('kategori'),
            'deskripsi' => $this->request->getPost('deskripsi'),
            'durasi_menit' => (int) $this->request->getPost('durasi_menit'),
            'harga' => (int) $this->request->getPost('harga'),
            'ikon' => $this->request->getPost('ikon') ?: 'bi-stars',
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);
        return redirect()->to('/admin/layanan')->with('success', 'Layanan ditambahkan.');
    }

    public function update(int $id)
    {
        $model = new LayananModel();
        if (! $model->find($id)) return redirect()->to('/admin/layanan')->with('error', 'Layanan tidak ditemukan.');
        $model->update($id, [
            'nama' => $this->request->getPost('nama'),
            'kategori' => $this->request->getPost('kategori'),
            'deskripsi' => $this->request->getPost('deskripsi'),
            'durasi_menit' => (int) $this->request->getPost('durasi_menit'),
            'harga' => (int) $this->request->getPost('harga'),
            'ikon' => $this->request->getPost('ikon') ?: 'bi-stars',
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);
        return redirect()->to('/admin/layanan')->with('success', 'Layanan diperbarui.');
    }

    public function delete(int $id)
    {
        (new LayananModel())->update($id, ['is_active' => 0]);
        return redirect()->to('/admin/layanan')->with('success', 'Layanan dinon-aktifkan.');
    }
}
