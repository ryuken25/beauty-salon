<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $services = db_connect()->table('services')->where('is_active', 1)->limit(6)->get()->getResultArray();
        return view('home/index', ['services' => $services]);
    }

    public function services(): string
    {
        $services = db_connect()->table('services')->where('is_active', 1)->orderBy('category')->orderBy('name')->get()->getResultArray();
        return view('home/services', ['services' => $services]);
    }
}
