<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$symbol = isset($_GET['symbol']) && is_string($_GET['symbol']) && trim($_GET['symbol']) !== ''
    ? trim($_GET['symbol'])
    : 'BIST:ASELS';
?><!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Borsa Pulse | Hisse Detayı</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>
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
        <div class="grid gap-8 lg:grid-cols-[1.35fr_0.85fr] p-6 sm:p-8 lg:p-10">
            <div class="space-y-6">
                <div class="flex flex-wrap items-center gap-3 text-xs sm:text-sm text-slate-300">
                    <a href="./index.php" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 transition hover:border-emerald-400/30 hover:text-white">
                        ← Dashboard'a dön
                    </a>
                    <span id="stockLastUpdatedBadge" class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1">Canlı veri bekleniyor</span>
                </div>

                <div class="space-y-4 max-w-3xl">
                    <p class="text-sm uppercase tracking-[0.25em] text-emerald-400">Hisse detay ekranı</p>
                    <h1 id="stockTitle" class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight">Sembol yükleniyor</h1>
                    <p id="stockSubtitle" class="text-base sm:text-lg text-slate-300 leading-8">OHLCV, göstergeler, scanner sonuçları ve yorumlar tek ekranda hazırlanıyor.</p>
                </div>

                <form id="symbolForm" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <label class="relative block flex-1">
                        <span class="sr-only">Sembol gir</span>
                        <input id="symbolInput" name="symbol" type="search" placeholder="Örnek: BIST:ASELS" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-500 focus:border-emerald-400/60">
                    </label>
                    <div class="flex gap-3">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300">Sembol Aç</button>
                        <button id="reloadStockButton" type="button" class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-100 transition hover:border-emerald-400/30 hover:text-white">Yenile</button>
                    </div>
                </form>
            </div>

            <div id="heroMetrics" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1"></div>
        </div>
    </section>

    <section id="stockSummaryCards" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"></section>

    <section class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
        <div class="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.25em] text-cyan-400">TradingView Lightweight Charts</p>
                    <h2 class="text-2xl font-bold">Candle ve hacim görünümü</h2>
                </div>
                <div id="chartMeta" class="text-sm text-slate-400"></div>
            </div>
            <div class="overflow-hidden rounded-[28px] border border-white/10 bg-slate-950/70 p-4">
                <div id="ohlcvChart" class="h-[420px] w-full"></div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
                <div class="mb-5">
                    <p class="text-sm uppercase tracking-[0.25em] text-fuchsia-400">Scanner özeti</p>
                    <h2 class="text-2xl font-bold">Sistem bu hisse için ne diyor?</h2>
                </div>
                <div id="scannerInsights" class="grid gap-4"></div>
            </div>

            <div class="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
                <div class="mb-5">
                    <p class="text-sm uppercase tracking-[0.25em] text-amber-400">Akıllı yorumlar</p>
                    <h2 class="text-2xl font-bold">Otomatik değerlendirme</h2>
                </div>
                <div id="commentaryList" class="space-y-3"></div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <div class="mb-5">
                <p class="text-sm uppercase tracking-[0.25em] text-emerald-400">Teknik görünüm</p>
                <h2 class="text-2xl font-bold">Göstergeler</h2>
            </div>
            <div id="indicatorGrid" class="grid gap-4 sm:grid-cols-2"></div>
        </div>

        <div class="rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <div class="mb-5">
                <p class="text-sm uppercase tracking-[0.25em] text-cyan-400">Snapshot</p>
                <h2 class="text-2xl font-bold">Fiyat ve hacim özeti</h2>
            </div>
            <div id="snapshotGrid" class="grid gap-4 sm:grid-cols-2"></div>
        </div>
    </section>
</div>

<script>
    window.BORSA_API_URL = '../api/market.php';
    window.BORSA_STOCK_SYMBOL = <?= json_encode($symbol, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="../assets/js/stock-detail.js"></script>
</body>
</html>
