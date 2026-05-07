<?= $this->extend('layouts/main') ?><?= $this->section('content') ?><?= view('partials/booking_detail',['booking'=>$booking,'admin'=>false]) ?><?= $this->endSection() ?>
