// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',
  fullyParallel: false,            // share one server, run serially
  forbidOnly: !!process.env.CI,
  retries: 0,
  workers: 1,
  reporter: [['list']],
  timeout: 20_000,
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    trace: 'retain-on-failure',
    video: 'off',
    screenshot: 'only-on-failure',
    actionTimeout: 8_000,
    navigationTimeout: 10_000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
