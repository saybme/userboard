<?php namespace Saybme\Ub\Classes\LicensePlate;

use Saybme\Ub\Classes\LicensePlate\Export\PngExporter;

/**
 * Публичный API генератора российских регистрационных знаков (SVG/PNG).
 *
 * Примеры:
 *   $g = new LicensePlateGenerator();
 *   $svg = $g->generate(type: 'car', number: 'А123ВС', region: '125', format: 'svg');
 *   $png = $g->generate(type: 'moto', number: '1234АВ', region: '125', format: 'png', width: 760);
 *   $svg = $g->generate(type: 'trailer', number: 'АВ1234', region: '125', format: 'svg');
 */
class LicensePlateGenerator
{
    private PlateValidator $validator;
    private PlateRenderer $renderer;
    private PngExporter $pngExporter;

    public function __construct(
        ?PlateValidator $validator = null,
        ?PlateRenderer $renderer = null,
        ?PngExporter $pngExporter = null
    ) {
        $this->validator = $validator ?? new PlateValidator();
        $this->renderer = $renderer ?? new PlateRenderer();
        $this->pngExporter = $pngExporter ?? new PngExporter($this->renderer);
    }

    /**
     * Универсальная точка входа.
     *
     * Поддерживает:
     * - generate(array $options)
     * - generate(string $type, string $number, string $region, string $format = 'svg', ?int $width = null, int $execution = 1, bool $flag = true)
     * - именованные аргументы PHP 8 (`type:`, `number:`, `region:`, …)
     *
     * @param array<string,mixed>|string $type тип или массив options
     * @return string SVG markup или бинарный PNG
     */
    public function generate(
        array|string $type,
        ?string $number = null,
        ?string $region = null,
        string $format = 'svg',
        ?int $width = null,
        int $execution = 1,
        bool $flag = true,
        bool $strictRegion = false
    ): string {
        $opts = $this->normalizeArgs($type, $number, $region, $format, $width, $execution, $flag, $strictRegion);

        $parsed = $this->validator->validate(
            (string) $opts['type'],
            (string) $opts['number'],
            (string) $opts['region'],
            (int) $opts['execution'],
            (bool) $opts['flag'],
            (bool) $opts['strictRegion']
        );

        $format = strtolower((string) $opts['format']);
        if ($format === 'svg') {
            return $this->renderer->renderSvg($parsed);
        }
        if ($format === 'png') {
            $w = $opts['width'] !== null ? (int) $opts['width'] : null;
            return $this->pngExporter->export($parsed, $w);
        }

        throw new PlateException('Формат должен быть svg или png (получено: «' . $opts['format'] . '»).');
    }

    public function toSvg(
        string $type,
        string $number,
        string $region,
        int $execution = 1,
        bool $flag = true,
        bool $strictRegion = false
    ): string {
        return $this->generate([
            'type' => $type,
            'number' => $number,
            'region' => $region,
            'format' => 'svg',
            'execution' => $execution,
            'flag' => $flag,
            'strictRegion' => $strictRegion,
        ]);
    }

    public function toPng(
        string $type,
        string $number,
        string $region,
        ?int $width = null,
        int $execution = 1,
        bool $flag = true,
        bool $strictRegion = false
    ): string {
        return $this->generate([
            'type' => $type,
            'number' => $number,
            'region' => $region,
            'format' => 'png',
            'width' => $width,
            'execution' => $execution,
            'flag' => $flag,
            'strictRegion' => $strictRegion,
        ]);
    }

    /**
     * Сохранить в файл. Расширение/format определяют тип.
     *
     * @param array<string,mixed>|null $options если null — используются остальные аргументы
     */
    public function save(
        string $path,
        array|string|null $typeOrOptions = null,
        ?string $number = null,
        ?string $region = null,
        ?string $format = null,
        ?int $width = null,
        int $execution = 1,
        bool $flag = true
    ): string {
        if (is_array($typeOrOptions)) {
            $opts = $typeOrOptions;
            if ($format !== null) {
                $opts['format'] = $format;
            }
            if ($width !== null) {
                $opts['width'] = $width;
            }
        } elseif (is_string($typeOrOptions) && $number !== null && $region !== null) {
            $ext = $format ?? (str_ends_with(strtolower($path), '.png') ? 'png' : 'svg');
            $opts = [
                'type' => $typeOrOptions,
                'number' => $number,
                'region' => $region,
                'format' => $ext,
                'width' => $width,
                'execution' => $execution,
                'flag' => $flag,
            ];
        } else {
            throw new PlateException('save(): укажите параметры номера или массив options.');
        }

        if (!isset($opts['format'])) {
            $opts['format'] = str_ends_with(strtolower($path), '.png') ? 'png' : 'svg';
        }

        $data = $this->generate($opts);
        $dir = dirname($path);
        if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new PlateException('Не удалось создать каталог: ' . $dir);
            }
        }
        if (file_put_contents($path, $data) === false) {
            throw new PlateException('Не удалось записать файл: ' . $path);
        }
        return $path;
    }

    /**
     * @param array<string,mixed>|string $type
     * @return array{type:string,number:string,region:string,format:string,width:?int,execution:int,flag:bool,strictRegion:bool}
     */
    private function normalizeArgs(
        array|string $type,
        ?string $number,
        ?string $region,
        string $format,
        ?int $width,
        int $execution,
        bool $flag,
        bool $strictRegion
    ): array {
        if (is_array($type)) {
            $o = $type;
            return [
                'type' => (string) ($o['type'] ?? ''),
                'number' => (string) ($o['number'] ?? $o['num'] ?? ''),
                'region' => (string) ($o['region'] ?? $o['reg'] ?? ''),
                'format' => (string) ($o['format'] ?? 'svg'),
                'width' => isset($o['width']) ? (int) $o['width'] : null,
                'execution' => (int) ($o['execution'] ?? 1),
                'flag' => (bool) ($o['flag'] ?? true),
                'strictRegion' => (bool) ($o['strictRegion'] ?? false),
            ];
        }

        if ($number === null || $region === null) {
            throw new PlateException('Укажите type, number и region.');
        }

        return [
            'type' => $type,
            'number' => $number,
            'region' => $region,
            'format' => $format,
            'width' => $width,
            'execution' => $execution,
            'flag' => $flag,
            'strictRegion' => $strictRegion,
        ];
    }
}
