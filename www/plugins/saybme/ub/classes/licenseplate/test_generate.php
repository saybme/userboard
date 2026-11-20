<?php
/**
 * CLI-тест генератора номеров.
 * Запуск: php test_generate.php
 */

declare(strict_types=1);

$base = __DIR__;

spl_autoload_register(function (string $class) use ($base): void {
    $prefix = 'Saybme\\Ub\\Classes\\LicensePlate\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = $base . DIRECTORY_SEPARATOR . $rel . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use Saybme\Ub\Classes\LicensePlate\LicensePlateGenerator;
use Saybme\Ub\Classes\LicensePlate\PlateException;

$outDir = $base . DIRECTORY_SEPARATOR . 'test_output';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$g = new LicensePlateGenerator();
$ok = 0;
$fail = 0;

function case_name(string $type, string $number, string $region, string $format): string
{
    $safe = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $type . '_' . $number . '_' . $region) ?: 'plate';
    return $safe . '.' . $format;
}

/** @param callable():void $fn */
function expect_ok(string $label, callable $fn): void
{
    global $ok, $fail;
    try {
        $fn();
        echo "[OK]   {$label}\n";
        $ok++;
    } catch (Throwable $e) {
        echo "[FAIL] {$label}: " . $e->getMessage() . "\n";
        $fail++;
    }
}

/** @param callable():void $fn */
function expect_fail(string $label, callable $fn): void
{
    global $ok, $fail;
    try {
        $fn();
        echo "[FAIL] {$label}: ожидалось исключение\n";
        $fail++;
    } catch (PlateException $e) {
        echo "[OK]   {$label}: " . $e->getMessage() . "\n";
        $ok++;
    } catch (Throwable $e) {
        echo "[FAIL] {$label}: неожиданный тип " . get_class($e) . ': ' . $e->getMessage() . "\n";
        $fail++;
    }
}

$valid = [
    ['car', 'А123ВС', '25'],
    ['car', 'А123ВС', '125'],
    ['car', 'М777ММ', '777'],
    ['trailer', 'АВ1234', '25'],
    ['trailer', 'АВ1234', '125'],
    ['moto', '1234АВ', '25'],
    ['moto', '1234АВ', '125'],
];

foreach ($valid as [$type, $number, $region]) {
    expect_ok("SVG {$type} {$number} {$region}", function () use ($g, $outDir, $type, $number, $region) {
        $svg = $g->generate(type: $type, number: $number, region: $region, format: 'svg');
        if (!str_contains($svg, '<svg') || !str_contains($svg, 'viewBox')) {
            throw new RuntimeException('SVG некорректен');
        }
        if (str_contains($svg, 'Arial') || str_contains($svg, 'Roboto')) {
            throw new RuntimeException('В SVG найдены системные шрифты');
        }
        $path = $outDir . DIRECTORY_SEPARATOR . case_name($type, $number, $region, 'svg');
        file_put_contents($path, $svg);
    });

    expect_ok("PNG {$type} {$number} {$region}", function () use ($g, $outDir, $type, $number, $region) {
        $png = $g->generate(type: $type, number: $number, region: $region, format: 'png', width: 520);
        if (strlen($png) < 100 || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new RuntimeException('PNG сигнатура неверна');
        }
        $path = $outDir . DIRECTORY_SEPARATOR . case_name($type, $number, $region, 'png');
        file_put_contents($path, $png);
    });
}

// moto execution 2
expect_ok('SVG moto exec 2', function () use ($g, $outDir) {
    $svg = $g->generate([
        'type' => 'moto',
        'number' => '1234АВ',
        'region' => '125',
        'format' => 'svg',
        'execution' => 2,
    ]);
    file_put_contents($outDir . '/moto_exec2_125.svg', $svg);
});

// Latin lookalikes
expect_ok('Latin lookalikes A123BC → А123ВС', function () use ($g, $outDir) {
    $svg = $g->generate('car', 'A123BC', '77', 'svg');
    file_put_contents($outDir . '/car_latin_mapped_77.svg', $svg);
});

// Array + classic API
expect_ok('array API', function () use ($g) {
    $g->generate(['type' => 'car', 'number' => 'К001КК', 'region' => '01', 'format' => 'svg']);
});
expect_ok('positional API', function () use ($g) {
    $g->generate('trailer', 'НЕ1234', '99', 'svg');
});
expect_ok('toSvg / toPng / save', function () use ($g, $outDir) {
    $g->toSvg('car', 'Х999ХХ', '199');
    $g->toPng('car', 'Х999ХХ', '199', 400);
    $g->save($outDir . '/saved_car.svg', 'car', 'С123СТ', '178');
});

// Invalid
expect_fail('invalid car format', fn () => $g->generate('car', '123ABC', '25', 'svg'));
expect_fail('invalid trailer format', fn () => $g->generate('trailer', 'А123ВС', '25', 'svg'));
expect_fail('invalid moto format', fn () => $g->generate('moto', 'А123ВС', '25', 'svg'));
expect_fail('bad letter', fn () => $g->generate('car', 'Я123ВС', '25', 'svg'));
expect_fail('bad region 4 digits', fn () => $g->generate('car', 'А123ВС', '1234', 'svg'));
expect_fail('bad region letters', fn () => $g->generate('car', 'А123ВС', 'abcd', 'svg'));
expect_ok('region 5 → 05', fn () => $g->generate('car', 'А123ВС', '5', 'svg'));
expect_fail('unknown type', fn () => $g->generate('boat', 'А123ВС', '25', 'svg'));
expect_fail('strict unknown region', function () use ($g) {
    $g->generate([
        'type' => 'car',
        'number' => 'А123ВС',
        'region' => '999',
        'format' => 'svg',
        'strictRegion' => true,
    ]);
});
expect_ok('non-strict unknown region 999', function () use ($g, $outDir) {
    $svg = $g->generate([
        'type' => 'car',
        'number' => 'А123ВС',
        'region' => '999',
        'format' => 'svg',
        'strictRegion' => false,
    ]);
    file_put_contents($outDir . '/car_region_999.svg', $svg);
});

echo "\n=== Результат: {$ok} ok, {$fail} fail ===\n";
echo "Файлы: {$outDir}\n";
exit($fail > 0 ? 1 : 0);
