<?php namespace Saybme\Ub\Classes\LicensePlate;

use Saybme\Ub\Models\Carnumber;
use System\Models\File;
use Log;

/**
 * Генерация SVG гос. номера и прикрепление к объявлению (attachOne photo).
 */
class PlatePhotoService
{
    private LicensePlateGenerator $generator;

    public function __construct(?LicensePlateGenerator $generator = null)
    {
        $this->generator = $generator ?? new LicensePlateGenerator();
    }

    /**
     * @return array{type:string,number:string,region:string}|null
     */
    public function extractFromCarnumber(Carnumber $item): ?array
    {
        $typeId = (int) $item->type;
        $generatorType = $this->mapSiteType($typeId);
        if ($generatorType === null) {
            return null;
        }

        $props = is_array($item->props) ? $item->props : [];

        $series = trim((string) ($item->series ?: $this->joinParts($props['series'] ?? null)));
        $digits = trim((string) ($item->number ?: $this->joinParts($props['number'] ?? null)));
        $region = trim((string) ($item->region ?: $this->joinParts($props['region'] ?? null)));

        $region = preg_replace('/\D+/', '', $region) ?? '';
        if ($region === '') {
            return null;
        }

        if ($typeId === 1) {
            $letter1 = mb_substr($series, 0, 1, 'UTF-8');
            $letters2 = mb_substr($series, 1, 2, 'UTF-8');
            $number = $letter1 . $digits . $letters2;
        } elseif ($typeId === 2) {
            $number = $digits . $series;
        } else {
            $number = $series . $digits;
        }

        $number = preg_replace('/\s+/u', '', (string) $number) ?? '';
        if ($number === '') {
            return null;
        }

        return [
            'type' => $generatorType,
            'number' => $number,
            'region' => $region,
        ];
    }

    public function fingerprint(Carnumber $item): string
    {
        $payload = $this->extractFromCarnumber($item);
        if ($payload === null) {
            return '';
        }
        return $payload['type'] . '|' . $payload['number'] . '|' . $payload['region'];
    }

    public function attachToCarnumber(Carnumber $item): bool
    {
        $payload = $this->extractFromCarnumber($item);
        if ($payload === null) {
            return false;
        }

        try {
            $svg = $this->generator->toSvg(
                $payload['type'],
                $payload['number'],
                $payload['region']
            );
        } catch (PlateException $e) {
            Log::info('PlatePhotoService: skip generate — ' . $e->getMessage(), [
                'carnumber_id' => $item->id,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('PlatePhotoService: generate failed', [
                'carnumber_id' => $item->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }

        $safeName = preg_replace('/[^\p{L}\p{N}\-_]+/u', '', $payload['number'] . '-' . $payload['region']) ?: 'plate';
        $filename = 'gosnomer-' . $item->id . '-' . $safeName . '.svg';

        if ($item->photo) {
            $item->photo->delete();
        }

        $file = new File;
        $file->fromData($svg, $filename);
        $file->is_public = true;
        $file->save();

        $item->photo = $file;

        $prev = Carnumber::$skipPlatePhotoRegen;
        Carnumber::$skipPlatePhotoRegen = true;
        try {
            $item->save();
        } finally {
            Carnumber::$skipPlatePhotoRegen = $prev;
        }

        return true;
    }

    public function mapSiteType(int $typeId): ?string
    {
        return match ($typeId) {
            1 => PlateTypes::CAR,
            2 => PlateTypes::MOTO,
            3 => PlateTypes::TRAILER,
            default => null,
        };
    }

    private function joinParts($value, $fallback = ''): string
    {
        if (is_array($value)) {
            ksort($value);
            $parts = [];
            foreach ($value as $part) {
                if ($part === null || $part === '') {
                    continue;
                }
                $parts[] = (string) $part;
            }
            return implode('', $parts);
        }

        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        return (string) $fallback;
    }
}
