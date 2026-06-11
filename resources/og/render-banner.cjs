const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();
    // LinkedIn-Profilbanner: 1584x396, in doppelter Auflösung gerendert.
    const page = await browser.newPage({ viewport: { width: 1584, height: 396 }, deviceScaleFactor: 2 });
    await page.goto('file://' + __dirname + '/linkedin-banner.html', { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({ path: __dirname + '/linkedin-banner.png', type: 'png' });
    await browser.close();
    console.log('resources/og/linkedin-banner.png erstellt (3168x792)');
})();
