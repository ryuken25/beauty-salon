<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use App\Models\LayananModel;

class LayananController extends BaseController
{
    public function index()
    {
        $rows = (new LayananModel())->orderBy('kategori')->orderBy('nama')->find();
        return view('owner/layanan/index', ['rows' => $rows]);
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
        return redirect()->to('/owner/layanan')->with('success', 'Layanan ditambahkan.');
    }

    public function update(int $id)
    {
        $model = new LayananModel();
        if (! $model->find($id)) return redirect()->to('/owner/layanan')->with('error', 'Layanan tidak ditemukan.');
        $model->update($id, [
            'nama' => $this->request->getPost('nama'),
            'kategori' => $this->request->getPost('kategori'),
            'deskripsi' => $this->request->getPost('deskripsi'),
            'durasi_menit' => (int) $this->request->getPost('durasi_menit'),
            'harga' => (int) $this->request->getPost('harga'),
            'ikon' => $this->request->getPost('ikon') ?: 'bi-stars',
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);
        return redirect()->to('/owner/layanan')->with('success', 'Layanan diperbarui.');
    }

    public function delete(int $id)
    {
        $model = new LayananModel();
        $layanan = $model->find($id);
        if (! $layanan) {
            return redirect()->to('/owner/layanan')->with('error', 'Layanan tidak ditemukan.');
        }
        // Soft delete when the layanan has booking history (keeps old bookings
        // intact); hard delete only when it has never been booked.
        if ($model->hasBookings($id)) {
            $model->delete($id); // soft delete (deleted_at = now)
            return redirect()->to('/owner/layanan')->with('success', 'Layanan dihapus (punya riwayat booking — data histori tetap aman).');
        }
        $model->delete($id, true); // purge
        return redirect()->to('/owner/layanan')->with('success', 'Layanan dihapus permanen.');
    }
}
