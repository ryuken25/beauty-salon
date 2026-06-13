# MEGAPROMPT — Generator BAB IV (Diagram DFD/ERD + Dokumen Word) Universal

> Tempel prompt ini ke **Claude Code** (atau agen sejenis) **di dalam root project**
> yang mau dibuatkan BAB IV-nya. Prompt ini dipasangkan dengan sebuah *skill* di
> `skills/bab4-diagrams/` berisi library Python teruji untuk membangun diagram
> RAPI dan dokumen Word yang BENAR. Tugasmu **bukan** menggambar koordinat atau
> mengetik nomor — tugasmu **membaca kode asli**, mengisi `spec.json` secara
> **logis**, menyiapkan screenshot, lalu menjalankan generator.

---

## 0. PERAN & TUJUAN

Kamu menyusun **BAB IV (Hasil dan Pembahasan)** skripsi sistem informasi. Output
akhir = **satu** folder `BabIvAssets/` yang berisi:

- `context/`     — Diagram Konteks (input/output **selaras 1:1**)
- `dfd 0/`       — DFD Level 0 (≤ 8 proses)
- `dfd 1.X/`     — DFD Level 1 untuk **setiap** proses yang didekomposisi (X = nomor proses induk)
- `erd/`         — ERD **crow's foot** (`erd.png`) + ERD **notasi Chen** (`erd_chen.png`)
- `mockup 4.4/`  — wireframe lite untuk 4.4 (digenerate otomatis)
- `BAB_IV.docx`  — **SEMUA** masuk ke SATU dokumen dengan urutan baku:
  - **4.1 Analisis** (Masalah + Kebutuhan Fungsional + Non-Fungsional dgn 2 tabel HW/SW)
  - **4.2 Perancangan** (Diagram Konteks + DFD Level 0 + DFD Level 1.X)
  - **4.3 Basis Data** (ERD crow's foot + ERD Chen + struktur tabel)
  - **4.4 Antarmuka** (wireframe lite)
  - **4.5 Implementasi** (screenshot ASLI sistem)
  - **4.6 Pengujian Black Box** (tabel skenario per fitur)
  - **4.7 Pengujian SUS** (tabel kuesioner + tabel skor konversi per responden + interpretasi)

> **Lihat `contoh/`** di pack ini: itu output NYATA (tanpa graphviz) lengkap dengan
> `BabIvAssets/BAB_IV.docx`, jadi kamu tahu persis bentuk akhir yang benar sebelum
> mulai. Tool bantu: `capture_screenshots.py` (tangkap screenshot 4.5 via Playwright)
> dan `verify_diagrams.py` (cek keseimbangan tiap diagram, mis. "kanan atas kosong").

> ## ✏️ Tiap diagram BISA DIEDIT MANUAL di draw.io
> Selain `.png`, generator selalu menyimpan file **`.drawio.xml`** (dan `.graphml`
> untuk yEd) di tiap folder diagram. **File `.drawio.xml` itu bisa dibuka & dirapikan
> manual** kalau ada bagian yang masih kurang sreg:
> 1. Buka **https://app.diagrams.net** (atau aplikasi draw.io desktop) → **Open Existing
>    Diagram** → pilih `…/dfd 0/dfd_level_0.drawio.xml`.
> 2. Geser kotak/garis/label sesukamu (drag), lalu **File → Export as → PNG** (centang
>    *Transparent* OFF, background putih) untuk mengganti PNG lama.
> 3. Atau sisipkan langsung file `.drawio` ke Word/draw.io.
>
> Jadi untuk DFD yang **sangat padat** (mis. 8 proses + banyak store + 3 eksternal),
> hasil otomatis sudah rapi & terbaca, dan **sisa perapian terakhir tinggal digeser di
> draw.io** — beri tahu user soal ini. (Catatan: **graphviz hanya memengaruhi tata
> letak ERD**, bukan DFD. DFD memakai engine draw.io bawaan yang sudah diatur, dan
> hasilnya tetap editable di draw.io.)

Prinsip diagram: **garis rapi tanpa label tumpang-tindih**; DFD **ortogonal**;
ERD crow's foot pakai **kaki gagak** (bukan teks "1"/"N"); ERD Chen pakai
**persegi (entitas) + elips (atribut) + belah ketupat (relasi)**. Tata letak
dihitung **library secara deterministik** (graphviz dipakai bila ada, tapi opsional), jadi minim persilangan dan tidak ada garis yang
menembus kotak.

---

## 1. ATURAN KERAS (WAJIB — ini yang membedakan dari output jelek)

1. **JANGAN menulis koordinat/XML diagram dengan tangan.** Kamu hanya mengisi
   struktur **logis** ke `spec.json`. Tata letak dihitung otomatis.
2. **JANGAN mengetik nomor gambar/tabel/sub-bab manual.** Generator memakai
   **heading style auto-number** + **field SEQ**. Nomor manual = sumber bug
   "Gambar 4.19 → 4.22" dan artefak "4. Halaman …". Dilarang.
3. **Baca kode SUMBER dulu** (Langkah 2). Jangan mengarang proses/entitas/aliran.
4. **Proses DFD per level maksimal 8** (aturan 7±2). Kalau audit > 8, **gabungkan**
   yang sejenis sampai ≤ 8.
5. **DEKOMPOSISI SEMUA proses utama ke Level 1.** Untuk **setiap** proses di Level 0
   yang punya lebih dari satu langkah (hampir semua proses CRUD: tambah/ubah/hapus/
   lihat, validasi, hitung, simpan), buat **satu** entry `level1`. Jadi kalau Level 0
   punya proses 1.0–6.0, idealnya muncul `dfd 1.1, 1.2, 1.3, 1.4, 1.5, 1.6`.
   **Jangan hanya membuat sebagian** (penyebab keluhan "kok nggak ada 1.1 1.2 1.4").
   Hanya boleh dilewati kalau proses itu benar-benar atomik (1 langkah).
6. **Konvensi label aliran**: masuk ke proses/store = `data_xxx`; keluar dari
   proses/store = `info_xxx`. Konsisten di semua diagram, **tanpa spasi** (snake_case).
7. **Diagram Konteks selaras 1:1**: untuk tiap entitas eksternal, usahakan jumlah
   aliran **masuk (data_)** seimbang dengan **keluar (info_)** sehingga panah kiri
   dan kanan terlihat berpasangan rapi.
8. **SETIAP node WAJIB punya aliran MASUK *dan* KELUAR** (tidak boleh ada yang
   cuma input atau cuma output) — di **Diagram Konteks** dan **DFD Level 0** ini
   berlaku untuk **semua** entitas eksternal, proses, **dan** data store:
   - **Proses**: minimal 1 aliran masuk + 1 keluar (kalau cuma masuk = "black
     hole", cuma keluar = "miracle" — keduanya salah). Berlaku **juga di Level 1**.
   - **Data store (Level 0)**: harus **ditulis** (`data_` masuk) **dan dibaca**
     (`info_` keluar). Jangan ada store yang hanya ditulis/hanya dibaca di Level 0.
   - **Entitas eksternal (Konteks/Level 0)**: mengirim (`data_`) **dan** menerima
     (`info_`).
   - **Pengecualian Level 1**: di DFD Level 1 (memecah SATU proses), sebuah data
     store boleh **cuma dibaca atau cuma ditulis** kalau proses itu memang hanya
     mengakses satu arah (mis. proses "lihat laporan" cuma baca). Itu **benar**.
   Generator mencetak **PERINGATAN** node yang belum seimbang (Konteks & Level 0
   penuh; Level 1 hanya proses) — perbaiki di `spec.json` sampai bersih.
   > **Akibatnya untuk DFD Level 0**: tiap **data store WAJIB punya panah masuk
   > (`data_`, ditulis) DAN panah keluar (`info_`, dibaca)**. Tidak boleh ada
   > store yang cuma "ngasih data" tanpa ada proses yang menulisinya, atau
   > sebaliknya. Kalau sebuah store hanya dibaca proses laporan, pastikan proses
   > lain (CRUD-nya) yang menulis store itu juga ada di Level 0.
   >
   > **ATURAN BOLAK-BALIK PER PAIR (PENTING — sumber keluhan "DFD tidak selaras"):**
   > setiap pasangan **(proses, data store)** yang muncul di Level 0 maupun Level 1
   > **WAJIB punya KEDUA arah** — yaitu `data_<store>` (proses → store) **DAN**
   > `info_<store>` (store → proses). Contoh dari skripsi acuan: setiap proses
   > "Pengolahan_data_X 0.0" punya pasangan `Data_X` masuk ke store **DAN**
   > `Update_X` keluar dari store. Tanpa ini, panah DFD jadi "1 arah doang" dan
   > terkesan tidak realistis (proses menulis tapi tak pernah membaca, atau baca
   > tapi tak pernah menulis). Helper mental: kalau spec.json ada baris
   > `{"src":"p2","dst":"d_ta","label":"data_tahun_ajaran"}`, tambahkan pasangan
   > `{"src":"d_ta","dst":"p2","label":"info_tahun_ajaran"}`. Berlaku juga di
   > Level 1 untuk pasangan proses-store yang aktif dipakai dua-arah (CRUD lengkap).
9. **Tata letak DFD = gaya "store di atas proses".** Library menata DFD seperti
   contoh skripsi: **proses berderet di kolom tengah**; **tiap data store
   diletakkan tepat DI ATAS proses pemiliknya** (panah baca+tulis pendek); dan
   **entitas eksternal jadi kotak tinggi di KIRI & KANAN**. Kamu **tidak** perlu
   mengatur posisi — cukup isi aliran yang benar. Aturan visual yang sudah
   ditangani library: garis antar store/proses boleh **ortogonal**; pakai
   **line-jump (arc)** saat menyilang tapi diminimalkan; dan panah ke entitas
   eksternal **boleh masuk dari sisi mana saja** (kiri/kanan/atas/bawah) — yang
   penting rapi. Store milik bersama (dipakai proses lain) dirutekan lewat
   koridor kanan secara otomatis.
   > **Skala besar otomatis ditangani**: kalau satu proses punya BANYAK store
   > (mis. "Manajemen Data Master" pegang 8 tabel), store-nya dibungkus jadi
   > **grid (maks 4 per baris)** di atas proses itu. Kalau ada **3+ entitas
   > eksternal**, yang sesisi dibagi ke **pita vertikal** terpisah supaya tinggi
   > kanvas tidak meledak. Lebar kanvas menyesuaikan agar store TIDAK menimpa
   > kotak eksternal. Kamu cukup memastikan aliran benar.
10. **ERD**: "Konseptual Basis Data" = **crow's foot** (utama). Sertakan juga
   **notasi Chen** (`"chen": true`) sebagai pelengkap. Garis ERD **tidak harus
   ortogonal**, yang penting **rapi** (sudah diatur library).
11. **4.4 Perancangan Antarmuka = WIREFRAME lite (mockup), BUKAN screenshot asli.**
   Versi lite/grayscale: logo cukup tulisan **"LOGO"**; kotak gambar/foto =
   **outline + silang X**; data **dummy** ("Siswa A", "Siswa B"), **tidak** ambil
   dari database. Digenerate otomatis dari `spec.antarmuka[].mockup`.
12. **4.5 Implementasi = SCREENSHOT ASLI dari sistem yang jalan.** Tangkap
    **SEMUA halaman** dan jelaskan tiap halaman. Bukan wireframe.
    **DILARANG mengarang jumlah screenshot.** Jangan pernah menulis kalimat
    seperti "29 screenshot" kalau filenya tidak ada — **jumlah = jumlah halaman
    nyata** yang berhasil ditangkap. Setiap `implementasi[].image` HARUS menunjuk
    file PNG yang benar-benar ada. Cara menangkap: pakai `capture_screenshots.py`
    (Playwright) di Langkah 4. Kalau app tak bisa dijalankan agen, **minta user**
    mengirim screenshot — jangan tandai 4.5 selesai sampai gambarnya tertanam.
    (Generator tidak error bila `image` kosong: bagian itu tampil judul+deskripsi
    saja, tinggal ditambah gambar lalu render ulang.)
13. **4.6 Pengujian = BLACK BOX per fitur.** Isi `spec.pengujian.blackbox`:
    satu grup per fitur program (audit dari Controllers/Views), tiap grup berisi
    skenario uji. Kolom tabel otomatis: **No | Data Input | Hasil yang Diharapkan
    | Hasil Pengamatan | Kesimpulan** (No diberi nomor otomatis). Skenario harus
    **sesuai fitur nyata** sistem (login, CRUD tiap modul, transaksi, laporan,
    validasi gagal, dst). Generator menambahkan kalimat kesimpulan + persentase.
14. **4.7 Pengujian = SUS otomatis** dari kuesioner `data.xlsx`. Generator membuat:
    tabel **daftar pernyataan** (No | Pernyataan | 1 2 3 4 5), tabel **contoh
    perhitungan** 1 responden, lalu tabel **skor konversi seluruh responden**
    (Responden | kontribusi 1..10 | Skor Total | Skor SUS) + rata-rata/grade/
    interpretasi — **persis format skripsi acuan**. Kamu cukup mengganti isi
    `data.xlsx` dengan jawaban responden nyata (urutan 10 pernyataan tetap; yang
    berbeda hanya jumlah responden & angka jawabannya). Skor dihitung library.
15. **Struktur tabel rata kiri otomatis.** Sel tabel digenerate **tanpa indentasi
    baris pertama** (tidak ada "tab" di kolom Field) — cukup isi `struktur_tabel`
    apa adanya; jangan menambah spasi/tab manual di teks sel.
16. **SEMUA harus masuk ke SATU `BAB_IV.docx`** dengan urutan 4.1–4.7 di atas.
17. **Verifikasi dengan MELIHAT** tiap PNG + jalankan `verify_diagrams.py` sebelum
    bilang selesai (Langkah 6). Kalau ada yang aneh/tak seimbang, perbaiki **spec**,
    render ulang — jangan edit XML.

---

## 2. AUDIT KODE SUMBER (sebelum mengisi spec)

Ekstrak struktur nyata sistem. Untuk **CodeIgniter 4**:

- **`app/Config/Routes.php`** → endpoint = kandidat fungsi/proses.
- **`app/Controllers/`** → tiap method publik = fungsi. Kelompokkan jadi proses
  Level 0 (mis. semua auth → "Autentikasi & Sesi"). Catat langkah-langkah di tiap
  method (validasi, hitung, simpan, kirim) → jadi sub-proses Level 1.
- **`app/Models/`** → tiap model = kandidat entitas; lihat `$allowedFields`,
  `$primaryKey`, relasi.
- **Migrasi/skema** (`app/Database/Migrations/` atau `.sql`) → kolom, PK, FK untuk
  ERD + struktur tabel.
- **`app/Views/`** → **daftar SEMUA halaman** untuk 4.4 (wireframe) & 4.5 (screenshot).
  Buat daftar lengkap; jangan sampai ada view yang tidak terdokumentasi.
- **Entitas eksternal** = aktor/role (cek filter auth, kolom `role`, menu per-role).

Framework lain (Laravel dst): padankan `routes/web.php`, `app/Http/Controllers`,
`app/Models`, `database/migrations`, `resources/views`.

Arah aliran: controller **menyimpan** ke DB → `data_`; controller **membaca/
menampilkan** → `info_`.

> Tulis ringkasan audit: aktor; proses Level 0 (≤8) **beserta sub-prosesnya**;
> store (tabel); entitas + atribut + PK/FK; relasi + kardinalitas; **daftar semua
> halaman/route**; dan **daftar fitur yang akan diuji black box** (mis. login,
> CRUD tiap modul, transaksi, laporan) beserta skenario sukses & gagalnya.

---

## 3. ISI `spec.json`

Salin `skills/bab4-diagrams/spec.example.json` sebagai titik awal. Skema lengkap di
`spec.schema.md`. Ringkasnya:

```jsonc
{
  "system_name": "Sistem Informasi ...",
  "chapter": 4,
  "bab_title": "Hasil dan Pembahasan",

  "external_entities": [ {"id":"owner","name":"Owner"}, ... ],
  "data_stores":       [ {"id":"d_user","code":"D1","name":"user"}, ... ],

  // Konteks: proses tunggal "sys" otomatis. Tulis aliran eksternal <-> "sys".
  // Selaras 1:1 -> tiap aktor punya pasangan data_ (masuk) & info_ (keluar).
  "context": { "flows": [ {"src":"owner","dst":"sys","label":"data_login_owner"},
                          {"src":"sys","dst":"owner","label":"info_dashboard"}, ... ] },

  // DFD Level 0: proses utama (<=8) + aliran (eksternal<->proses, proses<->store)
  "level0": {
    "processes": [ {"id":"p1","no":"1.0","name":"Autentikasi & Sesi"}, ... ],
    "flows":     [ {"src":"owner","dst":"p1","label":"data_login_owner"}, ... ]
  },

  // DFD Level 1: SATU entry per proses yang didekomposisi (lihat ATURAN #5: buat
  // untuk SEMUA proses utama). Folder otomatis "dfd 1.X" (X = angka depan parent_no).
  "level1": [
    { "parent_no":"1.0",
      "processes":[ {"id":"p1_1","no":"1.1","name":"Validasi Login"},
                    {"id":"p1_2","no":"1.2","name":"Buat Sesi"} ],
      "flows":[ {"src":"owner","dst":"p1_1","label":"data_login"},
                {"src":"p1_1","dst":"d_user","label":"data_user"}, ... ] },
    { "parent_no":"2.0", "processes":[...], "flows":[...] },
    // ... lanjutkan untuk 3.0, 4.0, 5.0, 6.0, dst.
  ],

  // ERD: crow's foot (utama) + Chen (pelengkap).
  "erd": {
    "chen": true,
    "entities": [
      {"id":"barang","name":"BARANG","pk":"idbarang","fks":["jenis","merk"],
       "attrs":["nama","harga"]}, ...
    ],
    "relations": [
      {"src":"jenis","dst":"barang","label":"menaungi","src_card":"one","dst_card":"many"}, ...
    ]
  },

  // ---- Konten dokumen ----
  "analisis": {
    "masalah": "…",
    "kebutuhan_fungsional": [ {"actor":"Owner","items":["…","…"]}, ... ],  // -> 1/2/3 + a/b/c
    "kebutuhan_nonfungsional": {
      "deskripsi":"…",
      "tabel":[ {"judul":"Perangkat Keras","header":["No","Perangkat","Spesifikasi"],
                 "rows":[["1","Processor","…"]], "widths_cm":[1.5,5,7.5]} ]
    }
  },
  "struktur_tabel": [ {"nama":"user","header":["Field","Tipe","Keterangan"],
                       "rows":[["iduser","INT","Primary Key"]], "widths_cm":[4,4,6]} ],

  // 4.4 ANTARMUKA = wireframe lite. Tiap item punya "mockup" (daftar komponen).
  // Library merender PNG grayscale otomatis (LOGO teks, kotak gambar bersilang X,
  // data dummy). JANGAN isi "image" di sini — biar digenerate.
  "antarmuka": [
    { "title":"Halaman Login", "desc":"…",
      "mockup":[
        {"type":"navbar","logo":true,"title":"NamaApp"},
        {"type":"form","title":"Login","fields":[{"label":"Username"},
          {"label":"Password","type":"password"}],"submit":"Masuk"}
      ] },
    { "title":"Dashboard", "desc":"…",
      "mockup":[
        {"type":"navbar","logo":true,"title":"NamaApp","menu":["Dashboard","Data","Logout"]},
        {"type":"sidebar","items":["Dashboard","Data Siswa","Nilai","Logout"]},
        {"type":"heading","text":"Dashboard"},
        {"type":"cards","items":[{"title":"Total Siswa","value":"120"}]},
        {"type":"table","title":"Daftar","columns":["No","Nama","Kelas","Aksi"],"dummy_rows":4},
        {"type":"image","label":"Grafik","h":160},
        {"type":"buttons","items":["Tambah","Export"]}
      ] }
  ],

  // 4.5 IMPLEMENTASI = screenshot ASLI. Isi "image" dengan path PNG hasil tangkapan
  // sistem yang jalan (Langkah 4). Buat satu item per HALAMAN — semua halaman.
  // JANGAN mengarang jumlah; tanpa file PNG, item tampil judul+desc saja.
  "implementasi": [
    { "title":"Halaman Login", "desc":"…", "image":"shots/login.png" },
    { "title":"Dashboard",     "desc":"…", "image":"shots/dashboard.png" }, ...
  ],

  // 4.6 PENGUJIAN BLACK BOX. Satu grup per fitur (hasil audit Controllers/Views).
  // Kolom tabel otomatis: No | Data Input | Hasil yang Diharapkan | Hasil
  // Pengamatan | Kesimpulan. "result" biasanya "Sesuai".
  "pengujian": {
    "blackbox": [
      { "judul":"Autentikasi & Sesi (Login/Logout)",
        "cases":[
          {"input":"Login username & password benar","expect":"Masuk ke dashboard sesuai peran","observe":"Berhasil masuk","result":"Sesuai"},
          {"input":"Login password salah","expect":"Sistem menolak + pesan error","observe":"Muncul pesan error","result":"Sesuai"}
        ] },
      { "judul":"Manajemen Data Barang", "cases":[ ... ] }
      // ... satu grup untuk SETIAP fitur utama sistem
    ]
  },

  // 4.7 PENGUJIAN (SUS). Tunjuk file kuesioner. Library hitung skor +
  // membuat tabel kuesioner, contoh perhitungan, tabel skor konversi semua
  // responden, dan interpretasi. Ganti isi data.xlsx dgn jawaban nyata.
  "sus": { "xlsx": "data.xlsx" }
}
```

**Komponen `mockup` yang didukung** (4.4): `navbar` (`logo`,`title`,`menu`),
`sidebar` (`items`), `heading` (`text`), `text` (`text`), `form`
(`title`,`fields[].label`,`submit`), `table` (`columns` + `rows` ATAU `dummy_rows`),
`image` (`label`,`w`,`h` → kotak bersilang X), `cards` (`items[].title/value`),
`buttons` (`items`). Data tabel kalau pakai `dummy_rows` otomatis terisi
"Siswa A/B/C", "Edit | Hapus", "…".

**File SUS `data.xlsx`**: ekspor jawaban kuesioner SUS (10 pernyataan skala 1–5,
ganjil positif / genap negatif). Library otomatis mendeteksi 10 kolom skala 1–5,
menghitung kontribusi per pernyataan ((ganjil: skor−1; genap: 5−skor)), skor per
responden ((Σkontribusi)×2,5), rata-rata, grade, dan interpretasi. Di dokumen 4.7
ini jadi: tabel daftar pernyataan, tabel **contoh** perhitungan 1 responden, dan
tabel **skor konversi seluruh responden**. **Urutan 10 pernyataan tetap** — kamu
hanya mengganti jumlah responden & angka jawaban di `data.xlsx`. Letakkan
`data.xlsx` di folder yang sama dengan `spec.json` (atau isi path relatifnya).

Aturan mengisi: `id` unik & konsisten antar bagian; setiap `flow.label` ikut
`data_`/`info_`; untuk 4.5 path `image` relatif terhadap tempat menjalankan generator.

---

## 4. SIAPKAN SCREENSHOT ASLI UNTUK 4.5 (WAJIB — jangan dilewati)

Bagian **4.5 Implementasi HARUS berisi tangkapan layar dari PROGRAM ASLI yang
berjalan** (bukan wireframe, bukan mockup, bukan dummy). Tanpa screenshot, 4.5
cuma berisi judul + caption kosong = **belum selesai**. Tangkap **semua halaman**
hasil audit `Views`/`Routes`. Dua cara — **lakukan salah satu, jangan diabaikan**:

**A. Tangkap otomatis dengan `capture_screenshots.py`** (kalau app bisa jalan lokal):
```bash
# 1) jalankan app CI4
php spark serve            # atau: php -S localhost:8080 -t public

# 2) buat config lalu edit (base_url, login, daftar pages = SEMUA halaman audit)
cd skills/bab4-diagrams
python capture_screenshots.py --init      # menulis shots.json contoh
#    edit shots.json: isi login (kalau perlu) + daftar 'pages' dari audit Views/Routes

# 3) tangkap -> PNG ke shots/
python capture_screenshots.py shots.json
```
Lalu isi path `shots/<nama>.png` ke tiap `implementasi[].image`. **Jumlah item =
jumlah halaman nyata yang tertangkap** — jangan mengarang angka.

**B. Minta screenshot ke user.** Kalau app **tidak bisa** dijalankan agen (butuh
DB, kredensial, hosting, dsb), **JANGAN diam** dan **JANGAN menulis jumlah palsu**.
Minta user: "Tolong kirim screenshot tiap halaman (login, dashboard, …)." Simpan
ke `shots/`, isi `implementasi[].image`, render ulang. Jangan tandai 4.5 selesai
sampai gambar aslinya benar-benar tertanam (atau user memilih melengkapi nanti).

> Pastikan **jumlah item `implementasi` = jumlah halaman** dari audit. Tidak ada
> halaman yang terlewat (ATURAN #13). Catatan: kalau path `image` belum ada
> filenya, generator tidak error — bagian itu hanya tampil judul+caption tanpa
> gambar, jadi screenshot bisa ditambahkan belakangan lalu render ulang.

---

## 5. JALANKAN GENERATOR

```bash
# dependensi WAJIB (sekali saja)
pip install python-docx playwright openpyxl pillow --break-system-packages
python -m playwright install chromium
# OPSIONAL: graphviz -> tata letak DFD level sedikit lebih rapi (lihat catatan)
#   Linux:   sudo apt-get install -y graphviz
#   Windows: winget install graphviz   (atau: choco install graphviz)
# viewer draw.io sudah dibundel offline di skills/bab4-diagrams/vendor/ (tanpa internet)

cd skills/bab4-diagrams
python build_babiv_assets.py /path/ke/spec.json --out ../../out

# cek keseimbangan tiap diagram (mendeteksi "kanan atas kosong", layout pincang):
python verify_diagrams.py ../../out/BabIvAssets        # tambah --strict utk exit !=0
```

> **graphviz itu OPSIONAL.** Semua diagram tetap dibuat penuh tanpa graphviz —
> termasuk **ERD Chen** (pakai layout melingkar bawaan) dan DFD (pakai layout
> draw.io ortogonal bawaan). Kalau graphviz **ada**, DFD level memakai tata letak
> graphviz yang sedikit lebih rapi; kalau **tidak ada**, otomatis pakai layout
> bawaan — tidak ada yang dilewati. Jadi tak perlu repot install graphviz (apalagi
> di Windows yang butuh admin); `pip install` di atas sudah cukup. **`pillow`
> penting** supaya latar PNG dipastikan putih (bukan transparan/hitam).

Opsi: `--no-docx` (diagram saja) · `--no-render` (xml + graphml saja, lewati PNG).

Hasil: `out/BabIvAssets/` (struktur seperti Bagian 0). Generator akan mencetak
ringkasan termasuk hasil SUS (jumlah responden, rata-rata, grade).

---

## 6. VERIFIKASI (wajib sebelum menyatakan selesai)

> **CROSS-CHECK SAMPAI SEMPURNA.** Jangan menyatakan selesai setelah sekali
> render. WAJIB: render → **BUKA & LIHAT tiap PNG** (terutama `dfd 0`) →
> jalankan `verify_diagrams.py` → kalau ada yang menumpuk/timpang/tinggi
> berlebihan, **perbaiki `spec.json`** (pecah/seimbangkan aliran, betulkan
> kepemilikan store) lalu **render ULANG** → ulangi sampai DFD benar-benar rapi
> dan 0 peringatan. Khusus DFD Level 0 sistem besar (8 proses / banyak store),
> bandingkan dengan `contoh/referensi_gaya_dfd/` — harus segaya itu.

Buka tiap PNG di `out/BabIvAssets/**/**.png` dan dokumen `BAB_IV.docx`:

- [ ] **Tidak ada label menumpuk**; tiap aliran punya jalur sendiri (tidak ada
      "garis yang jadi satu").
- [ ] **Background PUTIH solid** (bukan hitam/transparan). Kalau hitam: PNG-nya
      transparan — pasang `pillow` ATAU pastikan render terbaru dipakai
      (engine sudah memaksa latar putih). Garis hitam harus jelas terlihat.
- [ ] **Semua garis nyambung** dari sumber ke tujuan (tidak terputus-putus);
      DFD level memakai garis **ortogonal** (siku-siku), proses berbentuk
      **lingkaran**, entitas eksternal & data store berbentuk kotak.
- [ ] **Input/output seimbang**: generator TIDAK mencetak peringatan "input/output
      BELUM seimbang". Tiap proses, entitas eksternal, dan data store punya
      minimal 1 aliran **masuk** DAN 1 **keluar** (tidak ada yang cuma satu arah).
- [ ] **Diagram Konteks**: proses "0" di tengah, panah `data_` (masuk) / `info_`
      (keluar) **berpasangan rapi 1:1** kiri-kanan.
- [ ] **DFD Level 0 (gaya store-di-atas-proses)**: proses **≤ 8** berderet di
      kolom tengah; tiap store **tepat di atas proses pemiliknya**; eksternal
      kotak tinggi di kiri/kanan; **tidak ada store yang menimpa kotak eksternal**;
      kanvas **tidak kelewat tinggi/kosong**. Bandingkan ke `referensi_gaya_dfd/`.
- [ ] **DFD Level 1**: ada folder `dfd 1.X` untuk **setiap** proses yang dipecah
      (cek tidak ada yang hilang — 1.1, 1.2, 1.3, …); nomor sub-proses benar.
- [ ] **ERD crow's foot** (`erd.png`): kaki gagak muncul (bukan "1"/"N"); PK
      digaris-bawah; FK berlabel "FK"; garis tidak menembus kotak.
- [ ] **ERD Chen** (`erd_chen.png`): **selalu ada** (tanpa graphviz pun, pakai
      layout melingkar bawaan); entitas persegi, atribut elips, relasi belah
      ketupat, kardinalitas 1/N di garisnya, PK digaris-bawah.
- [ ] **4.4 (mockup)**: wireframe lite grayscale; logo = teks "LOGO"; kotak gambar
      **bersilang X**; data **dummy** (Siswa A/B), bukan dari DB.
- [ ] **4.5 (implementasi)**: screenshot **asli** sistem, **semua halaman** ada &
      dijelaskan. **Jumlah = jumlah halaman nyata** (tidak ada angka karangan).
- [ ] **4.6 (Black Box)**: ada tabel per fitur (No | Data Input | Hasil yang
      Diharapkan | Hasil Pengamatan | Kesimpulan), skenario sesuai fitur nyata,
      ditutup kalimat kesimpulan + persentase "Sesuai".
- [ ] **4.7 (SUS)**: ada tabel daftar pernyataan, contoh perhitungan 1 responden,
      tabel skor konversi **seluruh** responden (kontribusi 1..10 + Skor Total +
      Skor SUS), rata-rata + grade + interpretasi sesuai `data.xlsx`.
- [ ] **Struktur tabel rata kiri** (kolom Field tidak ber-"tab"/indentasi).
- [ ] **`verify_diagrams.py` lolos** (tidak ada peringatan kuadran kosong /
      kanvas terlalu sepi). Kalau ada, seimbangkan diagram lalu render ulang.
- [ ] **`BAB_IV.docx`**: buka, **Ctrl+A → F9** untuk update semua field. Heading
      ter-nomor otomatis (4.1 … 4.7), caption "Gambar 4.x"/"Tabel 4.x" **berurutan
      tanpa lompat**, dan **semua bagian ada dalam SATU dokumen** (4.1–4.7).
- [ ] **List 4.1 Analisis Kebutuhan Fungsional** rapi (penomoran 1/2/3 + a/b/c
      menjorok benar, **tidak meluber keluar halaman**).

Kalau ada yang kurang: **ubah `spec.json`** (pecah proses, betulkan arah/label,
tambah item antarmuka/implementasi yang terlewat, dll) lalu jalankan ulang.
**Jangan** edit `.drawio.xml` dengan tangan kecuali kosmetik kecil.

---

## 7. SERAHKAN

Serahkan folder `out/BabIvAssets/` ke user. Sebutkan singkat: jumlah proses
Level 0, berapa diagram Level 1 dibuat, hasil SUS (rata-rata + grade), dan
**ingatkan tekan Ctrl+A → F9 di Word** agar semua nomor ter-update.

---

## LAMPIRAN A — Penamaan folder Level 1

`parent_no` "1.0" → folder `dfd 1.1`, "5.0" → `dfd 1.5`. Artinya: **DFD Level 1
yang mendekomposisi Proses X.0**. Folder mengikuti nomor proses induk. Buat untuk
**semua** proses utama (lihat ATURAN #5), bukan sebagian.

## LAMPIRAN B — Kenapa pendekatan ini

Versi lama menyuruh AI menulis koordinat XML tanpa bisa "melihat" hasil → label
tabrakan, garis kusut/menembus kotak, ERD "1/N". Versi ini: deklarasikan
**logika** → **library** menata tata letak deterministik yang rapi (graphviz opsional) →
render dengan **engine draw.io asli** (PNG terverifikasi) → Word pakai **heading
auto-number + field SEQ** sehingga penomoran tak pernah bolong; 4.4 wireframe lite
otomatis, 4.5 screenshot asli, 4.6 SUS otomatis — semua dalam satu dokumen.
