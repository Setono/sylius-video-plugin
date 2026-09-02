import { defineConfig } from '@playwright/test';

const baseURL = process.env.BASE_URL || 'http://127.0.0.1:8000';

export default defineConfig({
    testDir: '../e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL,
        ignoreHTTPSErrors: true,
        screenshot: 'only-on-failure',
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'setup',
            testMatch: /.*\.setup\.ts/,
        },
        {
            name: 'chromium',
            use: {
                browserName: 'chromium',
                storageState: '../e2e/.auth/admin.json',
            },
            dependencies: ['setup'],
        },
    ],
    webServer: process.env.CI ? undefined : {
        command: 'symfony serve --no-tls --port=8000',
        url: `${baseURL}/admin/login`,
        reuseExistingServer: true,
        timeout: 120000,
    },
});
