import { test, expect } from './fixtures';

const YOUTUBE = 'https://www.youtube.com/embed/dQw4w9WgXcQ';

test.describe('Product videos', () => {
    test('adds an external URL video and renders it on the shop page', async ({ page, product, videos }) => {
        await videos.open();

        const row = await videos.addRow();
        await videos.chooseType(row, 'External URL');
        await row.locator('input[name$="[url]"]').fill(YOUTUBE);
        await videos.save();

        await expect(page.locator('.sylius-flash-message')).toContainText('successfully updated');

        // The saved row keeps its type and can no longer change it.
        await videos.open();
        await expect(videos.rows()).toHaveCount(1);
        await expect(videos.rows().first().locator('select[name$="[type]"]')).toBeDisabled();
        await expect(videos.rows().first().locator('input[name$="[url]"]')).toHaveValue(YOUTUBE);

        await page.goto(product.shopUrl);
        const player = page.locator('.setono-sylius-video--url iframe');
        await expect(player).toHaveAttribute('src', YOUTUBE);
    });

    test('shows only the fields of the selected type', async ({ videos }) => {
        await videos.open();
        const row = await videos.addRow();

        await videos.chooseType(row, 'Embed code');
        await expect(videos.fieldGroup(row, 'embed')).toBeVisible();
        await expect(videos.fieldGroup(row, 'url')).toBeHidden();
        await expect(videos.fieldGroup(row, 'file')).toBeHidden();

        await videos.chooseType(row, 'File upload');
        await expect(videos.fieldGroup(row, 'file')).toBeVisible();
        await expect(videos.fieldGroup(row, 'embed')).toBeHidden();
    });

    test('rejects a URL video without a URL and keeps the row', async ({ page, videos }) => {
        await videos.open();
        const row = await videos.addRow();
        await videos.chooseType(row, 'External URL');
        await videos.save();

        await expect(page.locator('.sylius-flash-message')).toHaveCount(0);
        await page.locator('a.item[data-tab="videos"]').click();
        await expect(videos.rows()).toHaveCount(1);
        await expect(videos.rows().first().locator('input[name$="[url]"]')).toBeVisible();
        await expect(videos.rows().first().locator('.sylius-validation-error, .ui.red.pointing.label')).toContainText(/not be blank/i);
    });

    test('removes a video so the shop page has no video block', async ({ page, product, videos }) => {
        await videos.open();
        const row = await videos.addRow();
        await videos.chooseType(row, 'External URL');
        await row.locator('input[name$="[url]"]').fill(YOUTUBE);
        await videos.save();
        await expect(page.locator('.sylius-flash-message')).toContainText('successfully updated');

        await videos.removeAllAndSave();

        await page.goto(product.shopUrl);
        await expect(page.locator('.setono-sylius-video-list')).toHaveCount(0);
    });
});
