<?php

namespace App\Services;

use App\Cache\FileCache;
use App\Indicators\IndicatorEngine;
use App\Scanners\DipScanner;
use App\Scanners\LowFloatScanner;
use App\Scanners\PatternScanner;
use App\Scanners\VolumeBurstScanner;

final class Platform
{
    private MarketDataProviderInterface $provider;
    private IndicatorEngine $indicatorEngine;
    private LowFloatScanner $lowFloatScanner;
    private DipScanner $dipScanner;
    private VolumeBurstScanner $volumeBurstScanner;
    private PatternScanner $patternScanner;
    private ScoreEngine $scoreEngine;
    private RuleBasedCommentary $commentary;

    public function __construct()
    {
        $cache = new FileCache(Config::get('app.cache_path'));
        $httpClient = new HttpClient($cache);
        $this->provider = new TradingViewMarketDataProvider($httpClient);
        $this->indicatorEngine = new IndicatorEngine();
        $this->lowFloatScanner = new LowFloatScanner();
        $this->dipScanner = new DipScanner();
        $this->volumeBurstScanner = new VolumeBurstScanner();
        $this->patternScanner = new PatternScanner();
        $this->scoreEngine = new ScoreEngine();
        $this->commentary = new RuleBasedCommentary();
    }

    public function analyzeSymbol(string $symbol): array
    {
        $snapshot = $this->provider->fetchQuoteSnapshot($symbol);
        $candles = $this->provider->fetchHistoricalCandles($symbol);
        $indicators = $this->indicatorEngine->build($candles);

        $scannerResults = [
            'low_float' => $this->lowFloatScanner->scan($snapshot, $indicators),
            'dip_recovery' => $this->dipScanner->scan($snapshot, $candles, $indicators),
            'volume_burst' => $this->volumeBurstScanner->scan($snapshot, $candles, $indicators),
            'pattern' => $this->patternScanner->scan($candles, $indicators),
        ];

        $scores = $this->scoreEngine->calculate($scannerResults);
        $comments = $this->commentary->generate($snapshot, $scannerResults, $scores);

        return [
            'snapshot' => $snapshot,
            'candles' => $candles,
            'indicators' => $indicators,
            'scanners' => $scannerResults,
            'scores' => $scores,
            'comments' => $comments,
        ];
    }

    public function analyzeMarket(int $limit = 10): array
    {
        $symbols = array_slice($this->provider->listSymbols(), 0, $limit);
        $results = [];

        foreach ($symbols as $symbol) {
            $results[] = $this->analyzeSymbol($symbol);
        }

        usort($results, static fn (array $left, array $right): int => ($right['scores']['overall'] <=> $left['scores']['overall']));

        return $results;
    }
}
