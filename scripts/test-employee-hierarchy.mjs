#!/usr/bin/env node
/**
 * Diagnose-Skript: Öffnet die Mitarbeiter-Hierarchie-Ansicht im Browser,
 * sammelt Console-Logs, prüft Cytoscape-State und macht Screenshots.
 */

import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';

const BASE = 'http://127.0.0.1:8000';
const TEAM = 'max-mustermanns-team';
const OUT = resolve(import.meta.dirname, '..', 'storage', 'app', 'hierarchy-debug');

mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
    viewport: { width: 1600, height: 1000 },
    locale: 'de-DE',
});
const page = await context.newPage();

const logs = [];
page.on('console', msg => logs.push(`[${msg.type()}] ${msg.text()}`));
page.on('pageerror', err => logs.push(`[PAGEERROR] ${err.message}`));

console.log('→ Login als max@mustermann.de');
await page.goto(`${BASE}/login`);
await page.fill('input[name="email"]', 'max@mustermann.de');
await page.fill('input[name="password"]', 'password');
await page.click('button[type="submit"]');
await page.waitForURL(url => !url.toString().includes('/login'), { timeout: 10000 });

console.log('→ Mitarbeiter-Liste öffnen');
await page.goto(`${BASE}/${TEAM}/employees`, { waitUntil: 'networkidle' });
await page.screenshot({ path: resolve(OUT, '01-list-view.png'), fullPage: false });

console.log('→ Tab "Hierarchie" anklicken');
const hierarchyButton = page.getByRole('tab', { name: /Hierarchie/ });
await hierarchyButton.click();
await page.waitForTimeout(1500);

await page.screenshot({ path: resolve(OUT, '02-after-tab-click.png'), fullPage: false });

const initialState = await page.evaluate(() => {
    const canvas = document.getElementById('employee-hierarchy-canvas');
    if (!canvas) return { found: false };
    const rect = canvas.getBoundingClientRect();
    const cy = canvas._cyreg?.cy || (canvas.firstChild && canvas.firstChild._cyreg?.cy);
    const cyData = window.PlanB?._lastEmployeeHierarchy || null;
    return {
        found: true,
        canvasWidth: rect.width,
        canvasHeight: rect.height,
        canvasInnerHTML: canvas.innerHTML.substring(0, 200),
        canvasChildren: canvas.children.length,
    };
});
console.log('Canvas state nach Tab-Klick:', JSON.stringify(initialState, null, 2));

console.log('→ "Alle Abteilungen" Dropdown öffnen und neu auswählen');
const dept = page.locator('select').filter({ hasText: 'Alle Abteilungen' });
await dept.selectOption({ label: 'Verwaltung' });
await page.waitForTimeout(800);
await page.screenshot({ path: resolve(OUT, '03-after-filter-verwaltung.png'), fullPage: false });

await dept.selectOption('');
await page.waitForTimeout(800);
await page.screenshot({ path: resolve(OUT, '04-after-filter-all.png'), fullPage: false });

const finalState = await page.evaluate(() => {
    const canvas = document.getElementById('employee-hierarchy-canvas');
    if (!canvas) return { found: false };
    const rect = canvas.getBoundingClientRect();
    return {
        found: true,
        canvasWidth: rect.width,
        canvasHeight: rect.height,
        canvasChildren: canvas.children.length,
        // Cytoscape rendert in <canvas>-Elementen
        innerCanvases: canvas.querySelectorAll('canvas').length,
    };
});
console.log('Canvas state nach Filter-Reset:', JSON.stringify(finalState, null, 2));

console.log('\n=== Browser Console Logs ===');
for (const l of logs) console.log(l);

await browser.close();
console.log(`\nScreenshots in ${OUT}`);
