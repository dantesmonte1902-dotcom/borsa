const state = {
    rawData: [],
    filteredData: [],
    selectedSymbol: null,
    query: '',
};

const numberFormatter = new Intl.NumberFormat('tr-TR');
const compactFormatter = new Intl.NumberFormat('tr-TR', { notation: 'compact', maximumFractionDigits: 1 });
const currencyFormatter = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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

            <div class="grid gap-3 sm:grid-cols-2">
                ${infoTile('Son Fiyat', formatPrice(item.snapshot?.close || null))}
                ${infoTile('İşlem Hacmi', formatCompact(item.snapshot?.volume || null))}
                ${infoTile('Günlük Aralık', `${formatPrice(item.snapshot?.low || null)} - ${formatPrice(item.snapshot?.high || null)}`)}
                ${infoTile('İşlem Değeri', `₺${formatCompact(item.snapshot?.value_traded || null)}`)}
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

function renderTable(data) {
    const table = document.getElementById('scannerTable');
    document.getElementById('resultCount').textContent = `${numberFormatter.format(data.length)} sonuç`;

    if (!data.length) {
        table.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-slate-400">Aramanıza uygun hisse bulunamadı.</td>
            </tr>
        `;
        return;
    }

    table.innerHTML = data.map(tableRow).join('');
}

function applyFilter() {
    const query = document.getElementById('searchInput').value.trim().toLocaleLowerCase('tr-TR');
    state.query = query;

    state.filteredData = state.rawData.filter((item) => {
        if (!query) {
            return true;
        }

        const symbol = String(item.snapshot?.symbol || '').toLocaleLowerCase('tr-TR');
        const comments = (item.comments || []).join(' ').toLocaleLowerCase('tr-TR');
        return symbol.includes(query) || comments.includes(query);
    });

    if (!state.filteredData.some((item) => item.snapshot?.symbol === state.selectedSymbol)) {
        state.selectedSymbol = state.filteredData[0]?.snapshot?.symbol || state.rawData[0]?.snapshot?.symbol || null;
    }

    const displayData = state.query ? state.filteredData : state.rawData;
    renderLeaders(displayData);
    renderTable(state.filteredData);
    renderDetail((displayData.find((item) => item.snapshot?.symbol === state.selectedSymbol)) || displayData[0] || null);
}

function attachInteractions() {
    document.querySelectorAll('[data-symbol]').forEach((element) => {
        element.addEventListener('click', () => {
            state.selectedSymbol = element.dataset.symbol;
            const displayData = state.query ? state.filteredData : state.rawData;
            renderLeaders(displayData);
            renderTable(state.filteredData);
            renderDetail((displayData.find((item) => item.snapshot?.symbol === state.selectedSymbol)) || null);
        });
    });
}

function setLoadingState() {
    document.getElementById('summaryCards').innerHTML = '<div class="md:col-span-2 xl:col-span-4 rounded-[28px] border border-white/10 bg-white/5 p-6 text-slate-400">Veriler yükleniyor...</div>';
    document.getElementById('leaderboard').innerHTML = '<div class="md:col-span-2 rounded-3xl border border-white/10 bg-slate-950/50 p-6 text-slate-400">Fırsat kartları hazırlanıyor...</div>';
    document.getElementById('detailPanel').innerHTML = '<div class="rounded-3xl border border-white/10 bg-slate-950/50 p-6 text-slate-400">Detay paneli hazırlanıyor...</div>';
    document.getElementById('insightGrid').innerHTML = '<div class="rounded-3xl border border-white/10 bg-slate-950/50 p-6 text-slate-400">İçgörüler hesaplanıyor...</div>';
    document.getElementById('scannerTable').innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Tablo yükleniyor...</td></tr>';
}

function updateTimestamp() {
    document.getElementById('lastUpdatedBadge').textContent = `Son güncelleme ${new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}`;
}

async function loadMarket() {
    setLoadingState();

    const response = await fetch(`${window.BORSA_API_URL}?limit=12`, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`API hatası: ${response.status}`);
    }

    const payload = await response.json();
    const data = Array.isArray(payload.data) ? payload.data : [];

    state.query = document.getElementById('searchInput').value.trim().toLocaleLowerCase('tr-TR');
    state.rawData = [...data].sort((left, right) => (right.scores?.overall || 0) - (left.scores?.overall || 0));
    state.filteredData = state.rawData.filter((item) => {
        if (!state.query) {
            return true;
        }

        const symbol = String(item.snapshot?.symbol || '').toLocaleLowerCase('tr-TR');
        const comments = (item.comments || []).join(' ').toLocaleLowerCase('tr-TR');
        return symbol.includes(state.query) || comments.includes(state.query);
    });
    state.selectedSymbol = state.selectedSymbol && state.rawData.some((item) => item.snapshot?.symbol === state.selectedSymbol)
        ? state.selectedSymbol
        : state.filteredData[0]?.snapshot?.symbol || state.rawData[0]?.snapshot?.symbol || null;

    renderSummary(state.rawData);
    renderLeaders(state.query ? state.filteredData : state.rawData);
    renderInsights(state.rawData);
    renderTable(state.filteredData);
    renderDetail((state.query ? state.filteredData : state.rawData).find((item) => item.snapshot?.symbol === state.selectedSymbol) || (state.query ? state.filteredData[0] : state.rawData[0]) || null);
    updateTimestamp();
    attachInteractions();
}

function showError(error) {
    const message = error instanceof Error ? error.message : 'Bilinmeyen hata';
    document.getElementById('summaryCards').innerHTML = `<div class="md:col-span-2 xl:col-span-4 rounded-[28px] border border-rose-400/20 bg-rose-400/10 p-6 text-rose-200">Veriler alınamadı: ${escapeHtml(message)}</div>`;
    document.getElementById('leaderboard').innerHTML = '<div class="md:col-span-2 rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6 text-rose-100">API bağlantısını ve ortam ayarlarınızı kontrol edin.</div>';
    document.getElementById('detailPanel').innerHTML = '<div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6 text-rose-100">Detay paneli veri bekliyor.</div>';
    document.getElementById('insightGrid').innerHTML = '<div class="rounded-3xl border border-rose-400/20 bg-rose-400/10 p-6 text-rose-100">İçgörüler hesaplanamadı.</div>';
    document.getElementById('scannerTable').innerHTML = `<tr><td colspan="7" class="px-4 py-10 text-center text-rose-200">${escapeHtml(message)}</td></tr>`;
    document.getElementById('resultCount').textContent = '0 sonuç';
}

document.getElementById('reloadButton').addEventListener('click', () => {
    loadMarket().catch(showError);
});

document.getElementById('searchInput').addEventListener('input', () => {
    applyFilter();
    attachInteractions();
});

loadMarket().catch(showError);
setInterval(() => loadMarket().catch(showError), 60000);
