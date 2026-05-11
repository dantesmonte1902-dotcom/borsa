<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
?><!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Borsa Pulse Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
<div class="max-w-7xl mx-auto px-6 py-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <p class="text-emerald-400 text-sm uppercase tracking-[0.3em]">BIST Hisse Tarama Platformu</p>
            <h1 class="text-4xl font-bold">Borsa Pulse</h1>
        </div>
        <button id="reloadButton" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold px-4 py-2 rounded-xl">Yenile</button>
    </div>

    <section class="grid gap-6 md:grid-cols-3 mb-8" id="summaryCards"></section>
    <section class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Tarama Sonuçları</h2>
            <span class="text-sm text-slate-400">AJAX ile canlı yenilenir</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-slate-400">
                <tr>
                    <th class="pb-3">Sembol</th>
                    <th class="pb-3">Genel Skor</th>
                    <th class="pb-3">Teknik</th>
                    <th class="pb-3">Hacim</th>
                    <th class="pb-3">Dipten Uzaklık</th>
                    <th class="pb-3">Yorum</th>
                </tr>
                </thead>
                <tbody id="scannerTable"></tbody>
            </table>
        </div>
    </section>

    <section class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6">
        <h2 class="text-xl font-semibold mb-4">Mimari Faz 1</h2>
        <ul class="space-y-2 text-slate-300 list-disc pl-6">
            <li>Saf PHP bootstrap, config, servis ve scanner mimarisi</li>
            <li>Teknik analiz motoru: RSI, MACD, EMA, SMA, Bollinger, ATR, Fibonacci</li>
            <li>Alarm kanalları, cron girişleri, SQL şeması ve API endpointleri</li>
        </ul>
    </section>
</div>
<script>window.BORSA_API_URL = '../api/market.php';</script>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>
