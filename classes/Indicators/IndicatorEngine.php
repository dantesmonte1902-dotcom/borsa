<?php

namespace App\Indicators;

final class IndicatorEngine
{
    public function build(array $candles): array
    {
        $close = array_column($candles, 'close');
        $highs = array_column($candles, 'high');
        $lows = array_column($candles, 'low');
        $volumes = array_column($candles, 'volume');

        return [
            'close' => $close,
            'volume_sma_20' => SMAIndicator::calculate($volumes, 20),
            'sma_20' => SMAIndicator::calculate($close, 20),
            'ema_20' => EMAIndicator::calculate($close, 20),
            'ema_50' => EMAIndicator::calculate($close, 50),
            'rsi_14' => RSIIndicator::calculate($close, 14),
            'macd' => MACDIndicator::calculate($close),
            'bollinger' => BollingerBandsIndicator::calculate($close),
            'atr_14' => ATRIndicator::calculate($candles),
            'fibonacci' => FibonacciLevels::calculate((float) max($highs), (float) min($lows)),
        ];
    }
}
