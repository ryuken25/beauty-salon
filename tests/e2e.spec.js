// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Real-browser end-to-end suite for SW Beauty Salon.
 *
 * Before running:
 *   - php spark serve            (port 8080)
 *   - rm writable/cache/*        (drop the throttler counter)
 *
 * Run:
 *   BASE_URL=http://localhost:8080 npx playwright test
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

async function logoutViaLink(page) {
  await page.goto('/logout');
  await expect(page).toHaveURL(/\/login$/);
}

// ─── 1. Anonymous access ───────────────────────────────────────────
test('anonymous GET /login renders the form', async ({ page }) => {
  await page.goto('/login');
  await expect(page).toHaveURL(/\/login$/);
  await expect(page.locator('input[name="email"]')).toBeVisible();
  await expect(page.locator('input[name="password"]')).toBeVisible();
});

test('anonymous /admin/dashboard bounces to /login', async ({ page }) => {
  await page.goto('/admin/dashboard');
  await expect(page).toHaveURL(/\/login$/);
});

test('anonymous /owner/dashboard bounces to /login', async ({ page }) => {
  await page.goto('/owner/dashboard');
  await expect(page).toHaveURL(/\/login$/);
});

test('anonymous /pelanggan/dashboard bounces to /login', async ({ page }) => {
  await page.goto('/pelanggan/dashboard');
  await expect(page).toHaveURL(/\/login$/);
});

// ─── 2. Admin role ─────────────────────────────────────────────────
test('admin login → /admin/dashboard with operational metrics only', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  await expect(page).toHaveURL(/\/admin\/dashboard$/);

  // Operational widgets visible
  await expect(page.getByText('Booking hari ini')).toBeVisible();
  await expect(page.getByText('Pending verifikasi')).toBeVisible();
  await expect(page.locator('h1, .h1').filter({ hasText: /Dashboard.*Admin/ })).toBeVisible();

  // Analytical content must NOT leak into admin view
  await expect(page.getByText('Pendapatan hari ini')).toHaveCount(0);
  await expect(page.getByText('Layanan terpopuler')).toHaveCount(0);
  await expect(page.locator('#chart7days')).toHaveCount(0);
});

test('admin → /owner/dashboard is rejected (302 → /admin/dashboard)', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  await page.goto('/owner/dashboard');
  await expect(page).toHaveURL(/\/admin\/dashboard$/);
});

test('admin sidebar has no "Layanan / Transaksi / Pengaturan" — those are pemilik', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  const sidebar = page.locator('.sidebar-admin');
  await expect(sidebar.getByText(/^Dashboard$/)).toBeVisible();
  await expect(sidebar.getByText(/^Booking$/)).toBeVisible();
  await expect(sidebar.getByText(/^Pelanggan$/)).toBeVisible();
  // not in operational sidebar:
  await expect(sidebar.getByText(/^Layanan$/)).toHaveCount(0);
  await expect(sidebar.getByText(/^Transaksi$/)).toHaveCount(0);
  await expect(sidebar.getByText(/^Pengaturan$/)).toHaveCount(0);
});

// ─── 3. Pemilik role ───────────────────────────────────────────────
test('pemilik login → /owner/dashboard with revenue + chart + top services', async ({ page }) => {
  await loginAs(page, OWNER.email, OWNER.pass);
  await expect(page).toHaveURL(/\/owner\/dashboard$/);

  await expect(page.getByText('Pendapatan hari ini', { exact: true })).toBeVisible();
  await expect(page.getByText('Pendapatan 7 hari', { exact: true })).toBeVisible();
  await expect(page.getByText('Pendapatan bulan ini', { exact: true })).toBeVisible();
  await expect(page.getByText('Layanan terpopuler', { exact: true })).toBeVisible();
  await expect(page.locator('#chart7days')).toBeVisible();
});

test('pemilik can also access /admin/dashboard (read-only)', async ({ page }) => {
  await loginAs(page, OWNER.email, OWNER.pass);
  await page.goto('/admin/dashboard');
  await expect(page).toHaveURL(/\/admin\/dashboard$/);
});

test('pemilik sidebar in owner area has Layanan / Transaksi / Pengaturan', async ({ page }) => {
  await loginAs(page, OWNER.email, OWNER.pass);
  const sidebar = page.locator('.sidebar-admin');
  await expect(sidebar.getByText(/^Layanan$/)).toBeVisible();
  await expect(sidebar.getByText(/^Transaksi$/)).toBeVisible();
  await expect(sidebar.getByText(/^Pengaturan$/)).toBeVisible();
  await expect(sidebar.getByText(/Panel Admin/)).toBeVisible();
});

// ─── 4. Logout + session-switch (the bug Nadi hit) ─────────────────
test('GET /logout clears session and returns the login form', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  await logoutViaLink(page);
  // After logout, /admin/dashboard should bounce to /login again
  await page.goto('/admin/dashboard');
  await expect(page).toHaveURL(/\/login$/);
  // GET /login while logged-out renders the form
  await expect(page.locator('input[name="email"]')).toBeVisible();
});

test('logged-in /login shows "switch account" banner with Logout button', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  await page.goto('/login');
  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByText(/Anda sudah login sebagai/)).toBeVisible();
  await expect(page.getByText(/Admin Salon/)).toBeVisible();
  // Logout link inside banner works
  await page.getByRole('link', { name: /Logout/ }).click();
  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByText(/Anda sudah login sebagai/)).toHaveCount(0);
});

test('admin → pemilik switch in same tab (no logout) lands on /owner/dashboard', async ({ page }) => {
  await loginAs(page, ADMIN.email, ADMIN.pass);
  await expect(page).toHaveURL(/\/admin\/dashboard$/);
  // Without logout: type owner credentials over the existing session
  await loginAs(page, OWNER.email, OWNER.pass);
  await expect(page).toHaveURL(/\/owner\/dashboard$/);
  await expect(page.getByText('Pendapatan hari ini')).toBeVisible();
});

// ─── 5. Customer cancel modal flow ─────────────────────────────────
test('public booking → pelanggan modal cancel renders properly', async ({ page, context }) => {
  // Create a fresh pelanggan + a booking via the public flow
  const stamp = Date.now();
  const email = `e2e_${stamp}@example.local`;
  const phone = '0812' + String(stamp).slice(-8);

  // Register a pelanggan
  await page.goto('/register');
  await page.fill('input[name="nama"]', 'E2E Tester');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="nomor_hp"]', phone);
  await page.fill('input[name="password"]', 'Password123!');
  await page.fill('input[name="password_confirm"]', 'Password123!');
  await Promise.all([
    page.waitForURL((u) => u.pathname.startsWith('/pelanggan/dashboard'), { timeout: 8000 }),
    page.click('button[type="submit"]'),
  ]);

  // Make a booking
  await page.goto('/booking');
  await page.locator('#layananList .card-salon').first().click();
  // Date strip — pick first non-disabled date item (today)
  await page.locator('#dateStrip .date-strip-item').first().click();
  // Wait for slot grid to populate then pick first available slot
  await page.waitForSelector('.slot.slot--available', { timeout: 8000 });
  await page.locator('.slot.slot--available').first().click();
  await Promise.all([
    page.waitForURL(/\/booking\/sukses\//, { timeout: 10000 }),
    page.click('#submitBtn'),
  ]);

  // Pelanggan dashboard now has a pending booking with a "Batalkan" button
  await page.goto('/pelanggan/dashboard');
  const cancelBtn = page.getByRole('button', { name: /Batalkan/ }).first();
  await expect(cancelBtn).toBeVisible();
  await cancelBtn.click();

  // Modal — verify content
  const modal = page.locator('#cancelModal');
  await expect(modal).toBeVisible();
  await expect(modal.getByText(/Konfirmasi Pembatalan Booking/)).toBeVisible();
  await expect(modal.getByText(/tidak bisa dikembalikan/)).toBeVisible();

  // Fill reason and confirm
  await modal.locator('textarea[name="cancellation_reason"]').fill('Test e2e cancel');
  await Promise.all([
    page.waitForURL(/\/pelanggan\/dashboard/, { timeout: 8000 }),
    modal.getByRole('button', { name: /Ya, Batalkan/ }).click(),
  ]);

  // Booking should now show as Batal with the reason rendered
  await expect(page.getByText(/berhasil dibatalkan/)).toBeVisible();
  await expect(page.getByText(/Alasan batal: Test e2e cancel/)).toBeVisible();
});

// ─── 6. Wrong-password rate limit only counts FAILURES ─────────────
test('wrong password → flash error; correct password right after still works', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="email"]', ADMIN.email);
  await page.fill('input[name="password"]', 'WrongPassword');
  await page.click('button[type="submit"]');
  await expect(page.getByText(/Email atau password salah/)).toBeVisible();

  // Now retry with correct password — must succeed (success resets counter)
  await page.fill('input[name="email"]', ADMIN.email);
  await page.fill('input[name="password"]', ADMIN.pass);
  await Promise.all([
    page.waitForURL(/\/admin\/dashboard$/, { timeout: 8000 }),
    page.click('button[type="submit"]'),
  ]);
});
