#!/usr/bin/env node
/**
 * Erzeugt die elf Marketing-Screenshots für die /funktionen/{slug}-Seiten.
 * Loggt einmalig als max@mustermann.de ein, navigiert dann durch die App
 * und schreibt PNGs nach public/screenshots/.
 *
 * Voraussetzungen:
 *  - Dev-Server läuft auf 127.0.0.1:8000 (php artisan serve)
 *  - Vite-Build ist aktuell (npm run build)
 *  - Demo-Daten sind geseedet (php artisan db:seed --class=DemoDataSeeder)
 */

import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';

const BASE = 'http://127.0.0.1:8000';
const TEAM = 'max-mustermanns-team';
const OUT = resolve(import.meta.dirname, '..', 'public', 'screenshots');
const VIEWPORT = { width: 1600, height: 1000 };

mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
    viewport: VIEWPORT,
    deviceScaleFactor: 2, // Retina-Quality
    locale: 'de-DE',
});
const page = await context.newPage();

console.log('→ Login als max@mustermann.de');
await page.goto(`${BASE}/login`);
await page.fill('input[name="email"]', 'max@mustermann.de');
await page.fill('input[name="password"]', 'password');
await page.click('button[type="submit"]');
await page.waitForURL(url => !url.toString().includes('/login'), { timeout: 10000 });
console.log('  ✓ Login erfolgreich');

const shots = [
    { url: `/${TEAM}/compliance`,                    file: 'compliance-overview.png',  fullPage: false },
    { url: `/${TEAM}/compliance`,                    file: 'compliance-trend.png',     fullPage: true  },
    { url: `/${TEAM}/risks`,                          file: 'risks-heatmap.png',        fullPage: false, scrollY: 300 },
    { url: `/${TEAM}/risks`,                          file: 'risks-detail.png',         fullPage: false, clickRiskName: 'Ransomware' },
    { url: `/${TEAM}/lessons-learned`,                file: 'lessons-list.png',         fullPage: false },
    { url: `/${TEAM}/lessons-learned`,                file: 'lessons-detail.png',       fullPage: false, clickFirst: true },
    { url: `/${TEAM}/scenario-runs/019dcbbc-fd21-7157-b145-fef08a76a5d0`, file: 'war-room.png', fullPage: true },
    { url: `/${TEAM}/audit-log`,                      file: 'audit-log.png',            fullPage: false },
    { url: `/${TEAM}/system-settings`,                file: 'audit-export-zip.png',     fullPage: false, scrollY: 1500 },
    { url: `/${TEAM}/api-tokens`,                     file: 'monitoring-tokens.png',    fullPage: false },
    { url: `/${TEAM}/api-tokens`,                     file: 'monitoring-alerts.png',    fullPage: false, scrollY: 800 },
];

for (const shot of shots) {
    console.log(`→ ${shot.file}`);
    await page.goto(`${BASE}${shot.url}`, { waitUntil: 'networkidle' });

    // Eventuelle Klick-Aktionen (Risiko-Detail, Lesson-Detail)
    if (shot.clickRiskName) {
        try {
            await page.locator(`a:has-text("${shot.clickRiskName}")`).first().click({ timeout: 3000 });
            await page.waitForLoadState('networkidle');
        } catch (e) {
            console.log(`  ⚠ Risk-Klick fehlgeschlagen, nehme Übersicht`);
        }
    }
    if (shot.clickFirst) {
        try {
            // Erste Lesson-Karte (UUID im Pfad, nicht /create) anklicken
            await page.locator('a[href*="/lessons-learned/"]:not([href*="/create"])').first().click({ timeout: 3000 });
            await page.waitForLoadState('networkidle');
        } catch (e) {
            console.log(`  ⚠ Lesson-Klick fehlgeschlagen, nehme Übersicht`);
        }
    }

    if (shot.scrollY) {
        await page.evaluate((y) => window.scrollTo(0, y), shot.scrollY);
        await page.waitForTimeout(300);
    }

    // Cursor verstecken, sonst zeigt Screenshot zufällige Tooltip-Reste
    await page.evaluate(() => {
        const style = document.createElement('style');
        style.textContent = '*, *::before, *::after { transition: none !important; animation: none !important; }';
        document.head.appendChild(style);
    });
    await page.waitForTimeout(200);

    await page.screenshot({
        path: resolve(OUT, shot.file),
        fullPage: shot.fullPage ?? false,
        animations: 'disabled',
    });
    console.log(`  ✓ gespeichert`);
}

await browser.close();
console.log('\n✓ alle Screenshots in public/screenshots/');
