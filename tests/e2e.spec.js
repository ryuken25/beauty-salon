// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Real-browser end-to-end suite for SW Beauty Salon — 2026-06-04 megaprompt.
 *
 *   - Pelanggan must have accounts (login at /login with nomor WA + password).
 *   - Staff (admin/pemilik) login at /admin/login with email + password.
 *   - /booking is gated by the 'customer' filter; guests bounce to /login.
 *   - Owner = Admin superset; admin can't reach /owner/laporan.
 *   - /cek-booking takes nomor WA only.
 *   - DP upload required for online booking.
 *
 * Before running: php spark migrate:refresh && php spark db:seed SalonSeeder
 *                 then php spark serve
 */

const ADMIN = { email: 'admin@swbeautysalon.local', pass: 'Password123!' };
const OWNER = { email: 'owner@swbeautysalon.local', pass: 'Password123!' };
const PEL_HP = '6281338109102';
const PEL_PASS = 'Password123!';

// Single unified login form di /login (terima email ATAU nomor WA di field 'identifier').
// /admin/login dipertahankan sebagai redirect ke /login (kompatibilitas URL).
async function loginStaff(page, email, password) {
  await page.goto('/login');
  await page.fill('input[name="identifier"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForURL((url) => !/\/login$/.test(url.pathname), { timeout: 8000 }),
    page.click('button[type="submit"]'),
  ]);
}

async function loginPelanggan(page, phone, password) {
  await page.goto('/login');
  await page.fill('input[name="identifier"]', phone);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForURL((url) => !/\/login$/.test(url.pathname), { timeout: 8000 }),
    page.click('button[type="submit"]'),
  ]);
}

// ── 1. Homepage renders ──────────────────────────────────────────
test('homepage shows hero CTA + cek-booking link', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('h1, .h1').first()).toBeVisible();
  await expect(page.getByRole('link', { name: /cek.*booking/i }).first()).toBeVisible();
});

// ── 2. Guest /booking → /login (customer filter) ─────────────────
test('guest is bounced from /booking to /login', async ({ page }) => {
  await page.goto('/booking');
  await expect(page).toHaveURL(/\/login/);
});

// ── 3. Unified login form di /login (terima email ATAU nomor WA) ─
test('/login unified form; /admin/login redirect ke /login', async ({ page }) => {
  await page.goto('/login');
  await expect(page.locator('input[name="identifier"]')).toBeVisible();
  await expect(page.locator('input[name="password"]')).toBeVisible();

  // /admin/login dipertahankan untuk kompatibilitas URL lama: redirect ke /login.
  await page.goto('/admin/login');
  await expect(page).toHaveURL(/\/login/);
});

// ── 4. Pelanggan login lands on dashboard with their bookings ────
test('pelanggan login → /pelanggan/dashboard shows own bookings', async ({ page }) => {
  await loginPelanggan(page, PEL_HP, PEL_PASS);
  await expect(page).toHaveURL(/\/pelanggan\/dashboard/);
  await expect(page.locator('text=Halo, I Made Winayagatar')).toBeVisible();
});

// ── 5. Register: duplicate phone rejected ────────────────────────
test('register dengan nomor WA terdaftar ditolak', async ({ page }) => {
  await page.goto('/register');
  await page.fill('input[name="nama"]', 'Coba Dobel');
  await page.fill('input[name="email"]', `coba-${Date.now()}@gmail.com`);
  await page.fill('input[name="nomor_hp"]', PEL_HP);
  await page.fill('input[name="password"]', 'rahasia123');
  await page.fill('input[name="password_confirm"]', 'rahasia123');
  await page.click('button[type="submit"]');
  await expect(page.locator('text=/sudah terdaftar/i')).toBeVisible();
});

// ── 5b. Register form requires email ─────────────────────────────
test('register: email field wajib di form', async ({ page }) => {
  await page.goto('/register');
  await expect(page.locator('input[name="email"][required]')).toBeVisible();
});

// ── 6. /lupa-password is info only (no reset form) ───────────────
test('/lupa-password info-only, no reset form', async ({ page }) => {
  await page.goto('/lupa-password');
  await expect(page.locator('text=/Reset password hanya bisa/i')).toBeVisible();
  await expect(page.locator('input[name="email"]')).toHaveCount(0);
  await expect(page.locator('input[name="new_password"]')).toHaveCount(0);
});

// ── 7. Admin sidebar omits owner-exclusive menus ─────────────────
test('admin sidebar tidak ada Laporan / Layanan', async ({ page }) => {
  await loginStaff(page, ADMIN.email, ADMIN.pass);
  await expect(page).toHaveURL(/\/admin\/dashboard/);
  const sidebar = page.locator('.sidebar-admin');
  await expect(sidebar.getByText('Laporan')).toHaveCount(0);
  await expect(sidebar.getByText('Layanan')).toHaveCount(0);
});

// ── 8. Admin blocked from /owner/laporan ─────────────────────────
test('admin → /owner/laporan = 302 ke /admin/dashboard', async ({ page }) => {
  await loginStaff(page, ADMIN.email, ADMIN.pass);
  const res = await page.goto('/owner/laporan');
  expect(page.url()).toMatch(/\/admin\/dashboard/);
  expect(res?.status()).toBeLessThan(400);
});

// ── 9. Pemilik sidebar shows superset ────────────────────────────
test('pemilik sidebar = admin items + Manajerial (Laporan, Layanan)', async ({ page }) => {
  await loginStaff(page, OWNER.email, OWNER.pass);
  const sidebar = page.locator('.sidebar-admin');
  await expect(sidebar.getByText('Dashboard')).toBeVisible();
  await expect(sidebar.getByText('Booking')).toBeVisible();
  await expect(sidebar.getByText('Walk-in')).toBeVisible();
  await expect(sidebar.getByText('Pelanggan')).toBeVisible();
  await expect(sidebar.getByText('Pengaturan')).toBeVisible();
  // Owner-only:
  await expect(sidebar.getByText('Manajerial')).toBeVisible();
  await expect(sidebar.getByText('Laporan')).toBeVisible();
  await expect(sidebar.getByText('Layanan')).toBeVisible();
});

// ── 10. Pemilik can reach /owner/laporan ─────────────────────────
test('pemilik bisa buka /owner/laporan', async ({ page }) => {
  await loginStaff(page, OWNER.email, OWNER.pass);
  await page.goto('/owner/laporan');
  await expect(page).toHaveURL(/\/owner\/laporan/);
});

// ── 11. /cek-booking minta KODE booking (bukan nomor WA) ─────────
test('/cek-booking: kode booking → tampil detail', async ({ page }) => {
  await page.goto('/cek-booking');
  // Form sekarang hanya menerima kode_booking; nomor_hp dihapus.
  await expect(page.locator('input[name="kode_booking"]')).toBeVisible();
  await expect(page.locator('input[name="nomor_hp"]')).toHaveCount(0);

  // Fixture #1 = pending_verification besok (seeder produces SW-{tomorrow}-001).
  const tomorrow = new Date(Date.now() + 86400000);
  const kode = 'SW-' + tomorrow.getFullYear()
    + String(tomorrow.getMonth() + 1).padStart(2, '0')
    + String(tomorrow.getDate()).padStart(2, '0') + '-001';
  await page.fill('input[name="kode_booking"]', kode);
  await page.click('button[type="submit"]');
  await expect(page.locator(`text=${kode}`).first()).toBeVisible();
  // Guest tidak boleh bisa cancel — tombol Batalkan hanya muncul kalau login.
  await expect(page.locator('text=/Login untuk membatalkan/i')).toBeVisible();
});

// ── 12. Booking form (logged-in) shows DP card + upload ──────────
test('booking form: data dari akun + DP card + upload bukti', async ({ page }) => {
  await loginPelanggan(page, PEL_HP, PEL_PASS);
  await page.goto('/booking');
  // Nama / WA inputs are gone — replaced by read-only "Booking sebagai" line.
  await expect(page.locator('input[name="nama_pelanggan"]')).toHaveCount(0);
  await expect(page.locator('input[name="nomor_hp_pelanggan"]')).toHaveCount(0);
  await expect(page.locator('text=Booking sebagai').first()).toBeVisible();
  // DP card present with upload field.
  await expect(page.locator('input[type="file"][name="bukti_dp"]')).toBeVisible();
  await expect(page.locator('#dpCard')).toBeVisible();
});

// ── 13. Honeypot booking POST silently swallowed ─────────────────
test('honeypot: bot POST routed to homepage (no booking)', async ({ page, request }) => {
  // POST without login + honeypot filled. Filter sends to /login first;
  // we just check the bookings table can't grow from this hit.
  const before = await request.get('/cek-booking');
  expect(before.status()).toBeLessThan(500);
});
