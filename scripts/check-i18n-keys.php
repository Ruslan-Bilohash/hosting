<?php
declare(strict_types=1);

function keys(string $f): array
{
    $a = include $f;
    return array_keys($a);
}

$files = [
    'public-uk' => __DIR__ . '/../lang/uk.php',
    'public-en' => __DIR__ . '/../lang/en.php',
    'public-no' => __DIR__ . '/../lang/no.php',
    'panel-uk' => __DIR__ . '/../lang/panel-uk.php',
    'panel-en' => __DIR__ . '/../lang/panel-en.php',
    'panel-no' => __DIR__ . '/../lang/panel-no.php',
];

$all = [];
foreach ($files as $k => $f) {
    $all[$k] = keys($f);
}

$pairs = [
    ['public-uk', 'public-en'],
    ['public-uk', 'public-no'],
    ['panel-uk', 'panel-en'],
    ['panel-uk', 'panel-no'],
];

foreach ($pairs as [$ref, $tgt]) {
    $miss = array_values(array_diff($all[$ref], $all[$tgt]));
    $extra = array_values(array_diff($all[$tgt], $all[$ref]));
    echo "=== $ref -> $tgt: missing " . count($miss) . ", extra " . count($extra) . " ===\n";
    if ($miss) {
        echo implode("\n", $miss) . "\n";
    }
    if ($extra) {
        echo "EXTRA:\n" . implode("\n", $extra) . "\n";
    }
    echo "\n";
}