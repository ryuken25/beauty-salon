<?php

namespace App\Controllers\Owner;

use App\Controllers\BaseController;
use App\Services\SlotService;

class LaporanController extends BaseController
{
    /**
     * AJAX endpoint untuk grafik pendapatan dinamis.
     * Param GET:
     *   mode   ∈ hari|minggu|bulan (default minggu)
     *   offset int ≤ 0 — 0 = terkini, -1 = sebelumnya, dst (offset>0 → clamp 0)
     *
     * Response:
     *   { mode, offset, title, labels[], values[], total, can_prev, can_next }
     */
    public function revenueData()
    {
        $mode = (string) $this->request->getGet('mode');
        if (! in_array($mode, ['hari', 'minggu', 'bulan'], true)) {
            $mode = 'minggu';
        }
        $offset = (int) $this->request->getGet('offset');
        if ($offset > 0) {
            $offset = 0;
        }

        $db = db_connect();
        $hariShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $bulanId = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $labels = [];
        $values = [];
        $title = '';
        $canPrev = true;
        $canNext = $offset < 0;

        if ($mode === 'hari') {
            // Bucket per jam dari jam_buka..jam_tutup.
            $hours = (new SlotService())->salonHours();
            $openHour = (int) substr((string) ($hours['jam_buka'] ?? '08:00'), 0, 2);
            $closeHour = (int) substr((string) ($hours['jam_tutup'] ?? '19:00'), 0, 2);
            if ($closeHour <= $openHour) {
                $closeHour = $openHour + 1;
            }
            $date = date('Y-m-d', strtotime(sprintf('%+d days', $offset)));
            $rows = $db->query(
                'SELECT HOUR(tanggal_transaksi) jam, SUM(nominal) total
                 FROM transaksi WHERE DATE(tanggal_transaksi) = ? GROUP BY HOUR(tanggal_transaksi)',
                [$date]
            )->getResultArray();
            $bucket = [];
            foreach ($rows as $r) {
                $bucket[(int) $r['jam']] = (int) $r['total'];
            }
            for ($h = $openHour; $h < $closeHour; $h++) {
                $labels[] = sprintf('%02d:00', $h);
                $values[] = $bucket[$h] ?? 0;
            }
            $ts = strtotime($date);
            $title = 'Pendapatan ' . $hariShort[(int) date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $bulanId[(int) date('n', $ts) - 1];
        } elseif ($mode === 'minggu') {
            // Window 7 hari berakhir di (today + offset*7), clamp end ≤ today.
            $endTs = strtotime(sprintf('%+d days', $offset * 7));
            $todayTs = strtotime(date('Y-m-d'));
            if ($endTs > $todayTs) $endTs = $todayTs;
            $end = date('Y-m-d', $endTs);
            $start = date('Y-m-d', strtotime('-6 days', $endTs));
            $rows = $db->query(
                'SELECT DATE(tanggal_transaksi) tgl, SUM(nominal) total
                 FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN ? AND ? GROUP BY DATE(tanggal_transaksi)',
                [$start, $end]
            )->getResultArray();
            $bucket = [];
            foreach ($rows as $r) {
                $bucket[$r['tgl']] = (int) $r['total'];
            }
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days", $endTs));
                $ts = strtotime($d);
                $labels[] = $hariShort[(int) date('w', $ts)] . ' ' . date('d', $ts);
                $values[] = $bucket[$d] ?? 0;
            }
            $title = $offset === 0
                ? '7 hari terakhir'
                : '7 hari berakhir ' . date('j M', $endTs);
            $canNext = $endTs < $todayTs;
        } else {
            // bulan — tgl 1..akhir bulan (bulan berjalan: 1..hari ini).
            $target = strtotime('first day of ' . sprintf('%+d months', $offset));
            $monthStart = date('Y-m-01', $target);
            $thisMonthStart = date('Y-m-01');
            $isCurrent = $monthStart === $thisMonthStart;
            $monthEnd = $isCurrent ? date('Y-m-d') : date('Y-m-t', $target);
            $rows = $db->query(
                'SELECT DAY(tanggal_transaksi) tgl, SUM(nominal) total
                 FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN ? AND ? GROUP BY DAY(tanggal_transaksi)',
                [$monthStart, $monthEnd]
            )->getResultArray();
            $bucket = [];
            foreach ($rows as $r) {
                $bucket[(int) $r['tgl']] = (int) $r['total'];
            }
            $lastDay = (int) date('d', strtotime($monthEnd));
            for ($d = 1; $d <= $lastDay; $d++) {
                $labels[] = (string) $d;
                $values[] = $bucket[$d] ?? 0;
            }
            $title = $bulanId[(int) date('n', $target) - 1] . ' ' . date('Y', $target);
            $canNext = ! $isCurrent;
        }

        return $this->response->setJSON([
            'mode' => $mode,
            'offset' => $offset,
            'title' => $title,
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
            'can_prev' => $canPrev,
            'can_next' => $canNext,
        ]);
    }


    public function index()
    {
        $db = db_connect();
        $today = date('Y-m-d');

        $pendapatanHariIni = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi)', $today)->get()->getRow()->nominal ?? 0);
        $pendapatanKemarin = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi)', date('Y-m-d', strtotime('-1 day')))->get()->getRow()->nominal ?? 0);
        $trend = $pendapatanKemarin > 0 ? (int) round((($pendapatanHariIni - $pendapatanKemarin) / $pendapatanKemarin) * 100) : null;

        $pendapatanBulanIni = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi) >=', date('Y-m-01'))->get()->getRow()->nominal ?? 0);

        // 7-day revenue series for the chart
        $chartLabels = [];
        $chartValues = [];
        $hariShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $sum = (int) ($db->table('transaksi')->selectSum('nominal')->where('DATE(tanggal_transaksi)', $d)->get()->getRow()->nominal ?? 0);
            $chartLabels[] = $hariShort[(int) date('w', strtotime($d))];
            $chartValues[] = $sum;
        }
        $totalMingguIni = array_sum($chartValues);

        // Status distribution this month
        $statusBreakdown = $db->table('bookings')
            ->select('status, COUNT(*) AS jumlah')
            ->where('created_at >=', date('Y-m-01 00:00:00'))
            ->groupBy('status')
            ->get()
            ->getResultArray();
        $statusMap = ['pending_verification' => 0, 'accepted' => 0, 'completed' => 0, 'rejected' => 0, 'cancelled' => 0];
        foreach ($statusBreakdown as $r) {
            $statusMap[$r['status']] = (int) $r['jumlah'];
        }

        $topServices = $db->table('bookings b')
            ->select('l.nama AS nama, COUNT(*) AS jumlah')
            ->join('layanan l', 'l.id = b.layanan_id')
            ->where('b.status', 'completed')
            ->where('b.completed_at >=', date('Y-m-01 00:00:00'))
            ->groupBy('l.id, l.nama')
            ->orderBy('jumlah', 'DESC')
            ->limit(5)
            ->get()->getResultArray();
        $totalTop = array_sum(array_column($topServices, 'jumlah')) ?: 1;

        return view('owner/laporan', [
            'pendapatan_hari_ini' => $pendapatanHariIni,
            'pendapatan_bulan_ini' => $pendapatanBulanIni,
            'trend' => $trend,
            'chart_labels' => $chartLabels,
            'chart_values' => $chartValues,
            'total_minggu' => $totalMingguIni,
            'status_map' => $statusMap,
            'top_services' => $topServices,
            'total_top' => $totalTop,
        ]);
    }
}
