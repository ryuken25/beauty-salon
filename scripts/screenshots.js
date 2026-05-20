// Visual smoke — capture key pages so colour/contrast clashes are easy to spot.
// Run: node scripts/screenshots.js   (needs `php spark serve` on :8080)
const { chromium } = require('@playwright/test');

const BASE = process.env.BASE_URL || 'http://localhost:8080';
const OUT = 'test-results/shots';
const fs = require('fs');

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1366, height: 900 } });

  async function shot(name) {
    await page.screenshot({ path: `${OUT}/${name}.png`, fullPage: true });
    console.log('  saved', name);
  }

  // Public pages
  for (const [path, name] of [['/', 'home'], ['/layanan', 'layanan'], ['/booking', 'booking'], ['/cek-booking', 'cek-booking'], ['/login', 'login']]) {
    await page.goto(BASE + path, { waitUntil: 'networkidle' });
    await shot('public-' + name);
  }

  // Owner area
  await page.goto(BASE + '/login');
  await page.fill('input[name="email"]', 'owner@swbeautysalon.local');
  await page.fill('input[name="password"]', 'Password123!');
  await Promise.all([page.waitForURL(/owner\/dashboard/), page.click('button[type="submit"]')]);
  for (const [path, name] of [['/owner/dashboard', 'dashboard'], ['/owner/layanan', 'layanan'], ['/owner/stylist', 'stylist'], ['/owner/transaksi', 'transaksi'], ['/owner/pengaturan', 'pengaturan']]) {
    await page.goto(BASE + path, { waitUntil: 'networkidle' });
    await shot('owner-' + name);
  }

  // Admin area
  await page.goto(BASE + '/logout');
  await page.goto(BASE + '/login');
  await page.fill('input[name="email"]', 'admin@swbeautysalon.local');
  await page.fill('input[name="password"]', 'Password123!');
  await Promise.all([page.waitForURL(/admin\/dashboard/), page.click('button[type="submit"]')]);
  for (const [path, name] of [['/admin/dashboard', 'dashboard'], ['/admin/booking', 'booking'], ['/admin/booking/walkin', 'walkin'], ['/admin/pelanggan', 'pelanggan']]) {
    await page.goto(BASE + path, { waitUntil: 'networkidle' });
    await shot('admin-' + name);
  }

  await browser.close();
  console.log('done');
})();
