<?php

declare(strict_types=1);

/** Begrenzter Messwertverlauf und konservative lineare Trendprognose. */
final class WLTForecast
{
    public static function append(array $state, string $fingerprint, int $now, float $temperature, int $window, int $gap, float $unitScale): array
    {
        $points = $state['points'] ?? [];
        $last = $points ? $points[count($points) - 1] : null;
        $reset = ($state['fingerprint'] ?? '') !== $fingerprint || !$last || $now < $last[0] ||
            $now - $last[0] > $gap || ($now >= $last[0] && $now - $last[0] <= 60 && abs($temperature - $last[1]) / $unitScale >= 6.0);
        if ($reset) {
            return ['fingerprint' => $fingerprint, 'startedAt' => $now, 'start' => $temperature, 'points' => [[$now, $temperature]]];
        }
        if ($now - $last[0] < 10) { return $state; }
        $points[] = [$now, $temperature];
        $state['points'] = array_slice(array_values(array_filter($points, static fn(array $p): bool => $p[0] >= $now - $window)), -361);
        return $state;
    }

    public static function estimate(array $state, int $now, float $current, mixed $target, float $unitScale, int $gap): array
    {
        $points = $state['points'] ?? [];
        $result = ['status' => 'learning', 'seconds' => null, 'eta' => null, 'rate' => null, 'progress' => null,
            'start' => $state['start'] ?? null, 'startedAt' => $state['startedAt'] ?? null, 'points' => self::chartPoints($points)];
        if ((!is_int($target) && !is_float($target)) || !is_finite((float) $target)) { return array_replace($result, ['status' => 'no_target']); }
        if ($current >= $target) { return array_replace($result, ['status' => 'reached', 'seconds' => 0, 'eta' => $now, 'progress' => 1.0]); }
        if (isset($state['start']) && $target > $state['start']) {
            $result['progress'] = max(0.0, min(1.0, ($current - $state['start']) / ($target - $state['start'])));
        }
        if (count($points) < 6) { return $result; }
        $last = $points[count($points) - 1];
        if ($now < $last[0] || $now - $last[0] > $gap) { return array_replace($result, ['status' => 'stale']); }
        $span = $last[0] - $points[0][0];
        if ($span < 180) { return $result; }
        $fit = self::fit($points);
        $rate = $fit['rate']; // Grad pro Minute, in der Geräteeinheit.
        $result['rate'] = $rate;
        $recentSpan = min(180, max(90, $span / 2));
        $recent = array_values(array_filter($points, static fn(array $p): bool => $p[0] >= $last[0] - $recentSpan));
        if (count($recent) < 3) { $recent = array_slice($points, -3); }
        $recentRate = self::fit($recent)['rate'];
        if ($rate / $unitScale < -0.02 || $recentRate / $unitScale < -0.02) { return array_replace($result, ['status' => 'cooling']); }
        if ($rate / $unitScale <= 0.02 || $recentRate / $unitScale <= 0.02) { return array_replace($result, ['status' => 'stalled']); }
        $rise = ($last[1] - $points[0][1]) / $unitScale;
        if ($rise < 0.3 || $fit['r2'] < 0.8 || $recentRate < $rate * 0.35 || $recentRate > $rate * 2.5) {
            return array_replace($result, ['status' => 'unstable']);
        }
        $seconds = ($target - $current) / $rate * 60;
        if (!is_finite($seconds) || $seconds > 43200) { return array_replace($result, ['status' => 'distant']); }
        return array_replace($result, ['status' => 'estimated', 'seconds' => (int) ceil($seconds), 'eta' => $now + (int) ceil($seconds)]);
    }

    private static function fit(array $points): array
    {
        $n = count($points);
        if ($n < 2) { return ['rate' => 0.0, 'r2' => 0.0]; }
        $origin = $points[0][0];
        $meanX = $meanY = 0.0;
        foreach ($points as [$time, $value]) { $meanX += ($time - $origin) / 60; $meanY += $value; }
        $meanX /= $n; $meanY /= $n;
        $xx = $xy = $yy = 0.0;
        foreach ($points as [$time, $value]) {
            $x = ($time - $origin) / 60 - $meanX; $y = $value - $meanY;
            $xx += $x * $x; $xy += $x * $y; $yy += $y * $y;
        }
        $rate = $xx > 0 ? $xy / $xx : 0.0;
        $r2 = $xx > 0 && $yy > 0 ? $xy * $xy / ($xx * $yy) : 0.0;
        return ['rate' => is_finite($rate) ? $rate : 0.0, 'r2' => is_finite($r2) ? max(0.0, min(1.0, $r2)) : 0.0];
    }

    private static function chartPoints(array $points): array
    {
        if (count($points) <= 48) { return $points; }
        $result = []; $last = count($points) - 1;
        for ($i = 0; $i < 48; $i++) { $result[] = $points[(int) floor($i * $last / 47)]; }
        return $result;
    }
}

trait WLTForecastSupport
{
    protected function forecastTime(): int { return time(); }

    private function forecastGap(): int { return max(120, $this->ReadPropertyInteger('Interval') * 3); }

    private function forecastState(int $number): array
    {
        $value = $this->GetBuffer('Forecast_' . $number);
        $state = $value === '' ? [] : json_decode($value, true);
        return is_array($state) ? $state : [];
    }

    private function recordForecast(array $data, array $settings): void
    {
        $known = json_decode($this->GetBuffer('ForecastChannels') ?: '[]', true) ?: [];
        $active = [];
        if ($this->ReadPropertyBoolean('ForecastEnabled')) {
            $now = $this->forecastTime();
            $unit = ($data['system']['unit'] ?? 'C') === 'F' ? 'F' : 'C';
            foreach ($data['channel'] as $channel) {
                $number = $channel['number'];
                if ($number > 255 || !$this->channelHasValidTemperature($channel, $settings)) { continue; }
                $active[] = $number;
                $fingerprint = sha1(WLTApi::encode([$this->ReadPropertyString('Host'), $unit, $channel['name'], $channel['typ'] ?? null, $channel['fixed'] ?? null]));
                $state = WLTForecast::append($this->forecastState($number), $fingerprint, $now, (float) $channel['temp'],
                    $this->ReadPropertyInteger('ForecastWindow') * 60, $this->forecastGap(), $unit === 'F' ? 1.8 : 1.0);
                $this->SetBuffer('Forecast_' . $number, WLTApi::encode($state));
            }
        }
        foreach (array_diff($known, $active) as $number) { $this->SetBuffer('Forecast_' . $number, ''); }
        $this->SetBuffer('ForecastChannels', WLTApi::encode($active));
    }

    private function channelForecast(array $channel, string $unit): array
    {
        if (!$this->ReadPropertyBoolean('ForecastEnabled')) { return ['status' => 'disabled', 'points' => []]; }
        $result = WLTForecast::estimate($this->forecastState($channel['number']), $this->forecastTime(), (float) $channel['temp'],
            $channel['max'] ?? null, $unit === 'F' ? 1.8 : 1.0, $this->forecastGap());
        if (!$this->ReadPropertyBoolean('ForecastChart')) { $result['points'] = []; }
        return $result;
    }

    private function resetChannelForecast(int $number): void
    {
        if (!IPS_SemaphoreEnter('WLT_' . $this->InstanceID, 1000)) { throw new RuntimeException('Instanz ist beschäftigt.'); }
        try {
            $channel = WLTApi::item($this->cached('Data')['channel'] ?? [], 'number', $number);
            if (!$this->ReadPropertyBoolean('ForecastEnabled') || !$this->stateValue('Online', false) ||
                !$this->channelHasValidTemperature($channel, $this->cached('Settings'))) {
                throw new RuntimeException('Verlauf kann derzeit nicht gestartet werden.');
            }
            $this->SetBuffer('Forecast_' . $number, '');
            // Erst der nächste echte Geräteabruf liefert den ersten neuen Messpunkt.
        } finally { IPS_SemaphoreLeave('WLT_' . $this->InstanceID); }
    }
}
