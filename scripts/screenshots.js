// Visual smoke — capture key pages so colour/contrast clashes are easy to spot.
// Run: node scripts/screenshots.js   (needs `php spark serve` on :8080)
const { chromium } = require('@playwright/test');

const BASE = process.env.BASE_URL || 'http://localhost:8080';
const OUT = 'halaman';
const fs = require('fs');

// 16:9 viewport (Full HD). Screenshots are viewport-sized, not full-page,
// so the output ratio matches exactly — easier to drop into 16:9 slide decks.
const VIEWPORT = { width: 1920, height: 1080 };

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: VIEWPORT });

  async function shot(name) {
    await page.screenshot({ path: `${OUT}/${name}.png`, fullPage: false });
    console.log('  saved', name);
  }
  async function login(email) {
    await page.goto(BASE + '/admin/logout');
    await page.goto(BASE + '/admin/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'Password123!');
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type="submit"]')]);
  }

  // Public
  for (const [path, name] of [['/', 'home'], ['/layanan', 'layanan'], ['/booking', 'booking'], ['/cek-booking', 'cek-booking'], ['/login', 'login']]) {
    await page.goto(BASE + path, { waitUntil: 'networkidle' });
    await shot('public-' + name);
  }

  // Owner — full superset sidebar + managerial pages
  await login('owner@swbeautysalon.local');
  for (const [path, name] of [
    ['/admin/dashboard', 'dashboard'], ['/admin/booking', 'booking'], ['/admin/booking/walkin', 'walkin'],
    ['/admin/pelanggan', 'pelanggan'], ['/admin/transaksi', 'transaksi'], ['/admin/pengaturan', 'pengaturan'],
    ['/owner/laporan', 'laporan'], ['/owner/layanan', 'layanan'],
  ]) {
    await page.goto(BASE + path, { waitUntil: 'networkidle' });
    await shot('owner-' + name);
  }

  // Admin — operational-only sidebar
  await login('admin@swbeautysalon.local');
  await page.goto(BASE + '/admin/dashboard', { waitUntil: 'networkidle' });
  await shot('admin-dashboard');

  await browser.close();
  console.log('done');
})();
