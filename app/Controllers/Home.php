<?php

namespace App\Controllers;

use App\Models\LayananModel;
use App\Models\SettingModel;

class Home extends BaseController
{
    public function index(): string
    {
        $services = (new LayananModel())->where('is_active', 1)->orderBy('harga', 'ASC')->limit(3)->find();
        return view('public/home', ['services' => $services]);
    }

    public function layanan(): string
    {
        $services = (new LayananModel())->where('is_active', 1)->orderBy('kategori')->orderBy('nama')->find();
        $kategori = [];
        foreach ($services as $s) $kategori[$s['kategori']] = true;
        return view('public/layanan', ['services' => $services, 'kategori' => array_keys($kategori)]);
    }
}
