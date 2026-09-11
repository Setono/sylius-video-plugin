import { test, expect } from './fixtures';

/**
 * The Cloudflare Stream type is enabled in the test application with placeholder credentials, so
 * these tests cover the admin form wiring; the upload itself needs a real account and is not run.
 */
test.describe('Cloudflare Stream videos', () => {
    test('offers the type with a browser upload field and keeps the hidden uid out of the toggle', async ({ videos }) => {
        await videos.open();
        const row = await videos.addRow();

        await videos.chooseType(row, 'Cloudflare Stream');

        const file = row.locator('input[type="file"][data-video-fields="cloudflare_stream"]');
        await expect(file).toBeVisible();
        await expect(file).toHaveAttribute('data-cloudflare-stream-upload', '/admin/videos/cloudflare-stream/direct-upload');
        await expect(file).toHaveAttribute('data-cloudflare-stream-chunk-size', '52428800');
        await expect(row.locator('input[data-cloudflare-stream-uid]')).toHaveCount(1);
        await expect(videos.fieldGroup(row, 'url')).toBeHidden();
        await expect(videos.fieldGroup(row, 'file')).toBeHidden();

        // Switching away hides the picker but must not hide the column the hidden uid sits in.
        await videos.chooseType(row, 'External URL');
        await expect(file).toBeHidden();
        await expect(row.locator('input[name$="[url]"]')).toBeVisible();
    });

    test('refuses to save a row whose upload has not finished', async ({ page, videos }) => {
        await videos.open();
        const row = await videos.addRow();
        await videos.chooseType(row, 'Cloudflare Stream');
        await videos.save();

        await expect(page.locator('.sylius-flash-message')).toHaveCount(0);
        await page.locator('a.item[data-tab="videos"]').click();
        await expect(videos.rows()).toHaveCount(1);
        await expect(videos.rows().first()).toContainText(/upload a video to Cloudflare Stream first/i);
    });
});
