// chart.js
const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
    const symbol = process.argv[2];
    if (!symbol) {
        console.log("Hisse sembolü girilmedi!");
        process.exit();
    }

    console.log("Başladı: " + symbol);

    const browser = await puppeteer.launch({
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1600, height: 900 });

    // 12 aylık grafik
    const url = `https://tr.tradingview.com/symbols/${symbol}/?timeframe=12M`;
    await page.goto(url, { waitUntil: 'networkidle2' });

    await new Promise(r => setTimeout(r, 15000));

    if (!fs.existsSync('charts')) {
        fs.mkdirSync('charts');
    }

    const filePath = `charts/${symbol.replace(':','_')}.png`;
    await page.screenshot({ path: filePath });

    console.log("Dosya oluşturuldu: " + filePath);
    await browser.close();
})();
