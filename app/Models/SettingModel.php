<?php
namespace App\Models;

class SettingModel extends BaseAppModel
{
    protected $table = 'settings';
    protected $allowedFields = ['key_name', 'value'];
    protected $useTimestamps = false;

    public function getValue(string $key, ?string $default = null): ?string
    {
        $row = $this->where('key_name', $key)->first();
        return $row['value'] ?? $default;
    }

    public function setValue(string $key, ?string $value): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->where('key_name', $key)->first();
        if ($existing) {
            $this->builder()->where('key_name', $key)->update(['value' => $value, 'updated_at' => $now]);
        } else {
            $this->builder()->insert(['key_name' => $key, 'value' => $value, 'updated_at' => $now]);
        }
    }

    public function all(): array
    {
        $rows = $this->findAll();
        $map = [];
        foreach ($rows as $r) {
            $map[$r['key_name']] = $r['value'];
        }
        return $map;
    }
}
