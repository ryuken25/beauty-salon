// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Real-browser end-to-end suite for SW Beauty Salon.
 *
 * Reflects the 2026-05-20 (megaprompt #3) changes:
 *   - Owner = Admin superset. One unified panel; pemilik sidebar has every
 *     admin menu PLUS Laporan / Layanan / Stylist.
 *   - Transaksi + Pengaturan are admin-tier; Laporan/Layanan/Stylist are
 *     owner-exclusive (admin gets 302 on /owner/*).
 *   - Cancel booking is a dedicated page flow at /cek-booking (no modal).
 *
 * Before running:  php spark serve   +   rm writable/cache/*
 */

const ADMIN = { email: 'admin@swbeautysalon.local', pass: 'Password123!' };
const OWNER = { email: 'owner@swbeautysalon.local', pass: 'Password123!' };

async function loginAs(page, email, password) {
  await page.goto('/login');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForURL((url) => !/\/login$/.test(url.pathname), { timeout: 8000 }),
    page.click('button[type="submit"]'),
  ]);
}

/**
 * Drive the public booking form end-to-end; returns the SW- code.
 * Uses a 30-minute (single-slot) layanan and a caller-chosen slot index
 * so concurrent test bookings on the same day never collide.
 */
async function makeBooking(page, phone, slotNth = 0) {
  await page.goto('/booking');
  await page.fill('input[name="nama_pelanggan"]', 'E2E ' + phone);
  await page.fill('input[name="nomor_hp_pelanggan"]', phone);
  await page.selectOption('#stylistSelect', { index: 1 });
  await page.locator('#layananList .card-salon[data-durasi="30"]').first().click();
  await page.locator('#dateStrip .date-strip-item').nth(1).click();
  await page.waitForSelector('.slot.slot--available', { timeout: 8000 });
  await page.locator('.slot.slot--available').nth(slotNth).click();
  await Promise.all([
    page.waitForURL(/\/booking\/sukses\//, { timeout: 10000 }),
    page.click('#submitBtn'),
  ]);
  return (await page.locator('#kodeBooking').textContent()).trim();
}

// ─── 1. Public surface ─────────────────────────────────────────────
test('homepage has Pesan + Cek/Batalkan CTAs, no login link', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.nav-salon').getByText(/Masuk/)).toHaveCount(0);
  await expect(page.locator('.nav-salon').getByRole('link', { name: /Pesan sekarang/ })).toBeVisible();
  await expect(page.getByRole('link', { name: /Cek atau Batalkan Booking/ })).toBeVisible();
});

test('legacy /register and /pelanggan redirect home', async ({ page }) => {
  await page.goto('/register');
  await expect(page).toHaveURL(/\/$/);
  await page.goto('/pelanggan');
  await expect(page).toHaveURL(/\/$/);
});

// ─── 2. Auth + role gating ─────────────────────────────────────────
test('anonymous /admin and /owner bounce to /login', async ({ page }) => {
  await page.goto('/admin/dashboard');
  await expect(page).toHaveURL(/\/login$/);
  await page.goto('/owner/laporan');
  await expect(page).toHaveURL(/\/login$/);
});

test('admin login → dashboard; sidebar has NO Laporan/Layanan/Stylist', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  await expect(page).toHaveURL(/\/admin\/dashboard$/);
  const sb = page.locator('.sidebar-admin');
  // operational menus present
  for (const m of ['Dashboard', 'Booking', 'Walk-in', 'Jadwal', 'Pelanggan', 'Transaksi', 'Pengaturan']) {
    await expect(sb.getByText(new RegExp('^' + m + '$'))).toBeVisible();
  }
  // managerial menus absent
  for (const m of ['Laporan', 'Layanan', 'Stylist']) {
    await expect(sb.getByText(new RegExp('^' + m + '$'))).toHaveCount(0);
  }
});

test('admin cannot reach owner-exclusive URLs (manual typing)', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  for (const path of ['/owner/laporan', '/owner/layanan', '/owner/stylist']) {
    await page.goto(path);
    await expect(page, 'admin blocked from ' + path).toHaveURL(/\/admin\/dashboard$/);
  }
});

test('admin CAN reach Transaksi + Pengaturan', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  await page.goto('/admin/transaksi');
  await expect(page).toHaveURL(/\/admin\/transaksi$/);
  await page.goto('/admin/pengaturan');
  await expect(page).toHaveURL(/\/admin\/pengaturan$/);
});

test('pemilik sidebar is the full superset (admin menus + 3 managerial)', async ({ page }) => {
  await loginAs(page, OWNER.email, OWNER.pass);
  const sb = page.locator('.sidebar-admin');
  for (const m of ['Dashboard', 'Booking', 'Walk-in', 'Jadwal', 'Pelanggan', 'Transaksi', 'Pengaturan', 'Laporan', 'Layanan', 'Stylist']) {
    await expect(sb.getByText(new RegExp('^' + m + '$'))).toBeVisible();
  }
});

test('pemilik /owner/laporan shows revenue + chart + top services', async ({ page }) => {
  await loginAs(page, OWNER.email, OWNER.pass);
  await page.goto('/owner/laporan');
  await expect(page).toHaveURL(/\/owner\/laporan$/);
  await expect(page.getByText('Pendapatan hari ini', { exact: true })).toBeVisible();
  await expect(page.locator('#chart7days')).toBeVisible();
  await expect(page.getByText('Layanan terpopuler', { exact: true })).toBeVisible();
});

// ─── 3. Owner stylist CRUD ─────────────────────────────────────────
test('owner stylist page: lists seeded stylists + can create', async ({ page }) => {
  await loginAs(page, OWNER.email, OWNER.pass);
  await page.goto('/owner/stylist');
  await expect(page.getByText('Ni Wayan Sutrisna Wati').first()).toBeVisible();
  const nama = 'E2E Stylist ' + Date.now();
  await page.click('button[data-bs-target="#newStylist"]');
  await page.fill('#newStylist input[name="nama"]', nama);
  await Promise.all([
    page.waitForURL(/\/owner\/stylist$/, { timeout: 8000 }),
    page.click('#newStylist button[type="submit"]'),
  ]);
  await expect(page.getByText(nama).first()).toBeVisible();
});

// ─── 4. Public booking flow ────────────────────────────────────────
test('public booking → SW- code on success page', async ({ page }) => {
  const kode = await makeBooking(page, '0812' + String(Date.now()).slice(-8));
  expect(kode).toMatch(/^SW-\d{8}-\d{3}$/);
  await expect(page.locator('#copyKode')).toBeVisible();
});

test('honeypot silently bounces a bot submission', async ({ page }) => {
  await page.goto('/booking');
  await page.fill('input[name="nama_pelanggan"]', 'Bot');
  await page.fill('input[name="nomor_hp_pelanggan"]', '081200000000');
  await page.selectOption('#stylistSelect', { index: 1 });
  await page.locator('#layananList .card-salon').first().click();
  await page.locator('#dateStrip .date-strip-item').nth(1).click();
  await page.waitForSelector('.slot.slot--available', { timeout: 8000 });
  await page.locator('.slot.slot--available').first().click();
  await page.evaluate(() => {
    const hp = document.querySelector('input[name="website"]');
    if (hp) hp.value = 'http://spam.example';
    document.getElementById('bookingForm').submit();
  });
  await page.waitForURL(/\/$/, { timeout: 8000 });
  expect(page.url()).not.toContain('/booking/sukses');
});

// ─── 5. Dedicated cancel-page flow ─────────────────────────────────
test('cek-booking → konfirmasi page → sukses page with WhatsApp link', async ({ page }) => {
  const phone = '0813' + String(Date.now()).slice(-8);
  const kode = await makeBooking(page, phone, 10);

  // Look it up
  await page.goto('/cek-booking');
  await page.fill('input[name="kode_booking"]', kode);
  await page.fill('input[name="nomor_hp"]', phone);
  await Promise.all([page.waitForLoadState('load'), page.click('button[type="submit"]')]);

  // Inline detail + cancel button
  const cancelLink = page.getByRole('link', { name: /Batalkan Booking/ });
  await expect(cancelLink).toBeVisible();
  await cancelLink.click();

  // Dedicated confirmation PAGE (not a modal)
  await expect(page).toHaveURL(/\/cek-booking\/.+\/batal/);
  await expect(page.locator('.h1').filter({ hasText: /Konfirmasi Pembatalan/ })).toBeVisible();
  await page.fill('textarea[name="cancellation_reason"]', 'E2E alasan batal');

  await Promise.all([
    page.waitForURL(/\/cek-booking-sukses\//, { timeout: 8000 }),
    page.click('#batalSubmit'),
  ]);
  // Success page
  await expect(page.locator('.h1').filter({ hasText: /Pembatalan Berhasil/ })).toBeVisible();
  const waBtn = page.locator('#waBtn');
  await expect(waBtn).toBeVisible();
  await expect(waBtn).toHaveAttribute('href', /wa\.me/);
});

test('cek-booking rejects a wrong kode + HP pair', async ({ page }) => {
  await page.goto('/cek-booking');
  await page.fill('input[name="kode_booking"]', 'SW-19990101-999');
  await page.fill('input[name="nomor_hp"]', '081200000000');
  await Promise.all([page.waitForLoadState('load'), page.click('button[type="submit"]')]);
  await expect(page.getByText(/tidak cocok/i)).toBeVisible();
});

// ─── 6. Login throttle is failure-only ─────────────────────────────
test('wrong password then correct password still succeeds', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="email"]', ADMIN.email);
  await page.fill('input[name="password"]', 'salahPassword');
  await page.click('button[type="submit"]');
  await expect(page.getByText(/Email atau password salah/)).toBeVisible();
  await page.fill('input[name="email"]', ADMIN.email);
  await page.fill('input[name="password"]', ADMIN.pass);
  await Promise.all([
    page.waitForURL(/\/admin\/dashboard$/, { timeout: 8000 }),
    page.click('button[type="submit"]'),
  ]);
});
