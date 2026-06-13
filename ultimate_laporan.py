#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
ultimate_laporan.py — generator BAB IV SW Beauty Salon, SEKALI JALAN dari awal
sampai akhir:

    python ultimate_laporan.py                  # semua: screenshot + diagram + docx
    python ultimate_laporan.py --no-shots       # lewati tangkap screenshot 4.5
    python ultimate_laporan.py --no-docx        # diagram saja
    python ultimate_laporan.py --out hasil      # ganti folder output (default: out)

Hasil: out/BabIvAssets/
    context/      Diagram Konteks (.drawio.xml + .graphml + .png — bisa dirapikan di draw.io)
    dfd 0/        DFD Level 0 (7 proses)
    dfd 1.1..1.7/ DFD Level 1 per proses
    erd/          ERD crow's foot (konseptual) + ERD notasi Chen
    mockup 4.4/   wireframe lite (digenerate otomatis)
    shots/        screenshot asli sistem (Playwright, login per role)
    BAB_IV.docx   4.1 Analisis · 4.2 Perancangan (dengan PENJELASAN per diagram)
                  · 4.3 Basis Data (dengan penjelasan) · 4.4 Antarmuka
                  · 4.5 Implementasi · 4.6 Black Box  (4.7 SUS sengaja TIDAK diisi)

Semua spec + narasi ada inline di file ini. Library tata letak dipakai dari
skills/bab4-diagrams (tidak menulis koordinat dengan tangan).
Setelah buka BAB_IV.docx: Ctrl+A lalu F9 untuk update semua nomor.
"""

from __future__ import annotations

import argparse
import copy
import json
import pathlib
import socket
import subprocess
import sys
import tempfile
import time

ROOT = pathlib.Path(__file__).resolve().parent
SKILL = ROOT / "skills" / "bab4-diagrams"
sys.path.insert(0, str(SKILL))

from build_babiv_assets import (build_context, build_level0, build_level1,
                                build_erd, emit_dfd, emit_erd, emit_erd_chen,
                                emit_mockups, validate_balance, _write_readme)
from babiv_docx import BabIvDoc

SYSTEM_NAME = "Sistem Informasi Booking SW Beauty Salon"
BASE_URL = "http://localhost:8080"
PASSWORD = "Password123!"
ACCOUNTS = {
    "admin":     "admin@swbeautysalon.local",
    "pemilik":   "owner@swbeautysalon.local",
    "pelanggan": "6281338109102",
}

# ============================================================================ #
# 1. SPEC — struktur LOGIS sistem (hasil audit Routes/Controllers/Models/
#    Migrations/Views). data_xxx = masuk (input), info_xxx = keluar (output).
# ============================================================================ #

SPEC = {
    "system_name": SYSTEM_NAME,
    "chapter": 4,
    "bab_title": "Hasil dan Pembahasan",

    "external_entities": [
        {"id": "pelanggan", "name": "Pelanggan"},
        {"id": "admin",     "name": "Admin"},
        {"id": "pemilik",   "name": "Pemilik"},
    ],
    "data_stores": [
        {"id": "d_users",     "code": "D1", "name": "users"},
        {"id": "d_layanan",   "code": "D2", "name": "layanan"},
        {"id": "d_bookings",  "code": "D3", "name": "bookings"},
        {"id": "d_slots",     "code": "D4", "name": "booking_slots"},
        {"id": "d_transaksi", "code": "D5", "name": "transaksi"},
        {"id": "d_settings",  "code": "D6", "name": "settings"},
        {"id": "d_logs",      "code": "D7", "name": "booking_logs"},
    ],

    # ---- Diagram Konteks: pasangan data_ (masuk) / info_ (keluar) 1:1 ------
    "context": {"flows": [
        {"src": "pelanggan", "dst": "sys", "label": "data_registrasi_login"},
        {"src": "sys", "dst": "pelanggan", "label": "info_sesi_pelanggan"},
        {"src": "pelanggan", "dst": "sys", "label": "data_pilih_katalog"},
        {"src": "sys", "dst": "pelanggan", "label": "info_katalog_promo"},
        {"src": "pelanggan", "dst": "sys", "label": "data_booking_slot"},
        {"src": "sys", "dst": "pelanggan", "label": "info_kode_booking"},
        {"src": "pelanggan", "dst": "sys", "label": "data_pembatalan_booking"},
        {"src": "sys", "dst": "pelanggan", "label": "info_status_booking"},

        {"src": "admin", "dst": "sys", "label": "data_login_staf"},
        {"src": "sys", "dst": "admin", "label": "info_sesi_staf"},
        {"src": "admin", "dst": "sys", "label": "data_verifikasi_booking_dp"},
        {"src": "sys", "dst": "admin", "label": "info_daftar_booking"},
        {"src": "admin", "dst": "sys", "label": "data_walkin"},
        {"src": "sys", "dst": "admin", "label": "info_jadwal_harian"},
        {"src": "admin", "dst": "sys", "label": "data_penyelesaian_booking"},
        {"src": "sys", "dst": "admin", "label": "info_rekap_transaksi"},
        {"src": "admin", "dst": "sys", "label": "data_pengaturan_salon"},
        {"src": "sys", "dst": "admin", "label": "info_pengaturan_tersimpan"},

        {"src": "pemilik", "dst": "sys", "label": "data_login_pemilik"},
        {"src": "sys", "dst": "pemilik", "label": "info_sesi_pemilik"},
        {"src": "pemilik", "dst": "sys", "label": "data_kelola_layanan"},
        {"src": "sys", "dst": "pemilik", "label": "info_layanan_promo"},
        {"src": "pemilik", "dst": "sys", "label": "data_permintaan_laporan"},
        {"src": "sys", "dst": "pemilik", "label": "info_laporan_pendapatan"},
    ]},

    # ---- DFD Level 0: 7 proses utama ---------------------------------------
    "level0": {
        "processes": [
            {"id": "p1", "no": "1.0", "name": "Autentikasi & Akun"},
            {"id": "p2", "no": "2.0", "name": "Kelola Layanan & Promo"},
            {"id": "p3", "no": "3.0", "name": "Pemesanan & Slot Booking"},
            {"id": "p4", "no": "4.0", "name": "Verifikasi Booking & DP"},
            {"id": "p5", "no": "5.0", "name": "Penyelesaian & Transaksi"},
            {"id": "p6", "no": "6.0", "name": "Laporan & Dashboard"},
            {"id": "p7", "no": "7.0", "name": "Pengaturan Sistem"},
        ],
        "flows": [
            # 1.0 Autentikasi & Akun  (store: D1)
            {"src": "pelanggan", "dst": "p1", "label": "data_registrasi_login"},
            {"src": "p1", "dst": "pelanggan", "label": "info_sesi_pelanggan"},
            {"src": "admin", "dst": "p1", "label": "data_login_staf"},
            {"src": "p1", "dst": "admin", "label": "info_sesi_staf"},
            {"src": "pemilik", "dst": "p1", "label": "data_login_pemilik"},
            {"src": "p1", "dst": "pemilik", "label": "info_sesi_pemilik"},
            {"src": "admin", "dst": "p1", "label": "data_kelola_akun_pelanggan"},
            {"src": "p1", "dst": "admin", "label": "info_daftar_pelanggan"},
            {"src": "p1", "dst": "d_users", "label": "data_akun"},
            {"src": "d_users", "dst": "p1", "label": "info_akun"},

            # 2.0 Kelola Layanan & Promo  (store: D2)
            {"src": "pemilik", "dst": "p2", "label": "data_layanan_promo"},
            {"src": "p2", "dst": "pemilik", "label": "info_layanan"},
            {"src": "pelanggan", "dst": "p2", "label": "data_pilih_katalog"},
            {"src": "p2", "dst": "pelanggan", "label": "info_katalog_promo"},
            {"src": "p2", "dst": "d_layanan", "label": "data_layanan"},
            {"src": "d_layanan", "dst": "p2", "label": "info_layanan"},

            # 3.0 Pemesanan & Slot Booking  (store: D3, D4, D7; baca D2)
            {"src": "pelanggan", "dst": "p3", "label": "data_booking_slot"},
            {"src": "p3", "dst": "pelanggan", "label": "info_kode_booking"},
            {"src": "pelanggan", "dst": "p3", "label": "data_pembatalan"},
            {"src": "p3", "dst": "pelanggan", "label": "info_status_booking"},
            {"src": "admin", "dst": "p3", "label": "data_walkin"},
            {"src": "p3", "dst": "admin", "label": "info_jadwal_booking"},
            {"src": "p3", "dst": "d_bookings", "label": "data_booking"},
            {"src": "d_bookings", "dst": "p3", "label": "info_booking"},
            {"src": "p3", "dst": "d_slots", "label": "data_slot_ditahan"},
            {"src": "d_slots", "dst": "p3", "label": "info_ketersediaan_slot"},
            {"src": "p3", "dst": "d_logs", "label": "data_log_booking"},
            {"src": "d_logs", "dst": "p3", "label": "info_riwayat_log"},
            {"src": "d_layanan", "dst": "p3", "label": "info_harga_durasi"},

            # 4.0 Verifikasi Booking & DP  (baca/tulis D3)
            {"src": "admin", "dst": "p4", "label": "data_keputusan_verifikasi"},
            {"src": "p4", "dst": "admin", "label": "info_hasil_verifikasi"},
            {"src": "p4", "dst": "pelanggan", "label": "info_konfirmasi_email"},
            {"src": "p4", "dst": "d_bookings", "label": "data_status_verifikasi"},
            {"src": "d_bookings", "dst": "p4", "label": "info_booking_pending"},

            # 5.0 Penyelesaian & Transaksi  (store: D5; baca/tulis D3)
            {"src": "admin", "dst": "p5", "label": "data_penyelesaian_booking"},
            {"src": "p5", "dst": "admin", "label": "info_rekap_transaksi"},
            {"src": "p5", "dst": "d_transaksi", "label": "data_transaksi"},
            {"src": "d_transaksi", "dst": "p5", "label": "info_transaksi"},
            {"src": "p5", "dst": "d_bookings", "label": "data_status_selesai"},
            {"src": "d_bookings", "dst": "p5", "label": "info_booking_diterima"},

            # 6.0 Laporan & Dashboard  (baca D3, D5)
            {"src": "pemilik", "dst": "p6", "label": "data_permintaan_laporan"},
            {"src": "p6", "dst": "pemilik", "label": "info_laporan_pendapatan"},
            {"src": "admin", "dst": "p6", "label": "data_permintaan_dashboard"},
            {"src": "p6", "dst": "admin", "label": "info_ringkasan_harian"},
            {"src": "d_transaksi", "dst": "p6", "label": "info_data_transaksi"},
            {"src": "d_bookings", "dst": "p6", "label": "info_data_booking"},

            # 7.0 Pengaturan Sistem  (store: D6)
            {"src": "admin", "dst": "p7", "label": "data_pengaturan_salon"},
            {"src": "p7", "dst": "admin", "label": "info_pengaturan_tersimpan"},
            {"src": "p7", "dst": "d_settings", "label": "data_setting"},
            {"src": "d_settings", "dst": "p7", "label": "info_setting"},
        ],
    },

    # ---- DFD Level 1: SEMUA proses 1.0–7.0 didekomposisi --------------------
    "level1": [
        {   # 1.0 Autentikasi & Akun
            "parent_no": "1.0",
            "processes": [
                {"id": "p1_1", "no": "1.1", "name": "Registrasi Akun Pelanggan"},
                {"id": "p1_2", "no": "1.2", "name": "Validasi Login Terpadu"},
                {"id": "p1_3", "no": "1.3", "name": "Kelola Akun Pelanggan"},
            ],
            "flows": [
                {"src": "pelanggan", "dst": "p1_1", "label": "data_registrasi"},
                {"src": "p1_1", "dst": "d_users", "label": "data_akun_baru"},
                {"src": "p1_1", "dst": "pelanggan", "label": "info_akun_terdaftar"},
                {"src": "pelanggan", "dst": "p1_2", "label": "data_login_pelanggan"},
                {"src": "admin", "dst": "p1_2", "label": "data_login_staf"},
                {"src": "pemilik", "dst": "p1_2", "label": "data_login_pemilik"},
                {"src": "d_users", "dst": "p1_2", "label": "info_kredensial"},
                {"src": "p1_2", "dst": "pelanggan", "label": "info_sesi_pelanggan"},
                {"src": "p1_2", "dst": "admin", "label": "info_sesi_staf"},
                {"src": "p1_2", "dst": "pemilik", "label": "info_sesi_pemilik"},
                {"src": "admin", "dst": "p1_3", "label": "data_reset_password"},
                {"src": "d_users", "dst": "p1_3", "label": "info_daftar_pelanggan"},
                {"src": "p1_3", "dst": "d_users", "label": "data_password_baru"},
                {"src": "p1_3", "dst": "admin", "label": "info_akun_diperbarui"},
            ],
        },
        {   # 2.0 Kelola Layanan & Promo
            "parent_no": "2.0",
            "processes": [
                {"id": "p2_1", "no": "2.1", "name": "Tambah/Ubah Layanan"},
                {"id": "p2_2", "no": "2.2", "name": "Kelola Galeri & Promo"},
                {"id": "p2_3", "no": "2.3", "name": "Tampilkan Katalog"},
            ],
            "flows": [
                {"src": "pemilik", "dst": "p2_1", "label": "data_layanan"},
                {"src": "p2_1", "dst": "d_layanan", "label": "data_layanan_baru"},
                {"src": "d_layanan", "dst": "p2_1", "label": "info_layanan_tersimpan"},
                {"src": "p2_1", "dst": "pemilik", "label": "info_daftar_layanan"},
                {"src": "pemilik", "dst": "p2_2", "label": "data_galeri_promo"},
                {"src": "p2_2", "dst": "d_layanan", "label": "data_gambar_promo"},
                {"src": "d_layanan", "dst": "p2_2", "label": "info_galeri_lama"},
                {"src": "p2_2", "dst": "pemilik", "label": "info_promo_aktif"},
                {"src": "pelanggan", "dst": "p2_3", "label": "data_pilih_layanan"},
                {"src": "d_layanan", "dst": "p2_3", "label": "info_layanan_aktif"},
                {"src": "p2_3", "dst": "pelanggan", "label": "info_detail_layanan"},
            ],
        },
        {   # 3.0 Pemesanan & Slot Booking
            "parent_no": "3.0",
            "processes": [
                {"id": "p3_1", "no": "3.1", "name": "Pilih Layanan & Jadwal"},
                {"id": "p3_2", "no": "3.2", "name": "Validasi Slot & Bukti DP"},
                {"id": "p3_3", "no": "3.3", "name": "Simpan Booking & Tahan Slot"},
                {"id": "p3_4", "no": "3.4", "name": "Booking Walk-in"},
                {"id": "p3_5", "no": "3.5", "name": "Cek & Batalkan Booking"},
            ],
            "flows": [
                {"src": "pelanggan", "dst": "p3_1", "label": "data_pilihan_booking"},
                {"src": "d_layanan", "dst": "p3_1", "label": "info_harga_durasi"},
                {"src": "d_settings", "dst": "p3_1", "label": "info_jam_operasional"},
                {"src": "d_slots", "dst": "p3_1", "label": "info_slot_terisi"},
                {"src": "p3_1", "dst": "pelanggan", "label": "info_slot_tersedia"},
                {"src": "p3_1", "dst": "p3_2", "label": "data_draf_booking"},
                {"src": "pelanggan", "dst": "p3_2", "label": "data_bukti_dp"},
                {"src": "p3_2", "dst": "p3_3", "label": "data_booking_valid"},
                {"src": "p3_3", "dst": "d_bookings", "label": "data_booking_baru"},
                {"src": "p3_3", "dst": "d_slots", "label": "data_slot_ditahan"},
                {"src": "p3_3", "dst": "d_logs", "label": "data_log_booking"},
                {"src": "p3_3", "dst": "pelanggan", "label": "info_kode_booking"},
                {"src": "admin", "dst": "p3_4", "label": "data_walkin"},
                {"src": "p3_4", "dst": "p3_3", "label": "data_booking_walkin"},
                {"src": "p3_3", "dst": "admin", "label": "info_booking_tersimpan"},
                {"src": "pelanggan", "dst": "p3_5", "label": "data_pembatalan"},
                {"src": "d_bookings", "dst": "p3_5", "label": "info_booking"},
                {"src": "p3_5", "dst": "d_bookings", "label": "data_status_batal"},
                {"src": "p3_5", "dst": "d_slots", "label": "data_pelepasan_slot"},
                {"src": "p3_5", "dst": "d_logs", "label": "data_log_pembatalan"},
                {"src": "p3_5", "dst": "pelanggan", "label": "info_status_booking"},
            ],
        },
        {   # 4.0 Verifikasi Booking & DP
            "parent_no": "4.0",
            "processes": [
                {"id": "p4_1", "no": "4.1", "name": "Periksa Bukti DP"},
                {"id": "p4_2", "no": "4.2", "name": "Terima/Tolak Booking"},
                {"id": "p4_3", "no": "4.3", "name": "Kirim Notifikasi"},
            ],
            "flows": [
                {"src": "admin", "dst": "p4_1", "label": "data_verifikasi_dp"},
                {"src": "d_bookings", "dst": "p4_1", "label": "info_booking_pending"},
                {"src": "p4_1", "dst": "d_bookings", "label": "data_status_pembayaran"},
                {"src": "p4_1", "dst": "p4_2", "label": "data_dp_terverifikasi"},
                {"src": "admin", "dst": "p4_2", "label": "data_keputusan_booking"},
                {"src": "p4_2", "dst": "d_bookings", "label": "data_status_booking"},
                {"src": "p4_2", "dst": "d_slots", "label": "data_pelepasan_slot"},
                {"src": "p4_2", "dst": "d_logs", "label": "data_log_verifikasi"},
                {"src": "p4_2", "dst": "p4_3", "label": "data_hasil_keputusan"},
                {"src": "p4_3", "dst": "pelanggan", "label": "info_konfirmasi_email"},
                {"src": "p4_3", "dst": "admin", "label": "info_template_wa"},
            ],
        },
        {   # 5.0 Penyelesaian & Transaksi
            "parent_no": "5.0",
            "processes": [
                {"id": "p5_1", "no": "5.1", "name": "Tandai Booking Selesai"},
                {"id": "p5_2", "no": "5.2", "name": "Hitung Total Pembayaran"},
                {"id": "p5_3", "no": "5.3", "name": "Catat Transaksi"},
                {"id": "p5_4", "no": "5.4", "name": "Rekap Transaksi"},
            ],
            "flows": [
                {"src": "admin", "dst": "p5_1", "label": "data_penyelesaian"},
                {"src": "d_bookings", "dst": "p5_1", "label": "info_booking_diterima"},
                {"src": "p5_1", "dst": "d_bookings", "label": "data_status_selesai"},
                {"src": "p5_1", "dst": "p5_2", "label": "data_booking_selesai"},
                {"src": "admin", "dst": "p5_2", "label": "data_biaya_tambahan"},
                {"src": "p5_2", "dst": "p5_3", "label": "data_total_bayar"},
                {"src": "p5_3", "dst": "d_transaksi", "label": "data_transaksi_baru"},
                {"src": "p5_3", "dst": "d_logs", "label": "data_log_transaksi"},
                {"src": "p5_3", "dst": "admin", "label": "info_transaksi_tersimpan"},
                {"src": "admin", "dst": "p5_4", "label": "data_filter_periode"},
                {"src": "d_transaksi", "dst": "p5_4", "label": "info_transaksi"},
                {"src": "p5_4", "dst": "admin", "label": "info_rekap_transaksi"},
            ],
        },
        {   # 6.0 Laporan & Dashboard
            "parent_no": "6.0",
            "processes": [
                {"id": "p6_1", "no": "6.1", "name": "Dashboard Operasional"},
                {"id": "p6_2", "no": "6.2", "name": "Laporan Pendapatan"},
                {"id": "p6_3", "no": "6.3", "name": "Grafik & Layanan Terlaris"},
            ],
            "flows": [
                {"src": "admin", "dst": "p6_1", "label": "data_permintaan_dashboard"},
                {"src": "d_bookings", "dst": "p6_1", "label": "info_booking_hari_ini"},
                {"src": "p6_1", "dst": "admin", "label": "info_ringkasan_harian"},
                {"src": "pemilik", "dst": "p6_2", "label": "data_permintaan_laporan"},
                {"src": "d_transaksi", "dst": "p6_2", "label": "info_transaksi"},
                {"src": "d_bookings", "dst": "p6_2", "label": "info_status_booking"},
                {"src": "p6_2", "dst": "pemilik", "label": "info_laporan_pendapatan"},
                {"src": "pemilik", "dst": "p6_3", "label": "data_mode_grafik"},
                {"src": "d_transaksi", "dst": "p6_3", "label": "info_pendapatan_periode"},
                {"src": "p6_3", "dst": "pemilik", "label": "info_grafik_pendapatan"},
            ],
        },
        {   # 7.0 Pengaturan Sistem
            "parent_no": "7.0",
            "processes": [
                {"id": "p7_1", "no": "7.1", "name": "Ubah Profil & Jam Operasional"},
                {"id": "p7_2", "no": "7.2", "name": "Kelola Template WhatsApp"},
                {"id": "p7_3", "no": "7.3", "name": "Ganti Password Staf"},
            ],
            "flows": [
                {"src": "admin", "dst": "p7_1", "label": "data_profil_salon"},
                {"src": "p7_1", "dst": "d_settings", "label": "data_jam_operasional"},
                {"src": "d_settings", "dst": "p7_1", "label": "info_pengaturan_lama"},
                {"src": "p7_1", "dst": "admin", "label": "info_pengaturan_tersimpan"},
                {"src": "admin", "dst": "p7_2", "label": "data_template_wa"},
                {"src": "p7_2", "dst": "d_settings", "label": "data_template_tersimpan"},
                {"src": "d_settings", "dst": "p7_2", "label": "info_template_wa"},
                {"src": "p7_2", "dst": "admin", "label": "info_pratinjau_template"},
                {"src": "admin", "dst": "p7_3", "label": "data_password_lama_baru"},
                {"src": "d_users", "dst": "p7_3", "label": "info_kredensial_staf"},
                {"src": "p7_3", "dst": "d_users", "label": "data_password_baru"},
                {"src": "p7_3", "dst": "admin", "label": "info_password_diperbarui"},
            ],
        },
    ],

    # ---- ERD: crow's foot (utama) + Chen (pelengkap) ------------------------
    "erd": {
        "chen": True,
        "entities": [
            {"id": "users", "name": "USERS", "pk": "id",
             "attrs": ["nama", "email", "nomor_hp", "password_hash", "role", "is_active"]},
            {"id": "layanan", "name": "LAYANAN", "pk": "id",
             "attrs": ["nama", "kategori", "durasi_menit", "harga",
                       "promo_persen", "gambar", "is_active"]},
            {"id": "bookings", "name": "BOOKINGS", "pk": "id",
             "fks": ["user_id", "layanan_id"],
             "attrs": ["kode_booking", "tanggal", "slot_mulai", "slot_selesai",
                       "jumlah_slot", "harga_layanan", "dp_amount",
                       "payment_status", "status"]},
            {"id": "booking_slots", "name": "BOOKING_SLOTS", "pk": "id",
             "fks": ["booking_id"],
             "attrs": ["tanggal", "slot_waktu", "status"]},
            {"id": "transaksi", "name": "TRANSAKSI", "pk": "id",
             "fks": ["booking_id"],
             "attrs": ["nominal", "base_price", "additional_price",
                       "metode_bayar", "tanggal_transaksi"]},
            {"id": "booking_logs", "name": "BOOKING_LOGS", "pk": "id",
             "fks": ["booking_id"],
             "attrs": ["event_type", "actor", "actor_role", "payload"]},
            {"id": "settings", "name": "SETTINGS", "pk": "id",
             "attrs": ["key_name", "value", "updated_at"]},
        ],
        "relations": [
            {"src": "users", "dst": "bookings", "label": "melakukan",
             "src_card": "one", "dst_card": "many"},
            {"src": "layanan", "dst": "bookings", "label": "dipesan_pada",
             "src_card": "one", "dst_card": "many"},
            {"src": "bookings", "dst": "booking_slots", "label": "menahan",
             "src_card": "one", "dst_card": "many"},
            {"src": "bookings", "dst": "transaksi", "label": "menghasilkan",
             "src_card": "one", "dst_card": "one"},
            {"src": "bookings", "dst": "booking_logs", "label": "tercatat_pada",
             "src_card": "one", "dst_card": "many"},
        ],
    },

    # ---- 4.1 Analisis --------------------------------------------------------
    "analisis": {
        "deskripsi":
            "Dalam pengembangan Sistem Informasi Booking SW Beauty Salon berbasis "
            "website, dilakukan analisis terhadap permasalahan yang terjadi pada "
            "proses bisnis salon serta dua aspek kebutuhan utama, yaitu kebutuhan "
            "fungsional dan kebutuhan non-fungsional. Analisis ini bertujuan untuk "
            "memastikan sistem yang dibangun sesuai dengan proses bisnis yang "
            "berjalan dan didukung oleh perangkat yang memadai.",
        "masalah":
            "SW Beauty Salon merupakan salah satu usaha jasa kecantikan di Tabanan, "
            "Bali yang sebelumnya menerima pemesanan layanan secara manual melalui "
            "pesan WhatsApp dan pencatatan di buku agenda. Cara ini menimbulkan "
            "beberapa permasalahan: (1) sering terjadi bentrok jadwal (double "
            "booking) karena ketersediaan jam layanan tidak tercatat terpusat; "
            "(2) admin harus membalas satu per satu pertanyaan ketersediaan jadwal "
            "sehingga respon menjadi lambat; (3) pembayaran uang muka (DP) sulit "
            "dilacak karena bukti transfer tersebar di percakapan; (4) pemilik "
            "tidak memiliki rekapitulasi pendapatan yang akurat karena transaksi "
            "dicatat manual; dan (5) informasi layanan beserta promo tidak "
            "terpublikasi dengan baik kepada pelanggan.\n"
            "Berdasarkan permasalahan tersebut, dibangun sistem informasi booking "
            "berbasis website dengan slot waktu tetap 30 menit, verifikasi DP, "
            "notifikasi WhatsApp manual (tautan wa.me), serta laporan pendapatan "
            "bagi pemilik, sehingga seluruh proses pemesanan sampai pelaporan "
            "tercatat dalam satu sistem.",
        "kebutuhan_fungsional": [
            {"actor": "Pelanggan", "items": [
                "Melakukan registrasi akun dan login menggunakan nomor WhatsApp.",
                "Melihat katalog layanan, detail layanan, galeri foto, dan promo yang sedang berlaku.",
                "Melakukan pemesanan (booking) layanan dengan memilih tanggal dan slot waktu yang tersedia.",
                "Mengunggah bukti pembayaran uang muka (DP) saat melakukan booking.",
                "Menerima kode booking dan memantau status booking melalui halaman cek booking.",
                "Membatalkan booking minimal 2 jam sebelum jadwal dimulai.",
                "Melihat riwayat booking pada dashboard pelanggan.",
            ]},
            {"actor": "Admin", "items": [
                "Melakukan login ke dalam sistem menggunakan email.",
                "Memverifikasi atau menolak booking yang masuk beserta bukti pembayaran DP.",
                "Mencatat booking walk-in untuk pelanggan yang datang langsung.",
                "Melihat jadwal harian dalam bentuk papan slot per 30 menit.",
                "Menyelesaikan booking dan mencatat transaksi pembayaran (termasuk biaya tambahan).",
                "Mengelola akun pelanggan (ubah nama dan reset password).",
                "Mengubah pengaturan salon (jam operasional, template WhatsApp, informasi DP).",
            ]},
            {"actor": "Pemilik", "items": [
                "Memiliki seluruh hak akses Admin.",
                "Mengelola data layanan beserta galeri foto, ikon, dan promo (tambah, ubah, hapus).",
                "Melihat laporan pendapatan dengan grafik dinamis (per hari, minggu, dan bulan).",
                "Melihat layanan terlaris dan distribusi status booking.",
            ]},
        ],
        "kebutuhan_nonfungsional": {
            "deskripsi":
                "Analisis kebutuhan non-fungsional meliputi spesifikasi perangkat "
                "keras (hardware) dan perangkat lunak (software) yang digunakan "
                "selama proses pengembangan sistem. Spesifikasi yang memadai "
                "diperlukan agar proses penulisan kode program dan pengujian "
                "berjalan lancar. Adapun perangkat yang digunakan dapat dilihat "
                "pada tabel berikut.",
            "tabel": [
                {"judul": "Perangkat Keras", "header": ["No", "Perangkat", "Spesifikasi"],
                 "rows": [
                     ["1", "Processor", "Intel Core i5 atau setara"],
                     ["2", "RAM", "8 GB"],
                     ["3", "Penyimpanan", "SSD 256 GB"],
                     ["4", "Monitor", "Resolusi 1366 × 768 atau lebih"],
                 ],
                 "widths_cm": [1.5, 5.0, 7.5]},
                {"judul": "Perangkat Lunak", "header": ["No", "Perangkat Lunak", "Keterangan"],
                 "rows": [
                     ["1", "Sistem Operasi", "Windows 11"],
                     ["2", "Web Server", "Apache (XAMPP)"],
                     ["3", "Bahasa Pemrograman", "PHP 8.1"],
                     ["4", "Framework", "CodeIgniter 4"],
                     ["5", "Basis Data", "MySQL / MariaDB"],
                     ["6", "Antarmuka", "Bootstrap 5, Bootstrap Icons, Chart.js"],
                     ["7", "Editor & Versi", "Visual Studio Code, Git"],
                     ["8", "Peramban", "Google Chrome"],
                 ],
                 "widths_cm": [1.5, 5.0, 7.5]},
            ],
        },
    },

    # ---- 4.3 Struktur tabel (7 tabel sesuai migrasi) -------------------------
    "struktur_tabel": [
        {"nama": "users", "header": ["Field", "Tipe", "Keterangan"],
         "rows": [
             ["id", "BIGINT UNSIGNED", "Primary Key, Auto Increment"],
             ["email", "VARCHAR(150)", "Email staf (unik, boleh kosong)"],
             ["password_hash", "VARCHAR(255)", "Kata sandi ter-hash (bcrypt)"],
             ["nama", "VARCHAR(100)", "Nama lengkap pengguna"],
             ["nomor_hp", "VARCHAR(20)", "Nomor WhatsApp pelanggan (unik, boleh kosong)"],
             ["role", "ENUM", "admin / pemilik / pelanggan"],
             ["is_active", "TINYINT(1)", "Status aktif akun (1 = aktif)"],
             ["created_at, updated_at", "DATETIME", "Waktu dibuat / diubah"],
         ], "widths_cm": [4.5, 4.0, 6.5]},
        {"nama": "layanan", "header": ["Field", "Tipe", "Keterangan"],
         "rows": [
             ["id", "BIGINT UNSIGNED", "Primary Key, Auto Increment"],
             ["nama", "VARCHAR(100)", "Nama layanan"],
             ["kategori", "VARCHAR(50)", "Kategori layanan (Hair, Nails, dll.)"],
             ["deskripsi", "TEXT", "Deskripsi layanan"],
             ["durasi_menit", "SMALLINT UNSIGNED", "Durasi layanan (kelipatan 30 menit)"],
             ["harga", "INT UNSIGNED", "Harga normal layanan"],
             ["ikon", "VARCHAR(50)", "Kelas ikon Bootstrap"],
             ["gambar", "JSON", "Galeri foto (array path, indeks 0 = sampul)"],
             ["promo_persen", "TINYINT UNSIGNED", "Persentase diskon promo (0–100)"],
             ["promo_deskripsi", "VARCHAR(255)", "Teks keterangan promo"],
             ["promo_mulai, promo_selesai", "DATE", "Rentang tanggal promo berlaku"],
             ["is_active", "TINYINT(1)", "Tampil pada katalog & form booking"],
             ["deleted_at", "DATETIME", "Penanda soft delete"],
             ["created_at, updated_at", "DATETIME", "Waktu dibuat / diubah"],
         ], "widths_cm": [4.5, 4.0, 6.5]},
        {"nama": "bookings", "header": ["Field", "Tipe", "Keterangan"],
         "rows": [
             ["id", "BIGINT UNSIGNED", "Primary Key, Auto Increment"],
             ["kode_booking", "VARCHAR(20)", "Kode unik format SW-YYYYMMDD-NNN"],
             ["user_id", "BIGINT UNSIGNED", "Foreign Key ke users (kosong untuk walk-in)"],
             ["nama_pelanggan", "VARCHAR(100)", "Nama pelanggan"],
             ["nomor_hp_pelanggan", "VARCHAR(20)", "Nomor WhatsApp pelanggan"],
             ["email_pelanggan", "VARCHAR(150)", "Email pelanggan (untuk notifikasi)"],
             ["layanan_id", "BIGINT UNSIGNED", "Foreign Key ke layanan"],
             ["tanggal", "DATE", "Tanggal booking"],
             ["slot_mulai, slot_selesai", "TIME", "Jam mulai dan selesai layanan"],
             ["jumlah_slot", "SMALLINT UNSIGNED", "Banyak slot 30 menit yang dipakai"],
             ["harga_layanan", "INT UNSIGNED", "Harga final (setelah promo)"],
             ["dp_amount", "INT UNSIGNED", "Nominal DP yang wajib dibayar"],
             ["dp_proof_path", "VARCHAR(255)", "Path file bukti pembayaran DP"],
             ["payment_status", "ENUM", "unpaid / dp_uploaded / dp_verified"],
             ["status", "ENUM", "pending_verification / accepted / rejected / cancelled / completed"],
             ["sumber", "ENUM", "online / walkin"],
             ["catatan", "TEXT", "Catatan tambahan"],
             ["wa_sent", "TINYINT(1)", "Penanda WhatsApp sudah dikirim"],
             ["verified_via, verified_at", "VARCHAR(60), DATETIME", "Informasi verifikasi"],
             ["completed_at, cancelled_at", "DATETIME", "Waktu selesai / batal"],
             ["cancelled_by", "VARCHAR(60)", "Pihak yang membatalkan"],
             ["rejection_reason, cancellation_reason", "TEXT", "Alasan tolak / batal"],
             ["email_reminder_sent_at", "DATETIME", "Waktu pengiriman email pengingat"],
             ["created_at, updated_at", "DATETIME", "Waktu dibuat / diubah"],
         ], "widths_cm": [4.8, 4.2, 6.0]},
        {"nama": "booking_slots", "header": ["Field", "Tipe", "Keterangan"],
         "rows": [
             ["id", "BIGINT UNSIGNED", "Primary Key, Auto Increment"],
             ["booking_id", "BIGINT UNSIGNED", "Foreign Key ke bookings"],
             ["tanggal", "DATE", "Tanggal slot"],
             ["slot_waktu", "TIME", "Jam slot (kelipatan 30 menit)"],
             ["status", "ENUM", "held (ditahan) / released (dilepas)"],
             ["created_at", "DATETIME", "Waktu dibuat"],
         ], "widths_cm": [4.5, 4.0, 6.5]},
        {"nama": "transaksi", "header": ["Field", "Tipe", "Keterangan"],
         "rows": [
             ["id", "BIGINT UNSIGNED", "Primary Key, Auto Increment"],
             ["booking_id", "BIGINT UNSIGNED", "Foreign Key ke bookings (unik, 1 transaksi per booking)"],
             ["nominal", "INT UNSIGNED", "Total pembayaran (harga + biaya tambahan)"],
             ["base_price", "INT UNSIGNED", "Harga dasar layanan"],
             ["additional_price", "INT UNSIGNED", "Biaya tambahan"],
             ["metode_bayar", "VARCHAR(30)", "cash / transfer / qris"],
             ["tanggal_transaksi", "DATETIME", "Waktu transaksi dicatat"],
             ["catatan", "TEXT", "Catatan pembayaran"],
             ["created_at", "DATETIME", "Waktu dibuat"],
         ], "widths_cm": [4.5, 4.0, 6.5]},
        {"nama": "settings", "header": ["Field", "Tipe", "Keterangan"],
         "rows": [
             ["id", "BIGINT UNSIGNED", "Primary Key, Auto Increment"],
             ["key_name", "VARCHAR(60)", "Nama kunci pengaturan (unik)"],
             ["value", "TEXT", "Nilai pengaturan"],
             ["updated_at", "DATETIME", "Waktu diubah"],
         ], "widths_cm": [4.5, 4.0, 6.5]},
        {"nama": "booking_logs", "header": ["Field", "Tipe", "Keterangan"],
         "rows": [
             ["id", "BIGINT UNSIGNED", "Primary Key, Auto Increment"],
             ["booking_id", "BIGINT UNSIGNED", "Foreign Key ke bookings"],
             ["event_type", "VARCHAR(40)", "Jenis kejadian (created, verified, cancelled, dll.)"],
             ["actor", "VARCHAR(100)", "Pelaku kejadian"],
             ["actor_role", "VARCHAR(20)", "Peran pelaku (admin / pemilik / pelanggan / system)"],
             ["payload", "JSON", "Data tambahan kejadian"],
             ["notes", "TEXT", "Catatan"],
             ["created_at", "DATETIME", "Waktu kejadian"],
         ], "widths_cm": [4.5, 4.0, 6.5]},
    ],

    # ---- 4.4 Antarmuka (wireframe lite — digenerate otomatis) ----------------
    "antarmuka": [],   # diisi fungsi _antarmuka() di bawah (biar ringkas)

    # ---- 4.5 Implementasi (screenshot asli — diisi otomatis saat capture) ----
    "implementasi": [],  # diisi fungsi _implementasi() di bawah

    # ---- 4.6 Pengujian Black Box ---------------------------------------------
    "pengujian": {"blackbox": [
        {"judul": "Autentikasi dan Registrasi Akun", "cases": [
            {"input": "Login admin dengan email dan password yang benar",
             "expect": "Masuk dan diarahkan ke dashboard admin",
             "observe": "Berhasil masuk ke dashboard admin", "result": "Sesuai"},
            {"input": "Login pelanggan dengan nomor WhatsApp dan password yang benar",
             "expect": "Masuk dan diarahkan ke dashboard pelanggan",
             "observe": "Berhasil masuk ke dashboard pelanggan", "result": "Sesuai"},
            {"input": "Login dengan password yang salah",
             "expect": "Sistem menolak dan menampilkan pesan kesalahan",
             "observe": "Muncul pesan kombinasi tidak cocok", "result": "Sesuai"},
            {"input": "Gagal login 8 kali berturut-turut dari IP yang sama",
             "expect": "Sistem memblokir percobaan login selama 15 menit",
             "observe": "Muncul pesan terlalu banyak percobaan", "result": "Sesuai"},
            {"input": "Registrasi dengan nomor WhatsApp yang sudah terdaftar",
             "expect": "Sistem menolak dan menampilkan pesan nomor sudah dipakai",
             "observe": "Registrasi ditolak dengan pesan validasi", "result": "Sesuai"},
            {"input": "Klik logout",
             "expect": "Sesi dihapus dan kembali ke halaman login",
             "observe": "Sesi berakhir, kembali ke halaman login", "result": "Sesuai"},
        ]},
        {"judul": "Katalog Layanan dan Promo", "cases": [
            {"input": "Membuka halaman beranda",
             "expect": "Tampil layanan unggulan dengan badge promo didahulukan",
             "observe": "Layanan unggulan dan promo tampil", "result": "Sesuai"},
            {"input": "Memfilter daftar layanan berdasarkan kategori",
             "expect": "Hanya layanan pada kategori terpilih yang tampil",
             "observe": "Filter kategori berfungsi", "result": "Sesuai"},
            {"input": "Membuka detail layanan yang sedang promo",
             "expect": "Galeri foto tampil, harga normal dicoret dan harga promo tampil",
             "observe": "Carousel galeri dan harga promo tampil benar", "result": "Sesuai"},
            {"input": "Membuka detail layanan yang dinonaktifkan pemilik",
             "expect": "Halaman tidak ditemukan / layanan tidak tampil di katalog",
             "observe": "Layanan nonaktif tidak dapat diakses", "result": "Sesuai"},
        ]},
        {"judul": "Pemesanan Booking Online", "cases": [
            {"input": "Booking layanan 90 menit dengan slot kosong dan bukti DP valid",
             "expect": "Booking tersimpan, kode SW-YYYYMMDD-NNN terbit, 3 slot tertahan",
             "observe": "Kode booking terbit dan 3 baris slot berstatus held", "result": "Sesuai"},
            {"input": "Booking pada slot yang sudah ditahan booking lain",
             "expect": "Sistem menolak dengan pesan slot tidak tersedia",
             "observe": "Muncul pesan slot waktu tidak tersedia", "result": "Sesuai"},
            {"input": "Booking dengan jam selesai melewati jam tutup salon",
             "expect": "Sistem menolak karena di luar jam operasional",
             "observe": "Muncul pesan di luar jam operasional", "result": "Sesuai"},
            {"input": "Submit booking tanpa mengunggah bukti DP",
             "expect": "Validasi gagal, booking tidak tersimpan",
             "observe": "Muncul pesan bukti DP wajib diunggah", "result": "Sesuai"},
            {"input": "Mengunggah bukti DP berupa file bukan gambar / lebih dari 2 MB",
             "expect": "Sistem menolak file yang tidak valid",
             "observe": "Muncul pesan validasi berkas", "result": "Sesuai"},
            {"input": "Mengakses halaman /booking tanpa login",
             "expect": "Dialihkan ke halaman login",
             "observe": "Pengguna diarahkan ke /login", "result": "Sesuai"},
        ]},
        {"judul": "Cek Status dan Pembatalan Booking", "cases": [
            {"input": "Cek booking dengan kode yang valid",
             "expect": "Detail booking dan status tampil",
             "observe": "Detail booking tampil sesuai kode", "result": "Sesuai"},
            {"input": "Cek booking dengan kode yang salah",
             "expect": "Pesan booking tidak ditemukan",
             "observe": "Muncul pesan tidak ditemukan", "result": "Sesuai"},
            {"input": "Gagal cek kode 5 kali berturut-turut dari IP yang sama",
             "expect": "Sistem memblokir pengecekan selama 15 menit",
             "observe": "Muncul pesan terlalu banyak percobaan", "result": "Sesuai"},
            {"input": "Pelanggan membatalkan booking lebih dari 2 jam sebelum jadwal",
             "expect": "Status menjadi cancelled dan slot dilepas",
             "observe": "Booking batal, slot kembali tersedia", "result": "Sesuai"},
            {"input": "Pelanggan membatalkan kurang dari 2 jam sebelum jadwal",
             "expect": "Sistem menolak pembatalan",
             "observe": "Muncul pesan minimal 2 jam sebelum jadwal", "result": "Sesuai"},
        ]},
        {"judul": "Verifikasi Booking dan DP oleh Admin", "cases": [
            {"input": "Admin memverifikasi booking berstatus menunggu verifikasi",
             "expect": "Status menjadi accepted, log tercatat, email konfirmasi terkirim",
             "observe": "Status berubah accepted dan log bertambah", "result": "Sesuai"},
            {"input": "Admin menolak booking dengan alasan",
             "expect": "Status menjadi rejected dan slot dilepas",
             "observe": "Status rejected, slot terhapus dari booking_slots", "result": "Sesuai"},
            {"input": "Admin memverifikasi bukti DP pada booking pending",
             "expect": "payment_status menjadi dp_verified dan booking otomatis diterima",
             "observe": "DP terverifikasi dan status menjadi accepted", "result": "Sesuai"},
            {"input": "Admin menandai pesan WhatsApp telah dikirim",
             "expect": "Penanda wa_sent aktif dan tercatat pada log",
             "observe": "Penanda dan log tercatat", "result": "Sesuai"},
        ]},
        {"judul": "Booking Walk-in dan Jadwal Harian", "cases": [
            {"input": "Admin mencatat booking walk-in pada slot kosong",
             "expect": "Booking langsung berstatus accepted tanpa DP",
             "observe": "Walk-in tersimpan dengan status accepted", "result": "Sesuai"},
            {"input": "Admin mencatat walk-in pada slot yang sudah terisi",
             "expect": "Sistem menolak dengan pesan slot tidak tersedia",
             "observe": "Muncul pesan slot tidak tersedia", "result": "Sesuai"},
            {"input": "Membuka halaman jadwal harian",
             "expect": "Papan jadwal per slot 30 menit tampil sesuai booking aktif",
             "observe": "Papan jadwal tampil dengan blok sesuai durasi", "result": "Sesuai"},
        ]},
        {"judul": "Penyelesaian Booking dan Transaksi", "cases": [
            {"input": "Menyelesaikan booking tanpa biaya tambahan",
             "expect": "Status completed dan transaksi tercatat sebesar harga layanan",
             "observe": "Transaksi tercatat dengan nominal sesuai", "result": "Sesuai"},
            {"input": "Menyelesaikan booking dengan biaya tambahan beserta catatan",
             "expect": "Nominal transaksi = harga layanan + biaya tambahan",
             "observe": "Rincian base, tambahan, dan total benar", "result": "Sesuai"},
            {"input": "Mengisi biaya tambahan tanpa catatan",
             "expect": "Validasi gagal, catatan wajib diisi",
             "observe": "Muncul pesan catatan wajib diisi", "result": "Sesuai"},
            {"input": "Menyelesaikan dengan mode nominal manual",
             "expect": "Transaksi tercatat sebesar nominal manual",
             "observe": "Nominal manual tersimpan dengan benar", "result": "Sesuai"},
            {"input": "Menekan tombol selesaikan dua kali (double submit)",
             "expect": "Hanya satu transaksi yang tercatat",
             "observe": "Transaksi tetap satu (idempoten)", "result": "Sesuai"},
        ]},
        {"judul": "Kelola Layanan dan Promo oleh Pemilik", "cases": [
            {"input": "Menambah layanan baru beserta galeri foto",
             "expect": "Layanan tersimpan dan tampil pada katalog publik",
             "observe": "Layanan baru tampil di katalog", "result": "Sesuai"},
            {"input": "Mengatur promo persen dengan rentang tanggal",
             "expect": "Harga promo dipakai pada katalog, booking, dan transaksi",
             "observe": "Harga final konsisten di seluruh halaman", "result": "Sesuai"},
            {"input": "Menonaktifkan layanan",
             "expect": "Layanan hilang dari katalog dan form booking",
             "observe": "Layanan tidak tampil lagi", "result": "Sesuai"},
            {"input": "Menghapus layanan yang memiliki riwayat booking",
             "expect": "Layanan di-soft delete agar riwayat tetap utuh",
             "observe": "Layanan tersembunyi, riwayat booking aman", "result": "Sesuai"},
            {"input": "Admin (bukan pemilik) membuka halaman kelola layanan",
             "expect": "Akses ditolak dan dialihkan ke dashboard admin",
             "observe": "Admin dialihkan ke dashboard", "result": "Sesuai"},
        ]},
        {"judul": "Laporan dan Dashboard", "cases": [
            {"input": "Membuka dashboard admin",
             "expect": "Kartu ringkasan booking hari ini, pending, dan selesai tampil",
             "observe": "Angka ringkasan sesuai data booking", "result": "Sesuai"},
            {"input": "Membuka laporan pendapatan pemilik setelah ada transaksi",
             "expect": "Pendapatan hari ini sesuai jumlah nominal transaksi",
             "observe": "Nilai pendapatan sesuai", "result": "Sesuai"},
            {"input": "Mengganti mode grafik (hari/minggu/bulan) dan menggeser periode",
             "expect": "Grafik berubah sesuai mode dan periode terpilih",
             "observe": "Grafik dinamis berfungsi", "result": "Sesuai"},
        ]},
        {"judul": "Pengaturan Sistem", "cases": [
            {"input": "Mengubah jam operasional salon",
             "expect": "Daftar slot pada form booking mengikuti jam baru",
             "observe": "Slot booking berubah sesuai jam baru", "result": "Sesuai"},
            {"input": "Mengubah template pesan WhatsApp dengan placeholder",
             "expect": "Pratinjau pesan menampilkan data booking sebenarnya",
             "observe": "Placeholder terisi data booking", "result": "Sesuai"},
            {"input": "Mengganti password staf dengan password lama yang salah",
             "expect": "Sistem menolak penggantian password",
             "observe": "Muncul pesan password lama salah", "result": "Sesuai"},
        ]},
    ]},
}


# ---------------------------------------------------------------------------- #
# 4.4 — daftar halaman + komponen wireframe lite (mockup digenerate otomatis)
# ---------------------------------------------------------------------------- #
def _nav_public(menu_extra=None):
    return {"type": "navbar", "logo": True, "title": "SW Beauty Salon",
            "menu": (menu_extra or ["Beranda", "Layanan", "Cek Booking", "Masuk"])}


def _side_admin(extra=False):
    items = ["Dashboard", "Booking", "Jadwal", "Walk-in", "Pelanggan",
             "Transaksi", "Pengaturan"]
    if extra:
        items += ["Laporan", "Layanan"]
    return {"type": "sidebar", "items": items + ["Logout"]}


def _antarmuka():
    A = []

    A.append({"title": "Halaman Login", "desc":
        "Halaman login terpadu digunakan oleh seluruh peran. Pelanggan masuk "
        "menggunakan nomor WhatsApp sedangkan staf (admin/pemilik) menggunakan "
        "email; sistem mengarahkan pengguna ke dashboard sesuai perannya.",
        "mockup": [_nav_public(),
            {"type": "form", "title": "Masuk",
             "fields": [{"label": "Email atau Nomor WhatsApp"},
                        {"label": "Password", "type": "password"}],
             "submit": "Masuk"}]})

    A.append({"title": "Halaman Registrasi Pelanggan", "desc":
        "Halaman registrasi dipakai calon pelanggan untuk membuat akun dengan "
        "mengisi nama, nomor WhatsApp, dan password. Setelah berhasil, "
        "pelanggan otomatis masuk ke dashboard.",
        "mockup": [_nav_public(),
            {"type": "form", "title": "Daftar Akun",
             "fields": [{"label": "Nama Lengkap"},
                        {"label": "Nomor WhatsApp"},
                        {"label": "Password", "type": "password"},
                        {"label": "Ulangi Password", "type": "password"}],
             "submit": "Daftar"}]})

    A.append({"title": "Halaman Beranda", "desc":
        "Halaman beranda menampilkan sambutan salon, layanan unggulan (promo "
        "didahulukan), serta tombol ajakan memesan layanan.",
        "mockup": [_nav_public(),
            {"type": "heading", "text": "SW Beauty Salon"},
            {"type": "text", "text": "Perawatan kecantikan terbaik di Tabanan"},
            {"type": "cards", "items": [
                {"title": "Layanan A", "value": "Rp 150.000"},
                {"title": "Layanan B", "value": "Rp 95.000"},
                {"title": "Layanan C", "value": "Rp 50.000"}]},
            {"type": "buttons", "items": ["Pesan Sekarang", "Lihat Semua Layanan"]}]})

    A.append({"title": "Halaman Daftar Layanan", "desc":
        "Halaman katalog menampilkan seluruh layanan aktif dalam bentuk kartu "
        "ber-cover foto beserta filter kategori dan badge promo.",
        "mockup": [_nav_public(),
            {"type": "heading", "text": "Daftar Layanan"},
            {"type": "buttons", "items": ["Semua", "Hair", "Nails", "Face", "Body"]},
            {"type": "cards", "items": [
                {"title": "Layanan A", "value": "Rp 150.000"},
                {"title": "Layanan B", "value": "Rp 95.000"},
                {"title": "Layanan C", "value": "Rp 50.000"}]},
            {"type": "image", "label": "Galeri Layanan", "h": 140}]})

    A.append({"title": "Halaman Detail Layanan", "desc":
        "Halaman detail menampilkan galeri foto (carousel), deskripsi, durasi, "
        "harga (dengan coretan bila promo), dan tombol pesan yang menyesuaikan "
        "status login pengguna.",
        "mockup": [_nav_public(),
            {"type": "heading", "text": "Detail Layanan"},
            {"type": "image", "label": "Galeri Foto", "h": 180},
            {"type": "text", "text": "Layanan A — durasi 90 menit — Rp 150.000"},
            {"type": "buttons", "items": ["Pesan Layanan Ini"]}]})

    A.append({"title": "Halaman Form Booking", "desc":
        "Form booking memandu pelanggan memilih layanan, tanggal, dan slot "
        "waktu bergaya pemilihan kursi bioskop (tersedia / dipilih / ditahan / "
        "terisi), lalu mengunggah bukti pembayaran DP.",
        "mockup": [_nav_public(["Beranda", "Layanan", "Dashboard", "Logout"]),
            {"type": "heading", "text": "Booking Layanan"},
            {"type": "form", "title": "Data Booking",
             "fields": [{"label": "Layanan"}, {"label": "Tanggal"},
                        {"label": "Slot Waktu"}, {"label": "Catatan"},
                        {"label": "Bukti DP (gambar)"}],
             "submit": "Kirim Booking"},
            {"type": "table", "title": "Slot Waktu",
             "columns": ["08:00", "08:30", "09:00", "09:30", "10:00", "10:30"],
             "dummy_rows": 1}]})

    A.append({"title": "Halaman Booking Berhasil", "desc":
        "Setelah booking tersimpan, sistem menampilkan kode booking, ringkasan "
        "pesanan, nominal DP, serta tombol WhatsApp untuk menghubungi salon.",
        "mockup": [_nav_public(["Beranda", "Layanan", "Dashboard", "Logout"]),
            {"type": "heading", "text": "Booking Berhasil"},
            {"type": "text", "text": "Kode Booking: SW-20260610-001"},
            {"type": "table", "title": "Ringkasan",
             "columns": ["Layanan", "Tanggal", "Jam", "DP"], "dummy_rows": 1},
            {"type": "buttons", "items": ["Hubungi via WhatsApp", "Lihat Dashboard"]}]})

    A.append({"title": "Halaman Cek Booking", "desc":
        "Halaman publik untuk memeriksa status booking dengan memasukkan kode "
        "booking yang diterima pelanggan.",
        "mockup": [_nav_public(),
            {"type": "form", "title": "Cek Booking",
             "fields": [{"label": "Kode Booking"}], "submit": "Cek"},
            {"type": "table", "title": "Hasil",
             "columns": ["Kode", "Layanan", "Jadwal", "Status"], "dummy_rows": 1}]})

    A.append({"title": "Halaman Dashboard Pelanggan", "desc":
        "Dashboard pelanggan menampilkan riwayat seluruh booking miliknya "
        "beserta badge status dan tautan menuju detail tiap booking.",
        "mockup": [_nav_public(["Beranda", "Layanan", "Booking Baru", "Logout"]),
            {"type": "heading", "text": "Dashboard Saya"},
            {"type": "table", "title": "Riwayat Booking",
             "columns": ["No", "Kode", "Layanan", "Jadwal", "Status", "Aksi"],
             "dummy_rows": 4}]})

    A.append({"title": "Halaman Detail Booking Pelanggan", "desc":
        "Halaman detail booking pelanggan menampilkan data booking milik akun "
        "yang sedang login (kepemilikan divalidasi lewat sesi), lini masa "
        "status, serta tombol pembatalan bila masih memenuhi syarat minimal "
        "2 jam sebelum jadwal.",
        "mockup": [_nav_public(["Beranda", "Layanan", "Booking Baru", "Logout"]),
            {"type": "heading", "text": "Detail Booking SW-20260610-001"},
            {"type": "table", "title": "Data Booking",
             "columns": ["Layanan", "Tanggal", "Jam", "DP", "Status"],
             "dummy_rows": 1},
            {"type": "table", "title": "Riwayat Status",
             "columns": ["Waktu", "Kejadian"], "dummy_rows": 3},
            {"type": "buttons", "items": ["Batalkan Booking", "Kembali"]}]})

    A.append({"title": "Halaman Dashboard Admin", "desc":
        "Dashboard admin menampilkan kartu ringkasan (booking hari ini, "
        "menunggu verifikasi, diterima, selesai) yang dapat diklik menuju "
        "daftar terkait, beserta booking terbaru.",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(),
            {"type": "heading", "text": "Dashboard"},
            {"type": "cards", "items": [
                {"title": "Booking Hari Ini", "value": "8"},
                {"title": "Menunggu Verifikasi", "value": "3"},
                {"title": "Diterima Hari Ini", "value": "4"},
                {"title": "Selesai Hari Ini", "value": "2"}]},
            {"type": "table", "title": "Booking Terbaru",
             "columns": ["Kode", "Pelanggan", "Layanan", "Jadwal", "Status"],
             "dummy_rows": 4}]})

    A.append({"title": "Halaman Daftar Booking (Admin)", "desc":
        "Halaman daftar booking menyediakan filter status, tanggal, bulan, dan "
        "pencarian nama/kode/nomor WhatsApp untuk mengelola seluruh booking.",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(),
            {"type": "heading", "text": "Daftar Booking"},
            {"type": "buttons", "items": ["Semua", "Menunggu", "Diterima", "Selesai", "Batal"]},
            {"type": "table", "title": "Booking",
             "columns": ["No", "Kode", "Pelanggan", "Layanan", "Jadwal", "Status", "Aksi"],
             "dummy_rows": 5}]})

    A.append({"title": "Halaman Detail Booking (Admin)", "desc":
        "Halaman detail menampilkan data booking, bukti DP, riwayat log, "
        "pratinjau pesan WhatsApp, serta tombol aksi (verifikasi, tolak, "
        "selesaikan, batalkan).",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(),
            {"type": "heading", "text": "Detail Booking SW-20260610-001"},
            {"type": "table", "title": "Data Booking",
             "columns": ["Pelanggan", "Layanan", "Jadwal", "DP", "Status"],
             "dummy_rows": 1},
            {"type": "image", "label": "Bukti DP", "h": 120},
            {"type": "buttons", "items": ["Verifikasi", "Tolak", "Selesaikan", "Batalkan"]}]})

    A.append({"title": "Halaman Jadwal Harian (Admin)", "desc":
        "Papan jadwal menampilkan seluruh slot 30 menit pada satu tanggal; "
        "booking berdurasi panjang tampil sebagai blok yang membentang "
        "beberapa slot.",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(),
            {"type": "heading", "text": "Jadwal Harian"},
            {"type": "table", "title": "Slot",
             "columns": ["Jam", "Booking", "Status"], "dummy_rows": 6}]})

    A.append({"title": "Halaman Booking Walk-in (Admin)", "desc":
        "Form walk-in dipakai admin mencatat pelanggan yang datang langsung; "
        "booking langsung berstatus diterima tanpa DP.",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(),
            {"type": "heading", "text": "Booking Walk-in"},
            {"type": "form", "title": "Data Walk-in",
             "fields": [{"label": "Nama Pelanggan"}, {"label": "Nomor WhatsApp"},
                        {"label": "Layanan"}, {"label": "Tanggal"},
                        {"label": "Slot Waktu"}],
             "submit": "Simpan"}]})

    A.append({"title": "Halaman Kelola Pelanggan (Admin)", "desc":
        "Halaman ini menampilkan seluruh akun pelanggan beserta jumlah booking "
        "dan menyediakan aksi ubah nama serta reset password.",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(),
            {"type": "heading", "text": "Kelola Pelanggan"},
            {"type": "table", "title": "Pelanggan",
             "columns": ["No", "Nama", "Nomor WA", "Jumlah Booking", "Aksi"],
             "dummy_rows": 4}]})

    A.append({"title": "Halaman Transaksi (Admin)", "desc":
        "Halaman transaksi menampilkan rekap pembayaran booking selesai dengan "
        "filter rentang tanggal serta ringkasan total, jumlah, dan rata-rata.",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(),
            {"type": "heading", "text": "Transaksi"},
            {"type": "cards", "items": [
                {"title": "Total", "value": "Rp 4.250.000"},
                {"title": "Jumlah", "value": "17"},
                {"title": "Rata-rata", "value": "Rp 250.000"}]},
            {"type": "table", "title": "Daftar Transaksi",
             "columns": ["No", "Kode", "Layanan", "Dasar", "Tambahan", "Total", "Metode"],
             "dummy_rows": 4}]})

    A.append({"title": "Halaman Pengaturan (Admin)", "desc":
        "Halaman pengaturan dipakai mengubah profil salon, jam operasional, "
        "rentang hari booking, informasi DP, dan template pesan WhatsApp.",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(),
            {"type": "heading", "text": "Pengaturan"},
            {"type": "form", "title": "Profil & Operasional",
             "fields": [{"label": "Nama Salon"}, {"label": "Alamat"},
                        {"label": "Jam Buka"}, {"label": "Jam Tutup"},
                        {"label": "Rentang Hari Booking"},
                        {"label": "Template WA Diterima"}],
             "submit": "Simpan"}]})

    A.append({"title": "Halaman Laporan (Pemilik)", "desc":
        "Halaman laporan menampilkan KPI pendapatan, grafik dinamis per "
        "hari/minggu/bulan dengan navigasi periode, distribusi status booking, "
        "dan layanan terlaris.",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(extra=True),
            {"type": "heading", "text": "Laporan Pendapatan"},
            {"type": "cards", "items": [
                {"title": "Hari Ini", "value": "Rp 850.000"},
                {"title": "Kemarin", "value": "Rp 600.000"},
                {"title": "Bulan Ini", "value": "Rp 12.400.000"}]},
            {"type": "image", "label": "Grafik Pendapatan", "h": 170},
            {"type": "table", "title": "Layanan Terlaris",
             "columns": ["No", "Layanan", "Jumlah", "Pendapatan"], "dummy_rows": 3}]})

    A.append({"title": "Halaman Kelola Layanan (Pemilik)", "desc":
        "Halaman CRUD layanan menampilkan daftar layanan beserta kategori, "
        "durasi, harga, dan promo; form tambah/ubah memuat pemilih ikon "
        "visual, galeri foto, dan pengaturan promo.",
        "mockup": [_nav_public(["Panel Admin", "Logout"]), _side_admin(extra=True),
            {"type": "heading", "text": "Kelola Layanan"},
            {"type": "buttons", "items": ["Tambah Layanan"]},
            {"type": "table", "title": "Layanan",
             "columns": ["No", "Nama", "Kategori", "Durasi", "Harga", "Promo", "Aksi"],
             "dummy_rows": 4}]})

    return A


# ---------------------------------------------------------------------------- #
# 4.5 — halaman yang ditangkap screenshot (role, nama file, url, judul, deskripsi)
#       url boleh memuat {layanan_id} {booking_id} {kode} — diisi dari database.
# ---------------------------------------------------------------------------- #
# URUTAN & JUDUL halaman di sini = SATU-SATUNYA sumber kebenaran untuk 4.4 dan
# 4.5 — keduanya WAJIB memuat halaman yang sama persis (judul + urutan).
SHOT_PAGES = [
    ("guest", "login", "/login", "Halaman Login",
     "Implementasi halaman login terpadu. Pelanggan masuk dengan nomor WhatsApp "
     "dan staf dengan email; satu form yang sama mengarahkan pengguna ke "
     "dashboard sesuai perannya, dilengkapi pembatasan 8 kali percobaan gagal."),
    ("guest", "register", "/register", "Halaman Registrasi Pelanggan",
     "Implementasi halaman registrasi pelanggan dengan validasi format nomor "
     "WhatsApp dan kekuatan password; nomor yang sudah terdaftar ditolak."),
    ("guest", "beranda", "/", "Halaman Beranda",
     "Implementasi halaman beranda yang menampilkan layanan unggulan dengan "
     "badge promo didahulukan serta tombol ajakan memesan layanan."),
    ("guest", "layanan", "/layanan", "Halaman Daftar Layanan",
     "Implementasi katalog layanan dengan kartu ber-cover foto, filter chip "
     "kategori, harga coret untuk layanan promo, dan tautan menuju detail."),
    ("guest", "layanan_detail", "/layanan/{layanan_id}", "Halaman Detail Layanan",
     "Implementasi halaman detail layanan dengan galeri carousel, deskripsi, "
     "durasi, harga final setelah promo, dan tombol pesan sadar-login."),
    ("pelanggan", "booking_form", "/booking", "Halaman Form Booking",
     "Implementasi form booking dengan pemilih slot bergaya bioskop lima "
     "status (tersedia, dipilih, ditahan, terisi, lewat), informasi DP, dan "
     "unggah bukti pembayaran."),
    ("pelanggan", "booking_sukses", "/booking/sukses/{kode}",
     "Halaman Booking Berhasil",
     "Implementasi halaman booking berhasil yang menampilkan kode booking, "
     "ringkasan pesanan, nominal DP, dan tombol WhatsApp untuk menghubungi "
     "salon."),
    ("guest", "cek_booking", "/cek-booking", "Halaman Cek Booking",
     "Implementasi halaman cek booking publik berbasis kode booking dengan "
     "pembatasan 5 kali kegagalan per 15 menit untuk mencegah penebakan kode."),
    ("pelanggan", "pelanggan_dashboard", "/pelanggan/dashboard",
     "Halaman Dashboard Pelanggan",
     "Implementasi dashboard pelanggan berisi riwayat seluruh booking milik "
     "akun beserta badge status dan tautan detail."),
    ("pelanggan", "pelanggan_booking_detail", "/pelanggan/booking/{kode}",
     "Halaman Detail Booking Pelanggan",
     "Implementasi halaman detail booking milik pelanggan login (kepemilikan "
     "divalidasi lewat sesi) dengan lini masa status dan tombol pembatalan "
     "bila masih memenuhi syarat minimal 2 jam sebelum jadwal."),
    ("admin", "admin_dashboard", "/admin/dashboard", "Halaman Dashboard Admin",
     "Implementasi dashboard admin dengan kartu ringkasan yang dapat diklik "
     "(hari ini, menunggu verifikasi, diterima, selesai) dan booking terbaru."),
    ("admin", "admin_booking", "/admin/booking", "Halaman Daftar Booking (Admin)",
     "Implementasi daftar booking dengan filter status, tanggal, bulan, dan "
     "pencarian nama/kode/nomor WhatsApp."),
    ("admin", "admin_booking_detail", "/admin/booking/{booking_id}",
     "Halaman Detail Booking (Admin)",
     "Implementasi detail booking admin: data pelanggan, bukti DP, riwayat "
     "log, pratinjau template WhatsApp, serta aksi verifikasi/tolak/"
     "selesaikan/batalkan."),
    ("admin", "admin_jadwal", "/admin/booking/jadwal",
     "Halaman Jadwal Harian (Admin)",
     "Implementasi papan jadwal harian per slot 30 menit; booking berdurasi "
     "panjang tampil membentang sesuai jumlah slotnya."),
    ("admin", "admin_walkin", "/admin/booking/walkin",
     "Halaman Booking Walk-in (Admin)",
     "Implementasi form walk-in untuk pelanggan datang langsung; booking "
     "langsung diterima tanpa DP."),
    ("admin", "admin_pelanggan", "/admin/pelanggan",
     "Halaman Kelola Pelanggan (Admin)",
     "Implementasi halaman kelola akun pelanggan: pencarian, ubah nama, dan "
     "reset password."),
    ("admin", "admin_transaksi", "/admin/transaksi", "Halaman Transaksi (Admin)",
     "Implementasi rekap transaksi dengan filter rentang tanggal, rincian "
     "harga dasar + biaya tambahan, dan ringkasan total."),
    ("admin", "admin_pengaturan", "/admin/pengaturan",
     "Halaman Pengaturan (Admin)",
     "Implementasi halaman pengaturan salon: profil, jam operasional, rentang "
     "hari booking, info DP, dan template pesan WhatsApp."),
    ("pemilik", "owner_laporan", "/owner/laporan", "Halaman Laporan (Pemilik)",
     "Implementasi laporan pemilik dengan KPI pendapatan, grafik Chart.js "
     "dinamis (hari/minggu/bulan + geser periode), distribusi status, dan "
     "layanan terlaris."),
    ("pemilik", "owner_layanan", "/owner/layanan", "Halaman Kelola Layanan (Pemilik)",
     "Implementasi halaman kelola layanan pemilik: daftar layanan, pemilih "
     "ikon visual, galeri foto, dan pengaturan promo per layanan."),
]


def _implementasi(shots_dir: pathlib.Path):
    items = []
    for _role, name, _url, title, desc in SHOT_PAGES:
        img = shots_dir / f"{name}.png"
        it = {"title": title, "desc": desc}
        if img.exists():
            it["image"] = str(img)
        items.append(it)
    return items


# ============================================================================ #
# 2. NARASI — penjelasan 4.2 & 4.3 (mengikuti gaya contoh BABIV.docx)
# ============================================================================ #

NARASI = {
    "perancangan_intro":
        "Tahapan perancangan merupakan penggambaran alur data dari Sistem "
        "Informasi Booking SW Beauty Salon menggunakan Data Flow Diagram (DFD), "
        "mulai dari Diagram Konteks sebagai gambaran paling umum, DFD Level 0 "
        "yang menjabarkan proses-proses utama, hingga DFD Level 1 yang merinci "
        "sub-proses pada setiap proses utama. Perancangan ini menjadi acuan "
        "dalam tahap implementasi sistem.",

    "konteks_intro":
        "Diagram Konteks merupakan representasi tingkat tertinggi yang "
        "memberikan gambaran umum mengenai batasan dan ruang lingkup Sistem "
        "Informasi Booking SW Beauty Salon. Dalam diagram ini seluruh operasi "
        "sistem direpresentasikan sebagai satu kesatuan proses utama yang "
        "berinteraksi langsung dengan tiga entitas eksternal, yaitu Pelanggan, "
        "Admin, dan Pemilik. Aliran berlabel data_ menunjukkan masukan dari "
        "entitas ke sistem, sedangkan aliran berlabel info_ menunjukkan "
        "keluaran dari sistem kepada entitas.",
    "konteks_rincian": "Berikut adalah rincian aliran data masuk dan keluar "
        "dari masing-masing entitas ke dalam sistem:",
    "konteks_entitas": [
        ("Pelanggan", [
            "Aliran Data Masuk: Pelanggan mengirimkan data registrasi dan login "
            "(data_registrasi_login), permintaan melihat katalog layanan "
            "(data_pilih_katalog), data pemesanan beserta pilihan slot dan bukti "
            "DP (data_booking_slot), serta permintaan pembatalan booking "
            "(data_pembatalan_booking).",
            "Aliran Data Keluar: Sebagai balasan, sistem memberikan sesi login "
            "pelanggan (info_sesi_pelanggan), informasi katalog beserta promo "
            "(info_katalog_promo), kode booking sebagai bukti pemesanan "
            "(info_kode_booking), dan status booking terkini "
            "(info_status_booking).",
        ]),
        ("Admin", [
            "Aliran Data Masuk: Admin memberikan data login staf "
            "(data_login_staf), keputusan verifikasi booking dan bukti DP "
            "(data_verifikasi_booking_dp), pencatatan booking walk-in "
            "(data_walkin), penyelesaian booking beserta rincian pembayaran "
            "(data_penyelesaian_booking), serta perubahan pengaturan salon "
            "(data_pengaturan_salon).",
            "Aliran Data Keluar: Sistem menyajikan sesi login staf "
            "(info_sesi_staf), daftar booking yang harus diproses "
            "(info_daftar_booking), jadwal harian per slot (info_jadwal_harian), "
            "rekap transaksi (info_rekap_transaksi), dan konfirmasi pengaturan "
            "tersimpan (info_pengaturan_tersimpan).",
        ]),
        ("Pemilik", [
            "Aliran Data Masuk: Pemilik mengirimkan data login pemilik "
            "(data_login_pemilik), pengelolaan data layanan beserta promo "
            "(data_kelola_layanan), dan permintaan laporan pendapatan "
            "(data_permintaan_laporan).",
            "Aliran Data Keluar: Sistem membalas dengan sesi login pemilik "
            "(info_sesi_pemilik), informasi layanan dan promo yang dikelola "
            "(info_layanan_promo), serta laporan pendapatan dan analitik "
            "(info_laporan_pendapatan).",
        ]),
    ],

    "level0_intro":
        "DFD Level 0 menjabarkan Diagram Konteks menjadi 7 (tujuh) proses utama "
        "yang saling terhubung. Seluruh proses berinteraksi dengan tiga entitas "
        "eksternal (Pelanggan, Admin, dan Pemilik) serta memakai 7 (tujuh) data "
        "store, yaitu D1 users, D2 layanan, D3 bookings, D4 booking_slots, "
        "D5 transaksi, D6 settings, dan D7 booking_logs. Setiap data store "
        "ditulis (aliran data_) sekaligus dibaca (aliran info_) sehingga aliran "
        "data berjalan dua arah. Adapun penjelasan tiap proses adalah sebagai "
        "berikut:",
    "level0_proses": [
        "Proses 1.0 (Autentikasi & Akun): Menangani registrasi akun pelanggan, "
        "login terpadu satu pintu (pelanggan memakai nomor WhatsApp, staf "
        "memakai email), serta pengelolaan akun pelanggan oleh admin (ubah nama "
        "dan reset password). Data kredensial divalidasi terhadap dan disimpan "
        "pada D1 users.",
        "Proses 2.0 (Kelola Layanan & Promo): Digunakan Pemilik untuk mengelola "
        "katalog layanan beserta galeri foto, ikon, dan promo yang disimpan "
        "pada D2 layanan. Data dari penyimpanan tersebut kemudian ditampilkan "
        "kepada Pelanggan sebagai katalog dan detail layanan.",
        "Proses 3.0 (Pemesanan & Slot Booking): Mengakomodasi pengajuan booking "
        "online oleh Pelanggan (termasuk unggah bukti DP), pencatatan walk-in "
        "oleh Admin, serta pengecekan dan pembatalan booking. Proses ini "
        "membaca harga dan durasi dari D2 layanan, merekam booking ke "
        "D3 bookings, menahan slot waktu pada D4 booking_slots, dan mencatat "
        "setiap aktivitas ke D7 booking_logs.",
        "Proses 4.0 (Verifikasi Booking & DP): Admin memeriksa bukti pembayaran "
        "DP dan memutuskan menerima atau menolak booking. Status booking dan "
        "status pembayaran diperbarui pada D3 bookings, lalu konfirmasi "
        "dikirimkan kepada Pelanggan melalui email dan template WhatsApp.",
        "Proses 5.0 (Penyelesaian & Transaksi): Menangani penyelesaian booking "
        "yang telah dilayani. Status booking pada D3 bookings diubah menjadi "
        "selesai dan satu transaksi pembayaran (harga dasar ditambah biaya "
        "tambahan, atau nominal manual) dicatat ke D5 transaksi untuk "
        "direkap oleh Admin.",
        "Proses 6.0 (Laporan & Dashboard): Membaca data dari D3 bookings dan "
        "D5 transaksi untuk menyusun ringkasan operasional harian bagi Admin "
        "serta laporan pendapatan dengan grafik dinamis bagi Pemilik.",
        "Proses 7.0 (Pengaturan Sistem): Admin mengubah profil salon, jam "
        "operasional, rentang hari booking, informasi DP, dan template pesan "
        "WhatsApp yang seluruhnya disimpan dan dibaca dari D6 settings.",
    ],

    "level1_intro":
        "DFD Level 1 merupakan dekomposisi dari setiap proses utama pada DFD "
        "Level 0 menjadi sub-proses yang lebih rinci sebagai berikut.",
    "level1": {
        "1.0": [
            "1.1 Registrasi Akun Pelanggan: Pelanggan mengirimkan data registrasi "
            "berupa nama, nomor WhatsApp, dan password. Sistem memvalidasi "
            "keunikan nomor, menyimpan akun baru ke D1 users, lalu memberikan "
            "konfirmasi akun terdaftar kepada Pelanggan.",
            "1.2 Validasi Login Terpadu: Pelanggan, Admin, dan Pemilik masuk "
            "melalui satu form yang sama. Sistem mendeteksi jenis identitas "
            "(email atau nomor WhatsApp), mencocokkan kredensial dengan "
            "D1 users, menerapkan pembatasan 8 kali kegagalan per 15 menit, dan "
            "menerbitkan sesi sesuai peran masing-masing.",
            "1.3 Kelola Akun Pelanggan: Admin melihat daftar akun pelanggan dari "
            "D1 users, lalu dapat memperbarui nama atau mereset password; "
            "perubahan disimpan kembali ke D1 users.",
        ],
        "2.0": [
            "2.1 Tambah/Ubah Layanan: Pemilik memasukkan data layanan (nama, "
            "kategori, durasi, harga, ikon). Sistem menyimpan ke D2 layanan dan "
            "menampilkan kembali daftar layanan yang telah diperbarui.",
            "2.2 Kelola Galeri & Promo: Pemilik mengunggah foto galeri serta "
            "mengatur persentase, rentang tanggal, dan deskripsi promo. Galeri "
            "lama dibaca dari D2 layanan untuk diubah, lalu perubahan disimpan "
            "kembali sehingga harga final promo berlaku konsisten.",
            "2.3 Tampilkan Katalog: Pelanggan memilih layanan pada katalog; "
            "sistem membaca layanan aktif dari D2 layanan dan menampilkan "
            "detail lengkap beserta galeri dan harga setelah promo.",
        ],
        "3.0": [
            "3.1 Pilih Layanan & Jadwal: Pelanggan memilih layanan, tanggal, dan "
            "slot. Sistem membaca harga dan durasi dari D2 layanan, jam "
            "operasional dari D6 settings, serta slot terisi dari "
            "D4 booking_slots untuk menampilkan ketersediaan slot secara "
            "real-time bergaya pemilihan kursi bioskop.",
            "3.2 Validasi Slot & Bukti DP: Sistem memvalidasi ulang slot di sisi "
            "server (mencegah balapan data), menghitung kewajiban DP, dan "
            "memeriksa berkas bukti DP yang diunggah Pelanggan.",
            "3.3 Simpan Booking & Tahan Slot: Booking yang valid disimpan ke "
            "D3 bookings dengan kode unik SW-YYYYMMDD-NNN, slot ditahan pada "
            "D4 booking_slots sebanyak durasi layanan, aktivitas dicatat ke "
            "D7 booking_logs, lalu kode booking dikirim kepada Pelanggan.",
            "3.4 Booking Walk-in: Admin mencatat pelanggan yang datang langsung; "
            "alurnya sama dengan booking online namun langsung berstatus "
            "diterima tanpa DP.",
            "3.5 Cek & Batalkan Booking: Pelanggan memeriksa status booking dan "
            "dapat membatalkan minimal 2 jam sebelum jadwal; status diubah pada "
            "D3 bookings, slot dilepas dari D4 booking_slots, dan pembatalan "
            "tercatat di D7 booking_logs.",
        ],
        "4.0": [
            "4.1 Periksa Bukti DP: Admin membuka booking berstatus menunggu dari "
            "D3 bookings, memeriksa bukti transfer DP, lalu memperbarui status "
            "pembayaran menjadi terverifikasi.",
            "4.2 Terima/Tolak Booking: Berdasarkan hasil pemeriksaan DP, Admin "
            "menerima atau menolak booking. Status pada D3 bookings diperbarui; "
            "bila ditolak, slot dilepas dari D4 booking_slots; setiap keputusan "
            "dicatat ke D7 booking_logs. Verifikasi DP pada booking pending "
            "otomatis menjadikan booking diterima.",
            "4.3 Kirim Notifikasi: Sistem mengirim email konfirmasi kepada "
            "Pelanggan dan menyiapkan pratinjau pesan WhatsApp (tautan wa.me) "
            "yang tinggal dikirim oleh Admin.",
        ],
        "5.0": [
            "5.1 Tandai Booking Selesai: Admin menyelesaikan booking berstatus "
            "diterima yang telah dilayani; status pada D3 bookings diubah "
            "menjadi selesai.",
            "5.2 Hitung Total Pembayaran: Sistem menghitung total dari harga "
            "dasar layanan ditambah biaya tambahan (wajib disertai catatan), "
            "atau memakai nominal manual bila dipilih.",
            "5.3 Catat Transaksi: Satu transaksi per booking dicatat ke "
            "D5 transaksi beserta metode bayar (tunai/transfer/QRIS) dan "
            "dicatat pula pada D7 booking_logs; sistem memastikan tidak terjadi "
            "pencatatan ganda.",
            "5.4 Rekap Transaksi: Admin memfilter transaksi berdasarkan rentang "
            "tanggal; sistem membaca D5 transaksi dan menampilkan rekap beserta "
            "total, jumlah, dan rata-rata.",
        ],
        "6.0": [
            "6.1 Dashboard Operasional: Admin membuka dashboard; sistem membaca "
            "booking hari ini dari D3 bookings dan menyajikan kartu ringkasan "
            "(total, menunggu verifikasi, diterima, selesai) beserta booking "
            "terbaru.",
            "6.2 Laporan Pendapatan: Pemilik meminta laporan; sistem membaca "
            "D5 transaksi dan D3 bookings untuk menyusun KPI pendapatan (hari "
            "ini, kemarin, bulan ini beserta tren) dan distribusi status "
            "booking.",
            "6.3 Grafik & Layanan Terlaris: Pemilik memilih mode grafik "
            "(hari/minggu/bulan) dan menggeser periode; sistem membaca "
            "pendapatan per periode dari D5 transaksi lalu menyajikan grafik "
            "dinamis beserta daftar layanan terlaris.",
        ],
        "7.0": [
            "7.1 Ubah Profil & Jam Operasional: Admin memperbarui nama, alamat, "
            "telepon salon, jam buka/tutup, dan rentang hari booking. Nilai "
            "lama dibaca dari D6 settings dan perubahan disimpan kembali; jam "
            "operasional baru langsung memengaruhi slot pada form booking.",
            "7.2 Kelola Template WhatsApp: Admin menyunting template pesan "
            "(diterima, ditolak, pengingat, selesai) yang berisi placeholder "
            "data booking; template disimpan pada D6 settings dan pratinjaunya "
            "ditampilkan pada halaman detail booking.",
            "7.3 Ganti Password Staf: Admin memasukkan password lama dan baru; "
            "sistem memverifikasi kredensial lama terhadap D1 users sebelum "
            "menyimpan hash password yang baru.",
        ],
    },

    "basisdata_intro":
        "Perancangan basis data bertujuan menggambarkan struktur penyimpanan "
        "data pada Sistem Informasi Booking SW Beauty Salon. Basis data "
        "dirancang menggunakan model relasional MySQL dengan tujuh tabel, yaitu "
        "users, layanan, bookings, booking_slots, transaksi, settings, dan "
        "booking_logs.",
    "konseptual":
        "Konseptual basis data disusun berdasarkan Entity Relationship Diagram "
        "(ERD) notasi crow's foot yang menggambarkan hubungan antar entitas. "
        "Hubungan yang terbentuk antara lain: (1) satu USERS (pelanggan) dapat "
        "melakukan banyak BOOKINGS, sementara booking walk-in boleh tidak "
        "terhubung ke akun; (2) satu LAYANAN dapat dipesan pada banyak "
        "BOOKINGS; (3) satu BOOKINGS menahan banyak BOOKING_SLOTS sesuai durasi "
        "layanan (satu baris per slot 30 menit) sebagai mekanisme anti bentrok "
        "jadwal; (4) satu BOOKINGS menghasilkan tepat satu TRANSAKSI yang "
        "dicatat saat booking diselesaikan; serta (5) satu BOOKINGS tercatat "
        "pada banyak BOOKING_LOGS sebagai jejak audit. Entitas SETTINGS berdiri "
        "sendiri sebagai penyimpan konfigurasi sistem bertipe key-value. Primary "
        "key tiap entitas digarisbawahi dan foreign key ditandai FK pada "
        "diagram berikut.",
    "struktur_intro":
        "Struktur tabel menjelaskan atribut yang dimiliki setiap entitas pada "
        "basis data beserta tipe data dan keterangannya. Galeri foto layanan "
        "sengaja disimpan sebagai kolom JSON pada tabel layanan (denormalisasi "
        "terkontrol) untuk menjaga jumlah tabel tetap minimal. Tabel-tabel "
        "berikut digunakan sebagai tempat penyimpanan data pada sistem.",
    "chen":
        "Sebagai pelengkap konseptual basis data, ERD juga digambarkan dengan "
        "notasi Chen, di mana entitas digambarkan dengan persegi, atribut "
        "dengan elips (primary key digarisbawahi), dan relasi dengan belah "
        "ketupat berkardinalitas 1/N. Pada notasi ini terlihat relasi melakukan "
        "antara USERS dan BOOKINGS (1:N), dipesan_pada antara LAYANAN dan "
        "BOOKINGS (1:N), menahan antara BOOKINGS dan BOOKING_SLOTS (1:N), "
        "menghasilkan antara BOOKINGS dan TRANSAKSI (1:1), serta tercatat_pada "
        "antara BOOKINGS dan BOOKING_LOGS (1:N).",
}


# ============================================================================ #
# 3. DOKUMEN — subclass BabIvDoc: 4.2 & 4.3 diisi penjelasan; 4.7 SUS dilewati
# ============================================================================ #

class UltimateDoc(BabIvDoc):
    def build_from_spec(self, spec, figures, sus_data=None):
        self.bab()
        self._section_analisis(spec)               # 4.1
        self._section_perancangan(spec, figures)   # 4.2 (+ narasi)
        self._section_basisdata(spec, figures)     # 4.3 (+ narasi)
        self._section_antarmuka(spec)              # 4.4
        self._section_implementasi(spec)           # 4.5
        self._section_blackbox(spec)               # 4.6
        # 4.7 SUS: sengaja TIDAK diisi (permintaan)

    # ---- 4.2 dengan penjelasan per diagram --------------------------------
    def _section_perancangan(self, spec, figures):
        N = NARASI
        self.h2("Perancangan Sistem")
        self.body(N["perancangan_intro"])

        if figures.get("context"):
            self.h3("Diagram Konteks")
            self.body(N["konteks_intro"])
            self.body(N["konteks_rincian"])
            from babiv_docx import _make_simple_list, _bind_numbering_para
            from docx.shared import Cm
            nid = _make_simple_list(self.doc, "decimal", left_cm=1.0,
                                    hanging_cm=0.5)
            for name, items in N["konteks_entitas"]:
                p = self.doc.add_paragraph(name, style="Paragraf")
                p.paragraph_format.left_indent = Cm(1.0)
                p.paragraph_format.first_line_indent = Cm(-0.5)
                _bind_numbering_para(p, nid, 0)
                self.alpha_list(items)
            self.figure(figures["context"], "Diagram Konteks")

        if figures.get("level0"):
            self.h3("DFD Level 0")
            self.body(N["level0_intro"])
            self.alpha_list(N["level0_proses"])
            self.figure(figures["level0"], "DFD Level 0")

        lvl1 = figures.get("level1") or {}
        if lvl1:
            self.h3("DFD Level 1")
            self.body(N["level1_intro"])

            def _key(no):
                try:
                    return [int(x) for x in str(no).split(".")]
                except ValueError:
                    return [99]
            for no in sorted(lvl1.keys(), key=_key):
                meta = lvl1[no]
                path = meta["path"] if isinstance(meta, dict) else meta
                name = meta.get("name", "") if isinstance(meta, dict) else ""
                title = f"DFD Level 1 Proses {no}" + (f" ({name})" if name else "")
                self.h4(title)
                desc = N["level1"].get(str(no))
                if desc:
                    self.alpha_list(desc)
                self.figure(path, title)

    # ---- 4.3 dengan penjelasan ---------------------------------------------
    def _section_basisdata(self, spec, figures):
        N = NARASI
        self.h2("Perancangan Basis Data")
        self.body(N["basisdata_intro"])

        if figures.get("erd"):
            self.h3("Konseptual Basis Data")
            self.body(N["konseptual"])
            self.figure(figures["erd"], "Konseptual Basis Data "
                        "(Entity Relationship Diagram Notasi Crow's Foot)")

        tables = spec.get("struktur_tabel")
        if tables:
            self.h3("Struktur Tabel")
            self.body(N["struktur_intro"])
            for tb in tables:
                self.table_caption(f"Struktur Tabel {tb.get('nama', '')}")
                self.data_table(tb["header"], tb["rows"], tb.get("widths_cm"))

        if figures.get("erd_chen"):
            self.h3("Entity Relationship Diagram (Notasi Chen)")
            self.body(N["chen"])
            self.figure(figures["erd_chen"],
                        "Entity Relationship Diagram (Notasi Chen)")


# ============================================================================ #
# 4. SCREENSHOT 4.5 — jalankan server bila perlu, login per role, tangkap PNG
# ============================================================================ #

def _port_open(host: str, port: int) -> bool:
    # php spark serve bisa bind ke ::1 (IPv6) saja — create_connection
    # mencoba semua alamat hasil resolusi (IPv4 dan IPv6).
    try:
        with socket.create_connection((host, port), timeout=1.0):
            return True
    except OSError:
        return False


def _sample_ids() -> dict:
    """Ambil id contoh (layanan, booking, kode booking pelanggan demo) dari DB
    lewat PHP CLI — tanpa dependensi driver MySQL di Python."""
    php = r"""<?php
mysqli_report(MYSQLI_REPORT_OFF);
$m = @new mysqli('localhost', 'root', '', 'sw_beauty_salon');
if ($m->connect_errno) { echo '{}'; exit; }
$o = [];
$q = $m->query("SELECT id FROM layanan WHERE is_active=1 AND deleted_at IS NULL ORDER BY id LIMIT 1");
if ($q && ($r = $q->fetch_row())) $o['layanan_id'] = $r[0];
$q = $m->query("SELECT id FROM bookings ORDER BY id DESC LIMIT 1");
if ($q && ($r = $q->fetch_row())) $o['booking_id'] = $r[0];
$q = $m->query("SELECT b.kode_booking FROM bookings b JOIN users u ON u.id=b.user_id
                WHERE u.nomor_hp='6281338109102' ORDER BY b.id DESC LIMIT 1");
if ($q && ($r = $q->fetch_row())) $o['kode'] = $r[0];
echo json_encode($o);
"""
    try:
        with tempfile.NamedTemporaryFile("w", suffix=".php", delete=False,
                                         encoding="utf-8") as f:
            f.write(php)
            tmp = f.name
        out = subprocess.run(["php", tmp], capture_output=True, text=True,
                             timeout=20).stdout.strip()
        pathlib.Path(tmp).unlink(missing_ok=True)
        return json.loads(out or "{}")
    except Exception as exc:
        print(f"  ! gagal ambil id contoh dari DB: {exc}")
        return {}


def capture_screenshots(shots_dir: pathlib.Path) -> int:
    """Tangkap screenshot SEMUA halaman (SHOT_PAGES) dengan login per role.
    Mengembalikan jumlah halaman yang berhasil ditangkap."""
    try:
        from playwright.sync_api import sync_playwright
    except Exception:
        print("  ! Playwright tidak tersedia — 4.5 dilewati "
              "(pip install playwright && python -m playwright install chromium)")
        return 0

    server = None
    if not _port_open("localhost", 8080):
        print("  server belum jalan — menjalankan `php spark serve` ...")
        server = subprocess.Popen(
            ["php", "spark", "serve"], cwd=str(ROOT),
            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        for _ in range(30):
            if _port_open("localhost", 8080):
                break
            time.sleep(1)
        else:
            print("  ! server tidak kunjung hidup — 4.5 dilewati")
            if server:
                server.terminate()
            return 0

    ids = _sample_ids()
    shots_dir.mkdir(parents=True, exist_ok=True)
    ok = 0
    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(args=["--no-sandbox"])
            contexts = {}

            def ctx_for(role):
                if role in contexts:
                    return contexts[role]
                c = browser.new_context(viewport={"width": 1366, "height": 900})
                pg = c.new_page()
                if role != "guest":
                    pg.goto(f"{BASE_URL}/login", wait_until="domcontentloaded")
                    pg.fill("input[name=identifier]", ACCOUNTS[role])
                    pg.fill("input[name=password]", PASSWORD)
                    pg.click("button[type=submit]")
                    pg.wait_for_load_state("networkidle")
                contexts[role] = (c, pg)
                return contexts[role]

            for role, name, url, _title, _desc in SHOT_PAGES:
                try:
                    url = url.format(**ids) if "{" in url else url
                except KeyError:
                    print(f"  ! lewati {name}: id contoh tidak ada di DB")
                    continue
                _c, pg = ctx_for(role)
                dst = shots_dir / f"{name}.png"
                try:
                    pg.goto(BASE_URL + url, wait_until="networkidle",
                            timeout=25000)
                    pg.wait_for_timeout(450)
                    # tutup modal yang auto-muncul (promo popup / closing soon)
                    pg.keyboard.press("Escape")
                    pg.wait_for_timeout(200)
                    pg.screenshot(path=str(dst), full_page=True)
                    print(f"    -> shots/{name}.png  [{role}]")
                    ok += 1
                except Exception as exc:
                    print(f"  ! gagal {name} ({url}): {exc}")
            browser.close()
    finally:
        if server:
            server.terminate()
            print("  server `php spark serve` dimatikan kembali.")
    print(f"  screenshot: {ok}/{len(SHOT_PAGES)} halaman tertangkap")
    return ok


# ============================================================================ #
# 5. MAIN — diagram -> mockup -> screenshot -> docx -> verifikasi
# ============================================================================ #

def main():
    ap = argparse.ArgumentParser(
        description="Generator BAB IV SW Beauty Salon (one-shot).")
    ap.add_argument("--out", default="out", help="folder output (default: out)")
    ap.add_argument("--no-shots", action="store_true",
                    help="lewati tangkap screenshot 4.5")
    ap.add_argument("--no-render", action="store_true",
                    help="hanya xml/graphml (tanpa PNG & docx)")
    ap.add_argument("--no-docx", action="store_true", help="lewati BAB_IV.docx")
    args = ap.parse_args()

    render = not args.no_render
    out = (ROOT / args.out).resolve()
    assets = out / "BabIvAssets"
    assets.mkdir(parents=True, exist_ok=True)

    spec = copy.deepcopy(SPEC)
    spec["antarmuka"] = _antarmuka()

    # 4.4 dan 4.5 WAJIB memuat halaman yang sama persis (judul + urutan)
    t44 = [it["title"] for it in spec["antarmuka"]]
    t45 = [p[3] for p in SHOT_PAGES]
    if t44 != t45:
        beda = [f"  4.4: {a!r}  vs  4.5: {b!r}"
                for a, b in zip(t44 + ["-"] * len(t45), t45 + ["-"] * len(t44))
                if a != b][:10]
        raise SystemExit("Halaman 4.4 dan 4.5 TIDAK sama!\n" + "\n".join(beda))

    print(f"Sistem : {spec['system_name']}")
    print(f"Output : {assets}")

    # -- 4.5 screenshot dulu (supaya path gambar siap saat docx dirakit) -----
    shots_dir = assets / "shots"
    if render and not args.no_shots:
        print("[1/6] Screenshot implementasi (4.5)")
        capture_screenshots(shots_dir)
    else:
        print("[1/6] Screenshot dilewati")
    spec["implementasi"] = _implementasi(shots_dir)
    n_shot = sum(1 for it in spec["implementasi"] if it.get("image"))

    figures = {}
    warns = []

    print("[2/6] Diagram Konteks")
    ctx = build_context(spec)
    warns += validate_balance(ctx, "Diagram Konteks")
    figures["context"] = emit_dfd(ctx, "context", assets / "context",
                                  "diagram_konteks", render)

    print("[3/6] DFD Level 0")
    lv0 = build_level0(spec)
    warns += validate_balance(lv0, "DFD Level 0")
    figures["level0"] = emit_dfd(lv0, "level", assets / "dfd 0",
                                 "dfd_level_0", render)

    print("[4/6] DFD Level 1 (7 proses)")
    figures["level1"] = {}
    for entry in spec["level1"]:
        parent_no = str(entry["parent_no"]).strip()
        k = parent_no.split(".")[0]
        dfd = build_level1(spec, entry)
        warns += validate_balance(dfd, f"DFD Level 1 (proses {parent_no})",
                                  processes_only=True)
        path = emit_dfd(dfd, "level", assets / f"dfd 1.{k}",
                        f"dfd_level_1_proses_{k}", render)
        pname = next((p["name"] for p in spec["level0"]["processes"]
                      if str(p["no"]) == parent_no), "")
        figures["level1"][parent_no] = {"path": path, "name": pname}

    print("[5/6] ERD (crow's foot + Chen)")
    erd = build_erd(spec)
    figures["erd"] = emit_erd(erd, assets / "erd", "erd", render)
    figures["erd_chen"] = emit_erd_chen(erd, assets / "erd", "erd_chen", render)

    emit_mockups(spec, assets, render)

    if render and not args.no_docx:
        print("[6/6] BAB_IV.docx")
        doc = UltimateDoc(chapter=4, bab_title=spec["bab_title"],
                          system_name=spec["system_name"])
        doc.build_from_spec(spec, figures)
        docx_path = assets / "BAB_IV.docx"
        doc.save(str(docx_path))
        print(f"    -> {docx_path}")
    else:
        print("[6/6] BAB_IV.docx dilewati")

    _write_readme(assets, spec, figures)

    # -- arsip kinknadi.zip (seluruh BabIvAssets) -----------------------------
    if render:
        import shutil
        zip_base = ROOT / "kinknadi"
        shutil.make_archive(str(zip_base), "zip", root_dir=str(out),
                            base_dir="BabIvAssets")
        print(f"\nArsip: {zip_base}.zip")

    # -- verifikasi keseimbangan visual tiap PNG ------------------------------
    if render:
        print("\nVerifikasi diagram (verify_diagrams.py):")
        subprocess.run([sys.executable, str(SKILL / "verify_diagrams.py"),
                        str(assets)], cwd=str(SKILL))

    print("\n================ RINGKASAN ================")
    print(f"Proses Level 0      : {len(spec['level0']['processes'])}")
    print(f"Diagram Level 1     : {len(spec['level1'])} (1.1 s.d. 1.7)")
    print(f"Screenshot 4.5      : {n_shot}/{len(SHOT_PAGES)} halaman")
    print(f"Wireframe 4.4       : {len(spec['antarmuka'])} halaman")
    print(f"Tabel blackbox 4.6  : {len(spec['pengujian']['blackbox'])} fitur")
    print("4.7 SUS             : tidak diisi (sesuai permintaan)")
    if warns:
        print(f"PERINGATAN balance  : {len(warns)} — periksa output di atas!")
    else:
        print("Balance DFD         : OK (semua node punya aliran masuk & keluar)")
    print("Halaman 4.4 = 4.5  : sama persis (judul & urutan) — tervalidasi")
    print("Arsip               : kinknadi.zip (di root project)")
    print("Buka BAB_IV.docx lalu tekan Ctrl+A dan F9 untuk update semua nomor.")
    print("Tiap diagram punya .drawio.xml — bisa dirapikan di https://app.diagrams.net")


if __name__ == "__main__":
    main()
