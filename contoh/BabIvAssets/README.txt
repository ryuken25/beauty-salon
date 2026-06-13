BabIvAssets — Sistem Informasi Penjualan ChelisNet
============================================================

Struktur:
  context/        Diagram Konteks (proses tunggal '0', input/output 1:1)
  dfd 0/          DFD Level 0 (proses utama 1.0, 2.0, ...)
  dfd 1.X/        DFD Level 1 yang mendekomposisi Proses X.0
                  (2 diagram level 1 dibuat)
  erd/            erd.png = ERD crow's foot (konseptual basis data)
                  erd_chen.png = ERD notasi Chen (persegi/elips/belah ketupat)
  mockup 4.4/     wireframe lite untuk 4.4 (LOGO teks, kotak gambar bersilang X)
  BAB_IV.docx     SEMUA bagian dalam SATU dokumen: 4.1 Analisis, 4.2 DFD,
                  4.3 ERD/Basis Data, 4.4 Antarmuka (wireframe),
                  4.5 Implementasi (screenshot), 4.6 Pengujian (SUS).

Tiap folder diagram berisi: *.drawio.xml (draw.io), *.graphml (yEd), *.png.

Catatan:
  - DFD ortogonal; ERD ditata graphviz (minim persilangan, tak menembus kotak).
  - data_xxx = aliran masuk (input), info_xxx = aliran keluar (output).
  - 4.4 = wireframe (data dummy, bukan DB); 4.5 = screenshot sistem asli.
  - Regenerasi semua: jalankan build_babiv_assets.py lagi.

Buka BAB_IV.docx di Word, lalu tekan Ctrl+A kemudian F9 untuk
memperbarui semua nomor (heading & caption) bila perlu.