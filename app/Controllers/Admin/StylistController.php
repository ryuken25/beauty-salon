<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StylistModel;
use App\Models\StylistScheduleModel;

class StylistController extends BaseController
{
    public function index()
    {
        $rows = (new StylistModel())->orderBy('is_default', 'DESC')->orderBy('nama')->find();
        return view('admin/stylist/index', ['rows' => $rows]);
    }

    public function store()
    {
        $rules = ['nama' => 'required|min_length[2]', 'nomor_hp' => 'permit_empty|min_length[8]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        $isDefault = $this->request->getPost('is_default') ? 1 : 0;
        if ($isDefault) (new StylistModel())->builder()->where('is_default', 1)->update(['is_default' => 0]);
        $id = (new StylistModel())->insert([
            'nama' => $this->request->getPost('nama'),
            'nomor_hp' => $this->request->getPost('nomor_hp'),
            'peran' => $this->request->getPost('peran'),
            'is_default' => $isDefault,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);
        $hari = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($hari as $h) $rows[] = ['stylist_id' => $id, 'hari' => $h, 'jam_mulai' => '08:00:00', 'jam_selesai' => '19:00:00', 'is_libur' => 0, 'created_at' => $now, 'updated_at' => $now];
        db_connect()->table('stylist_schedules')->insertBatch($rows);
        return redirect()->to('/admin/stylist')->with('success', 'Stylist ditambahkan.');
    }

    public function update(int $id)
    {
        $model = new StylistModel();
        if (! $model->find($id)) return redirect()->to('/admin/stylist')->with('error', 'Stylist tidak ditemukan.');
        $isDefault = $this->request->getPost('is_default') ? 1 : 0;
        if ($isDefault) $model->builder()->where('id !=', $id)->update(['is_default' => 0]);
        $model->update($id, [
            'nama' => $this->request->getPost('nama'),
            'nomor_hp' => $this->request->getPost('nomor_hp'),
            'peran' => $this->request->getPost('peran'),
            'is_default' => $isDefault,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);
        return redirect()->to('/admin/stylist')->with('success', 'Stylist diperbarui.');
    }

    public function delete(int $id)
    {
        (new StylistModel())->update($id, ['is_active' => 0]);
        return redirect()->to('/admin/stylist')->with('success', 'Stylist dinon-aktifkan.');
    }

    public function jadwal(int $id)
    {
        $stylist = (new StylistModel())->find($id);
        if (! $stylist) return redirect()->to('/admin/stylist')->with('error', 'Stylist tidak ditemukan.');
        $hari = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        if ($this->request->getMethod() === 'POST') {
            foreach ($hari as $h) {
                $isLibur = (int) $this->request->getPost('libur_' . $h) ? 1 : 0;
                $row = (new StylistScheduleModel())->forStylist($id, $h);
                $data = [
                    'stylist_id' => $id, 'hari' => $h,
                    'jam_mulai' => $this->request->getPost('mulai_' . $h) ?: '08:00',
                    'jam_selesai' => $this->request->getPost('selesai_' . $h) ?: '19:00',
                    'is_libur' => $isLibur,
                ];
                if ($row) (new StylistScheduleModel())->update($row['id'], $data);
                else (new StylistScheduleModel())->insert($data);
            }
            return redirect()->to('/admin/stylist/' . $id . '/jadwal')->with('success', 'Jadwal diperbarui.');
        }
        $schedules = [];
        foreach ($hari as $h) {
            $row = (new StylistScheduleModel())->forStylist($id, $h);
            $schedules[$h] = $row ?: ['hari' => $h, 'jam_mulai' => '08:00', 'jam_selesai' => '19:00', 'is_libur' => 0];
        }
        return view('admin/stylist/jadwal', ['stylist' => $stylist, 'schedules' => $schedules]);
    }
}
