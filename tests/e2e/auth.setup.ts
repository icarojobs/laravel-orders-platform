import { test as setup } from '@playwright/test';
import { ADMIN_STATE } from '../../playwright.config';
import { login } from './helpers';

setup('authenticate as admin', async ({ page }) => {
    await login(page);
    await page.context().storageState({ path: ADMIN_STATE });
});
