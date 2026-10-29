<?php

declare(strict_types=1);

function measuredSeries(callable $temperature, int $duration = 600, float $scale = 1.0): array
{
    $state = [];
    for ($second = 0; $second <= $duration; $second += 30) {
        $state = WLTForecast::append($state, 'probe', 100000 + $second, $temperature($second), 3600, 120, $scale);
    }
    return $state;
}
$linear = measuredSeries(static fn(int $t): float => 72.0 + $t / 60 * 0.3);
$prediction = WLTForecast::estimate($linear, 100600, 75.0, 82.0, 1.0, 120);
check($prediction['status'] === 'estimated' && abs($prediction['seconds'] - 1400) <= 1, 'Linearer Anstieg von 72 auf 75 °C in zehn Minuten ergibt etwa 23 Minuten 20 Sekunden bis 82 °C');
check(abs($prediction['rate'] - 0.3) < 0.00001 && abs($prediction['progress'] - 0.3) < 0.00001, 'Trend und Temperaturfortschritt beziehen sich auf den gemessenen Startwert');
$fahrenheit = measuredSeries(static fn(int $t): float => (72.0 + $t / 60 * 0.3) * 1.8 + 32, 600, 1.8);
$fp = WLTForecast::estimate($fahrenheit, 100600, 167.0, 179.6, 1.8, 120);
check($fp['status'] === 'estimated' && abs($fp['seconds'] - $prediction['seconds']) <= 1, 'Fahrenheit liefert für denselben Verlauf dieselbe Restzeit');
check(WLTForecast::estimate($linear, 100600, 82.0, 82.0, 1.0, 120)['status'] === 'reached', 'Erreichte Obergrenze benötigt keine Lernphase oder weitere Zeitprognose');
check(WLTForecast::estimate($linear, 100600, 75.0, null, 1.0, 120)['status'] === 'no_target', 'Fehlende Obergrenze erzeugt keine Restzeit');
$short = measuredSeries(static fn(int $t): float => 70.0 + $t / 600, 120);
check(WLTForecast::estimate($short, 100120, 70.2, 82.0, 1.0, 120)['status'] === 'learning', 'Vor drei Minuten wird keine Restzeit ausgegeben');
$flat = measuredSeries(static fn(int $t): float => 72.0);
check(WLTForecast::estimate($flat, 100600, 72.0, 82.0, 1.0, 120)['status'] === 'stalled', 'Plateau unterdrückt eine Restzeit statt unendlich langer Zahl');
$cooling = measuredSeries(static fn(int $t): float => 75.0 - $t / 60 * 0.3);
check(WLTForecast::estimate($cooling, 100600, 72.0, 82.0, 1.0, 120)['status'] === 'cooling', 'Abkühlung erzeugt keine negative Restzeit');
$stall = measuredSeries(static fn(int $t): float => 70.0 + min($t, 300) / 60 * 0.3);
check(WLTForecast::estimate($stall, 100600, 71.5, 82.0, 1.0, 120)['status'] === 'stalled', 'Ein neues Plateau wird trotz vorherigen Temperaturanstiegs erkannt');
$noisy = measuredSeries(static fn(int $t): float => 70.0 + $t / 600 + ((int) ($t / 30) % 2 ? 0.8 : -0.8));
check(WLTForecast::estimate($noisy, 100600, 70.2, 82.0, 1.0, 120)['status'] === 'unstable', 'Unruhiger Verlauf wird nicht mit einer scheinbar genauen Restzeit versehen');
check(WLTForecast::estimate($linear, 100800, 75.0, 82.0, 1.0, 120)['status'] === 'stale', 'Veraltete Messhistorie wird nicht zur Prognose verwendet');
$reset = WLTForecast::append($linear, 'other-probe', 100630, 75.2, 600, 120, 1.0);
check(count($reset['points']) === 1 && $reset['startedAt'] === 100630, 'Fühlerwechsel setzt Verlauf und Startpunkt zurück');
$reset = WLTForecast::append($linear, 'probe', 100800, 75.2, 600, 120, 1.0);
check(count($reset['points']) === 1, 'Längere Unterbrechung startet den Verlauf neu');
$reset = WLTForecast::append($linear, 'probe', 100630, 60.0, 600, 120, 1.0);
check(count($reset['points']) === 1 && $reset['start'] === 60.0, 'Großer abrupter Temperatursprung startet einen neuen Verlauf');
$reset = WLTForecast::append($linear, 'probe', 99999, 75.0, 600, 120, 1.0);
check(count($reset['points']) === 1, 'Zurückgestellte Uhr erzeugt keinen negativen Zeitverlauf');
$unchanged = WLTForecast::append($linear, 'probe', 100605, 75.0, 600, 120, 1.0);
check(count($unchanged['points']) === count($linear['points']), 'Schnelle manuelle Abrufe erzeugen keine überzähligen Trendpunkte');
$slow = measuredSeries(static fn(int $t): float => 70.0 + $t / 60 * 0.05);
check(WLTForecast::estimate($slow, 100600, 70.5, 999.0, 1.0, 120)['status'] === 'distant', 'Mehr als zwölf Stunden entfernte lineare Prognose wird nicht als belastbar ausgegeben');
$bounded = [];
for ($second = 0; $second <= 8000; $second += 10) { $bounded = WLTForecast::append($bounded, 'probe', 100000 + $second, 70.0, 3600, 120, 1.0); }
$boundedView = WLTForecast::estimate($bounded, 108000, 70.0, 82.0, 1.0, 120);
check(count($bounded['points']) <= 361 && strlen(WLTApi::encode($bounded)) < 65536 && count($boundedView['points']) === 48, 'Verlauf und Diagrammdaten sind begrenzt und bleiben unter den Symcon-Puffergrenzen');
check(end($boundedView['points'])[0] === 108000, 'Begrenzte Diagrammübertragung enthält den letzten echten Messpunkt');

class ForecastTestDevice extends TestDevice
{
    public int $clock = 1000000;
    protected function forecastTime(): int { return $this->clock; }
}
$heat = new ForecastTestDevice(false);
$heat->deviceData['channel'][0]['max'] = 82.0;
for ($second = 0; $second <= 600; $second += 30) {
    $heat->clock = 1000001 + $second;
    $heat->deviceData['channel'][0]['temp'] = 72.0 + $second / 60 * 0.3;
    $heat->Update();
}
$channel = end($heat->visualizationMessages)['channels'][0];
check($channel['forecast']['status'] === 'estimated' && abs($channel['forecast']['seconds'] - 1400) <= 1 && count($heat->variables) === 0, 'Echter Modulabruf liefert Restzeit direkt an die Kachel ohne Statusvariablen');
$heat->deviceData['channel'][0]['max'] = 78.0;
$heat->Update();
$newForecast = end($heat->visualizationMessages)['channels'][0]['forecast'];
check($newForecast['status'] === 'estimated' && abs($newForecast['seconds'] - 600) <= 1 && $newForecast['startedAt'] === $channel['forecast']['startedAt'], 'Geänderte Obergrenze berechnet die Restzeit neu und erhält den Temperaturverlauf');
$heat->properties['AllowWrite'] = false;
$writes = count($heat->writes);
check(tileRequest($heat, ['action' => 'forecastReset', 'number' => 1])['ok'] && count($heat->writes) === $writes, 'Verlauf kann ohne Geräteschreibzugriff zurückgesetzt werden');
check(end($heat->visualizationMessages)['channels'][0]['forecast']['points'] === [], 'Reset erzeugt keine künstliche Messung aus alten Cachewerten');
$heat->clock += 30; $heat->Update();
check(count(end($heat->visualizationMessages)['channels'][0]['forecast']['points']) === 1, 'Erst der nächste echte Abruf startet die neue Messreihe');
$heat->properties['TileControls'] = false;
check(!tileRequest($heat, ['action' => 'forecastReset', 'number' => 1])['ok'], 'Gesperrte Kachelbedienung verhindert auch den Verlauf-Reset');
$heat->properties['TileControls'] = true;
$heat->properties['ForecastChart'] = false; $heat->Update();
check(end($heat->visualizationMessages)['channels'][0]['forecast']['points'] === [], 'Ausgeschaltetes Diagramm überträgt keine Verlaufspunkte');
$heat->properties['ForecastChart'] = true;
$heat->deviceData['channel'][0]['temp'] = 999.0; $heat->Update();
check($heat->buffers['Forecast_1'] === '', 'Ungültiger Fühler verwirft seinen Messverlauf');
$heat->deviceData['channel'][0]['temp'] = 72.0; $heat->Update();
check(count(end($heat->visualizationMessages)['channels'][0]['forecast']['points']) === 1, 'Wieder angeschlossener Fühler beginnt mit frischem Verlauf');
$heat->properties['ForecastEnabled'] = false; $heat->Update();
check(end($heat->visualizationMessages)['channels'][0]['forecast']['status'] === 'disabled' && $heat->buffers['Forecast_1'] === '', 'Deaktivierte Prognose verwirft interne Messreihen und liefert keine Berechnung');
$heat->properties['ForecastEnabled'] = true;
$heat->properties['ForecastWindow'] = 1; $heat->ApplyChanges();
check($heat->status === 201 && $heat->timers['Poll'] === 0, 'Ungültiges Trendfenster wird als Konfigurationsfehler abgefangen');

// Vorschau aus tatsächlichen, synthetisch gespeisten Modulabrufen – ohne Wartezeiten.
$forecastPreview = new ForecastTestDevice(false);
$forecastPreview->properties['TileTheme'] = 'dark';
$forecastPreview->properties['TilePrimary'] = 13;
$forecastPreview->deviceData['channel'][0]['max'] = 150.0;
$forecastPreview->deviceData['channel'][1]['max'] = 75.0;
$forecastPreview->deviceData['channel'][2]['max'] = 150.0;
$previewTime = time() - 600;
for ($second = 0; $second <= 600; $second += 30) {
    $forecastPreview->clock = $previewTime + $second;
    $forecastPreview->deviceData['channel'][1]['temp'] = 57.3 + $second / 60 * 0.3;
    $forecastPreview->Update();
}
$previewData = end($forecastPreview->visualizationMessages);
check($previewData['channels'][1]['forecast']['status'] === 'estimated' && count($forecastPreview->variables) === 0, 'Kachelvorschau enthält einen tatsächlich berechneten Trend aus Testdaten ohne Hilfsvariablen');
file_put_contents(__DIR__ . '/tile-state.generated.json', WLTApi::encode($previewData));
file_put_contents(__DIR__ . '/tile.generated.html', $forecastPreview->GetVisualizationTile());
