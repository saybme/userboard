<?php
/**
 * Standalone HTML-демо генератора номеров.
 * Подключается маршрутом /dev/license-plate при app.debug=true.
 *
 * @var string|null $generateUrl URL эндпоинта генерации (если задан роутом)
 */
$generateUrl = $generateUrl ?? 'generate';
?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Генератор номерных знаков — демо</title>
<style>
  :root {
    --bg: #1a1d23;
    --panel: #242830;
    --text: #e8eaed;
    --muted: #9aa0a6;
    --accent: #3d8bfd;
    --border: #3c4048;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; font-family: "Segoe UI", system-ui, sans-serif;
    background: var(--bg); color: var(--text); min-height: 100vh;
  }
  .wrap { max-width: 960px; margin: 0 auto; padding: 24px 16px 48px; }
  h1 { font-size: 1.35rem; font-weight: 600; margin: 0 0 4px; }
  .sub { color: var(--muted); font-size: 0.9rem; margin-bottom: 24px; }
  form {
    display: grid; gap: 12px;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    background: var(--panel); padding: 16px; border-radius: 10px;
    border: 1px solid var(--border);
  }
  label { display: flex; flex-direction: column; gap: 4px; font-size: 0.8rem; color: var(--muted); }
  input, select {
    background: var(--bg); color: var(--text); border: 1px solid var(--border);
    border-radius: 6px; padding: 8px 10px; font-size: 0.95rem;
  }
  .actions { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
  button {
    background: var(--accent); color: #fff; border: 0; border-radius: 6px;
    padding: 10px 16px; font-size: 0.9rem; cursor: pointer; font-weight: 600;
  }
  button.secondary { background: transparent; border: 1px solid var(--border); color: var(--text); }
  button:disabled { opacity: 0.5; cursor: wait; }
  .preview {
    margin-top: 24px; background: var(--panel); border: 1px solid var(--border);
    border-radius: 10px; padding: 20px; min-height: 140px;
    display: flex; align-items: center; justify-content: center;
  }
  .preview img, .preview object { max-width: 100%; height: auto; background: transparent; }
  .err {
    margin-top: 12px; padding: 10px 12px; border-radius: 6px;
    background: #3b1d1d; color: #ffb4b4; display: none;
  }
  .err.show { display: block; }
  .meta { margin-top: 8px; font-size: 0.8rem; color: var(--muted); }
</style>
</head>
<body>
<div class="wrap">
  <h1>Генератор российских номерных знаков</h1>
  <p class="sub">SVG / PNG · типы car / trailer / moto · только для отладки (debug)</p>

  <form id="form">
    <label>Тип
      <select name="type" id="type">
        <option value="car">Легковой (тип 1)</option>
        <option value="trailer">Прицеп (тип 2)</option>
        <option value="moto">Мотоцикл (тип 4)</option>
      </select>
    </label>
    <label>Номер
      <input name="number" id="number" value="А123ВС" autocomplete="off">
    </label>
    <label>Регион
      <input name="region" id="region" value="125" autocomplete="off">
    </label>
    <label>Формат
      <select name="format" id="format">
        <option value="svg">SVG</option>
        <option value="png">PNG</option>
      </select>
    </label>
    <label>Ширина PNG (px)
      <input name="width" id="width" type="number" value="760" min="100" max="4000">
    </label>
    <label>Исполнение (moto)
      <select name="execution" id="execution">
        <option value="1">1 — 190×145</option>
        <option value="2">2 — 190×175</option>
      </select>
    </label>
    <label>Флаг
      <select name="flag" id="flag">
        <option value="1">Да</option>
        <option value="0">Нет</option>
      </select>
    </label>
    <div class="actions">
      <button type="submit" id="btn">Сгенерировать</button>
      <button type="button" class="secondary" id="download">Скачать</button>
    </div>
  </form>

  <div class="err" id="err"></div>
  <div class="preview" id="preview"><span class="meta">Нажмите «Сгенерировать»</span></div>
  <p class="meta" id="info"></p>
</div>
<script>
(function () {
  const generateUrl = <?= json_encode($generateUrl, JSON_UNESCAPED_UNICODE) ?>;
  const form = document.getElementById('form');
  const preview = document.getElementById('preview');
  const err = document.getElementById('err');
  const info = document.getElementById('info');
  const btn = document.getElementById('btn');
  const typeEl = document.getElementById('type');
  const numberEl = document.getElementById('number');
  let lastBlob = null;
  let lastMime = 'image/svg+xml';
  let lastName = 'plate.svg';

  const defaults = {
    car: 'А123ВС',
    trailer: 'АВ1234',
    moto: '1234АВ'
  };
  typeEl.addEventListener('change', () => {
    numberEl.value = defaults[typeEl.value] || numberEl.value;
  });

  function showError(msg) {
    err.textContent = msg || 'Ошибка';
    err.classList.add('show');
  }
  function clearError() {
    err.classList.remove('show');
    err.textContent = '';
  }

  async function generate(download) {
    clearError();
    btn.disabled = true;
    const fd = new FormData(form);
    const params = new URLSearchParams();
    for (const [k, v] of fd.entries()) params.set(k, v);

    try {
      const res = await fetch(generateUrl + '?' + params.toString(), {
        method: 'GET',
        headers: { 'Accept': 'image/*, application/json' }
      });
      const ct = res.headers.get('content-type') || '';
      if (!res.ok) {
        let msg = 'HTTP ' + res.status;
        if (ct.includes('json')) {
          const j = await res.json();
          msg = j.error || msg;
        }
        throw new Error(msg);
      }
      const blob = await res.blob();
      lastBlob = blob;
      lastMime = blob.type || (params.get('format') === 'png' ? 'image/png' : 'image/svg+xml');
      const ext = lastMime.includes('png') ? 'png' : 'svg';
      lastName = (params.get('number') || 'plate') + '_' + (params.get('region') || '') + '.' + ext;

      const url = URL.createObjectURL(blob);
      preview.innerHTML = '';
      const img = document.createElement('img');
      img.alt = 'Номерной знак';
      img.src = url;
      preview.appendChild(img);
      info.textContent = lastMime + ' · ' + blob.size + ' байт';

      if (download) {
        const a = document.createElement('a');
        a.href = url;
        a.download = lastName;
        a.click();
      }
    } catch (e) {
      showError(e.message || String(e));
    } finally {
      btn.disabled = false;
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    generate(false);
  });
  document.getElementById('download').addEventListener('click', () => {
    if (lastBlob) {
      const url = URL.createObjectURL(lastBlob);
      const a = document.createElement('a');
      a.href = url;
      a.download = lastName;
      a.click();
    } else {
      generate(true);
    }
  });
})();
</script>
</body>
</html>
