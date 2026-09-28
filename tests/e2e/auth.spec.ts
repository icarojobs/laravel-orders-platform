import { expect, test } from '@playwright/test';
import { login } from './helpers';

test.use({ storageState: { cookies: [], origins: [] } });

test('guests are sent to the login page', async ({ page }) => {
    await page.goto('/orders');

    await expect(page).toHaveURL(/\/login$/);
});

test('wrong credentials are rejected', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#email').fill('nobody@example.com');
    await page.locator('#password').fill('wrong-password');
    await page.locator('[data-test="login-button"]').click();

    await expect(
        page.getByText('These credentials do not match our records.'),
    ).toBeVisible();
});

test('viewer logs in and sees the dashboard', async ({ page }) => {
    await login(page, 'viewer@example.com');

    await expect(
        page.getByRole('heading', { name: 'Últimos 30 dias' }),
    ).toBeVisible();
    await expect(page.getByTestId('kpi-orders')).not.toHaveText('0');
    await expect(page.getByTestId('kpi-revenue')).toContainText('R$');
});
