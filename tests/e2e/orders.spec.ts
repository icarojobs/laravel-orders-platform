import { expect, test } from '@playwright/test';
import { login } from './helpers';

test.describe('orders', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/dashboard');
        await page.getByRole('link', { name: 'Pedidos' }).first().click();
        await expect(page.getByTestId('orders-table')).toBeVisible();
    });

    test('filters by status', async ({ page }) => {
        await page.locator('#status').selectOption('paid');
        await page.getByRole('button', { name: 'Filtrar' }).click();

        await expect(page).toHaveURL(/status=paid/);
        const badges = page
            .getByTestId('orders-table')
            .getByTestId('order-status');
        await expect(badges.first()).toHaveText('Pago');
        for (const text of await badges.allTextContents()) {
            expect(text).toBe('Pago');
        }
    });

    test('searches by order number and opens the detail page', async ({
        page,
    }) => {
        const number =
            (await page
                .getByTestId('orders-table')
                .locator('tbody tr a')
                .first()
                .textContent()) ?? '';

        await page.locator('#search').fill(number);
        await page.getByRole('button', { name: 'Filtrar' }).click();

        await expect(page.getByTestId('orders-total')).toContainText(
            '1 pedido(s)',
        );
        await page.getByRole('link', { name: number }).click();

        await expect(page.getByTestId('order-number')).toHaveText(number);
        await expect(page.getByTestId('order-total')).toContainText('R$');
    });

    test('confirms the payment of a pending order', async ({ page }) => {
        await page.locator('#status').selectOption('pending');
        await page.getByRole('button', { name: 'Filtrar' }).click();
        await expect(page).toHaveURL(/status=pending/);

        await page
            .getByTestId('orders-table')
            .locator('tbody tr a')
            .first()
            .click();
        await expect(page.getByTestId('order-number')).toBeVisible();
        await expect(page.getByTestId('order-status')).toHaveText('Pendente');

        await page.getByRole('button', { name: 'Confirmar pagamento' }).click();

        await expect(page.getByTestId('order-status')).toHaveText('Pago');
        await expect(page.getByText(/atualizado para Pago/)).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Marcar como enviado' }),
        ).toBeVisible();
    });
});

test.describe('as a viewer', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('cannot change orders', async ({ page }) => {
        await login(page, 'viewer@example.com');
        await page.goto('/orders?status=pending');
        await page
            .getByTestId('orders-table')
            .locator('tbody tr a')
            .first()
            .click();

        await expect(page.getByTestId('order-number')).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Confirmar pagamento' }),
        ).toHaveCount(0);
    });
});
