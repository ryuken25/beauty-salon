<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class SettingsController extends BaseController
{
    private array $keys = [
        'salon_whatsapp',
        'salon_whatsapp_owner',
        'salon_open_time',
        'salon_close_time',
        'telegram_allowed_chat_ids',
    ];

    public function index()
    {
        $db = db_connect();
        if ($this->request->getMethod() === 'POST') {
            foreach ($this->keys as $key) {
                $val = (string) $this->request->getPost($key);
                if (in_array($key, ['salon_open_time', 'salon_close_time'], true) && $val !== '' && ! preg_match('/^\d{1,2}:\d{2}$/', $val)) {
                    continue;
                }
                $exists = $db->table('app_settings')->where('setting_key', $key)->get()->getRowArray();
                $exists
                    ? $db->table('app_settings')->where('setting_key', $key)->update(['setting_value' => $val, 'updated_at' => date('Y-m-d H:i:s')])
                    : $db->table('app_settings')->insert(['setting_key' => $key, 'setting_value' => $val, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
            return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
        }
        $rows = $db->table('app_settings')->get()->getResultArray();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        return view('admin/settings/index', ['settings' => $settings]);
    }
}
