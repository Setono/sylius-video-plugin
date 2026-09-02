import { test as base, expect, type Locator, type Page } from '@playwright/test';

type Product = {
    /** Admin id of the product every test works on (the first one in the catalog). */
    id: string;
    /** Shop URL of the same product. */
    shopUrl: string;
};

type Fixtures = {
    product: Product;
    videos: VideosTab;
};

/**
 * Drives the Videos tab of the product edit page the way a merchant does: real clicks on the
 * tab, the collection buttons and the Semantic UI type dropdown.
 */
export class VideosTab {
    constructor(private readonly page: Page, private readonly productId: string) {}

    async open(): Promise<void> {
        await this.page.goto(`/admin/products/${this.productId}/edit`);
        await this.page.locator('a.item[data-tab="videos"]').click();
        await expect(this.pane()).toBeVisible();
    }

    pane(): Locator {
        return this.page.locator('div.ui.tab[data-tab="videos"]');
    }

    rows(): Locator {
        return this.pane().locator('[data-form-collection="item"]');
    }

    async addRow(): Promise<Locator> {
        const before = await this.rows().count();
        await this.pane().locator('[data-form-collection="add"]').click();
        await expect(this.rows()).toHaveCount(before + 1);

        return this.rows().nth(before);
    }

    /** Picks a type in the row's type select by its visible label ("External URL", "Embed code", "File upload"). */
    async chooseType(row: Locator, label: string): Promise<void> {
        const select = row.locator('select[name$="[type]"]');
        await select.selectOption({ label });
        await expect(select).toHaveValue(/./);
    }

    fieldGroup(row: Locator, type: string): Locator {
        return row.locator(`[data-video-fields="${type}"]`);
    }

    async save(): Promise<void> {
        await this.page.locator('#sylius_save_changes_button').click();
    }

    async removeAllAndSave(): Promise<void> {
        await this.open();
        const deletes = this.pane().locator('[data-form-collection="delete"]');
        while ((await deletes.count()) > 0) {
            await deletes.first().click();
        }
        await this.save();
        await expect(this.page.locator('.sylius-flash-message')).toContainText('successfully updated');
    }
}

export const test = base.extend<Fixtures>({
    product: async ({ page }, use) => {
        await page.goto('/admin/products/');
        const edit = page.locator('a[href$="/edit"][href*="/admin/products/"]').first();
        const href = await edit.getAttribute('href');
        const id = href?.match(/\/admin\/products\/(\d+)\/edit/)?.[1];
        if (!id) {
            throw new Error('No product found in the admin catalog; load the Sylius fixtures first.');
        }

        // The admin's "Show product in shop page" link is absolute to the channel hostname, which
        // is not the server under test; build the shop URL from the slug instead.
        await page.goto(`/admin/products/${id}/edit`);
        const slug = await page.locator('input[name$="[translations][en_US][slug]"]').inputValue();
        if (slug === '') {
            throw new Error('The product has no en_US slug.');
        }

        await use({ id, shopUrl: `/en_US/products/${slug}` });
    },
    videos: async ({ page, product }, use) => {
        const tab = new VideosTab(page, product.id);
        await use(tab);
        // Leave the product without videos for the next test.
        await tab.removeAllAndSave();
    },
});

export { expect } from '@playwright/test';
