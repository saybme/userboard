<?php namespace Saybme\Ub\Classes\LicensePlate;

use Saybme\Ub\Models\Carnumber;
use System\Models\File;
use Log;

/**
 * Генерация PNG гос. номера и прикрепление к объявлению (attachOne photo).
 */
class PlatePhotoService
{
    /** Ширина PNG по умолчанию для карточек сайта */
    public const DEFAULT_WIDTH = 1040;

    private LicensePlateGenerator $generator;

    public function __construct(?LicensePlateGenerator $generator = null)
    {
        $this->generator = $generator ?? new LicensePlateGenerator();
    }

    /**
     * Собрать данные номера из модели. null — если недостаточно данных.
     *
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

        $series = $this->joinParts($props['series'] ?? null, $item->series);
        $digits = $this->joinParts($props['number'] ?? null, $item->number);
        $region = $this->joinParts($props['region'] ?? null, $item->region);

        $region = preg_replace('/\D+/', '', (string) $region) ?? '';
        if ($region === '') {
            return null;
        }

        if ($typeId === 1) {
            // А + 123 + ВС
            $letter1 = mb_substr($series, 0, 1, 'UTF-8');
            $letters2 = mb_substr($series, 1, 2, 'UTF-8');
            $number = $letter1 . $digits . $letters2;
        } elseif ($typeId === 2) {
            // 1234 + АВ
            $number = $digits . $series;
        } else {
            // АВ + 1234
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

    /**
     * Сгенерировать и прикрепить photo. false — пропуск (нет данных / ошибка).
     */
    public function attachToCarnumber(Carnumber $item, ?int $width = null): bool
    {
        $payload = $this->extractFromCarnumber($item);
        if ($payload === null) {
            return false;
        }

        try {
            $png = $this->generator->toPng(
                $payload['type'],
                $payload['number'],
                $payload['region'],
                $width ?? self::DEFAULT_WIDTH
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
        $filename = 'gosnomer-' . $item->id . '-' . $safeName . '.png';

        $file = new File;
        $file->fromData($png, $filename);
        $file->is_public = true;
        $file->save();

        $item->photo = $file;
        $item->save();

        return true;
    }

    /**
     * Тип сайта (1 авто / 2 мото / 3 прицеп) → тип генератора.
     */
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
