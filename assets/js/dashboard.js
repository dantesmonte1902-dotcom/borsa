const state = {
    rawData: [],
    filteredData: [],
    selectedSymbol: null,
    query: '',
    activeFilter: 'all',
};

const MARKET_LIMIT = 12;
const CHART_WINDOW = 24;

const numberFormatter = new Intl.NumberFormat('tr-TR');
const compactFormatter = new Intl.NumberFormat('tr-TR', { notation: 'compact', maximumFractionDigits: 1 });
const currencyFormatter = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function buildStockDetailUrl(symbol) {
    return `./stock.php?symbol=${encodeURIComponent(symbol || '')}`;
}

const quickFilters = [
    {
        id: 'all',
        label: 'Tümü',
        accentClass: 'from-slate-400/30 to-slate-500/10 text-slate-100 border-white/10',
        predicate: () => true,
    },
    {
        id: 'strong',
        label: 'Güçlü Skor',
        accentClass: 'from-emerald-400/30 to-emerald-500/10 text-emerald-100 border-emerald-400/20',
        predicate: (item) => (item.scores?.overall || 0) >= 70,
    },
    {
        id: 'volume',
        label: 'Hacim Patlaması',
        accentClass: 'from-cyan-400/30 to-cyan-500/10 text-cyan-100 border-cyan-400/20',
        predicate: (item) => (item.scanners?.volume_burst?.metrics?.volume_ratio || 0) >= 1.5,
    },
    {
        id: 'dip',
        label: 'Dip Yakını',
        accentClass: 'from-amber-400/30 to-amber-500/10 text-amber-100 border-amber-400/20',
        predicate: (item) => (item.scanners?.dip_recovery?.metrics?.distance_to_year_low_pct || 999) <= 12,
    },
    {
        id: 'oversold',
        label: 'RSI Düşük',
        accentClass: 'from-fuchsia-400/30 to-fuchsia-500/10 text-fuchsia-100 border-fuchsia-400/20',
        predicate: (item) => {
            const rsi = getLastNumericValue(item.indicators?.rsi_14);
            return typeof rsi === 'number' && rsi < 35;
        },
    },
];

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

function formatNumber(value, digits = 2) {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '-';
    }

    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(value);
}

function formatCompact(value) {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '-';
    }

    return compactFormatter.format(value);
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

function formatPrice(value) {
    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '-';
    }

    return `₺${currencyFormatter.format(value)}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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

function pulseMeta(avgScore) {
    if (avgScore >= 75) {
        return { value: 'Güçlü Momentum', text: 'Genel skorlar güçlü; piyasa görünümü pozitif ilerliyor.' };
    }

    if (avgScore >= 55) {
        return { value: 'Dengeli İzleme', text: 'Piyasada seçici fırsatlar var; detay kartından hisseleri kıyaslayın.' };
    }

    return { value: 'Temkinli Bölge', text: 'Sinyaller zayıf; hacim ve dip dönüşü metriklerine odaklanın.' };
}

function getFilterDefinition(filterId) {
    return quickFilters.find((filter) => filter.id === filterId) || quickFilters[0];
}

function matchesQuery(item, query) {
    if (!query) {
        return true;
    }

    const normalizedQuery = query.toLocaleLowerCase('tr-TR');
    const symbol = String(item.snapshot?.symbol || '').toLocaleLowerCase('tr-TR');
    const name = String(item.snapshot?.name || '').toLocaleLowerCase('tr-TR');
    const comments = (item.comments || []).join(' ').toLocaleLowerCase('tr-TR');

    return symbol.includes(normalizedQuery) || name.includes(normalizedQuery) || comments.includes(normalizedQuery);
}

function applyActiveFilters(data) {
    const predicate = getFilterDefinition(state.activeFilter).predicate;

    return data.filter((item) => matchesQuery(item, state.query) && predicate(item));
}

function deriveLeaders(data) {
    const volumeLeader = [...data].sort((left, right) => ((right.scanners?.volume_burst?.metrics?.volume_ratio || 0) - (left.scanners?.volume_burst?.metrics?.volume_ratio || 0)))[0] || null;
    const dipLeader = [...data].sort((left, right) => ((left.scanners?.dip_recovery?.metrics?.distance_to_year_low_pct || Infinity) - (right.scanners?.dip_recovery?.metrics?.distance_to_year_low_pct || Infinity)))[0] || null;
    const lowFloatLeader = [...data].sort((left, right) => ((left.snapshot?.free_float_shares || Infinity) - (right.snapshot?.free_float_shares || Infinity)))[0] || null;

    return {
        best: data[0] || null,
        volumeLeader,
        dipLeader,
        lowFloatLeader,
    };
}

function summaryCard(title, value, caption) {
    return `
        <div class="rounded-[28px] border border-white/10 bg-white/5 p-5 sm:p-6 backdrop-blur-xl">
            <p class="text-sm text-slate-400 mb-3">${escapeHtml(title)}</p>
            <p class="text-3xl font-bold tracking-tight">${escapeHtml(value)}</p>
            <p class="mt-3 text-sm text-slate-300">${escapeHtml(caption)}</p>
        </div>
    `;
}

function renderSummary(data) {
    const avgOverall = data.length ? data.reduce((sum, item) => sum + (item.scores?.overall || 0), 0) / data.length : 0;
    const avgTechnical = data.length ? data.reduce((sum, item) => sum + (item.scores?.technical || 0), 0) / data.length : 0;
    const positiveCount = data.filter((item) => (item.scores?.overall || 0) >= 70).length;
    const totalVolume = data.reduce((sum, item) => sum + (item.snapshot?.value_traded || 0), 0);

    document.getElementById('summaryCards').innerHTML = [
        summaryCard('Ortalama Genel Skor', formatScore(avgOverall), 'Piyasadaki genel kalite görünümü'),
        summaryCard('Ortalama Teknik Güç', formatScore(avgTechnical), 'Teknik göstergelerin ortalama gücü'),
        summaryCard('Güçlü Aday Sayısı', numberFormatter.format(positiveCount), '70+ skorlu hisseler hızlı fırsat listesi'),
        summaryCard('Toplam İşlem Değeri', `₺${formatCompact(totalVolume)}`, 'Ekrandaki hisselerin toplam değeri'),
    ].join('');

    const pulse = pulseMeta(avgOverall);
    document.getElementById('marketPulseValue').textContent = pulse.value;
    document.getElementById('marketPulseText').textContent = pulse.text;
}

function renderQuickFilters(data) {
    document.getElementById('quickFilters').innerHTML = quickFilters.map((filter) => {
        const isActive = state.activeFilter === filter.id;
        const count = data.filter(filter.predicate).length;
        const activeClass = isActive
            ? 'ring-2 ring-white/20 shadow-lg shadow-black/20 -translate-y-0.5'
            : 'opacity-85 hover:opacity-100';

        return `
            <button
                type="button"
                data-filter="${escapeHtml(filter.id)}"
                class="shrink-0 rounded-2xl border bg-gradient-to-br px-4 py-3 text-left transition ${filter.accentClass} ${activeClass}"
            >
                <span class="block text-xs uppercase tracking-[0.2em] text-white/70">Hızlı filtre</span>
                <span class="mt-1 block text-sm font-semibold">${escapeHtml(filter.label)}</span>
                <span class="mt-2 block text-xs text-white/70">${numberFormatter.format(count)} sembol</span>
            </button>
        `;
    }).join('');
}

function leaderCard(title, label, item, metric, accentClass) {
    if (!item) {
        return `
            <div class="rounded-3xl border border-white/10 bg-slate-950/50 p-5">
                <p class="text-sm ${accentClass} mb-2">${escapeHtml(title)}</p>
                <p class="text-xl font-semibold">Veri yok</p>
            </div>
        `;
    }

    const active = state.selectedSymbol === item.snapshot?.symbol ? 'ring-2 ring-emerald-400/50' : '';
    const comments = (item.comments || []).slice(0, 1).join(' ') || 'Yorum bekleniyor';

    return `
        <button data-symbol="${escapeHtml(item.snapshot?.symbol || '')}" class="leader-card text-left rounded-3xl border border-white/10 bg-slate-950/50 p-5 transition hover:border-emerald-400/40 hover:bg-slate-950/80 ${active}">
            <p class="text-sm ${accentClass} mb-2">${escapeHtml(title)}</p>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-2xl font-bold">${escapeHtml(item.snapshot?.symbol || '-')}</p>
                    <p class="text-sm text-slate-400 mt-1">${escapeHtml(label)}</p>
                </div>
                <span class="rounded-full border px-3 py-1 text-xs font-medium ${scoreTone(item.scores?.overall || 0)}">${formatScore(item.scores?.overall || 0)}</span>
            </div>
            <p class="mt-4 text-sm text-slate-300">${escapeHtml(metric)}</p>
            <p class="mt-4 text-sm text-slate-400 line-clamp-2">${escapeHtml(comments)}</p>
        </button>
    `;
}

function renderLeaders(data) {
    const { best, volumeLeader, dipLeader, lowFloatLeader } = deriveLeaders(data);

    document.getElementById('leaderboard').innerHTML = [
        leaderCard('En Güçlü Hisse', 'Genel skorda lider', best, `Fiyat ${formatPrice(best?.snapshot?.close || null)} · Teknik ${formatScore(best?.scores?.technical || null)}`, 'text-emerald-400'),
        leaderCard('Hacim Patlaması', 'Hacim oranında öne çıkan', volumeLeader, `Hacim oranı ${formatNumber(volumeLeader?.scanners?.volume_burst?.metrics?.volume_ratio || null, 2)}x`, 'text-cyan-400'),
        leaderCard('Dipten Toparlanma', 'Yıllık dip yakınında', dipLeader, `Yıllık dip uzaklığı ${formatPercent(dipLeader?.scanners?.dip_recovery?.metrics?.distance_to_year_low_pct || null, 2)}`, 'text-amber-400'),
        leaderCard('Low Float Adayı', 'Daha düşük serbest dolaşım', lowFloatLeader, `Free float ${formatCompact(lowFloatLeader?.snapshot?.free_float_shares || null)}`, 'text-fuchsia-400'),
    ].join('');
}

function infoTile(label, value) {
    return `
        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 mb-2">${escapeHtml(label)}</p>
            <p class="text-lg font-semibold">${escapeHtml(value)}</p>
        </div>
    `;
}

function buildMiniChart(candles) {
    const points = Array.isArray(candles)
        ? candles.slice(-CHART_WINDOW).map((candle) => Number(candle?.close)).filter((value) => Number.isFinite(value))
        : [];

    if (points.length < 2) {
        return null;
    }

    const width = 320;
    const height = 120;
    const padding = 10;
    const min = Math.min(...points);
    const max = Math.max(...points);
    const range = max - min || 1;
    const xStep = (width - (padding * 2)) / Math.max(points.length - 1, 1);

    const coordinates = points.map((value, index) => {
        const x = padding + (index * xStep);
        const y = height - padding - (((value - min) / range) * (height - (padding * 2)));

        return [Number(x.toFixed(2)), Number(y.toFixed(2))];
    });

    const linePath = coordinates.map(([x, y], index) => `${index === 0 ? 'M' : 'L'} ${x} ${y}`).join(' ');
    const areaPath = `${linePath} L ${coordinates[coordinates.length - 1][0]} ${height - padding} L ${coordinates[0][0]} ${height - padding} Z`;
    const first = points[0];
    const last = points[points.length - 1];
    const change = first ? ((last - first) / first) * 100 : 0;

    return {
        areaPath,
        linePath,
        high: max,
        low: min,
        last,
        change,
        tone: change >= 0 ? 'emerald' : 'rose',
    };
}

function renderMiniChart(item) {
    const chart = buildMiniChart(item.candles || []);

    if (!chart) {
        return `
            <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 text-sm text-slate-400">
                Mini grafik için yeterli candle verisi yok.
            </div>
        `;
    }

    const isPositive = chart.tone === 'emerald';
    const strokeColor = isPositive ? '#34d399' : '#fb7185';
    const gradientColor = isPositive ? '52, 211, 153' : '251, 113, 133';
    const changeClass = isPositive ? 'text-emerald-300' : 'text-rose-300';

    return `
        <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Mini grafik</p>
                    <h3 class="mt-2 text-lg font-semibold">Son ${CHART_WINDOW} gün görünümü</h3>
                </div>
                <div class="text-right">
                    <p class="text-sm ${changeClass}">${formatSignedPercent(chart.change, 2)}</p>
                    <p class="mt-1 text-xs text-slate-500">Son kapanış ${formatPrice(chart.last)}</p>
                </div>
            </div>
            <div class="mt-4 overflow-hidden rounded-[24px] border border-white/5 bg-gradient-to-br from-white/5 to-white/[0.02] p-3">
                <svg viewBox="0 0 320 120" class="h-32 w-full" role="img" aria-label="Fiyat mini grafiği">
                    <defs>
                        <linearGradient id="mini-chart-fill" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="rgba(${gradientColor},0.38)"></stop>
                            <stop offset="100%" stop-color="rgba(${gradientColor},0.02)"></stop>
                        </linearGradient>
                    </defs>
                    <path d="${chart.areaPath}" fill="url(#mini-chart-fill)"></path>
                    <path d="${chart.linePath}" fill="none" stroke="${strokeColor}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    ${infoTile('Trend', formatSignedPercent(chart.change, 2))}
                    ${infoTile('Periyot Zirvesi', formatPrice(chart.high))}
                    ${infoTile('Periyot Dibi', formatPrice(chart.low))}
                </div>
            </div>
        </div>
    `;
}

function renderDetail(item) {
    const detailPanel = document.getElementById('detailPanel');

    if (!item) {
        detailPanel.innerHTML = '<p class="text-slate-400">Detay görmek için bir hisse seçin.</p>';
        return;
    }

    const rsi = getLastNumericValue(item.indicators?.rsi_14);
    const ema20 = getLastNumericValue(item.indicators?.ema_20);
    const sma50 = getLastNumericValue(item.indicators?.sma_50);
    const atr = getLastNumericValue(item.indicators?.atr_14);
    const macdValue = getLastNumericValue(item.indicators?.macd?.macd || item.indicators?.macd);
    const latestComment = (item.comments || []).join(' ') || 'Henüz yorum üretilmedi.';

    detailPanel.innerHTML = `
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.25em] text-emerald-400">Seçili hisse</p>
                    <h2 class="mt-2 text-3xl font-black tracking-tight">${escapeHtml(item.snapshot?.symbol || '-')}</h2>
                    <p class="mt-2 text-slate-400">${escapeHtml(item.snapshot?.name || item.snapshot?.symbol || '-')}</p>
                </div>
                <div class="rounded-3xl border px-4 py-3 ${scoreTone(item.scores?.overall || 0)}">
                    <p class="text-xs uppercase tracking-[0.2em]">Genel skor</p>
                    <p class="text-3xl font-bold mt-1">${formatScore(item.scores?.overall || 0)}</p>
                </div>
            </div>

            ${renderMiniChart(item)}

            <div class="grid gap-3 sm:grid-cols-2">
                ${infoTile('Son Fiyat', formatPrice(item.snapshot?.close || null))}
                ${infoTile('İşlem Hacmi', formatCompact(item.snapshot?.volume || null))}
                ${infoTile('Günlük Aralık', `${formatPrice(item.snapshot?.low || null)} - ${formatPrice(item.snapshot?.high || null)}`)}
                ${infoTile('İşlem Değeri', `₺${formatCompact(item.snapshot?.value_traded || null)}`)}
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="${escapeHtml(buildStockDetailUrl(item.snapshot?.symbol || ''))}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-400 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300">
                    Tam analiz sayfasını aç
                </a>
                <span class="inline-flex items-center rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-300">
                    Candle chart ve detaylı teknik görünüm ayrı sayfada hazır.
                </span>
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold">Skor dağılımı</h3>
                    <span class="text-sm text-slate-400">Hızlı kalite kontrolü</span>
                </div>
                <div class="space-y-3">
                    ${['overall', 'technical', 'volume'].map((key) => {
                        const labels = { overall: 'Genel', technical: 'Teknik', volume: 'Hacim' };
                        const value = item.scores?.[key] || 0;
                        return `
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="text-slate-300">${labels[key]}</span>
                                    <span class="text-slate-400">${formatScore(value)}</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-cyan-400" style="width:${Math.max(0, Math.min(100, value))}%"></div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-3">Teknik göstergeler</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    ${infoTile('RSI 14', formatNumber(rsi, 2))}
                    ${infoTile('MACD', formatNumber(macdValue, 3))}
                    ${infoTile('EMA 20', formatPrice(ema20))}
                    ${infoTile('SMA 50', formatPrice(sma50))}
                    ${infoTile('ATR 14', formatNumber(atr, 3))}
                    ${infoTile('Free Float', formatCompact(item.snapshot?.free_float_shares || null))}
                </div>
            </div>

            <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
                <p class="text-sm uppercase tracking-[0.2em] text-slate-500 mb-2">Akıllı yorum</p>
                <p class="text-sm leading-7 text-slate-200">${escapeHtml(latestComment)}</p>
            </div>
        </div>
    `;
}

function insightCard(title, value, description, accentClass) {
    return `
        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm ${accentClass} mb-2">${escapeHtml(title)}</p>
                    <p class="text-2xl font-bold">${escapeHtml(value)}</p>
                </div>
            </div>
            <p class="mt-3 text-sm text-slate-300 leading-6">${escapeHtml(description)}</p>
        </div>
    `;
}

function renderInsights(data) {
    const strongCount = data.filter((item) => (item.scores?.overall || 0) >= 70).length;
    const oversoldCount = data.filter((item) => {
        const rsi = getLastNumericValue(item.indicators?.rsi_14);
        return typeof rsi === 'number' && rsi < 35;
    }).length;
    const highVolumeCount = data.filter((item) => (item.scanners?.volume_burst?.metrics?.volume_ratio || 0) >= 1.5).length;
    const nearDipCount = data.filter((item) => (item.scanners?.dip_recovery?.metrics?.distance_to_year_low_pct || 999) <= 12).length;

    document.getElementById('insightGrid').innerHTML = [
        insightCard('Yüksek skor', numberFormatter.format(strongCount), 'Genel skoru 70 ve üzeri olan güçlü hisseler.', 'text-emerald-400'),
        insightCard('RSI düşük bölge', numberFormatter.format(oversoldCount), 'RSI 35 altına sarkan ve tepki potansiyeli taşıyan hisseler.', 'text-amber-400'),
        insightCard('Hacim hızlananlar', numberFormatter.format(highVolumeCount), 'Hacim oranı 1.5x ve üzeri olan dikkat çekici semboller.', 'text-cyan-400'),
        insightCard('Yıllık dibe yakın', numberFormatter.format(nearDipCount), 'Yıllık dip seviyesine görece yakın kalan adaylar.', 'text-fuchsia-400'),
    ].join('');
}

function tableRow(item) {
    const rsi = getLastNumericValue(item.indicators?.rsi_14);
    const active = state.selectedSymbol === item.snapshot?.symbol ? 'bg-emerald-400/10' : 'bg-transparent';
    const summary = (item.comments || []).slice(0, 1).join(' ') || 'Yorum bulunamadı';

    return `
        <tr class="cursor-pointer border-t border-white/10 ${active} transition hover:bg-white/5" data-symbol="${escapeHtml(item.snapshot?.symbol || '')}">
            <td class="px-4 py-4 align-top">
                <div class="font-semibold">${escapeHtml(item.snapshot?.symbol || '-')}</div>
                <div class="text-xs text-slate-500 mt-1">${escapeHtml(item.snapshot?.name || '-')}</div>
            </td>
            <td class="px-4 py-4 align-top">${formatPrice(item.snapshot?.close || null)}</td>
            <td class="px-4 py-4 align-top">
                <span class="rounded-full border px-3 py-1 text-xs font-medium ${scoreTone(item.scores?.overall || 0)}">${formatScore(item.scores?.overall || 0)}</span>
            </td>
            <td class="px-4 py-4 align-top">${formatNumber(rsi, 2)}</td>
            <td class="px-4 py-4 align-top">${formatCompact(item.snapshot?.volume || null)}</td>
            <td class="px-4 py-4 align-top">${formatPercent(item.scanners?.dip_recovery?.metrics?.distance_to_year_low_pct || null, 2)}</td>
            <td class="px-4 py-4 align-top max-w-sm text-slate-300">${escapeHtml(summary)}</td>
        </tr>
    `;
}

function mobileRow(item) {
    const summary = (item.comments || []).slice(0, 1).join(' ') || 'Yorum bulunamadı';
    const isActive = state.selectedSymbol === item.snapshot?.symbol;

    return `
        <button
            type="button"
            data-symbol="${escapeHtml(item.snapshot?.symbol || '')}"
            class="rounded-3xl border p-4 text-left transition ${isActive ? 'border-emerald-400/40 bg-emerald-400/10' : 'border-white/10 bg-slate-950/50 hover:bg-white/5'}"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-lg font-bold">${escapeHtml(item.snapshot?.symbol || '-')}</p>
                    <p class="mt-1 text-xs text-slate-500">${escapeHtml(item.snapshot?.name || '-')}</p>
                </div>
                <span class="rounded-full border px-3 py-1 text-xs font-medium ${scoreTone(item.scores?.overall || 0)}">${formatScore(item.scores?.overall || 0)}</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                ${infoTile('Fiyat', formatPrice(item.snapshot?.close || null))}
                ${infoTile('Hacim', formatCompact(item.snapshot?.volume || null))}
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-300">${escapeHtml(summary)}</p>
        </button>
    `;
}

function renderTable(data) {
    const table = document.getElementById('scannerTable');
    const mobileList = document.getElementById('mobileScannerList');

    document.getElementById('resultCount').textContent = `${numberFormatter.format(data.length)} sonuç`;

    if (!data.length) {
        table.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-slate-400">Aramanıza uygun hisse bulunamadı.</td>
            </tr>
        `;
        mobileList.innerHTML = '<div class="rounded-3xl border border-white/10 bg-slate-950/50 p-5 text-sm text-slate-400">Mobil görünüm için eşleşen hisse bulunamadı.</div>';
        return;
    }

    table.innerHTML = data.map(tableRow).join('');
    mobileList.innerHTML = data.map(mobileRow).join('');
}

function syncSelection() {
    if (state.filteredData.some((item) => item.snapshot?.symbol === state.selectedSymbol)) {
        return;
    }

    state.selectedSymbol = state.filteredData[0]?.snapshot?.symbol || state.rawData[0]?.snapshot?.symbol || null;
}

function renderActiveViews() {
    syncSelection();
    renderQuickFilters(state.rawData);
    renderLeaders(state.filteredData);
    renderTable(state.filteredData);

    const activeItem = state.filteredData.find((item) => item.snapshot?.symbol === state.selectedSymbol) || state.filteredData[0] || null;
    state.selectedSymbol = activeItem?.snapshot?.symbol || null;
    renderDetail(activeItem);
}

function applyFilter() {
    state.query = document.getElementById('searchInput').value.trim();
    state.filteredData = applyActiveFilters(state.rawData);
    renderActiveViews();
}

function setLoadingState() {
    document.getElementById('summaryCards').innerHTML = '<div class="md:col-span-2 xl:col-span-4 rounded-[28px] border border-white/10 bg-white/5 p-6 text-slate-400">Veriler yükleniyor...</div>';
    document.getElementById('quickFilters').innerHTML = '<div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-400">Filtreler hazırlanıyor...</div>';
    document.getElementById('leaderboard').innerHTML = '<div class="md:col-span-2 rounded-3xl border border-white/10 bg-slate-950/50 p-6 text-slate-400">Fırsat kartları hazırlanıyor...</div>';
    document.getElementById('detailPanel').innerHTML = '<div class="rounded-3xl border border-white/10 bg-slate-950/50 p-6 text-slate-400">Detay paneli hazırlanıyor...</div>';
    document.getElementById('insightGrid').innerHTML = '<div class="rounded-3xl border border-white/10 bg-slate-950/50 p-6 text-slate-400">İçgörüler hesaplanıyor...</div>';
    document.getElementById('mobileScannerList').innerHTML = '<div class="rounded-3xl border border-white/10 bg-slate-950/50 p-5 text-sm text-slate-400 lg:hidden">Mobil kartlar hazırlanıyor...</div>';
    document.getElementById('scannerTable').innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Tablo yükleniyor...</td></tr>';
}

function updateTimestamp() {
    document.getElementById('lastUpdatedBadge').textContent = `Son güncelleme ${new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}`;
}

async function loadMarket() {
    setLoadingState();

    const response = await fetch(`${window.BORSA_API_URL}?limit=${MARKET_LIMIT}`, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`API hatası: ${response.status}`);
    }

    const payload = await response.json();
    const data = Array.isArray(payload.data) ? payload.data : [];

    state.rawData = [...data].sort((left, right) => (right.scores?.overall || 0) - (left.scores?.overall || 0));
    state.filteredData = applyActiveFilters(state.rawData);
    syncSelection();

    renderSummary(state.rawData);
    renderInsights(state.rawData);
    renderActiveViews();
    updateTimestamp();
}

function showError(error) {
    const message = error instanceof Error ? error.message : 'Bilinmeyen hata';
    document.getElementById('summaryCards').innerHTML = `<div class="md:col-span-2 xl:col-span-4 rounded-[28px] border border-rose-400/20 bg-rose-400/10 p-6 text-rose-200">Veriler alınamadı: ${escapeHtml(message)}</div>`;
    document.getElementById('quickFilters').innerHTML = '<div class="rounded-2xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">Filtreler veri bekliyor.</div>';
    document.getElementById('leaderboard').innerHTML = '<div class="md:col-span-2 rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6 text-rose-100">API bağlantısını ve ortam ayarlarınızı kontrol edin.</div>';
    document.getElementById('detailPanel').innerHTML = '<div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6 text-rose-100">Detay paneli veri bekliyor.</div>';
    document.getElementById('insightGrid').innerHTML = '<div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6 text-rose-100">İçgörüler hesaplanamadı.</div>';
    document.getElementById('mobileScannerList').innerHTML = '<div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-5 text-sm text-rose-100 lg:hidden">Mobil kartlar yüklenemedi.</div>';
    document.getElementById('scannerTable').innerHTML = `<tr><td colspan="7" class="px-4 py-10 text-center text-rose-200">${escapeHtml(message)}</td></tr>`;
    document.getElementById('resultCount').textContent = '0 sonuç';
}

document.addEventListener('click', (event) => {
    const filterButton = event.target.closest('[data-filter]');
    if (filterButton) {
        state.activeFilter = filterButton.dataset.filter || 'all';
        state.filteredData = applyActiveFilters(state.rawData);
        renderActiveViews();
        return;
    }

    const symbolButton = event.target.closest('[data-symbol]');
    if (symbolButton) {
        state.selectedSymbol = symbolButton.dataset.symbol || null;
        renderActiveViews();
    }
});

document.getElementById('reloadButton').addEventListener('click', () => {
    loadMarket().catch(showError);
});

document.getElementById('searchInput').addEventListener('input', () => {
    applyFilter();
});

loadMarket().catch(showError);
setInterval(() => loadMarket().catch(showError), 60000);
