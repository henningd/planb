const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage({ viewport: { width: 1200, height: 630 }, deviceScaleFactor: 1 });
    await page.goto('file://' + __dirname + '/og-image.html', { waitUntil: 'networkidle' });
    // Sicherstellen, dass Webfonts geladen sind, bevor der Screenshot entsteht.
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({ path: __dirname + '/../../public/og-image.png', type: 'png' });
    await browser.close();
    console.log('../../public/og-image.png erstellt');
})();
