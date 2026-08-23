<?php
/**
 * Pure PHP password_verify() benchmark.
 *
 * This intentionally bypasses CodeIgniter, Nginx, PHP-FPM, sessions and DB.
 * Run inside the PHP container, for example:
 *
 * docker exec e-ujian-php php tests/load/php/benchmark-password-verify.php \
 *   'password' '$2y$10$...'
 */

$password = $argv[1] ?? '';
$hash = $argv[2] ?? '';
$iterations = isset($argv[3]) ? max(1, (int) $argv[3]) : 1;

if ($password === '' || $hash === '') {
    fwrite(STDERR, "Usage: php benchmark-password-verify.php <password> <hash> [iterations]\n");
    exit(2);
}

$durations = [];
$success = 0;

for ($i = 0; $i < $iterations; $i++) {
    $start = hrtime(true);
    $valid = password_verify($password, $hash);
    $durationMs = (hrtime(true) - $start) / 1_000_000;

    $durations[] = $durationMs;
    $success += $valid ? 1 : 0;
}

sort($durations, SORT_NUMERIC);
$count = count($durations);
$sum = array_sum($durations);
$avg = $sum / $count;

$percentile = static function (array $values, float $percentile): float {
    $count = count($values);
    if ($count === 1) {
        return (float) $values[0];
    }

    $rank = ($percentile / 100) * ($count - 1);
    $lower = (int) floor($rank);
    $upper = (int) ceil($rank);

    if ($lower === $upper) {
        return (float) $values[$lower];
    }

    $weight = $rank - $lower;
    return $values[$lower] + (($values[$upper] - $values[$lower]) * $weight);
};

$result = [
    'timestamp' => date(DATE_ATOM),
    'php_version' => PHP_VERSION,
    'password_algorithm' => password_get_info($hash)['algoName'] ?? 'unknown',
    'iterations' => $count,
    'success' => $success,
    'failure' => $count - $success,
    'avg_ms' => round($avg, 3),
    'min_ms' => round($durations[0], 3),
    'p50_ms' => round($percentile($durations, 50), 3),
    'p95_ms' => round($percentile($durations, 95), 3),
    'p99_ms' => round($percentile($durations, 99), 3),
    'max_ms' => round($durations[$count - 1], 3),
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
