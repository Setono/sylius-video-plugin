import { test as setup, expect } from '@playwright/test';
import path from 'path';

const authFile = path.join(__dirname, '.auth/admin.json');

setup('authenticate as admin', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByLabel('Username').fill('sylius');
    await page.getByLabel('Password').fill('sylius');
    await page.getByRole('button', { name: 'Login' }).click();

    await expect(page).toHaveURL(/\/admin\//);

    await page.context().storageState({ path: authFile });
});
