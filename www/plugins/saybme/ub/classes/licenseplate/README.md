# SVG-генератор госномеров РФ

Только SVG. Без Imagick, GD, системных шрифтов и PNG.

Контуры символов — векторные path из RoadNumbers 2.0 (GPL), встроены в `GlyphPaths.php`.

## Типы (ГОСТ Р 50577-2018)

| Код API | ГОСТ | Размер |
|--------|------|--------|
| `car` | тип 1 | 520×112 мм |
| `trailer` | тип 2 | 520×112 мм |
| `moto` | тип 4 | 190×145 мм |

## API

```php
$g = new \Saybme\Ub\Classes\LicensePlate\LicensePlateGenerator();
$svg = $g->toSvg('car', 'А888АА', '00');
$g->save('/tmp/plate.svg', 'moto', '1234АВ', '125');
```

## Тест / демо

```bash
php test_generate.php
```

Демо (при `app.debug`): `/dev/license-plate`
