"""
Generate dokumen .docx struktur tabel SW Beauty Salon langsung dari database,
supaya isinya tidak mungkin meleset dari skema yang sebenarnya berjalan.

Isi dokumen:
  1. Ringkasan tabel
  2. Entity Relationship Diagram notasi Crow's Foot (digambar dengan Graphviz,
     relasi dan kardinalitasnya dibaca dari foreign key di basis data)
  3. Struktur tiap tabel dengan format lampiran laporan TA:
     No | Nama Field | Tipe Data | Panjang | Null | Keterangan

Pemakaian:
    python scripts/generate_struktur_tabel_docx.py docs/Struktur_Tabel_SW_Beauty_Salon.docx

Prasyarat: MySQL menyala, paket python-docx, dan Graphviz (perintah `dot`).
"""
import os
import subprocess
import sys
from docx import Document
from docx.shared import Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.section import WD_ORIENT, WD_SECTION

MYSQL = r"C:\xampp\mysql\bin\mysql.exe"
DOT = "dot"
DB = "sw_beauty_salon"

TABLES = [
    ("users", "Menyimpan seluruh akun yang dapat masuk ke sistem: admin, pemilik, dan pelanggan."),
    ("layanan", "Katalog layanan salon beserta harga, durasi, galeri foto, dan pengaturan promo."),
    ("bookings", "Tabel inti pemesanan, mencakup booking online oleh pelanggan maupun walk-in yang diinput admin."),
    ("booking_slots", "Satu baris mewakili satu slot 30 menit yang ditahan sebuah booking. Menjadi acuan utama pencegahan jadwal bentrok."),
    ("transaksi", "Catatan pembayaran, dibuat satu kali ketika booking berpindah ke status selesai. Berelasi satu-ke-satu dengan bookings."),
    ("settings", "Konfigurasi aplikasi bergaya kunci-nilai yang dapat diubah dari halaman Pengaturan."),
    ("booking_logs", "Jejak audit setiap perubahan status booking."),
]

# Keterangan per kolom. Kunci: (tabel, kolom).
KET = {
    ("users", "id"): "Primary Key, Auto Increment",
    ("users", "email"): "Email untuk masuk bagi staf (admin/pemilik), bersifat unik. Kosong untuk pelanggan",
    ("users", "password_hash"): "Kata sandi yang sudah diacak dengan bcrypt",
    ("users", "nama"): "Nama lengkap pengguna",
    ("users", "nomor_hp"): "Nomor WhatsApp pelanggan, bersifat unik, dipakai sebagai identitas masuk",
    ("users", "role"): "Peran pengguna di dalam sistem",
    ("users", "is_active"): "Status akun, 1 berarti aktif",
    ("users", "created_at"): "Waktu data dibuat",
    ("users", "updated_at"): "Waktu data terakhir diubah",

    ("layanan", "id"): "Primary Key, Auto Increment",
    ("layanan", "nama"): "Nama layanan",
    ("layanan", "kategori"): "Pengelompokan layanan",
    ("layanan", "deskripsi"): "Penjelasan layanan",
    ("layanan", "durasi_menit"): "Lama pengerjaan dalam menit, kelipatan 30",
    ("layanan", "harga"): "Harga normal layanan dalam rupiah",
    ("layanan", "ikon"): "Nama ikon yang dipakai pada kartu layanan",
    ("layanan", "gambar"): "Galeri foto berupa larik path gambar, indeks pertama menjadi sampul",
    ("layanan", "promo_persen"): "Besar diskon 0 sampai 100 persen, kosong berarti tanpa promo",
    ("layanan", "promo_mulai"): "Tanggal promo mulai berlaku, kosong berarti tanpa batas awal",
    ("layanan", "promo_selesai"): "Tanggal promo berakhir, kosong berarti tanpa batas akhir",
    ("layanan", "promo_deskripsi"): "Teks keterangan promo",
    ("layanan", "is_active"): "Layanan tampil di katalog, 1 berarti aktif",
    ("layanan", "created_at"): "Waktu data dibuat",
    ("layanan", "updated_at"): "Waktu data terakhir diubah",
    ("layanan", "deleted_at"): "Penanda penghapusan lunak, kosong berarti belum dihapus",

    ("bookings", "id"): "Primary Key, Auto Increment",
    ("bookings", "kode_booking"): "Kode unik booking dengan format SW-YYYYMMDD-NNN",
    ("bookings", "user_id"): "Foreign Key ke tabel users, kosong untuk booking walk-in",
    ("bookings", "nama_pelanggan"): "Nama lengkap pelanggan",
    ("bookings", "nomor_hp_pelanggan"): "Nomor WhatsApp pelanggan",
    ("bookings", "email_pelanggan"): "Email tujuan pengiriman kode booking dan invoice",
    ("bookings", "layanan_id"): "Foreign Key ke tabel layanan",
    ("bookings", "tanggal"): "Tanggal kunjungan",
    ("bookings", "slot_mulai"): "Jam mulai layanan",
    ("bookings", "slot_selesai"): "Jam selesai layanan",
    ("bookings", "jumlah_slot"): "Banyaknya slot 30 menit yang ditahan",
    ("bookings", "harga_layanan"): "Harga yang ditagihkan, sudah dipotong promo",
    ("bookings", "original_service_price"): "Harga normal saat booking dibuat, disimpan agar nota lama tidak ikut berubah ketika harga katalog diperbarui",
    ("bookings", "promo_name"): "Nama promo yang berlaku saat booking dibuat",
    ("bookings", "promo_discount_value"): "Besar potongan promo dalam persen",
    ("bookings", "remaining_payment"): "Sisa yang dibayar di salon setelah dikurangi uang muka",
    ("bookings", "dp_amount"): "Nominal uang muka",
    ("bookings", "dp_proof_path"): "Lokasi berkas bukti transfer uang muka",
    ("bookings", "payment_status"): "Status pembayaran uang muka",
    ("bookings", "dp_verified_at"): "Waktu uang muka diverifikasi admin",
    ("bookings", "email_reminder_sent_at"): "Waktu email pengingat dikirim, kosong berarti belum dikirim",
    ("bookings", "status"): "Status booking",
    ("bookings", "sumber"): "Asal booking, dari web atau walk-in",
    ("bookings", "catatan"): "Catatan tambahan dari pelanggan atau admin",
    ("bookings", "wa_sent"): "Penanda pesan WhatsApp sudah dikirim admin",
    ("bookings", "verified_via"): "Kanal yang dipakai saat memverifikasi booking",
    ("bookings", "verified_at"): "Waktu booking diverifikasi",
    ("bookings", "completed_at"): "Waktu booking diselesaikan",
    ("bookings", "cancelled_at"): "Waktu booking dibatalkan",
    ("bookings", "cancelled_by"): "Pihak yang membatalkan, pelanggan atau admin",
    ("bookings", "cancellation_reason"): "Alasan pembatalan",
    ("bookings", "rejection_reason"): "Alasan penolakan oleh admin",
    ("bookings", "created_at"): "Waktu data dibuat",
    ("bookings", "updated_at"): "Waktu data terakhir diubah",

    ("booking_slots", "id"): "Primary Key, Auto Increment",
    ("booking_slots", "booking_id"): "Foreign Key ke tabel bookings",
    ("booking_slots", "tanggal"): "Tanggal slot ditahan",
    ("booking_slots", "slot_waktu"): "Jam mulai slot 30 menit",
    ("booking_slots", "status"): "Status penahanan slot",
    ("booking_slots", "created_at"): "Waktu data dibuat",

    ("transaksi", "id"): "Primary Key, Auto Increment",
    ("transaksi", "booking_id"): "Foreign Key ke tabel bookings, bersifat unik karena satu booking hanya punya satu transaksi",
    ("transaksi", "nominal"): "Total yang dibayar pelanggan",
    ("transaksi", "base_price"): "Harga layanan sebelum biaya tambahan",
    ("transaksi", "additional_price"): "Biaya tambahan di luar harga layanan",
    ("transaksi", "dp_paid"): "Uang muka yang sudah dibayar",
    ("transaksi", "sisa_bayar"): "Sisa pelunasan yang dibayar di salon",
    ("transaksi", "metode_bayar"): "Metode pembayaran",
    ("transaksi", "tanggal_transaksi"): "Waktu transaksi dicatat",
    ("transaksi", "catatan"): "Catatan transaksi",
    ("transaksi", "created_at"): "Waktu data dibuat",

    ("settings", "id"): "Primary Key, Auto Increment",
    ("settings", "key_name"): "Nama pengaturan, bersifat unik, misalnya jam_buka atau wa_admin",
    ("settings", "value"): "Nilai pengaturan",
    ("settings", "updated_at"): "Waktu pengaturan terakhir diubah",

    ("booking_logs", "id"): "Primary Key, Auto Increment",
    ("booking_logs", "booking_id"): "Foreign Key ke tabel bookings",
    ("booking_logs", "event_type"): "Jenis kejadian, misalnya dibuat, diverifikasi, atau dibatalkan",
    ("booking_logs", "actor"): "Nama pelaku perubahan",
    ("booking_logs", "actor_role"): "Peran pelaku perubahan",
    ("booking_logs", "payload"): "Data pendukung kejadian",
    ("booking_logs", "notes"): "Catatan bebas",
    ("booking_logs", "created_at"): "Waktu kejadian dicatat",
}


def q(sql, ncol):
    # strip() biasa akan memakan tab kosong di ujung baris terakhir (kolom
    # COLUMN_KEY/EXTRA yang kosong), jadi buang newline-nya saja lalu
    # panjangkan tiap baris sampai jumlah kolom yang diharapkan.
    out = subprocess.run(
        [MYSQL, "-u", "root", "-N", "-B", "-D", DB, "-e", sql],
        capture_output=True, text=True, check=True,
    ).stdout.strip("\r\n")
    if not out:
        return []
    rows = []
    for line in out.splitlines():
        f = line.rstrip("\r").split("\t")
        f += [""] * (ncol - len(f))
        rows.append(f[:ncol])
    return rows


def kolom(tabel):
    rows = q(
        "SELECT ORDINAL_POSITION, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, "
        "CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, IS_NULLABLE, COLUMN_KEY, EXTRA "
        f"FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{DB}' "
        f"AND TABLE_NAME='{tabel}' ORDER BY ORDINAL_POSITION",
        9,
    )
    hasil = []
    for no, nama, dtype, ctype, charlen, numprec, nullable, key, extra in rows:
        tipe = dtype.upper()
        if "unsigned" in ctype:
            tipe += " UNSIGNED"

        if dtype == "enum":
            tipe = "ENUM"
            isi = ctype[ctype.index("(") + 1:ctype.rindex(")")]
            panjang = isi.replace("'", "").replace(",", ", ")
        elif charlen not in ("NULL", ""):
            panjang = charlen
        elif "(" in ctype:
            # Ambil lebar tampilan dari COLUMN_TYPE, misal tinyint(1) -> 1.
            # NUMERIC_PRECISION memberi 3 untuk tinyint(1) sehingga menyesatkan.
            panjang = ctype[ctype.index("(") + 1:ctype.index(")")]
        elif numprec not in ("NULL", ""):
            panjang = numprec
        else:
            panjang = "-"

        if dtype in ("date", "time", "datetime", "text", "longtext"):
            panjang = "-"
        if dtype == "longtext":
            tipe = "JSON" if (tabel, nama) in (("layanan", "gambar"), ("booking_logs", "payload")) else "TEXT"

        hasil.append({
            "no": no,
            "nama": nama,
            "tipe": tipe,
            "panjang": str(panjang),
            "null": "Tidak" if nullable == "NO" else "Ya",
            "ket": KET.get((tabel, nama), "-"),
        })
    return hasil


# ─────────────────────────────────────────────────────────────
#  Entity Relationship Diagram (notasi Crow's Foot)
# ─────────────────────────────────────────────────────────────

# Ujung garis mengikuti notasi Crow's Foot:
#   tee  + tee   -> tepat satu
#   tee  + odot  -> nol atau satu
#   tee  + crow  -> satu atau banyak
#   odot + crow  -> nol atau banyak
KARD = {
    "tepat_satu": "teetee",
    "nol_satu": "odottee",
    "satu_banyak": "teecrow",
    "nol_banyak": "odotcrow",
}


def relasi():
    """Baca foreign key dari basis data lalu simpulkan kardinalitasnya."""
    fks = q(
        "SELECT k.TABLE_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, "
        "c.IS_NULLABLE, c.COLUMN_KEY "
        "FROM information_schema.KEY_COLUMN_USAGE k "
        "JOIN information_schema.COLUMNS c ON c.TABLE_SCHEMA = k.TABLE_SCHEMA "
        "  AND c.TABLE_NAME = k.TABLE_NAME AND c.COLUMN_NAME = k.COLUMN_NAME "
        f"WHERE k.TABLE_SCHEMA='{DB}' AND k.REFERENCED_TABLE_NAME IS NOT NULL "
        "ORDER BY k.REFERENCED_TABLE_NAME, k.TABLE_NAME",
        5,
    )

    hasil = []
    for anak, kolom, induk, nullable, key in fks:
        # Sisi induk: kalau kolom FK boleh kosong, satu baris anak bisa saja
        # tidak terhubung ke induk mana pun (contoh: booking walk-in tanpa akun).
        sisi_induk = KARD["nol_satu"] if nullable == "YES" else KARD["tepat_satu"]

        if key == "UNI":
            # UNIQUE berarti satu induk paling banyak punya satu baris anak.
            sisi_anak, teks = KARD["nol_satu"], "1 : 0..1"
        elif (anak, kolom) == ("booking_slots", "booking_id"):
            # Setiap booking selalu menahan minimal satu slot 30 menit.
            sisi_anak, teks = KARD["satu_banyak"], "1 : 1..N"
        else:
            sisi_anak, teks = KARD["nol_banyak"], "1 : 0..N"

        hasil.append({
            "induk": induk, "anak": anak, "kolom": kolom,
            "sisi_induk": sisi_induk, "sisi_anak": sisi_anak, "teks": teks,
        })
    return hasil


def dot_entitas(tabel, kols, fk_cols):
    baris = [
        '<TR><TD BGCOLOR="#2F2A26" ALIGN="CENTER" PORT="__judul">'
        f'<FONT COLOR="#FFFFFF" POINT-SIZE="13"><B>{tabel}</B></FONT></TD></TR>'
    ]
    for k in kols:
        nama = k["nama"]
        if nama == "id":
            tanda, warna = "PK", "#FBF1DC"
        elif nama in fk_cols:
            tanda, warna = "FK", "#E8EEF9"
        else:
            tanda, warna = "&#160;&#160;", "#FFFFFF"

        label = f"<B>{nama}</B>" if nama == "id" else nama
        tipe = k["tipe"].replace(" UNSIGNED", "").capitalize()
        baris.append(
            f'<TR><TD BGCOLOR="{warna}" ALIGN="LEFT" PORT="{nama}">'
            f'<FONT POINT-SIZE="10"><B>{tanda}</B>  {label}  '
            f'<FONT COLOR="#8A8A8A">{tipe}</FONT></FONT></TD></TR>'
        )

    isi = "\n    ".join(baris)
    return (
        f"  {tabel} [label=<\n"
        '    <TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" CELLPADDING="3" COLOR="#2F2A26">\n'
        f"    {isi}\n"
        "    </TABLE>>];"
    )


def buat_erd(semua, keluaran_png):
    rel = relasi()
    fk_per_tabel = {}
    for r in rel:
        fk_per_tabel.setdefault(r["anak"], set()).add(r["kolom"])

    baris = [
        "digraph ERD {",
        '  graph [rankdir=LR, nodesep=0.5, ranksep=1.6, bgcolor="white", pad=0.25];',
        '  node  [shape=plaintext, fontname="Helvetica"];',
        '  edge  [color="#2F2A26", penwidth=1.2, dir=both, fontname="Helvetica", '
        'fontsize=10, fontcolor="#444444"];',
        "",
    ]
    for tabel, _ in TABLES:
        baris.append(dot_entitas(tabel, semua[tabel], fk_per_tabel.get(tabel, set())))
    baris.append("")

    for r in rel:
        baris.append(
            f'  {r["induk"]}:id -> {r["anak"]}:{r["kolom"]} '
            f'[arrowtail={r["sisi_induk"]}, arrowhead={r["sisi_anak"]}, '
            f'label="{r["teks"]}"];'
        )
    baris.append("}")

    path_dot = os.path.splitext(keluaran_png)[0] + ".dot"
    os.makedirs(os.path.dirname(path_dot) or ".", exist_ok=True)
    with open(path_dot, "w", encoding="utf-8") as f:
        f.write("\n".join(baris) + "\n")

    subprocess.run([DOT, "-Tpng", "-Gdpi=170", path_dot, "-o", keluaran_png], check=True)
    return path_dot, rel


def buat_legenda(keluaran_png):
    """Gambar kecil berisi contoh keempat bentuk ujung garis Crow's Foot."""
    # Graphviz menumpuk baris dari bawah ke atas, jadi daftar ini sengaja
    # dibalik supaya urutan yang tampil dimulai dari "tepat satu".
    contoh = [
        ("nol atau banyak", KARD["nol_banyak"]),
        ("satu atau banyak", KARD["satu_banyak"]),
        ("nol atau satu", KARD["nol_satu"]),
        ("tepat satu", KARD["tepat_satu"]),
    ]
    baris = [
        "digraph LEGENDA {",
        '  graph [rankdir=LR, nodesep=0.15, ranksep=1.1, bgcolor="white", pad=0.12];',
        '  node [shape=box, style=filled, fillcolor="#FFFFFF", color="#FFFFFF", '
        'fontname="Helvetica", fontsize=11, width=0.1, height=0.28];',
        '  edge [color="#2F2A26", penwidth=1.3, fontname="Helvetica", fontsize=11];',
    ]
    for i, (teks, bentuk) in enumerate(contoh):
        baris += [
            f'  a{i} [label="", width=0.05];',
            f'  b{i} [label="{teks}", fontsize=11];',
            f'  a{i} -> b{i} [arrowhead={bentuk}, arrowtail=none, dir=forward];',
        ]
    baris.append("}")

    path_dot = os.path.splitext(keluaran_png)[0] + ".dot"
    with open(path_dot, "w", encoding="utf-8") as f:
        f.write("\n".join(baris) + "\n")
    subprocess.run([DOT, "-Tpng", "-Gdpi=170", path_dot, "-o", keluaran_png], check=True)
    return keluaran_png


def ukuran_png(path):
    """Baca lebar dan tinggi PNG dari header, tanpa perlu paket tambahan."""
    with open(path, "rb") as f:
        head = f.read(26)
    return int.from_bytes(head[16:20], "big"), int.from_bytes(head[20:24], "big")


def atur_halaman(sec, margin):
    """Samakan semua halaman ke ukuran A4 tegak dengan margin yang diminta."""
    sec.orientation = WD_ORIENT.PORTRAIT
    sec.page_width = Cm(21.0)
    sec.page_height = Cm(29.7)
    for m in ("left_margin", "right_margin", "top_margin", "bottom_margin"):
        setattr(sec, m, margin)


def set_font(doc):
    st = doc.styles["Normal"]
    st.font.name = "Times New Roman"
    st.font.size = Pt(12)


def judul(doc, teks, level):
    p = doc.add_heading(teks, level=level)
    for run in p.runs:
        run.font.name = "Times New Roman"
        run.font.color.rgb = RGBColor(0, 0, 0)
        run.font.size = Pt(14 if level == 1 else 12)
    return p


def buat_tabel(doc, kolom_data):
    t = doc.add_table(rows=1, cols=6)
    t.style = "Table Grid"
    t.alignment = WD_TABLE_ALIGNMENT.CENTER

    header = ["No", "Nama Field", "Tipe Data", "Panjang", "Null", "Keterangan"]
    lebar = [Cm(1.0), Cm(3.8), Cm(2.6), Cm(2.0), Cm(1.4), Cm(6.2)]

    for i, (teks, w) in enumerate(zip(header, lebar)):
        sel = t.rows[0].cells[i]
        sel.width = w
        p = sel.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = p.add_run(teks)
        run.bold = True
        run.font.size = Pt(10)

    for k in kolom_data:
        sel = t.add_row().cells
        nilai = [k["no"], k["nama"], k["tipe"], k["panjang"], k["null"], k["ket"]]
        for i, (v, w) in enumerate(zip(nilai, lebar)):
            sel[i].width = w
            p = sel[i].paragraphs[0]
            if i in (0, 3, 4):
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            run = p.add_run(str(v))
            run.font.size = Pt(10)
    return t


def main(keluaran):
    doc = Document()
    set_font(doc)

    atur_halaman(doc.sections[0], Cm(2.5))

    judul(doc, "Struktur Tabel Basis Data", 1)
    p = doc.add_paragraph(
        "Sistem Informasi Pemesanan SW Beauty Salon. Basis data sw_beauty_salon "
        "dengan mesin penyimpanan InnoDB. Seluruh primary key bernama id, bertipe "
        "BIGINT UNSIGNED, dan bersifat auto increment sehingga nilainya dihasilkan "
        "otomatis oleh basis data, bukan diisi oleh pengguna."
    )
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

    judul(doc, "Ringkasan Tabel", 2)
    ring = doc.add_table(rows=1, cols=4)
    ring.style = "Table Grid"
    for i, teks in enumerate(["No", "Nama Tabel", "Jumlah Field", "Keterangan"]):
        p = ring.rows[0].cells[i].paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run(teks)
        r.bold = True
        r.font.size = Pt(10)

    semua = {}
    for i, (tabel, ket) in enumerate(TABLES, start=1):
        semua[tabel] = kolom(tabel)
        sel = ring.add_row().cells
        for j, v in enumerate([i, tabel, len(semua[tabel]), ket]):
            p = sel[j].paragraphs[0]
            if j in (0, 2):
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            r = p.add_run(str(v))
            r.font.size = Pt(10)

    # ── Halaman ERD (mendatar supaya diagram muat lebar) ──────
    png = os.path.join(os.path.dirname(keluaran) or ".", "ERD_SW_Beauty_Salon.png")
    path_dot, rel = buat_erd(semua, png)

    # Diagram lebih tinggi daripada lebar, jadi halamannya tetap tegak
    # dengan margin dipersempit supaya gambar bisa sebesar mungkin.
    sec_erd = doc.add_section(WD_SECTION.NEW_PAGE)
    atur_halaman(sec_erd, Cm(1.5))

    judul(doc, "Entity Relationship Diagram", 2)
    p = doc.add_paragraph(
        "Relasi antar tabel digambarkan dengan notasi Crow's Foot. Garis penghubung "
        "diambil langsung dari foreign key yang terpasang di basis data, sehingga "
        "diagram ini selalu sesuai dengan keadaan tabel yang sebenarnya. Kotak "
        "bertanda PK adalah primary key dan FK adalah foreign key."
    )
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

    # Skalakan gambar agar muat lebar sekaligus tinggi halaman.
    px_w, px_h = ukuran_png(png)
    lebar_ada = sec_erd.page_width - sec_erd.left_margin - sec_erd.right_margin
    tinggi_ada = sec_erd.page_height - sec_erd.top_margin - sec_erd.bottom_margin - Cm(4.0)
    lebar_pakai = min(lebar_ada, int(tinggi_ada * px_w / px_h))
    doc.add_picture(png, width=lebar_pakai)
    doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER

    cap = doc.add_paragraph("Gambar 1. Entity Relationship Diagram SW Beauty Salon")
    cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
    for r in cap.runs:
        r.font.size = Pt(10)
        r.italic = True

    doc.add_page_break()
    judul(doc, "Keterangan Notasi dan Relasi", 2)

    png_leg = buat_legenda(
        os.path.join(os.path.dirname(keluaran) or ".", "ERD_Legenda_CrowsFoot.png")
    )
    p = doc.add_paragraph("Arti bentuk ujung garis pada diagram:")
    doc.add_picture(png_leg, width=Cm(9.0))
    doc.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER
    doc.add_paragraph()

    trel = doc.add_table(rows=1, cols=5)
    trel.style = "Table Grid"
    for i, teks in enumerate(["No", "Tabel Induk", "Tabel Anak", "Kunci Penghubung", "Kardinalitas"]):
        pp = trel.rows[0].cells[i].paragraphs[0]
        pp.alignment = WD_ALIGN_PARAGRAPH.CENTER
        rr = pp.add_run(teks)
        rr.bold = True
        rr.font.size = Pt(10)
    for i, r in enumerate(rel, start=1):
        sel = trel.add_row().cells
        nilai = [i, r["induk"], r["anak"], f'{r["anak"]}.{r["kolom"]}', r["teks"]]
        for j, v in enumerate(nilai):
            pp = sel[j].paragraphs[0]
            if j in (0, 4):
                pp.alignment = WD_ALIGN_PARAGRAPH.CENTER
            rr = pp.add_run(str(v))
            rr.font.size = Pt(10)

    p = doc.add_paragraph(
        "Tabel settings tidak memiliki relasi ke tabel lain karena isinya berupa "
        "pasangan kunci dan nilai untuk konfigurasi aplikasi, bukan data transaksi."
    )
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

    # ── Margin normal lagi untuk struktur tiap tabel ──────────
    sec_tab = doc.add_section(WD_SECTION.NEW_PAGE)
    atur_halaman(sec_tab, Cm(2.5))

    for i, (tabel, ket) in enumerate(TABLES, start=1):
        judul(doc, f"Tabel {tabel}", 2)
        p = doc.add_paragraph(ket)
        p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        buat_tabel(doc, semua[tabel])
        doc.add_paragraph()
        if i < len(TABLES):
            doc.add_page_break()

    doc.save(keluaran)
    total = sum(len(v) for v in semua.values())
    print(f"Tersimpan: {keluaran}")
    print(f"ERD      : {png}")
    print(f"Sumber   : {path_dot}")
    for tabel, kols in semua.items():
        print(f"  {tabel}: {len(kols)} field")
    print(f"  TOTAL: {total} field di {len(semua)} tabel, {len(rel)} relasi")


if __name__ == "__main__":
    main(sys.argv[1])
