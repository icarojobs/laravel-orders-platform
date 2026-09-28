import { expect, test } from '@playwright/test';

test('livewire low stock widget reacts to the threshold and restocks', async ({
    page,
}) => {
    await page.goto('/inventory?threshold=0');

    const rows = page.locator('tbody tr[wire\\:key]');
    await expect(
        page.getByText('Nenhum produto abaixo do limite.'),
    ).toBeVisible();

    await page.getByTestId('threshold').fill('1000');
    await expect(rows.first()).toBeVisible();
    await expect(page).toHaveURL(/threshold=1000/);

    const stockBefore = Number(
        await rows.first().locator('td').nth(2).textContent(),
    );
    await rows.first().getByRole('button', { name: '+10 unidades' }).click();

    await expect(page.getByTestId('restock-message')).toContainText(
        `estoque atualizado para ${stockBefore + 10}`,
    );
});
