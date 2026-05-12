# System Usability Scale (SUS) — SW Beauty Salon

Instrumen baku John Brooke (1986) yang sudah diterjemahkan ke Bahasa Indonesia. Sepuluh pernyataan dengan skala Likert 1–5 (1 = Sangat Tidak Setuju, 5 = Sangat Setuju). Target responden minimal 5–10 orang per kelompok pengguna (pelanggan + admin/pemilik).

## Pernyataan

Untuk setiap pernyataan, lingkari salah satu: 1 (sangat tidak setuju) — 2 — 3 — 4 — 5 (sangat setuju).

1. Saya berpikir akan menggunakan sistem ini lagi.
2. Saya merasa sistem ini rumit untuk digunakan.
3. Saya merasa sistem ini mudah digunakan.
4. Saya membutuhkan bantuan dari orang lain atau teknisi dalam menggunakan sistem ini.
5. Saya merasa fitur-fitur sistem ini berjalan dengan semestinya.
6. Saya merasa ada banyak hal yang tidak konsisten pada sistem ini.
7. Saya merasa orang lain akan memahami cara menggunakan sistem ini dengan cepat.
8. Saya merasa sistem ini membingungkan.
9. Saya merasa tidak ada hambatan dalam menggunakan sistem ini.
10. Saya perlu membiasakan diri terlebih dahulu sebelum menggunakan sistem ini.

## Perhitungan skor

Untuk setiap responden:

- Pernyataan **ganjil** (1, 3, 5, 7, 9): skor = (jawaban − 1).
- Pernyataan **genap** (2, 4, 6, 8, 10): skor = (5 − jawaban).
- Jumlahkan 10 skor, lalu kalikan dengan **2.5**.

```
SUS = ((q1−1) + (5−q2) + (q3−1) + (5−q4) + (q5−1) + (5−q6) + (q7−1) + (5−q8) + (q9−1) + (5−q10)) × 2.5
```

Skor akhir 0–100.

## Skor rata-rata sampel

```
SUS_total = Σ SUS_responden / jumlah_responden
```

## Interpretasi (Tabel 2.6 proposal)

| Skor | Grade | Adjective |
|---|---|---|
| ≥ 81 | A | Excellent |
| 68 – 80 | B | Good |
| 51 – 67 | C | Okay / Marginal |
| 35 – 50 | D | Poor |
| < 35 | F | Worst Imaginable |

(Catatan: ambang batas berbeda-beda di literatur. Versi yang umum dipakai oleh Bangor et al. 2009 dan dikutip di proposal SEMPRO: ≥ 80.3 Excellent, 71.4–80.3 Good, 50.9–71.4 OK, dst.)

## Template tabel rekap

| Responden | Q1 | Q2 | Q3 | Q4 | Q5 | Q6 | Q7 | Q8 | Q9 | Q10 | Skor (×2.5) | Grade |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| R01 |  |  |  |  |  |  |  |  |  |  |  |  |
| R02 |  |  |  |  |  |  |  |  |  |  |  |  |
| R03 |  |  |  |  |  |  |  |  |  |  |  |  |
| R04 |  |  |  |  |  |  |  |  |  |  |  |  |
| R05 |  |  |  |  |  |  |  |  |  |  |  |  |
| **Rata-rata** | | | | | | | | | | | | |

## Pelaksanaan

1. Responden mengakses sistem pada device masing-masing (mobile / desktop) selama ±15 menit.
2. Skenario task minimal: cari layanan, buat booking, lihat halaman sukses, cek status via /cek-booking, batalkan booking (opsional).
3. Setelah selesai task, responden mengisi kuesioner (online via Google Form atau cetak).
4. Hitung skor per responden, lalu rata-rata.
5. Sajikan distribusi (histogram) + skor rata-rata dengan interpretasi.

## Rekomendasi tindak lanjut

- Skor < 68 → ada masalah usability serius. Lakukan revisi UI sebelum submit TA.
- Skor 68–80 → acceptable, tapi catat feedback kualitatif responden untuk perbaikan minor.
- Skor ≥ 80 → siap submit. Tulis di Bab IV hasil pengujian.
