// Kiểm tra giao diện trên mobile/tablet/desktop: không tràn ngang, không lỗi JS, chụp ảnh.
// Chạy: BASE_URL=http://ezfin.test node tests/browser/ui-smoke.mjs [thư-mục-ảnh]
// Không có Chromium của Playwright? Đặt CHROMIUM_PATH tới trình duyệt Chromium/Chrome sẵn có.
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const baseUrl = process.env.BASE_URL ?? 'http://ezfin.test';
const outDir = process.argv[2] ?? 'storage/app/ui-screenshots';
const pages = ['/', '/the-tin-dung', '/the-tin-dung/so-sanh', '/lai-suat-tiet-kiem', '/tai-khoan-ngan-hang', '/cong-cu', '/cong-cu/lai-kep', '/cong-cu/lai-tien-gui', '/cong-cu/tiet-kiem-dinh-ky', '/cong-cu/khoan-vay', '/cong-cu/tra-gop', '/_ui', '/_ui/so-sanh', '/_ui/ung-dung'];
const widths = [360, 768, 1280];

mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || undefined,
    args: process.env.HOST_RESOLVER_RULES ? [`--host-resolver-rules=${process.env.HOST_RESOLVER_RULES}`] : [],
});

const failures = [];

for (const path of pages) {
    for (const width of widths) {
        const page = await browser.newPage({ viewport: { width, height: 800 } });
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        page.on('console', (message) => message.type() === 'error' && errors.push(message.text()));

        const response = await page.goto(baseUrl + path, { waitUntil: 'networkidle' });
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        const name = `${path.replace(/\W+/g, '-').replace(/^-|-$/g, '') || 'home'}-${width}`;
        await page.screenshot({ path: `${outDir}/${name}.png`, fullPage: true });

        const problems = [];
        if (!response || response.status() !== 200) problems.push(`HTTP ${response?.status()}`);
        if (overflow > 0) problems.push(`tràn ngang ${overflow}px`);
        if (errors.length) problems.push(`lỗi JS: ${errors.join(' | ')}`);

        console.log(`${problems.length ? '✗' : '✓'} ${path} @ ${width}px${problems.length ? ' — ' + problems.join('; ') : ''}`);
        if (problems.length) failures.push(name);
        await page.close();
    }
}

await browser.close();
process.exit(failures.length ? 1 : 0);
