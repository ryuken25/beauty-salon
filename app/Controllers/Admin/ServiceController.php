<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ServiceModel;

class ServiceController extends BaseController
{
    private ServiceModel $m;

    public function __construct()
    {
        $this->m = new ServiceModel();
    }

    public function index()
    {
        return view('admin/services/index', ['services' => $this->m->withDeleted()->findAll()]);
    }

    public function new()
    {
        return view('admin/services/form', ['service' => null]);
    }

    public function create()
    {
        $data = $this->validData();
        if ($data === null) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->m->insert($data);

        return redirect()->to('/admin/layanan')->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function edit($id = null)
    {
        $service = $this->m->withDeleted()->find($id);
        if (! $service) {
            return redirect()->to('/admin/layanan')->with('error', 'Layanan tidak ditemukan.');
        }

        return view('admin/services/form', ['service' => $service]);
    }

    public function update($id = null)
    {
        if (! $this->m->withDeleted()->find($id)) {
            return redirect()->to('/admin/layanan')->with('error', 'Layanan tidak ditemukan.');
        }

        $data = $this->validData();
        if ($data === null) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->m->update($id, $data);

        return redirect()->to('/admin/layanan')->with('success', 'Layanan berhasil diperbarui.');
    }

    public function delete($id = null)
    {
        if (! $this->m->withDeleted()->find($id)) {
            return redirect()->to('/admin/layanan')->with('error', 'Layanan tidak ditemukan.');
        }

        $this->m->update($id, ['is_active' => 0]);
        $this->m->delete($id);

        return redirect()->to('/admin/layanan')->with('success', 'Layanan dinonaktifkan/dihapus lunak agar booking historis tetap aman.');
    }

    private function validData(): ?array
    {
        $rules = [
            'category' => 'required|max_length[100]',
            'name' => 'required|max_length[160]',
            'duration_minutes' => 'required|integer|greater_than_equal_to[30]',
            'price' => 'required|decimal|greater_than_equal_to[0]',
        ];
        if (! $this->validate($rules)) {
            return null;
        }

        $duration = (int) $this->request->getPost('duration_minutes');
        if ($duration % 30 !== 0) {
            $this->validator->setError('duration_minutes', 'Durasi layanan wajib kelipatan 30 menit.');

            return null;
        }

        return [
            'category' => $this->request->getPost('category'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'duration_minutes' => $duration,
            'price' => (float) $this->request->getPost('price'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ];
    }
}
