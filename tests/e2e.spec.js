// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Real-browser end-to-end suite for SW Beauty Salon.
 *
 * Reflects the 2026-05-20 business changes:
 *   - No customer login/registration — booking is fully public.
 *   - Stylist feature re-added with full CRUD under /owner/stylist.
 *   - Cancel booking via /cek-booking (kode + HP) with a confirm modal.
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

// ─── 1. Public surface ─────────────────────────────────────────────
test('homepage renders with a "Pesan sekarang" CTA and no login link', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.nav-salon')).toBeVisible();
  await expect(page.locator('.nav-salon').getByText(/Masuk/)).toHaveCount(0);
  await expect(page.locator('.nav-salon').getByRole('link', { name: /Pesan sekarang/ })).toBeVisible();
});

test('legacy /register redirects to homepage', async ({ page }) => {
  await page.goto('/register');
  await expect(page).toHaveURL(/\/$/);
});

test('legacy /pelanggan redirects to homepage', async ({ page }) => {
  await page.goto('/pelanggan');
  await expect(page).toHaveURL(/\/$/);
});

test('legacy /pelanggan/dashboard redirects to homepage', async ({ page }) => {
  await page.goto('/pelanggan/dashboard');
  await expect(page).toHaveURL(/\/$/);
});

// ─── 2. Auth gating ────────────────────────────────────────────────
test('anonymous /admin/dashboard and /owner/dashboard bounce to /login', async ({ page }) => {
  await page.goto('/admin/dashboard');
  await expect(page).toHaveURL(/\/login$/);
  await page.goto('/owner/dashboard');
  await expect(page).toHaveURL(/\/login$/);
});

test('login form has no customer-register link', async ({ page }) => {
  await page.goto('/login');
  await expect(page.getByText(/Daftar sebagai pelanggan/)).toHaveCount(0);
  await expect(page.getByText(/Khusus Admin/)).toBeVisible();
});

// ─── 3. Admin vs Owner ─────────────────────────────────────────────
test('admin login lands on /admin/dashboard (operational, no revenue)', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  await expect(page).toHaveURL(/\/admin\/dashboard$/);
  await expect(page.getByText('Pendapatan hari ini')).toHaveCount(0);
  await expect(page.locator('#chart7days')).toHaveCount(0);
});

test('admin → /owner/dashboard is rejected', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  await page.goto('/owner/dashboard');
  await expect(page).toHaveURL(/\/admin\/dashboard$/);
});

test('pemilik login lands on /owner/dashboard with revenue + chart', async ({ page }) => {
  await loginAs(page, OWNER.email, OWNER.pass);
  await expect(page).toHaveURL(/\/owner\/dashboard$/);
  await expect(page.getByText('Pendapatan hari ini', { exact: true })).toBeVisible();
  await expect(page.locator('#chart7days')).toBeVisible();
});

// ─── 4. Owner stylist CRUD ─────────────────────────────────────────
test('owner stylist page lists seeded stylists and supports create', async ({ page }) => {
  await loginAs(page, OWNER.email, OWNER.pass);
  await page.goto('/owner/stylist');
  await expect(page.locator('.h1').filter({ hasText: 'Stylist' })).toBeVisible();
  await expect(page.getByText('Ni Wayan Sutrisna Wati').first()).toBeVisible();

  // Create a new stylist
  const stamp = Date.now();
  const nama = 'E2E Stylist ' + stamp;
  await page.click('button[data-bs-target="#newStylist"]');
  await page.fill('#newStylist input[name="nama"]', nama);
  await page.fill('#newStylist input[name="spesialisasi"]', 'Test Spec');
  await Promise.all([
    page.waitForURL(/\/owner\/stylist$/, { timeout: 8000 }),
    page.click('#newStylist button[type="submit"]'),
  ]);
  await expect(page.getByText(nama).first()).toBeVisible();
});

test('owner sidebar has a Stylist menu item', async ({ page }) => {
  await loginAs(page, OWNER.email, OWNER.pass);
  await page.goto('/owner/dashboard');
  await expect(page.locator('.sidebar-admin').getByText(/^Stylist$/)).toBeVisible();
});

// ─── 5. Public booking flow (no login) ─────────────────────────────
test('public booking flow: pick layanan + stylist + slot → SW- code on success', async ({ page }) => {
  await page.goto('/booking');
  // Customer data
  await page.fill('input[name="nama_pelanggan"]', 'E2E Pelanggan');
  const phone = '0812' + String(Date.now()).slice(-8);
  await page.fill('input[name="nomor_hp_pelanggan"]', phone);
  // Stylist (required dropdown)
  await page.selectOption('#stylistSelect', { index: 1 });
  // Layanan
  await page.locator('#layananList .card-salon').first().click();
  // Date — first item (today)
  await page.locator('#dateStrip .date-strip-item').first().click();
  // Slot
  await page.waitForSelector('.slot.slot--available', { timeout: 8000 });
  await page.locator('.slot.slot--available').first().click();
  await Promise.all([
    page.waitForURL(/\/booking\/sukses\//, { timeout: 10000 }),
    page.click('#submitBtn'),
  ]);
  // Success page shows an SW- code + copy button
  await expect(page.locator('#kodeBooking')).toHaveText(/^SW-\d{8}-\d{3}$/);
  await expect(page.locator('#copyKode')).toBeVisible();
  await expect(page.getByText(/Mohon menunggu konfirmasi via WhatsApp/)).toBeVisible();
});

test('honeypot — a bot filling the hidden field is silently bounced', async ({ page }) => {
  await page.goto('/booking');
  await page.fill('input[name="nama_pelanggan"]', 'Bot');
  await page.fill('input[name="nomor_hp_pelanggan"]', '081200000000');
  await page.selectOption('#stylistSelect', { index: 1 });
  await page.locator('#layananList .card-salon').first().click();
  await page.locator('#dateStrip .date-strip-item').first().click();
  await page.waitForSelector('.slot.slot--available', { timeout: 8000 });
  await page.locator('.slot.slot--available').first().click();
  // Fill the honeypot like a bot would, then submit the form directly
  await page.evaluate(() => {
    const hp = document.querySelector('input[name="website"]');
    if (hp) hp.value = 'http://spam.example';
    document.getElementById('bookingForm').submit();
  });
  await page.waitForURL(/\/$/, { timeout: 8000 });
  // Bounced to homepage — no /booking/sukses
  expect(page.url()).not.toContain('/booking/sukses');
});

// ─── 6. Cancel flow via /cek-booking (Opsi A) ──────────────────────
test('cek-booking → cancel modal → booking becomes Batal', async ({ page }) => {
  // Create a booking first
  await page.goto('/booking');
  await page.fill('input[name="nama_pelanggan"]', 'E2E Cancel');
  const phone = '0813' + String(Date.now()).slice(-8);
  await page.fill('input[name="nomor_hp_pelanggan"]', phone);
  await page.selectOption('#stylistSelect', { index: 1 });
  await page.locator('#layananList .card-salon').first().click();
  await page.locator('#dateStrip .date-strip-item').first().click();
  await page.waitForSelector('.slot.slot--available', { timeout: 8000 });
  await page.locator('.slot.slot--available').first().click();
  await Promise.all([
    page.waitForURL(/\/booking\/sukses\//, { timeout: 10000 }),
    page.click('#submitBtn'),
  ]);

  // Look it up at /cek-booking
  await page.goto('/cek-booking');
  await page.fill('input[name="nomor_hp"]', phone);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]'),
  ]);
  const cancelBtn = page.getByRole('button', { name: /Batalkan/ }).first();
  await expect(cancelBtn).toBeVisible();
  await cancelBtn.click();

  const modal = page.locator('#cancelModal');
  await expect(modal).toBeVisible();
  await expect(modal.getByText(/Konfirmasi Pembatalan Booking/)).toBeVisible();
  await modal.locator('textarea[name="cancellation_reason"]').fill('E2E batal test');
  await Promise.all([
    page.waitForLoadState('load'),
    modal.getByRole('button', { name: /Ya, Batalkan/ }).click(),
  ]);
  await expect(page.getByText(/berhasil dibatalkan/)).toBeVisible();
});

// ─── 7. Login throttle is failure-only ─────────────────────────────
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
