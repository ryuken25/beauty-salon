<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use App\Models\StylistModel;

class StylistController extends BaseController
{
    private const UPLOAD_DIR = FCPATH . 'uploads/stylist/';

    public function index()
    {
        // withDeleted(false) is the default — soft-deleted stylists stay hidden.
        $rows = (new StylistModel())->orderBy('is_active', 'DESC')->orderBy('nama')->findAll();
        return view('owner/stylist/index', ['rows' => $rows]);
    }

    public function store()
    {
        if (! $this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->collect();
        $foto = $this->handleUpload();
        if ($foto !== null) {
            $data['foto'] = $foto;
        }
        (new StylistModel())->insert($data);
        return redirect()->to('/owner/stylist')->with('success', 'Stylist ditambahkan.');
    }

    public function update(int $id)
    {
        $model = new StylistModel();
        $stylist = $model->find($id);
        if (! $stylist) {
            return redirect()->to('/owner/stylist')->with('error', 'Stylist tidak ditemukan.');
        }
        if (! $this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->collect();
        $foto = $this->handleUpload();
        if ($foto !== null) {
            $data['foto'] = $foto;
            $this->deleteOldPhoto($stylist['foto'] ?? null);
        }
        $model->update($id, $data);
        return redirect()->to('/owner/stylist')->with('success', 'Stylist diperbarui.');
    }

    public function delete(int $id)
    {
        $model = new StylistModel();
        $stylist = $model->find($id);
        if (! $stylist) {
            return redirect()->to('/owner/stylist')->with('error', 'Stylist tidak ditemukan.');
        }
        // Hard delete only when the stylist has no booking history; otherwise
        // soft-delete so historical bookings keep their stylist reference.
        if ($model->hasBookings($id)) {
            $model->delete($id); // soft delete (deleted_at = now)
            return redirect()->to('/owner/stylist')->with('success', 'Stylist dinonaktifkan (punya riwayat booking, data histori tetap aman).');
        }
        $this->deleteOldPhoto($stylist['foto'] ?? null);
        $model->delete($id, true); // purge — no bookings reference it
        return redirect()->to('/owner/stylist')->with('success', 'Stylist dihapus permanen.');
    }

    // ── helpers ───────────────────────────────────────────────────
    private function rules(): array
    {
        return [
            'nama' => 'required|min_length[2]|max_length[100]',
            'spesialisasi' => 'permit_empty|max_length[120]',
            'jam_kerja_mulai' => 'permit_empty|regex_match[/^\d{2}:\d{2}$/]',
            'jam_kerja_selesai' => 'permit_empty|regex_match[/^\d{2}:\d{2}$/]',
            'foto' => 'permit_empty|is_image[foto]|max_size[foto,2048]|mime_in[foto,image/png,image/jpg,image/jpeg,image/webp]',
        ];
    }

    private function collect(): array
    {
        return [
            'nama' => trim((string) $this->request->getPost('nama')),
            'spesialisasi' => trim((string) $this->request->getPost('spesialisasi')) ?: null,
            'jam_kerja_mulai' => $this->request->getPost('jam_kerja_mulai') ?: null,
            'jam_kerja_selesai' => $this->request->getPost('jam_kerja_selesai') ?: null,
            'is_active' => $this->request->getPost('is_active') === '0' ? 0 : 1,
        ];
    }

    private function handleUpload(): ?string
    {
        $file = $this->request->getFile('foto');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
        if (! is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0775, true);
        }
        $name = $file->getRandomName();
        $file->move(self::UPLOAD_DIR, $name);
        return 'uploads/stylist/' . $name;
    }

    private function deleteOldPhoto(?string $relPath): void
    {
        if ($relPath && is_file(FCPATH . $relPath)) {
            @unlink(FCPATH . $relPath);
        }
    }
}
