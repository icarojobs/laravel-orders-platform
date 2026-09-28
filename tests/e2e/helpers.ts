import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';

export async function login(
    page: Page,
    email = 'admin@example.com',
    password = 'password',
) {
    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('[data-test="login-button"]').click();
    await expect(page).toHaveURL(/\/dashboard$/);
}
