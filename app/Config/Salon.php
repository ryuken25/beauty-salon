<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi khusus SW Beauty Salon.
 *
 * Nilai di sini adalah fallback paling akhir untuk nomor WhatsApp admin.
 * Urutan pembacaan (lihat wa_admin_number() di app/Helpers/whatsapp_helper.php):
 *
 *   1. setting `wa_admin` di tabel `settings` — diubah dari /admin/pengaturan
 *   2. variabel `salon.waAdmin` di file .env
 *   3. properti $waAdmin di bawah
 *
 * CodeIgniter otomatis menimpa properti config dengan variabel .env yang
 * berawalan nama class (`salon.`), jadi langkah 2 dan 3 ditangani oleh
 * properti yang sama — tidak perlu kode tambahan.
 */
class Salon extends BaseConfig
{
    /**
     * Nomor WhatsApp admin yang dihubungi pelanggan.
     *
     * Boleh ditulis 08xx, +62xx, atau 62xx — dinormalisasi oleh wa_normalize()
     * sebelum dipakai membentuk tautan wa.me.
     */
    public string $waAdmin = '087862183074';
}
