async function loadMarket() {
    const response = await fetch(`${window.BORSA_API_URL}?limit=6`, { headers: { 'Accept': 'application/json' } });
    const payload = await response.json();
    const data = payload.data || [];

    const summaryCards = document.getElementById('summaryCards');
    const scannerTable = document.getElementById('scannerTable');

    const avgScore = data.length ? (data.reduce((sum, item) => sum + (item.scores?.overall || 0), 0) / data.length).toFixed(2) : '0.00';
    const best = data[0]?.snapshot?.symbol || '-';
    const volumeLeader = [...data].sort((a, b) => (b.scanners?.volume_burst?.metrics?.volume_ratio || 0) - (a.scanners?.volume_burst?.metrics?.volume_ratio || 0))[0]?.snapshot?.symbol || '-';

    summaryCards.innerHTML = [
        { title: 'Ortalama Genel Skor', value: avgScore },
        { title: 'En Güçlü Sembol', value: best },
        { title: 'Hacim Lideri', value: volumeLeader },
    ].map(card => `
        <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
            <p class="text-slate-400 text-sm mb-2">${card.title}</p>
            <p class="text-3xl font-semibold">${card.value}</p>
        </div>
    `).join('');

    scannerTable.innerHTML = data.map(item => `
        <tr class="border-t border-slate-800 align-top">
            <td class="py-3 font-semibold">${item.snapshot.symbol}</td>
            <td class="py-3 text-emerald-400">${item.scores.overall}</td>
            <td class="py-3">${item.scores.technical}</td>
            <td class="py-3">${item.scores.volume}</td>
            <td class="py-3">${item.scanners.dip_recovery.metrics.distance_to_year_low_pct}%</td>
            <td class="py-3 text-slate-300">${(item.comments || []).join(' ')}</td>
        </tr>
    `).join('');
}

document.getElementById('reloadButton').addEventListener('click', loadMarket);
loadMarket().catch(console.error);
setInterval(() => loadMarket().catch(console.error), 60000);
