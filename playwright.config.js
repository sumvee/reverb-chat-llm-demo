import { defineConfig, devices } from '@playwright/test';

// Drives the running Docker stack at :8091. Uses the locally-installed Chrome
// (channel: 'chrome') so no browser download is needed. Start the app first:
//   docker compose up -d
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: process.env.APP_URL || 'http://localhost:8091',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        // Logs in once and saves the session; every other project reuses it.
        { name: 'setup', testMatch: /auth\.setup\.js/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'], channel: 'chrome', storageState: 'tests/e2e/.auth/user.json' },
            dependencies: ['setup'],
        },
    ],
});
