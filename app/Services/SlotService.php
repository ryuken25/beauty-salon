<?php

namespace App\Services;

use RuntimeException;

class SlotService
{
    public const SLOT_MINUTES = 30;
    public const DEFAULT_OPEN = '09:00';
    public const DEFAULT_CLOSE = '21:00';

    public function slotCount(int $durationMinutes): int
    {
        if ($durationMinutes < self::SLOT_MINUTES || $durationMinutes % self::SLOT_MINUTES !== 0) {
            throw new RuntimeException('Durasi layanan wajib kelipatan 30 menit.');
        }

        return (int) ($durationMinutes / self::SLOT_MINUTES);
    }

    public function salonHours(): array
    {
        $db = db_connect();
        $rows = $db->table('app_settings')->whereIn('setting_key', ['salon_open_time', 'salon_close_time'])->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) {
            $map[$r['setting_key']] = $r['setting_value'];
        }
        $open = $this->normaliseTime($map['salon_open_time'] ?? '', self::DEFAULT_OPEN);
        $close = $this->normaliseTime($map['salon_close_time'] ?? '', self::DEFAULT_CLOSE);
        if (strtotime("1970-01-01 {$close}") <= strtotime("1970-01-01 {$open}")) {
            $open = self::DEFAULT_OPEN;
            $close = self::DEFAULT_CLOSE;
        }
        return ['open' => $open, 'close' => $close];
    }

    private function normaliseTime(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '' || ! preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            return $fallback;
        }
        $parts = explode(':', $value);
        $h = max(0, min(23, (int) $parts[0]));
        $m = max(0, min(59, (int) $parts[1]));
        return sprintf('%02d:%02d', $h, $m);
    }

    public function generateAvailableSlots(int $serviceId, int $stylistId, string $date): array
    {
        $db = db_connect();
        if (! $this->validDate($date)) {
            return [];
        }

        $service = $db->table('services')->where('id', $serviceId)->where('is_active', 1)->get()->getRowArray();
        if (! $service) {
            return [];
        }
        try {
            $count = $this->slotCount((int) $service['duration_minutes']);
        } catch (RuntimeException $e) {
            return [];
        }
        $stylist = $db->table('stylists')->where('id', $stylistId)->where('is_active', 1)->get()->getRowArray();
        if (! $stylist) {
            return [];
        }
        $dayOff = $db->table('stylist_day_offs')->where(['stylist_id' => $stylistId, 'date' => $date])->countAllResults() > 0;
        if ($dayOff) {
            return [];
        }
        $day = (int) date('w', strtotime($date));
        $hour = $db->table('stylist_working_hours')->where(['stylist_id' => $stylistId, 'day_of_week' => $day, 'is_working' => 1])->get()->getRowArray();
        if (! $hour || $date < date('Y-m-d')) {
            return [];
        }
        $salon = $this->salonHours();
        $windowStart = max(strtotime($date . ' ' . $hour['start_time']), strtotime($date . ' ' . $salon['open']));
        $windowEnd = min(strtotime($date . ' ' . $hour['end_time']), strtotime($date . ' ' . $salon['close']));
        if ($windowEnd <= $windowStart) {
            return [];
        }
        $slots = [];
        for ($cursor = $windowStart; $cursor + ($count * 1800) <= $windowEnd; $cursor += 1800) {
            if ($date === date('Y-m-d') && $cursor < time()) {
                continue;
            }
            $slotStart = date('H:i:00', $cursor);
            $slotEnd = date('H:i:00', $cursor + ($count * 1800));
            $slots[] = [
                'start' => substr($slotStart, 0, 5),
                'end' => substr($slotEnd, 0, 5),
                'duration_minutes' => $count * self::SLOT_MINUTES,
                'available' => $this->isSequenceAvailable($stylistId, $date, $slotStart, $count),
            ];
        }
        return $slots;
    }

    public function validateSlot(int $serviceId, int $stylistId, string $date, string $startTime): array
    {
        if (! $this->validDate($date)) {
            throw new RuntimeException('Tanggal booking tidak valid.');
        }
        if ($date < date('Y-m-d')) {
            throw new RuntimeException('Tanggal booking tidak boleh masa lalu.');
        }
        if (! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime)) {
            throw new RuntimeException('Format waktu mulai tidak valid.');
        }
        $startTime = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
        [$h, $m] = array_map('intval', explode(':', $startTime));
        if (! in_array($m, [0, 30], true)) {
            throw new RuntimeException('Waktu mulai harus sesuai grid 30 menit.');
        }
        $db = db_connect();
        $stylist = $db->table('stylists')->where('id', $stylistId)->where('is_active', 1)->get()->getRowArray();
        if (! $stylist) {
            throw new RuntimeException('Stylist tidak ditemukan atau tidak aktif.');
        }
        $service = $db->table('services')->where('id', $serviceId)->where('is_active', 1)->get()->getRowArray();
        if (! $service) {
            throw new RuntimeException('Layanan tidak ditemukan atau tidak aktif.');
        }
        $dayOff = $db->table('stylist_day_offs')->where(['stylist_id' => $stylistId, 'date' => $date])->countAllResults() > 0;
        if ($dayOff) {
            throw new RuntimeException('Stylist libur pada tanggal tersebut.');
        }
        $day = (int) date('w', strtotime($date));
        $hour = $db->table('stylist_working_hours')->where(['stylist_id' => $stylistId, 'day_of_week' => $day, 'is_working' => 1])->get()->getRowArray();
        if (! $hour) {
            throw new RuntimeException('Stylist tidak bekerja pada tanggal tersebut.');
        }
        $count = $this->slotCount((int) $service['duration_minutes']);
        $start = strtotime($date . ' ' . $startTime);
        $end = $start + ($count * 1800);
        $salon = $this->salonHours();
        $windowStart = max(strtotime($date . ' ' . $hour['start_time']), strtotime($date . ' ' . $salon['open']));
        $windowEnd = min(strtotime($date . ' ' . $hour['end_time']), strtotime($date . ' ' . $salon['close']));
        if ($start < $windowStart || $end > $windowEnd) {
            throw new RuntimeException('Waktu booking berada di luar jam operasional salon atau stylist.');
        }
        if (! $this->isSequenceAvailable($stylistId, $date, $startTime, $count)) {
            throw new RuntimeException('Slot sudah terisi, silakan pilih waktu lain.');
        }
        return ['service' => $service, 'slot_count' => $count, 'end_time' => date('H:i:00', $end), 'start_time' => $startTime];
    }

    public function isSequenceAvailable(int $stylistId, string $date, string $startTime, int $slotCount): bool
    {
        $db = db_connect();
        $start = strtotime($date . ' ' . (strlen($startTime) === 5 ? $startTime . ':00' : $startTime));
        for ($i = 0; $i < $slotCount; $i++) {
            $slot = date('H:i:00', $start + ($i * 1800));
            $exists = $db->table('booking_slots')->where(['stylist_id' => $stylistId, 'slot_date' => $date, 'slot_start' => $slot])->countAllResults();
            if ($exists > 0) {
                return false;
            }
        }
        return true;
    }

    private function validDate(string $date): bool
    {
        $parsed = date_create_from_format('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
