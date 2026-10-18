<?php

declare(strict_types=1);

/** Local WLANThermo ESP32 HTTP API. No Symcon dependencies. */
final class WLTApi
{
    public static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public static function object(string $body): array
    {
        $object = json_decode($body, false, 64, JSON_THROW_ON_ERROR);
        if (!$object instanceof stdClass) {
            throw new RuntimeException('JSON-Objekt erwartet.');
        }
        return json_decode($body, true, 64, JSON_THROW_ON_ERROR);
    }

    public static function baseURL(string $url): string
    {
        $url = rtrim(trim($url), '/');
        if (strpos($url, '://') === false) {
            $url = 'http://' . $url;
        }
        $p = parse_url($url);
        if ($p === false || !in_array($p['scheme'] ?? '', ['http', 'https'], true) || empty($p['host']) ||
            isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment']) || !empty($p['path'])) {
            throw new InvalidArgumentException('Nur IP/Hostname mit optionalem http(s):// und Port eingeben, ohne Pfad oder Zugangsdaten.');
        }
        return $url;
    }

    public static function request(string $base, string $path, ?array $payload, string $user, string $password, int $timeout): string
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('PHP-cURL wird benötigt.');
        }
        $ch = curl_init(self::baseURL($base) . $path);
        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => ['Accept: application/json, text/plain', 'Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2];
        if ($password !== '') {
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC | CURLAUTH_DIGEST;
            $options[CURLOPT_USERPWD] = $user . ':' . $password;
        }
        if ($payload !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = self::encode($payload);
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch);
        curl_close($ch);
        if ($body === false) {
            throw new RuntimeException('HTTP-Verbindung fehlgeschlagen (cURL ' . $error . ').');
        }
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException('HTTP ' . $code . ($code === 401 ? ': Admin-Zugangsdaten prüfen.' : ' beim Abruf von ' . $path));
        }
        return $body;
    }

    public static function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (preg_match('/pass|token|secret|user_key|chat_id|ssid/i', (string) $key)) {
                $data[$key] = '[ausgeblendet]';
            } elseif (is_array($value)) {
                $data[$key] = self::redact($value);
            }
        }
        return $data;
    }

    public static function finiteNumber(mixed $v, float $min, float $max): float
    {
        if ((!is_int($v) && !is_float($v)) || !is_finite((float) $v) || $v < $min || $v > $max) {
            throw new InvalidArgumentException('Zahl außerhalb des erlaubten Bereichs: ' . $min . ' … ' . $max);
        }
        return (float) $v;
    }

    public static function integer(mixed $v, int $min, int $max): int
    {
        if (!is_int($v) || $v < $min || $v > $max) {
            throw new InvalidArgumentException('Ganzzahl außerhalb des erlaubten Bereichs.');
        }
        return $v;
    }

    public static function text(mixed $v, int $length): string
    {
        if (!is_string($v) || preg_match('//u', $v) !== 1 || preg_match_all('/./us', $v) > $length || preg_match('/[\x00-\x1f]/', $v)) {
            throw new InvalidArgumentException('Ungültiger Text (maximal ' . $length . ' Zeichen).');
        }
        return $v;
    }

    public static function item(array $items, string $key, mixed $id): array
    {
        foreach ($items as $item) {
            if (is_array($item) && isset($item[$key]) && (string) $item[$key] === (string) $id) {
                return $item;
            }
        }
        throw new InvalidArgumentException('Gerät/Kanal/Profil nicht vorhanden.');
    }

    public static function verify(array $actual, array $expected): void
    {
        foreach ($expected as $key => $v) {
            if (!array_key_exists($key, $actual)) {
                throw new RuntimeException('Rücklesen fehlgeschlagen: ' . $key);
            }
            $a = $actual[$key];
            if (is_bool($v) && in_array($a, [0, 1], true)) { $a = (bool) $a; }
            $matches = is_float($v) || is_int($v) ? is_numeric($a) && abs((float) $a - $v) < 0.011 : $a === $v;
            if (!$matches) {
                throw new RuntimeException('Gerät hat den Wert nicht übernommen: ' . $key);
            }
        }
    }
}
