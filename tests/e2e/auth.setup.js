import { test as setup, expect } from '@playwright/test';

const authFile = 'tests/e2e/.auth/user.json';

// Logs in with the seeded demo user and persists the session so the rest of the
// suite starts authenticated. Also doubles as the auth smoke test.
setup('authenticate', async ({ page }) => {
    await page.goto('/login');
    await page.fill('#email', 'demo@example.com');
    await page.fill('#password', 'password');
    await page.click('button[type=submit]');

    await page.waitForURL('**/chat');
    await expect(page.locator('[data-chat-widget]')).toBeVisible();

    await page.context().storageState({ path: authFile });
});
