const stockState = {
    symbol: (window.BORSA_STOCK_SYMBOL || 'BIST:ASELS').trim(),
    payload: null,
    chart: null,
    candleSeries: null,
    volumeSeries: null,
    resizeObserver: null,
};

const stockNumberFormatter = new Intl.NumberFormat('tr-TR');
const stockCompactFormatter = new Intl.NumberFormat('tr-TR', { notation: 'compact', maximumFractionDigits: 1 });
const stockCurrencyFormatter = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatNumber(value, digits = 2) {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '-';
    }

    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(value);
}

function formatPrice(value) {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '-';
    }

    return `₺${stockCurrencyFormatter.format(value)}`;
}

function formatCompact(value) {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '-';
    }

    return stockCompactFormatter.format(value);
}

function formatPercent(value, digits = 2) {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '-';
    }

    return `${formatNumber(value, digits)}%`;
}

function formatSignedPercent(value, digits = 2) {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '-';
    }

    const sign = value > 0 ? '+' : '';
    return `${sign}${formatNumber(value, digits)}%`;
}

function formatScore(value) {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '-';
    }

    return formatNumber(value, 1);
}

function getLastNumericValue(series) {
    if (!Array.isArray(series)) {
        return null;
    }

    for (let index = series.length - 1; index >= 0; index -= 1) {
        const value = series[index];
        if (typeof value === 'number' && Number.isFinite(value)) {
            return value;
        }
    }

    return null;
}

function infoTile(label, value, caption = '') {
    return `
        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 mb-2">${escapeHtml(label)}</p>
            <p class="text-lg font-semibold">${escapeHtml(value)}</p>
            ${caption ? `<p class="mt-2 text-sm text-slate-400">${escapeHtml(caption)}</p>` : ''}
        </div>
    `;
}

function insightCard(title, value, description, accentClass) {
    return `
        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
            <p class="text-sm ${accentClass} mb-2">${escapeHtml(title)}</p>
            <p class="text-2xl font-bold">${escapeHtml(value)}</p>
            <p class="mt-3 text-sm text-slate-300 leading-6">${escapeHtml(description)}</p>
        </div>
    `;
}

function commentCard(comment) {
    return `
        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 text-sm leading-7 text-slate-200">
            ${escapeHtml(comment)}
        </div>
    `;
}

function scoreTone(score) {
    if (score >= 80) {
        return 'text-emerald-300 bg-emerald-400/10 border-emerald-400/20';
    }

    if (score >= 60) {
        return 'text-cyan-300 bg-cyan-400/10 border-cyan-400/20';
    }

    if (score >= 40) {
        return 'text-amber-300 bg-amber-400/10 border-amber-400/20';
    }

    return 'text-rose-300 bg-rose-400/10 border-rose-400/20';
}

function setLoadingState() {
    document.getElementById('stockTitle').textContent = 'Sembol yükleniyor';
    document.getElementById('stockSubtitle').textContent = 'OHLCV, göstergeler, scanner sonuçları ve yorumlar hazırlanıyor.';
    document.getElementById('heroMetrics').innerHTML = '<div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 text-slate-400">Özet hazırlanıyor...</div>';
    document.getElementById('stockSummaryCards').innerHTML = '<div class="md:col-span-2 xl:col-span-4 rounded-[28px] border border-white/10 bg-white/5 p-6 text-slate-400">Detay verileri yükleniyor...</div>';
    document.getElementById('scannerInsights').innerHTML = '<div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 text-slate-400">Scanner sonuçları hazırlanıyor...</div>';
    document.getElementById('commentaryList').innerHTML = '<div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 text-slate-400">Yorumlar hazırlanıyor...</div>';
    document.getElementById('indicatorGrid').innerHTML = '<div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 text-slate-400">Göstergeler hazırlanıyor...</div>';
    document.getElementById('snapshotGrid').innerHTML = '<div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 text-slate-400">Snapshot hazırlanıyor...</div>';
    document.getElementById('chartMeta').textContent = 'Grafik hazırlanıyor';
    document.getElementById('stockLastUpdatedBadge').textContent = 'Canlı veri bekleniyor';
}

function showError(error) {
    const message = error instanceof Error ? error.message : 'Bilinmeyen hata';
    document.getElementById('stockTitle').textContent = stockState.symbol || 'Hisse detayı';
    document.getElementById('stockSubtitle').textContent = 'Veri alınamadı. API bağlantısını ve ortam ayarlarını kontrol edin.';
    document.getElementById('heroMetrics').innerHTML = `<div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-5 text-rose-100">${escapeHtml(message)}</div>`;
    document.getElementById('stockSummaryCards').innerHTML = `<div class="md:col-span-2 xl:col-span-4 rounded-[28px] border border-rose-400/20 bg-rose-400/10 p-6 text-rose-100">${escapeHtml(message)}</div>`;
    document.getElementById('scannerInsights').innerHTML = '<div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-5 text-rose-100">Scanner özeti alınamadı.</div>';
    document.getElementById('commentaryList').innerHTML = '<div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-5 text-rose-100">Yorumlar alınamadı.</div>';
    document.getElementById('indicatorGrid').innerHTML = '<div class="rounded-2xl border border-rose-400/20 bg-rose-400/10 p-4 text-rose-100">Göstergeler alınamadı.</div>';
    document.getElementById('snapshotGrid').innerHTML = '<div class="rounded-2xl border border-rose-400/20 bg-rose-400/10 p-4 text-rose-100">Snapshot alınamadı.</div>';
    document.getElementById('chartMeta').textContent = 'Grafik yüklenemedi';
}

function renderHero(payload) {
    const snapshot = payload.snapshot || {};
    const candles = Array.isArray(payload.candles) ? payload.candles : [];
    const first = candles[0]?.close;
    const last = candles[candles.length - 1]?.close;
    const trend = (typeof first === 'number' && Number.isFinite(first) && typeof last === 'number' && Number.isFinite(last) && first !== 0)
        ? ((last - first) / first) * 100
        : null;

    document.getElementById('stockTitle').textContent = snapshot.symbol || stockState.symbol;
    document.getElementById('stockSubtitle').textContent = snapshot.name || 'Tek hisse detay ekranı';
    document.getElementById('symbolInput').value = snapshot.symbol || stockState.symbol;
    document.getElementById('heroMetrics').innerHTML = [
        `
            <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
                <p class="text-sm text-slate-400 mb-2">Son fiyat</p>
                <p class="text-3xl font-bold">${escapeHtml(formatPrice(snapshot.close || null))}</p>
                <p class="mt-2 text-sm ${trend !== null && trend >= 0 ? 'text-emerald-300' : 'text-rose-300'}">${escapeHtml(formatSignedPercent(trend, 2))}</p>
            </div>
        `,
        `
            <div class="rounded-3xl border ${scoreTone(payload.scores?.overall || 0)} p-5">
                <p class="text-sm mb-2">Genel skor</p>
                <p class="text-3xl font-bold">${escapeHtml(formatScore(payload.scores?.overall || 0))}</p>
                <p class="mt-2 text-sm">Teknik ${escapeHtml(formatScore(payload.scores?.technical || 0))} · Hacim ${escapeHtml(formatScore(payload.scores?.volume || 0))}</p>
            </div>
        `,
    ].join('');
}

function renderSummary(payload) {
    const snapshot = payload.snapshot || {};
    const candles = Array.isArray(payload.candles) ? payload.candles : [];
    const lastCandle = candles[candles.length - 1] || {};

    document.getElementById('stockSummaryCards').innerHTML = [
        infoTile('Son kapanış', formatPrice(snapshot.close || null), 'Snapshot close değeri'),
        infoTile('Günlük aralık', `${formatPrice(snapshot.low || null)} - ${formatPrice(snapshot.high || null)}`, 'Gün içi low/high'),
        infoTile('İşlem hacmi', formatCompact(snapshot.volume || null), 'Snapshot volume'),
        infoTile('Son candle hacmi', formatCompact(lastCandle.volume || null), 'Grafikteki en güncel bar'),
    ].join('');
}

function renderSnapshot(payload) {
    const snapshot = payload.snapshot || {};
    document.getElementById('snapshotGrid').innerHTML = [
        infoTile('Sembol', snapshot.symbol || '-'),
        infoTile('Şirket adı', snapshot.name || '-'),
        infoTile('İşlem değeri', `₺${formatCompact(snapshot.value_traded || null)}`),
        infoTile('Piyasa değeri', `₺${formatCompact(snapshot.market_cap || null)}`),
        infoTile('Serbest dolaşım', formatCompact(snapshot.free_float_shares || null)),
        infoTile('Hacim', formatCompact(snapshot.volume || null)),
    ].join('');
}

function renderIndicators(payload) {
    const indicators = payload.indicators || {};
    const candles = Array.isArray(payload.candles) ? payload.candles : [];
    const first = candles[0]?.close;
    const last = candles[candles.length - 1]?.close;
    const yearChange = (typeof first === 'number' && typeof last === 'number' && first !== 0) ? ((last - first) / first) * 100 : null;

    document.getElementById('indicatorGrid').innerHTML = [
        infoTile('RSI 14', formatNumber(getLastNumericValue(indicators.rsi_14), 2)),
        infoTile('EMA 20', formatPrice(getLastNumericValue(indicators.ema_20))),
        infoTile('SMA 20', formatPrice(getLastNumericValue(indicators.sma_20))),
        infoTile('ATR 14', formatNumber(getLastNumericValue(indicators.atr_14), 3)),
        infoTile('MACD', formatNumber(getLastNumericValue(indicators.macd?.macd || indicators.macd), 3)),
        infoTile('1Y trend', formatSignedPercent(yearChange, 2)),
    ].join('');
}

function renderScanners(payload) {
    const scanners = payload.scanners || {};
    document.getElementById('scannerInsights').innerHTML = [
        insightCard('Dip yakınlığı', formatPercent(scanners.dip_recovery?.metrics?.distance_to_year_low_pct || null, 2), 'Yıllık dip seviyesine uzaklık.', 'text-amber-400'),
        insightCard('Hacim oranı', `${formatNumber(scanners.volume_burst?.metrics?.volume_ratio || null, 2)}x`, 'Son dönemdeki hacim patlaması gücü.', 'text-cyan-400'),
        insightCard('Low float', formatCompact(payload.snapshot?.free_float_shares || null), 'Serbest dolaşım hissesi görünümü.', 'text-fuchsia-400'),
        insightCard('Pattern skoru', scanners.pattern?.signal ? 'Aktif' : 'Nötr', 'Formasyon tarayıcısının mevcut sinyali.', 'text-emerald-400'),
    ].join('');
}

function renderComments(payload) {
    const comments = Array.isArray(payload.comments) && payload.comments.length
        ? payload.comments
        : ['Henüz yorum üretilmedi.'];

    document.getElementById('commentaryList').innerHTML = comments.map(commentCard).join('');
}

function setupChart(payload) {
    const container = document.getElementById('ohlcvChart');
    const candles = Array.isArray(payload.candles) ? payload.candles : [];

    if (!candles.length || !window.LightweightCharts) {
        container.innerHTML = '<div class="flex h-full items-center justify-center rounded-2xl border border-white/10 bg-slate-950/60 text-sm text-slate-400">Grafik verisi veya chart kütüphanesi bulunamadı.</div>';
        return;
    }

    const chartData = candles.map((candle) => ({
        time: candle.time,
        open: Number(candle.open),
        high: Number(candle.high),
        low: Number(candle.low),
        close: Number(candle.close),
    })).filter((candle) => [candle.open, candle.high, candle.low, candle.close].every(Number.isFinite));

    const volumeData = candles.map((candle) => {
        const open = Number(candle.open);
        const close = Number(candle.close);
        const volume = Number(candle.volume);

        return {
            time: candle.time,
            value: Number.isFinite(volume) ? volume : 0,
            color: close >= open ? 'rgba(52, 211, 153, 0.45)' : 'rgba(251, 113, 133, 0.45)',
        };
    });

    if (!stockState.chart) {
        stockState.chart = window.LightweightCharts.createChart(container, {
            autoSize: true,
            layout: {
                background: { color: '#020617' },
                textColor: '#cbd5e1',
            },
            grid: {
                vertLines: { color: 'rgba(148, 163, 184, 0.08)' },
                horzLines: { color: 'rgba(148, 163, 184, 0.08)' },
            },
            rightPriceScale: {
                borderColor: 'rgba(148, 163, 184, 0.18)',
            },
            timeScale: {
                borderColor: 'rgba(148, 163, 184, 0.18)',
            },
            crosshair: {
                mode: window.LightweightCharts.CrosshairMode.Normal,
            },
        });

        stockState.candleSeries = stockState.chart.addCandlestickSeries({
            upColor: '#34d399',
            borderUpColor: '#34d399',
            wickUpColor: '#34d399',
            downColor: '#fb7185',
            borderDownColor: '#fb7185',
            wickDownColor: '#fb7185',
        });

        stockState.volumeSeries = stockState.chart.addHistogramSeries({
            priceFormat: { type: 'volume' },
            priceScaleId: '',
        });

        stockState.volumeSeries.priceScale().applyOptions({
            scaleMargins: {
                top: 0.82,
                bottom: 0,
            },
        });

        stockState.candleSeries.priceScale().applyOptions({
            scaleMargins: {
                top: 0.08,
                bottom: 0.24,
            },
        });

        if (typeof ResizeObserver === 'function') {
            stockState.resizeObserver?.disconnect();
            stockState.resizeObserver = new ResizeObserver(() => {
                stockState.chart?.timeScale().fitContent();
            });
            stockState.resizeObserver.observe(container);
        }
    }

    stockState.candleSeries.setData(chartData);
    stockState.volumeSeries.setData(volumeData);
    stockState.chart.timeScale().fitContent();
    document.getElementById('chartMeta').textContent = `${stockNumberFormatter.format(chartData.length)} günlük candle`;
}

function updateTimestamp() {
    document.getElementById('stockLastUpdatedBadge').textContent = `Son güncelleme ${new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}`;
}

async function loadStock() {
    setLoadingState();

    const response = await fetch(`${window.BORSA_API_URL}?symbol=${encodeURIComponent(stockState.symbol)}`, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`API hatası: ${response.status}`);
    }

    const payload = await response.json();
    if (!payload || !payload.snapshot) {
        throw new Error('Beklenen hisse verisi alınamadı.');
    }

    stockState.payload = payload;
    renderHero(payload);
    renderSummary(payload);
    renderSnapshot(payload);
    renderIndicators(payload);
    renderScanners(payload);
    renderComments(payload);
    setupChart(payload);
    updateTimestamp();

    const nextUrl = `${window.location.pathname}?symbol=${encodeURIComponent(payload.snapshot.symbol || stockState.symbol)}`;
    window.history.replaceState({}, '', nextUrl);
}

document.getElementById('symbolForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const nextSymbol = document.getElementById('symbolInput').value.trim();
    if (!nextSymbol) {
        return;
    }

    stockState.symbol = nextSymbol;
    loadStock().catch(showError);
});

document.getElementById('reloadStockButton').addEventListener('click', () => {
    loadStock().catch(showError);
});

loadStock().catch(showError);
setInterval(() => loadStock().catch(showError), 60000);
