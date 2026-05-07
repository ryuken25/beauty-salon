<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StylistModel;

class StylistController extends BaseController
{
    private StylistModel $m;

    public function __construct()
    {
        $this->m = new StylistModel();
    }

    public function index()
    {
        return view('admin/stylists/index', ['stylists' => $this->m->withDeleted()->findAll()]);
    }

    public function new()
    {
        return view('admin/stylists/form', ['stylist' => null]);
    }

    public function create()
    {
        $data = $this->validData();
        if ($data === null) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $id = $this->m->insert($data);
        $this->seedHours((int) $id);

        return redirect()->to('/admin/stylist')->with('success', 'Stylist berhasil ditambahkan.');
    }

    public function edit($id = null)
    {
        $stylist = $this->m->withDeleted()->find($id);
        if (! $stylist) {
            return redirect()->to('/admin/stylist')->with('error', 'Stylist tidak ditemukan.');
        }

        return view('admin/stylists/form', ['stylist' => $stylist]);
    }

    public function update($id = null)
    {
        if (! $this->m->withDeleted()->find($id)) {
            return redirect()->to('/admin/stylist')->with('error', 'Stylist tidak ditemukan.');
        }

        $data = $this->validData();
        if ($data === null) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->m->update($id, $data);

        return redirect()->to('/admin/stylist')->with('success', 'Stylist berhasil diperbarui.');
    }

    public function delete($id = null)
    {
        if (! $this->m->withDeleted()->find($id)) {
            return redirect()->to('/admin/stylist')->with('error', 'Stylist tidak ditemukan.');
        }

        $this->m->update($id, ['is_active' => 0]);
        $this->m->delete($id);

        return redirect()->to('/admin/stylist')->with('success', 'Stylist dinonaktifkan/dihapus lunak agar booking historis tetap aman.');
    }

    public function hours($id)
    {
        $db = db_connect();
        if (! $this->m->find($id)) {
            return redirect()->to('/admin/stylist')->with('error', 'Stylist tidak ditemukan atau tidak aktif.');
        }

        if ($this->request->getMethod() === 'POST') {
            for ($d = 0; $d <= 6; $d++) {
                $start = $this->request->getPost("start_$d") ?: '08:00';
                $end = $this->request->getPost("end_$d") ?: '19:00';
                if (! $this->validTime($start) || ! $this->validTime($end) || strtotime($end) <= strtotime($start)) {
                    return redirect()->back()->withInput()->with('error', 'Jam kerja harus valid dan jam selesai harus lebih besar dari jam mulai.');
                }

                $row = [
                    'stylist_id' => $id,
                    'day_of_week' => $d,
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_working' => $this->request->getPost("work_$d") ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $exists = $db->table('stylist_working_hours')->where(['stylist_id' => $id, 'day_of_week' => $d])->get()->getRowArray();
                $exists ? $db->table('stylist_working_hours')->where('id', $exists['id'])->update($row) : $db->table('stylist_working_hours')->insert($row + ['created_at' => date('Y-m-d H:i:s')]);
            }

            return redirect()->back()->with('success', 'Jam kerja stylist diperbarui.');
        }

        return view('admin/stylists/hours', ['stylist' => $this->m->find($id), 'hours' => $db->table('stylist_working_hours')->where('stylist_id', $id)->get()->getResultArray()]);
    }

    private function validData(): ?array
    {
        if (! $this->validate(['name' => 'required|max_length[120]', 'phone' => 'permit_empty|min_length[8]|max_length[30]'])) {
            return null;
        }

        return ['name' => $this->request->getPost('name'), 'phone' => $this->request->getPost('phone'), 'is_owner' => $this->request->getPost('is_owner') ? 1 : 0, 'is_active' => $this->request->getPost('is_active') ? 1 : 0];
    }

    private function validTime(string $time): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):(00|30)$/', $time);
    }

    private function seedHours(int $id): void
    {
        $db = db_connect();
        for ($d = 0; $d <= 6; $d++) {
            $db->table('stylist_working_hours')->insert(['stylist_id' => $id, 'day_of_week' => $d, 'start_time' => '08:00', 'end_time' => '19:00', 'is_working' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
