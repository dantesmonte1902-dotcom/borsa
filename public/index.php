<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
?><!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Borsa Pulse | Modern Piyasa Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    boxShadow: {
                        glow: '0 20px 80px rgba(16, 185, 129, 0.18)',
                    },
                    colors: {
                        surface: {
                            950: '#050816',
                            900: '#0b1120',
                            800: '#111827',
                            700: '#1f2937',
                        },
                    },
                },
            },
        };
    </script>
</head>
<body class="min-h-screen bg-surface-950 text-slate-100 selection:bg-emerald-400/30">
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-32 left-1/2 h-96 w-96 -translate-x-1/2 rounded-full bg-emerald-500/15 blur-3xl"></div>
    <div class="absolute top-80 right-0 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
</div>

<div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-6 sm:space-y-8">
    <section class="rounded-[32px] border border-white/10 bg-white/5 backdrop-blur-xl shadow-glow overflow-hidden">
        <div class="grid gap-8 lg:grid-cols-[1.5fr_0.9fr] p-6 sm:p-8 lg:p-10">
            <div class="space-y-6">
                <div class="flex flex-wrap items-center gap-3 text-xs sm:text-sm text-slate-300">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        BIST Hisse Tarama Platformu
                    </span>
                    <span id="lastUpdatedBadge" class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1">Canlı veri bekleniyor</span>
                </div>

                <div class="space-y-4 max-w-3xl">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight">Muhteşem, modern ve okunabilir <span class="text-emerald-400">borsa ekranı</span></h1>
                    <p class="text-base sm:text-lg text-slate-300 leading-8">Tüm kritik verileri tek sayfada görün. En güçlü hisseleri, hacim patlamalarını, dipten toparlanma fırsatlarını ve teknik göstergeleri birkaç saniyede inceleyin.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button id="reloadButton" class="inline-flex items-center justify-center rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300">Verileri Yenile</button>
                    <div class="inline-flex items-center rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">Tek sayfada özet, detay ve tarama görünümü</div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1" id="heroHighlights">
                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
                    <p class="text-sm text-slate-400 mb-2">Piyasa görünümü</p>
                    <p id="marketPulseValue" class="text-3xl font-bold">Hazırlanıyor</p>
                    <p id="marketPulseText" class="mt-2 text-sm text-slate-300">Veri geldiğinde genel durum burada gösterilecek.</p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-cyan-500/15 to-emerald-500/10 p-5">
                    <p class="text-sm text-slate-300 mb-2">Akıllı panel</p>
                    <ul class="space-y-2 text-sm text-slate-200">
                        <li>• Öne çıkan fırsatlar</li>
                        <li>• Tıklanabilir hisse detay kartı</li>
                        <li>• Arama ve hızlı filtreleme</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="summaryCards" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"></section>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-6">
                <div>
                    <p class="text-sm uppercase tracking-[0.25em] text-emerald-400">Spot ışığı</p>
                    <h2 class="text-2xl font-bold">En dikkat çeken hisseler</h2>
                </div>
                <label class="relative block w-full lg:max-w-sm">
                    <span class="sr-only">Sembol ara</span>
                    <input id="searchInput" type="search" placeholder="Sembol veya yorum ara..." class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none ring-0 placeholder:text-slate-500 focus:border-emerald-400/60">
                </label>
            </div>
            <div id="leaderboard" class="grid gap-4 md:grid-cols-2"></div>
        </div>

        <div id="detailPanel" class="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-xl"></div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.25em] text-cyan-400">Piyasa tablosu</p>
                    <h2 class="text-2xl font-bold">Tüm hisseleri kolayca karşılaştırın</h2>
                </div>
                <span id="resultCount" class="text-sm text-slate-400"></span>
            </div>
            <div class="overflow-hidden rounded-3xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-950/80 text-left text-slate-400">
                        <tr>
                            <th class="px-4 py-4 font-medium">Sembol</th>
                            <th class="px-4 py-4 font-medium">Fiyat</th>
                            <th class="px-4 py-4 font-medium">Genel Skor</th>
                            <th class="px-4 py-4 font-medium">RSI</th>
                            <th class="px-4 py-4 font-medium">Hacim</th>
                            <th class="px-4 py-4 font-medium">Dip Uzaklığı</th>
                            <th class="px-4 py-4 font-medium">Özet</th>
                        </tr>
                        </thead>
                        <tbody id="scannerTable"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <div class="mb-5">
                <p class="text-sm uppercase tracking-[0.25em] text-fuchsia-400">Tarama içgörüleri</p>
                <h2 class="text-2xl font-bold">Sistem ne görüyor?</h2>
            </div>
            <div id="insightGrid" class="grid gap-4"></div>
        </div>
    </section>
</div>

<script>window.BORSA_API_URL = '../api/market.php';</script>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>
