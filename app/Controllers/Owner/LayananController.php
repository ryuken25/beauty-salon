<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use App\Models\LayananModel;

class LayananController extends BaseController
{
    private const UPLOAD_SUBDIR = 'uploads/layanan';

    public function index()
    {
        $rows = (new LayananModel())->orderBy('kategori')->orderBy('nama')->find();
        return view('owner/layanan/index', [
            'rows' => $rows,
            'ikon_list' => $this->ikonList(),
        ]);
    }

    public function store()
    {
        $rules = [
            'nama' => 'required|min_length[2]|max_length[100]',
            'kategori' => 'required',
            'durasi_menit' => 'required|in_list[30,60,90,120,150,180,210,240]',
            'harga' => 'required|is_natural',
            'promo_persen' => 'permit_empty|is_natural|less_than_equal_to[100]',
            'promo_deskripsi' => 'permit_empty|max_length[255]',
            'promo_mulai' => 'permit_empty|valid_date[Y-m-d]',
            'promo_selesai' => 'permit_empty|valid_date[Y-m-d]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $paths = $this->moveUploadedImages('gambar');
        if ($paths === false) {
            return redirect()->back()->withInput()->with('error', 'Salah satu file gambar tidak valid (max 2MB, png/jpg/webp).');
        }

        try {
            (new LayananModel())->insert([
                'nama' => $this->request->getPost('nama'),
                'kategori' => $this->request->getPost('kategori'),
                'deskripsi' => $this->request->getPost('deskripsi'),
                'durasi_menit' => (int) $this->request->getPost('durasi_menit'),
                'harga' => (int) $this->request->getPost('harga'),
                'ikon' => $this->request->getPost('ikon') ?: 'bi-stars',
                'is_active' => $this->request->getPost('is_active') ? 1 : 0,
                'gambar' => LayananModel::encodeGambar($paths),
                'promo_persen' => $this->promoOrNull($this->request->getPost('promo_persen')),
                'promo_deskripsi' => $this->emptyToNull($this->request->getPost('promo_deskripsi')),
                'promo_mulai' => $this->emptyToNull($this->request->getPost('promo_mulai')),
                'promo_selesai' => $this->emptyToNull($this->request->getPost('promo_selesai')),
            ]);
        } catch (\Throwable $e) {
            $this->rollbackFiles($paths);
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan layanan: ' . $e->getMessage());
        }
        return redirect()->to('/owner/layanan')->with('success', 'Layanan ditambahkan.');
    }

    public function update(int $id)
    {
        $model = new LayananModel();
        $layanan = $model->find($id);
        if (! $layanan) {
            return redirect()->to('/owner/layanan')->with('error', 'Layanan tidak ditemukan.');
        }

        $current = LayananModel::gambarList($layanan);

        // Hapus gambar yang dicentang. Pakai path string (bukan index) supaya
        // pergeseran index tidak salah hapus.
        $hapus = (array) $this->request->getPost('hapus_gambar');
        if ($hapus) {
            foreach ($hapus as $p) {
                $p = (string) $p;
                if (in_array($p, $current, true)) {
                    @unlink(FCPATH . $p);
                    $current = array_values(array_diff($current, [$p]));
                }
            }
        }

        // Tambah gambar baru.
        $newPaths = $this->moveUploadedImages('gambar');
        if ($newPaths === false) {
            return redirect()->back()->withInput()->with('error', 'Salah satu file gambar tidak valid (max 2MB, png/jpg/webp).');
        }
        $final = array_values(array_merge($current, $newPaths));

        try {
            $model->update($id, [
                'nama' => $this->request->getPost('nama'),
                'kategori' => $this->request->getPost('kategori'),
                'deskripsi' => $this->request->getPost('deskripsi'),
                'durasi_menit' => (int) $this->request->getPost('durasi_menit'),
                'harga' => (int) $this->request->getPost('harga'),
                'ikon' => $this->request->getPost('ikon') ?: 'bi-stars',
                'is_active' => $this->request->getPost('is_active') ? 1 : 0,
                'gambar' => LayananModel::encodeGambar($final),
                'promo_persen' => $this->promoOrNull($this->request->getPost('promo_persen')),
                'promo_deskripsi' => $this->emptyToNull($this->request->getPost('promo_deskripsi')),
                'promo_mulai' => $this->emptyToNull($this->request->getPost('promo_mulai')),
                'promo_selesai' => $this->emptyToNull($this->request->getPost('promo_selesai')),
            ]);
        } catch (\Throwable $e) {
            $this->rollbackFiles($newPaths);
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui layanan: ' . $e->getMessage());
        }
        return redirect()->to('/owner/layanan')->with('success', 'Layanan diperbarui.');
    }

    public function delete(int $id)
    {
        $model = new LayananModel();
        $layanan = $model->find($id);
        if (! $layanan) {
            return redirect()->to('/owner/layanan')->with('error', 'Layanan tidak ditemukan.');
        }
        if ($model->hasBookings($id)) {
            // Soft delete — biarkan file galeri karena riwayat booking masih
            // bisa render thumbnail.
            $model->delete($id);
            return redirect()->to('/owner/layanan')->with('success', 'Layanan dihapus (punya riwayat booking — data histori tetap aman).');
        }
        // Hard delete → bersihkan file galeri biar disk tidak nyampah.
        foreach (LayananModel::gambarList($layanan) as $p) {
            @unlink(FCPATH . $p);
        }
        $model->delete($id, true);
        return redirect()->to('/owner/layanan')->with('success', 'Layanan dihapus permanen.');
    }

    /**
     * Pindahkan tiap file dari input 'gambar[]' ke uploads/layanan dan kembalikan
     * array path relatif. Validasi per file: image, max 2MB, mime aman. Kalau ada
     * yang gagal validasi → false (caller tampilkan error).
     *
     * @return array<int,string>|false
     */
    private function moveUploadedImages(string $field): array|false
    {
        $files = $this->request->getFileMultiple($field);
        if (! $files) return [];

        $dir = FCPATH . self::UPLOAD_SUBDIR;
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
            @file_put_contents($dir . '/index.html', '');
        }

        $allowedExt = ['png', 'jpg', 'jpeg', 'webp'];
        $paths = [];
        foreach ($files as $f) {
            if (! $f || ! $f->isValid()) continue;
            if ($f->hasMoved()) continue;
            if ((int) $f->getSizeByUnit('kb') > 2048) return false;
            $ext = strtolower($f->getExtension() ?: pathinfo((string) $f->getName(), PATHINFO_EXTENSION));
            if (! in_array($ext, $allowedExt, true)) return false;
            $mime = (string) $f->getMimeType();
            if (! str_starts_with($mime, 'image/')) return false;
            $name = $f->getRandomName();
            $f->move($dir, $name);
            $paths[] = self::UPLOAD_SUBDIR . '/' . $name;
        }
        return $paths;
    }

    private function rollbackFiles(array $paths): void
    {
        foreach ($paths as $p) {
            @unlink(FCPATH . $p);
        }
    }

    private function promoOrNull($val): ?int
    {
        $n = (int) $val;
        return $n > 0 ? $n : null;
    }

    private function emptyToNull($val): ?string
    {
        $s = trim((string) $val);
        return $s === '' ? null : $s;
    }

    /** Daftar ikon untuk picker visual. */
    private function ikonList(): array
    {
        return [
            'bi-flower1', 'bi-flower2', 'bi-flower3', 'bi-brush', 'bi-emoji-smile', 'bi-emoji-kiss',
            'bi-sun', 'bi-moon-stars', 'bi-water', 'bi-fire', 'bi-snow', 'bi-lightning',
            'bi-droplet', 'bi-droplet-half', 'bi-droplet-fill', 'bi-scissors', 'bi-palette', 'bi-palette-fill',
            'bi-eye', 'bi-eye-fill', 'bi-gem', 'bi-stars', 'bi-magic', 'bi-wind',
            'bi-cloud', 'bi-shield-check', 'bi-shield', 'bi-hand-index', 'bi-pen', 'bi-heart',
        ];
    }
}
