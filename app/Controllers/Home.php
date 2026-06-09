<?php

namespace App\Controllers;

use App\Models\LayananModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Home extends BaseController
{
    public function index(): string
    {
        // Highlight 3 layanan teratas (promo dulu, lalu harga termurah).
        $model = new LayananModel();
        $services = $model->where('is_active', 1)
            ->orderBy('(promo_persen IS NOT NULL AND promo_persen > 0)', 'DESC', false)
            ->orderBy('harga', 'ASC')
            ->limit(3)
            ->find();
        return view('public/home', ['services' => $services]);
    }

    public function layanan(): string
    {
        $services = (new LayananModel())->activeForListing();
        $kategori = [];
        foreach ($services as $s) {
            $kategori[$s['kategori']] = true;
        }
        return view('public/layanan', [
            'services' => $services,
            'kategori' => array_keys($kategori),
        ]);
    }

    public function detail(int $id): string
    {
        $model = new LayananModel();
        $layanan = $model->where('is_active', 1)->find($id);
        if (! $layanan) {
            throw PageNotFoundException::forPageNotFound('Layanan tidak ditemukan.');
        }
        return view('public/layanan_detail', [
            'layanan' => $layanan,
            'gambar' => LayananModel::gambarList($layanan),
            'is_promo' => LayananModel::isPromo($layanan),
            'harga_final' => LayananModel::hargaFinal($layanan),
            'is_logged_in' => (bool) session('is_logged_in'),
        ]);
    }
}
