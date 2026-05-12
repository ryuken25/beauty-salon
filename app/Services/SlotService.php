<?php

namespace App\Services;

use App\Models\BookingSlotModel;
use App\Models\LayananModel;
use App\Models\SettingModel;
use App\Models\StylistModel;
use App\Models\StylistScheduleModel;
use RuntimeException;

class SlotService
{
    public const SLOT_MINUTES = 30;
    public const DEFAULT_OPEN = '08:00';
    public const DEFAULT_CLOSE = '19:00';
    public const RANGE_HARI = 7;

    private const HARI_MAP = [0 => 'minggu', 1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu'];

    public function hariOf(string $tanggal): string
    {
        return self::HARI_MAP[(int) date('w', strtotime($tanggal))];
    }

    public function salonHours(): array
    {
        $set = new SettingModel();
        return [
            'jam_buka' => $set->getValue('jam_buka', self::DEFAULT_OPEN),
            'jam_tutup' => $set->getValue('jam_tutup', self::DEFAULT_CLOSE),
        ];
    }

    public function rangeHari(): int
    {
        return (int) ((new SettingModel())->getValue('range_hari_booking', (string) self::RANGE_HARI));
    }

    public function inRange(string $tanggal): bool
    {
        $today = date('Y-m-d');
        $max = date('Y-m-d', strtotime("+{$this->rangeHari()} days"));
        return $tanggal >= $today && $tanggal <= $max;
    }

    public function jumlahSlot(int $durasiMenit): int
    {
        if ($durasiMenit < self::SLOT_MINUTES || $durasiMenit % self::SLOT_MINUTES !== 0) {
            throw new RuntimeException('Durasi layanan harus kelipatan 30 menit.');
        }
        return (int) ($durasiMenit / self::SLOT_MINUTES);
    }

    public function allSlots(): array
    {
        $hours = $this->salonHours();
        $start = strtotime("1970-01-01 {$hours['jam_buka']}");
        $end = strtotime("1970-01-01 {$hours['jam_tutup']}");
        $slots = [];
        for ($t = $start; $t < $end; $t += 1800) {
            $slots[] = date('H:i', $t);
        }
        return $slots;
    }

    public function availabilityFor(int $layananId, int $stylistId, string $tanggal): array
    {
        $layanan = (new LayananModel())->where('is_active', 1)->find($layananId);
        if (! $layanan) {
            throw new RuntimeException('Layanan tidak valid.');
        }
        $stylist = (new StylistModel())->where('is_active', 1)->find($stylistId);
        if (! $stylist) {
            throw new RuntimeException('Stylist tidak valid.');
        }
        if (! $this->inRange($tanggal)) {
            throw new RuntimeException('Tanggal di luar rentang booking.');
        }

        $jumlahSlot = $this->jumlahSlot((int) $layanan['durasi_menit']);
        $hours = $this->salonHours();
        $hari = $this->hariOf($tanggal);
        $schedule = (new StylistScheduleModel())->forStylist($stylistId, $hari);

        $allSlots = $this->allSlots();
        $booked = (new BookingSlotModel())->heldOn($stylistId, $tanggal);

        $stylistOpen = $schedule && ! $schedule['is_libur'] ? substr($schedule['jam_mulai'], 0, 5) : null;
        $stylistClose = $schedule && ! $schedule['is_libur'] ? substr($schedule['jam_selesai'], 0, 5) : null;

        return [
            'all_slots' => $allSlots,
            'booked' => $booked,
            'jam_buka' => $hours['jam_buka'],
            'jam_tutup' => $hours['jam_tutup'],
            'durasi_menit' => (int) $layanan['durasi_menit'],
            'jumlah_slot' => $jumlahSlot,
            'stylist_open' => $stylistOpen,
            'stylist_close' => $stylistClose,
            'is_libur' => ! $schedule || $schedule['is_libur'],
        ];
    }

    public function validateBookingSlot(int $layananId, int $stylistId, string $tanggal, string $slotMulai): array
    {
        if (! preg_match('/^\d{2}:\d{2}$/', $slotMulai)) {
            throw new RuntimeException('Format jam tidak valid.');
        }
        [$h, $m] = array_map('intval', explode(':', $slotMulai));
        if (! in_array($m, [0, 30], true)) {
            throw new RuntimeException('Jam mulai harus kelipatan 30 menit.');
        }

        $av = $this->availabilityFor($layananId, $stylistId, $tanggal);
        if ($av['is_libur']) {
            throw new RuntimeException('Salon libur pada tanggal yang dipilih.');
        }
        $startTs = strtotime("{$tanggal} {$slotMulai}");
        $endTs = $startTs + ($av['jumlah_slot'] * 1800);
        $openTs = strtotime("{$tanggal} {$av['jam_buka']}");
        $closeTs = strtotime("{$tanggal} {$av['jam_tutup']}");
        if ($av['stylist_open']) {
            $openTs = max($openTs, strtotime("{$tanggal} {$av['stylist_open']}"));
            $closeTs = min($closeTs, strtotime("{$tanggal} {$av['stylist_close']}"));
        }
        if ($startTs < $openTs || $endTs > $closeTs) {
            throw new RuntimeException('Waktu di luar jam operasional salon.');
        }
        if ($tanggal === date('Y-m-d') && $startTs < time()) {
            throw new RuntimeException('Jam yang dipilih sudah lewat.');
        }

        $slotsNeeded = [];
        for ($i = 0; $i < $av['jumlah_slot']; $i++) {
            $slotsNeeded[] = date('H:i', $startTs + ($i * 1800));
        }
        foreach ($slotsNeeded as $s) {
            if (in_array($s, $av['booked'], true)) {
                throw new RuntimeException('Slot waktu tidak tersedia. Silakan pilih jam lain.');
            }
        }

        return [
            'jumlah_slot' => $av['jumlah_slot'],
            'durasi_menit' => $av['durasi_menit'],
            'slot_mulai' => $slotMulai,
            'slot_selesai' => date('H:i', $endTs),
            'slots_needed' => $slotsNeeded,
        ];
    }
}
