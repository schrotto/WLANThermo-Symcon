<?php

declare(strict_types=1);
require __DIR__ . '/../libs/Api.php';
$base = $argv[1];
$count = 0;
function check(bool $ok, string $msg): void {
    global $count;
    if (!$ok) { throw new RuntimeException($msg); }
    echo 'HTTP OK ' . ++$count . ': ' . $msg . PHP_EOL;
}
foreach (['/ok', '/auth', '/digest'] as $path) {
    check(WLTApi::object(WLTApi::request($base, $path, null, 'admin', 'secret', 2))['ok'], 'GET ' . $path);
}
check(trim(WLTApi::request($base, '/post', ['name' => 'Grillfühler', 'min' => 10.5], '', '', 2)) === 'true', 'JSON POST with UTF-8');
foreach (['/denied', '/redirect', '/missing', '/timeout'] as $path) {
    $caught = false;
    try { WLTApi::request($base, $path, null, '', '', 1); } catch (RuntimeException $e) { $caught = true; }
    check($caught, 'Handle ' . $path);
}
echo $count . " HTTP assertions passed.\n";
