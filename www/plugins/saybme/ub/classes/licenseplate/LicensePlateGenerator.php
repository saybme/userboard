<?php namespace Saybme\Ub\Classes\LicensePlate;

/**
 * SVG-генератор госномеров РФ (только SVG, без Imagick/GD/шрифтов ОС).
 */
class LicensePlateGenerator
{
    private PlateValidator $validator;
    private SvgPlateBuilder $builder;

    public function __construct(?PlateValidator $validator = null, ?SvgPlateBuilder $builder = null)
    {
        $this->validator = $validator ?? new PlateValidator();
        $this->builder = $builder ?? new SvgPlateBuilder();
    }

    /**
     * @param array<string,mixed>|string $type
     */
    public function generate(
        array|string $type,
        ?string $number = null,
        ?string $region = null,
        string $format = 'svg'
    ): string {
        if (is_array($type)) {
            $opts = $type;
            $type = (string) ($opts['type'] ?? 'car');
            $number = (string) ($opts['number'] ?? '');
            $region = (string) ($opts['region'] ?? '');
            $format = strtolower((string) ($opts['format'] ?? 'svg'));
        }

        $format = strtolower($format);
        if ($format !== 'svg') {
            throw new PlateException('Поддерживается только format=svg.');
        }

        $parsed = $this->validator->parse((string) $type, (string) $number, (string) $region);
        return $this->builder->build($parsed);
    }

    public function toSvg(string $type, string $number, string $region): string
    {
        return $this->generate($type, $number, $region, 'svg');
    }

    public function save(string $path, string $type, string $number, string $region): string
    {
        $svg = $this->toSvg($type, $number, $region);
        if (!str_ends_with(strtolower($path), '.svg')) {
            $path .= '.svg';
        }
        if (file_put_contents($path, $svg) === false) {
            throw new PlateException('Не удалось сохранить SVG.');
        }
        return $path;
    }
}
