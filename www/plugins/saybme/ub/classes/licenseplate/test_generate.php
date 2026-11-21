<?php
declare(strict_types=1);

$base = __DIR__;
spl_autoload_register(static function (string $class) use ($base): void {
    $prefix = 'Saybme\\Ub\\Classes\\LicensePlate\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = $base . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
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

function expect_ok(string $name, callable $fn): void
{
    global $ok, $fail;
    try {
        $fn();
        echo "[OK]   {$name}\n";
        $ok++;
    } catch (Throwable $e) {
        echo "[FAIL] {$name}: " . $e->getMessage() . "\n";
        $fail++;
    }
}

$cases = [
    ['car', 'А123ВС', '25'],
    ['car', 'А888АА', '00'],
    ['car', 'М333МН', '125'],
    ['trailer', 'АВ1234', '25'],
    ['trailer', 'АВ1234', '125'],
    ['moto', '1234АВ', '25'],
    ['moto', '1234АВ', '125'],
];

foreach ($cases as [$type, $number, $region]) {
    expect_ok("{$type} {$number} {$region}", function () use ($g, $outDir, $type, $number, $region) {
        $svg = $g->toSvg($type, $number, $region);
        if (!str_starts_with($svg, '<?xml')) {
            throw new RuntimeException('no xml decl');
        }
        if (!str_contains($svg, 'viewBox=')) {
            throw new RuntimeException('no viewBox');
        }
        if (!str_contains($svg, '<path ') && !str_contains($svg, '<rect ')) {
            throw new RuntimeException('no frame');
        }
        if (!str_contains($svg, '<tspan ')) {
            throw new RuntimeException('no text slots');
        }
        if (preg_match('/imagick|gd_/i', $svg)) {
            throw new RuntimeException('gd/imagick leak');
        }
        if (!str_contains($svg, 'RoadNumbers')) {
            throw new RuntimeException('expected RoadNumbers font');
        }
        // XSS: no raw user text nodes beyond paths
        if (str_contains($svg, '<script') || str_contains($svg, 'javascript:')) {
            throw new RuntimeException('xss');
        }
        $safe = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', "{$type}_{$number}_{$region}") ?: 'plate';
        file_put_contents($outDir . "/{$safe}.svg", $svg);
    });
}

expect_ok('reject bad letter', function () use ($g) {
    try {
        $g->toSvg('car', 'Я123ВС', '25');
        throw new RuntimeException('expected exception');
    } catch (PlateException $e) {
        // ok
    }
});

expect_ok('reject png format', function () use ($g) {
    try {
        $g->generate('car', 'А123ВС', '25', 'png');
        throw new RuntimeException('expected exception');
    } catch (PlateException $e) {
        // ok
    }
});

expect_ok('reject html injection in number', function () use ($g) {
    try {
        $g->toSvg('car', '<script>A123BC', '25');
        throw new RuntimeException('expected exception');
    } catch (PlateException $e) {
        // ok
    }
});

echo "\n=== {$ok} ok, {$fail} fail ===\n";
exit($fail > 0 ? 1 : 0);
