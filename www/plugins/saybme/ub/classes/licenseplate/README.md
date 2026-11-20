# Генератор российских номерных знаков (SVG / PNG)

Самодостаточный PHP-модуль плагина `Saybme.Ub` для отрисовки регистрационных знаков по ГОСТ Р 50577 (типы 1, 2, 4).

**Namespace:** `Saybme\Ub\Classes\LicensePlate`

## Публичный API

```php
use Saybme\Ub\Classes\LicensePlate\LicensePlateGenerator;

$g = new LicensePlateGenerator();

// Именованные аргументы
$svg = $g->generate(type: 'car', number: 'А123ВС', region: '125', format: 'svg');
$png = $g->generate(type: 'moto', number: '1234АВ', region: '125', format: 'png', width: 760);
$svg = $g->generate(type: 'trailer', number: 'АВ1234', region: '125', format: 'svg');

// Массив
$svg = $g->generate(['type' => 'car', 'number' => 'А123ВС', 'region' => '77', 'format' => 'svg']);

// Позиционные
$svg = $g->generate('car', 'А123ВС', '125', 'svg');

// Хелперы
$g->toSvg('car', 'А123ВС', '125');
$g->toPng('car', 'А123ВС', '125', 800);
$g->save('/path/plate.svg', 'car', 'А123ВС', '125');
```

Параметры: `type`, `number`, `region`, `format` (`svg`|`png`), `width` (px для PNG), `execution` (1|2 для moto), `flag` (bool), `strictRegion` (bool, default false).

## Типы

| type | ГОСТ | Размер | Формат номера | Пример |
|------|------|--------|---------------|--------|
| `car` | тип 1 | 520×112 мм | LDDDLL + регион | А123ВС 125 |
| `trailer` | тип 2 | 520×112 мм | LLDDDD + регион | АВ1234 125 |
| `moto` | тип 4 | exec1: 190×145 мм; exec2: 190×175 мм | DDDDLL + регион | 1234АВ 125 |

Допустимые буквы: **А В Е К М Н О Р С Т У Х** (кириллица). Латинские визуальные аналоги (A B E K M H O P C T Y X) автоматически приводятся к кириллице.

## Геометрия

Единицы SVG user units = миллиметры (`viewBox` = габарит знака).

Ориентиры ГОСТ (табл. 1 / прил. А):

- окантовка **3 мм**;
- высота основных знаков типа 1/2: **76 мм**, толщина штриха ≥ **11 мм**;
- цифры региона: **45 мм** (2 знака), ~**36 мм** (3 знака);
- скругление углов ~**7.5 мм**;
- RUS и флаг РФ (бело-сине-красный) в панели региона.

Константы см. `Templates/AbstractPlate.php` и шаблоны типов.

## Честность по глифам и отступам

Контуры символов в `Glyphs/GlyphLibrary.php` — **векторные реконструкции** по пропорциям приложений Б/В (блочный FE-schrift-подобный стиль), **не** оцифрованные сканы официальных чертежей.

Межосевые/краевые отступы по фигурам А.1 / А.2 / А.6 / А.8 заданы по опубликованным габаритам 520×112 и 190×145 и высотам табл. 1 — их нужно **визуально сверить** с официальным стандартом при необходимости юридически точного макета.

Исполнение 2 мото-знака (190×175) использует приближённую компоновку RUS/флага.

## PNG

1. Если доступен **Imagick** — SVG → PNG.
2. Иначе **GD**: растеризация тех же layout-команд (мм→px), прозрачный фон вне скруглённой пластины, белое поле знака.

Внешние API и composer-зависимости не используются.

## Демо (только debug)

При `config('app.debug') === true` в `routes.php`:

- `GET /dev/license-plate` — HTML-форма
- `GET|POST /dev/license-plate/generate` — отдаёт `image/svg+xml` или `image/png`; ошибки валидации → `422` JSON `{ "error": "..." }`

При `debug=false` маршруты не регистрируются.

## CLI-тест

```bash
php classes/licenseplate/test_generate.php
```

Результаты пишутся в `classes/licenseplate/test_output/`.

## Требования

- PHP 8.2+
- расширения: `dom` / `xml` (стандартно), **`gd`** (обязательно для PNG без Imagick)
- опционально: `imagick`

## Структура

```
classes/licenseplate/
  LicensePlateGenerator.php
  PlateValidator.php
  PlateRenderer.php
  PlateTypes.php
  PlateException.php
  Glyphs/GlyphLibrary.php
  Templates/AbstractPlate.php
  Templates/CarPlate.php
  Templates/MotorcyclePlate.php
  Templates/TrailerPlate.php
  Export/PngExporter.php
  Regions/RegionCodes.php
  demo/index.php
  test_generate.php
  README.md
```
