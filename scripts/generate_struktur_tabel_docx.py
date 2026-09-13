"""
Generate dokumen .docx struktur tabel SW Beauty Salon langsung dari database,
supaya isinya tidak mungkin meleset dari skema yang sebenarnya berjalan.

Format tabel mengikuti lampiran laporan TA:
No | Nama Field | Tipe Data | Panjang | Null | Keterangan
"""
import subprocess
import sys
from docx import Document
from docx.shared import Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.section import WD_ORIENT

MYSQL = r"C:\xampp\mysql\bin\mysql.exe"
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

    sec = doc.sections[0]
    sec.orientation = WD_ORIENT.PORTRAIT
    for m in ("left_margin", "right_margin"):
        setattr(sec, m, Cm(2.5))

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

    doc.add_page_break()

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
    for tabel, kols in semua.items():
        print(f"  {tabel}: {len(kols)} field")
    print(f"  TOTAL: {total} field di {len(semua)} tabel")


if __name__ == "__main__":
    main(sys.argv[1])
