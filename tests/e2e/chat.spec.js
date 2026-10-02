import { test, expect } from '@playwright/test';

// The core real-time assertion: a message sent by one user arrives live in
// another user's browser, over Reverb. Two independent browser contexts (two
// "people") rather than storageState. This test IS the demo.
test.use({ storageState: { cookies: [], origins: [] } });

async function login(context, email) {
    const page = await context.newPage();
    await page.goto('/login');
    await page.fill('#email', email);
    await page.fill('#password', 'password');
    await page.click('button[type=submit]');
    await page.waitForURL('**/chat');
    return page;
}

test('a message sent by one user arrives live for the other', async ({ browser, baseURL }) => {
    const ctxA = await browser.newContext({ baseURL });
    const ctxB = await browser.newContext({ baseURL });

    const demo = await login(ctxA, 'demo@example.com');
    const adison = await login(ctxB, 'adison@example.com');

    const demoChat = demo.locator('[data-chat-widget]');
    const adisonChat = adison.locator('[data-chat-widget]');

    await demoChat.locator('[data-chat-conversations] a', { hasText: 'Adison Lee' }).click();
    await adisonChat.locator('[data-chat-conversations] a', { hasText: 'Demo User' }).click();
    await expect(demoChat.locator('[data-chat-messages] .msg').first()).toBeVisible();

    const token = 'ping-' + Date.now();
    await demoChat.locator('[data-chat-input]').fill(token);
    await demoChat.locator('[data-chat-form] button[type=submit]').click();

    // it renders as an incoming (them) bubble on adison's side, live
    await expect(adisonChat.locator('.msg--them', { hasText: token })).toBeVisible({ timeout: 8000 });

    await ctxA.close();
    await ctxB.close();
});

test('presence marks participants online', async ({ browser, baseURL }) => {
    const ctxA = await browser.newContext({ baseURL });
    const ctxB = await browser.newContext({ baseURL });
    const demo = await login(ctxA, 'demo@example.com');
    await login(ctxB, 'adison@example.com');

    await expect(demo.locator('[data-chat-presence]')).toContainText(/online/, { timeout: 8000 });

    await ctxA.close();
    await ctxB.close();
});
