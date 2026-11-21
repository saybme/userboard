<?php
/** @var string $generateUrl */
$generateUrl = $generateUrl ?? '/dev/license-plate/generate';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Генератор госномеров (SVG)</title>
  <style>
    :root { color-scheme: light; }
    body { font-family: Georgia, "Times New Roman", serif; max-width: 820px; margin: 2rem auto; padding: 0 1rem; background: #f3efe6; color: #1a1a1a; }
    h1 { font-size: 1.6rem; font-weight: 600; }
    label { display: block; margin-top: .9rem; font-size: .95rem; }
    input, select, button { font: inherit; font-size: 1rem; padding: .45rem .6rem; margin-top: .25rem; }
    button { cursor: pointer; background: #1a1a1a; color: #fff; border: 0; padding: .55rem 1rem; }
    button.secondary { background: #555; margin-left: .5rem; }
    .preview { margin-top: 1.5rem; background: #fff; border: 1px solid #ccc; padding: 1rem; overflow-x: auto; }
    .preview svg { max-width: 100%; height: auto; display: block; }
    .err { color: #a40000; margin-top: 1rem; white-space: pre-wrap; }
    .hint { color: #555; font-size: .9rem; margin-top: .4rem; }
  </style>
</head>
<body>
  <h1>Госномера РФ — SVG (ГОСТ Р 50577-2018)</h1>
  <p class="hint">Тип 1 — автомобиль, тип 2 — прицеп, тип 4 — мотоцикл. Только SVG, без PNG/шрифтов ОС.</p>
  <form id="f">
    <label>Тип
      <select name="type">
        <option value="car">Тип 1 — автомобиль</option>
        <option value="trailer">Тип 2 — прицеп</option>
        <option value="moto">Тип 4 — мотоцикл</option>
      </select>
    </label>
    <label>Номер
      <input name="number" value="А888АА" required autocomplete="off">
    </label>
    <label>Регион
      <input name="region" value="00" required autocomplete="off">
    </label>
    <div style="margin-top:1rem">
      <button type="submit">Показать</button>
      <button type="button" class="secondary" id="dl">Скачать SVG</button>
    </div>
  </form>
  <div class="err" id="err" hidden></div>
  <div class="preview" id="out"></div>
  <script>
    const url = <?= json_encode($generateUrl, JSON_UNESCAPED_SLASHES) ?>;
    const form = document.getElementById('f');
    const out = document.getElementById('out');
    const err = document.getElementById('err');
    let lastSvg = '';

    async function generate() {
      err.hidden = true;
      const q = new URLSearchParams(new FormData(form));
      q.set('format', 'svg');
      const res = await fetch(url + '?' + q.toString(), { headers: { 'Accept': 'image/svg+xml,application/json' } });
      const text = await res.text();
      if (!res.ok) {
        let msg = text;
        try { msg = JSON.parse(text).error || text; } catch (e) {}
        err.textContent = msg;
        err.hidden = false;
        out.innerHTML = '';
        lastSvg = '';
        return;
      }
      lastSvg = text;
      out.innerHTML = text;
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); generate(); });
    document.getElementById('dl').addEventListener('click', () => {
      if (!lastSvg) return;
      const blob = new Blob([lastSvg], { type: 'image/svg+xml;charset=utf-8' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'plate.svg';
      a.click();
      URL.revokeObjectURL(a.href);
    });
    generate();
  </script>
</body>
</html>
