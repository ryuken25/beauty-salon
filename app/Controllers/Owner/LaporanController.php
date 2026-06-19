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
            
            $rowsDp = $db->query(
                "SELECT HOUR(dp_verified_at) jam, SUM(dp_amount) total
                 FROM bookings WHERE payment_status = 'dp_verified' AND DATE(dp_verified_at) = ? GROUP BY HOUR(dp_verified_at)",
                [$date]
            )->getResultArray();

            $rowsPelunasan = $db->query(
                "SELECT HOUR(tanggal_transaksi) jam, SUM(sisa_bayar) total
                 FROM transaksi WHERE DATE(tanggal_transaksi) = ? GROUP BY HOUR(tanggal_transaksi)",
                [$date]
            )->getResultArray();

            $bucket = [];
            foreach ($rowsDp as $r) {
                $bucket[(int) $r['jam']] = ($bucket[(int) $r['jam']] ?? 0) + (int) $r['total'];
            }
            foreach ($rowsPelunasan as $r) {
                $bucket[(int) $r['jam']] = ($bucket[(int) $r['jam']] ?? 0) + (int) $r['total'];
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
            
            $rowsDp = $db->query(
                "SELECT DATE(dp_verified_at) tgl, SUM(dp_amount) total
                 FROM bookings WHERE payment_status = 'dp_verified' AND DATE(dp_verified_at) BETWEEN ? AND ? GROUP BY DATE(dp_verified_at)",
                [$start, $end]
            )->getResultArray();

            $rowsPelunasan = $db->query(
                "SELECT DATE(tanggal_transaksi) tgl, SUM(sisa_bayar) total
                 FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN ? AND ? GROUP BY DATE(tanggal_transaksi)",
                [$start, $end]
            )->getResultArray();

            $bucket = [];
            foreach ($rowsDp as $r) {
                $bucket[$r['tgl']] = ($bucket[$r['tgl']] ?? 0) + (int) $r['total'];
            }
            foreach ($rowsPelunasan as $r) {
                $bucket[$r['tgl']] = ($bucket[$r['tgl']] ?? 0) + (int) $r['total'];
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
            
            $rowsDp = $db->query(
                "SELECT DAY(dp_verified_at) tgl, SUM(dp_amount) total
                 FROM bookings WHERE payment_status = 'dp_verified' AND DATE(dp_verified_at) BETWEEN ? AND ? GROUP BY DAY(dp_verified_at)",
                [$monthStart, $monthEnd]
            )->getResultArray();

            $rowsPelunasan = $db->query(
                "SELECT DAY(tanggal_transaksi) tgl, SUM(sisa_bayar) total
                 FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN ? AND ? GROUP BY DAY(tanggal_transaksi)",
                [$monthStart, $monthEnd]
            )->getResultArray();

            $bucket = [];
            foreach ($rowsDp as $r) {
                $bucket[(int) $r['tgl']] = ($bucket[(int) $r['tgl']] ?? 0) + (int) $r['total'];
            }
            foreach ($rowsPelunasan as $r) {
                $bucket[(int) $r['tgl']] = ($bucket[(int) $r['tgl']] ?? 0) + (int) $r['total'];
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

        $dpToday = (int) ($db->table('bookings')->selectSum('dp_amount')->where('payment_status', 'dp_verified')->where('DATE(dp_verified_at)', $today)->get()->getRow()->dp_amount ?? 0);
        $pelunasanToday = (int) ($db->table('transaksi')->selectSum('sisa_bayar')->where('DATE(tanggal_transaksi)', $today)->get()->getRow()->sisa_bayar ?? 0);
        $pendapatanHariIni = $dpToday + $pelunasanToday;

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $dpYesterday = (int) ($db->table('bookings')->selectSum('dp_amount')->where('payment_status', 'dp_verified')->where('DATE(dp_verified_at)', $yesterday)->get()->getRow()->dp_amount ?? 0);
        $pelunasanYesterday = (int) ($db->table('transaksi')->selectSum('sisa_bayar')->where('DATE(tanggal_transaksi)', $yesterday)->get()->getRow()->sisa_bayar ?? 0);
        $pendapatanKemarin = $dpYesterday + $pelunasanYesterday;
        $trend = $pendapatanKemarin > 0 ? (int) round((($pendapatanHariIni - $pendapatanKemarin) / $pendapatanKemarin) * 100) : null;

        $dpBulanIni = (int) ($db->table('bookings')->selectSum('dp_amount')->where('payment_status', 'dp_verified')->where('DATE(dp_verified_at) >=', date('Y-m-01'))->get()->getRow()->dp_amount ?? 0);
        $pelunasanBulanIni = (int) ($db->table('transaksi')->selectSum('sisa_bayar')->where('DATE(tanggal_transaksi) >=', date('Y-m-01'))->get()->getRow()->sisa_bayar ?? 0);
        $pendapatanBulanIni = $dpBulanIni + $pelunasanBulanIni;

        // 7-day revenue series for the chart
        $chartLabels = [];
        $chartValues = [];
        $hariShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $dpSum = (int) ($db->table('bookings')->selectSum('dp_amount')->where('payment_status', 'dp_verified')->where('DATE(dp_verified_at)', $d)->get()->getRow()->dp_amount ?? 0);
            $pelunasanSum = (int) ($db->table('transaksi')->selectSum('sisa_bayar')->where('DATE(tanggal_transaksi)', $d)->get()->getRow()->sisa_bayar ?? 0);
            $sum = $dpSum + $pelunasanSum;
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

        // --- Filter tanggal untuk laporan & tabel transaksi ---
        $start = (string) ($this->request->getGet('start') ?: date('Y-m-01'));
        $end = (string) ($this->request->getGet('end') ?: date('Y-m-d'));

        // Query transaksi & booking aktif dalam periode filter
        $transactions = $db->query(
            "SELECT 
                b.id AS booking_id,
                b.kode_booking,
                b.nama_pelanggan,
                b.status AS booking_status,
                b.dp_amount,
                b.payment_status,
                b.dp_verified_at,
                b.completed_at,
                b.final_service_price AS final_price,
                l.nama AS nama_layanan,
                t.base_price,
                t.additional_price,
                t.sisa_bayar,
                t.tanggal_transaksi
             FROM bookings b
             JOIN layanan l ON l.id = b.layanan_id
             LEFT JOIN transaksi t ON t.booking_id = b.id
             WHERE (b.payment_status = 'dp_verified' AND DATE(b.dp_verified_at) BETWEEN ? AND ?)
                OR (b.status = 'completed' AND DATE(b.completed_at) BETWEEN ? AND ?)
             ORDER BY COALESCE(t.tanggal_transaksi, b.dp_verified_at) DESC",
             [$start, $end, $start, $end]
        )->getResultArray();

        $totalDpRange = (int) ($db->table('bookings')
            ->selectSum('dp_amount')
            ->where('payment_status', 'dp_verified')
            ->where('DATE(dp_verified_at) >=', $start)
            ->where('DATE(dp_verified_at) <=', $end)
            ->get()->getRow()->dp_amount ?? 0);

        $totalSisaRange = (int) ($db->table('transaksi')
            ->selectSum('sisa_bayar')
            ->where('DATE(tanggal_transaksi) >=', $start)
            ->where('DATE(tanggal_transaksi) <=', $end)
            ->get()->getRow()->sisa_bayar ?? 0);

        $totalPendapatanRange = $totalDpRange + $totalSisaRange;

        $bookingDpBelumSelesai = $db->table('bookings')
            ->where('status', 'accepted')
            ->where('payment_status', 'dp_verified')
            ->countAllResults();

        $bookingSelesaiRange = $db->table('bookings')
            ->where('status', 'completed')
            ->where('DATE(completed_at) >=', $start)
            ->where('DATE(completed_at) <=', $end)
            ->countAllResults();

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
            // Param filter baru
            'start' => $start,
            'end' => $end,
            'transactions' => $transactions,
            'total_dp_range' => $totalDpRange,
            'total_sisa_range' => $totalSisaRange,
            'total_pendapatan_range' => $totalPendapatanRange,
            'booking_dp_belum_selesai' => $bookingDpBelumSelesai,
            'booking_selesai_range' => $bookingSelesaiRange,
        ]);
    }
}
