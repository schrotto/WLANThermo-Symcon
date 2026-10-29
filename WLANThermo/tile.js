'use strict';
(() => {
  const $ = id => document.getElementById(id);
  let state = {}, selected = 0, pending = null, timeout = null;
  const probes = new Map(), pitmasters = new Map();
  const fmt = value => typeof value === 'number' && Number.isFinite(value) ? new Intl.NumberFormat('de-DE', {maximumFractionDigits:1}).format(value) : '—';
  const node = (tag, cls, text) => { const el = document.createElement(tag); if (cls) el.className = cls; if (text !== undefined) el.textContent = text; return el; };
  const announce = (message, error = false) => { $('notice').textContent = message; $('notice').className = error ? 'error' : ''; $('notice').hidden = !message; };
  const fraction = ch => state.config?.forecast !== false && Number.isFinite(ch.forecast?.progress) ? ch.forecast.progress : ch.valid && Number.isFinite(ch.max) && ch.max > 0 ? Math.max(0, Math.min(1, ch.temperature / ch.max)) : 0;
  const aboveMaximum = ch => ch.valid && Number.isFinite(ch.max) && ch.temperature > ch.max;
  const describe = ch => !ch.valid ? 'Kein aktueller Messwert' : !Number.isFinite(ch.max) ? 'Keine Obergrenze verfügbar' : ch.temperature > ch.max ? 'Obergrenze überschritten' : ch.temperature === ch.max ? 'Obergrenze erreicht' : 'Noch ' + fmt(ch.max - ch.temperature) + ' ' + state.unit + ' bis zur Obergrenze';
  const forecastText = forecast => {
    if (!forecast) return 'Temperaturverlauf wird gesammelt …';
    if (forecast.status === 'estimated' && Number.isFinite(forecast.seconds)) {
      if (forecast.seconds < 60) return 'Noch weniger als 1 Min.';
      const minutes = Math.max(1, Math.round(forecast.seconds / 60));
      const duration = minutes >= 60 ? Math.floor(minutes / 60) + ' Std. ' + (minutes % 60) + ' Min.' : minutes + ' Min.';
      return 'Noch ca. ' + duration;
    }
    return ({reached:'Obergrenze erreicht', learning:'Temperaturverlauf wird gesammelt …', stalled:'Temperaturplateau – keine Restzeitschätzung', cooling:'Temperatur fällt – keine Restzeitschätzung', unstable:'Schwankender Verlauf – keine verlässliche Restzeit', distant:'Ziel zu weit entfernt – noch keine verlässliche Restzeit', stale:'Messverlauf nicht aktuell', no_target:'Keine Obergrenze verfügbar', disabled:''})[forecast.status] || 'Noch keine verlässliche Restzeit';
  };
  function forecastElements(number) {
    const el = node('section', 'forecast'); el.setAttribute('aria-label', 'Restzeitschätzung und Temperaturverlauf');
    const title = node('p', 'forecast-title'), detail = node('p', 'forecast-detail');
    const svgNode = tag => document.createElementNS('http://www.w3.org/2000/svg', tag);
    const chart = svgNode('svg'); chart.setAttribute('viewBox', '0 0 320 104'); chart.setAttribute('role', 'img'); chart.classList.add('forecast-chart');
    const target = svgNode('line'), actual = svgNode('polyline'), predicted = svgNode('line');
    target.setAttribute('class', 'forecast-target'); actual.setAttribute('class', 'forecast-actual'); predicted.setAttribute('class', 'forecast-predicted');
    chart.append(target, actual, predicted);
    const legend = node('div', 'forecast-legend', 'Messverlauf · Trend gestrichelt · Obergrenze punktiert');
    const times = node('div', 'forecast-times'), startTime = node('span'), endTime = node('span'); times.append(startTime, endTime);
    const reset = node('button', 'forecast-reset', 'Verlauf neu starten'); reset.type = 'button'; reset.setAttribute('aria-label', 'Temperaturverlauf für Kanal ' + number + ' neu starten');
    reset.addEventListener('click', () => send('forecastReset', {number}));
    el.append(title, detail, chart, legend, times, reset);
    return {el, title, detail, chart, target, actual, predicted, legend, times, startTime, endTime, reset};
  }
  function renderForecast(ui, ch, cfg) {
    const forecast = ch.forecast, enabled = cfg.forecast !== false;
    ui.el.hidden = !enabled;
    if (!enabled) return;
    ui.title.textContent = forecastText(forecast);
    const estimated = forecast?.status === 'estimated' && Number.isFinite(forecast.eta);
    const details = [];
    if (estimated) details.push('Ziel voraussichtlich gegen ' + new Date(forecast.eta * 1000).toLocaleTimeString('de-DE', {hour:'2-digit', minute:'2-digit'}) + ' · Trend-Schätzung');
    if (Number.isFinite(forecast?.rate)) details.push((forecast.rate > 0 ? '+' : '') + new Intl.NumberFormat('de-DE', {maximumFractionDigits:2}).format(forecast.rate) + ' ' + state.unit + '/min');
    if (!details.length) details.push('Mindestens 3 Minuten gültige Messwerte erforderlich.');
    ui.detail.textContent = details.join(' · ');
    ui.reset.hidden = !cfg.controls; ui.reset.disabled = Boolean(pending) || !state.online;
    const points = (forecast?.points || []).filter(p => Array.isArray(p) && Number.isFinite(p[0]) && Number.isFinite(p[1]));
    const visible = cfg.forecastChart !== false && points.length >= 2 && Number.isFinite(ch.max);
    ui.chart.hidden = ui.legend.hidden = ui.times.hidden = !visible;
    if (!visible) return;
    const first = points[0], last = points[points.length - 1];
    const horizon = estimated ? Math.min(forecast.seconds, Math.max(300, last[0] - first[0])) : 0;
    const end = last[0] + horizon;
    const nextValue = estimated ? Math.min(ch.max, last[1] + forecast.rate * horizon / 60) : last[1];
    const values = points.map(p => p[1]).concat([ch.max, nextValue]);
    const bottom = Math.min(...values), top = Math.max(...values), pad = Math.max(0.5, (top - bottom) * 0.12);
    const x = time => 8 + 304 * (time - first[0]) / Math.max(1, end - first[0]);
    const y = value => 96 - 88 * (value - bottom + pad) / Math.max(1, top - bottom + 2 * pad);
    ui.actual.setAttribute('points', points.map(p => x(p[0]).toFixed(2) + ',' + y(p[1]).toFixed(2)).join(' '));
    for (const [key, val] of Object.entries({x1:8,x2:312,y1:y(ch.max),y2:y(ch.max)})) ui.target.setAttribute(key, val);
    ui.predicted.hidden = !estimated;
    ui.predicted.setAttribute('visibility', estimated ? 'visible' : 'hidden');
    if (estimated) for (const [key, val] of Object.entries({x1:x(last[0]),y1:y(last[1]),x2:x(end),y2:y(nextValue)})) ui.predicted.setAttribute(key, val);
    ui.chart.setAttribute('aria-label', 'Gemessener Temperaturverlauf bis ' + fmt(ch.temperature) + ' ' + state.unit + ', Obergrenze ' + fmt(ch.max) + ' ' + state.unit + (estimated ? ', gestrichelte Trendfortschreibung' : ''));
    const clock = time => new Date(time * 1000).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'});
    ui.startTime.textContent = clock(first[0]); ui.endTime.textContent = clock(end) + (estimated ? ' · Trend' : '');
  }
  function send(action, values = {}) {
    if (pending) return;
    if (typeof window.requestAction !== 'function') { announce('Bedienung ist nur in der verbundenen Symcon-Visualisierung verfügbar.', true); return; }
    const requestId = 'wlt_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
    pending = requestId;
    announce(action === 'refresh' ? 'Messwerte werden abgerufen …' : 'Änderung wird am Gerät geprüft …');
    render();
    timeout = setTimeout(() => { pending = null; announce('Keine Bestätigung erhalten. Vor erneutem Bedienen bitte aktualisieren.', true); render(); }, state.config?.actionTimeout || 35000);
    try { window.requestAction('TileAction', JSON.stringify({requestId, action, ...values})); }
    catch (error) { clearTimeout(timeout); pending = null; announce('Anfrage konnte nicht gesendet werden.', true); render(); }
  }
  function field(label, type, min, max) {
    const wrapper = node('label', 'field'); wrapper.append(node('span', '', label));
    const input = node('input'); input.type = type; input.step = '1'; input.min = String(min); input.max = String(max); input.required = true;
    wrapper.append(input); return {wrapper, input};
  }
  function probe(ch) {
    const el = node('article', 'probe'); el.tabIndex = 0; el.setAttribute('aria-label', 'Kanal ' + ch.number);
    const head = node('div', 'probe-head'), names = node('div'), kicker = node('div', 'probe-kicker'), name = node('h3');
    names.append(kicker, name); head.append(names, node('span', 'probe-status'));
    const value = node('div', 'probe-value'), label = node('div', 'probe-label'), rail = node('div', 'rail'), bar = node('span'), limits = node('div', 'rail-labels'), high = node('span');
    rail.append(bar); limits.append(high);
    const details = node('details'), summary = node('summary', '', 'Obergrenze bearbeiten'), form = node('form', 'limits-form');
    const max = field('Obergrenze', 'number', -30, 999), submit = node('button', '', 'Obergrenze übernehmen'); submit.type = 'submit';
    form.append(max.wrapper, submit); details.append(summary, form);
    const forecast = forecastElements(ch.number);
    el.append(head, value, label, rail, limits, forecast.el, details);
    const choose = () => { if (state.config?.hero && !state.config?.primary) { selected = ch.number; render(); } };
    head.addEventListener('click', choose); value.addEventListener('click', choose);
    el.addEventListener('keydown', event => { if (event.target === el && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); choose(); } });
    form.addEventListener('submit', event => { event.preventDefault(); if (form.reportValidity()) send('channel', {number:ch.number, max:Number(max.input.value)}); });
    return {el, kicker, name, value, label, bar, rail, high, details, forecast, max:max.input, submit};
  }
  function pitmaster(pm) {
    const el = node('article', 'pitmaster'), heading = node('div', 'pm-heading'), title = node('h3', '', 'Pitmaster ' + pm.id), meta = node('span', 'pm-meta'); heading.append(title, meta);
    const form = node('form', 'pm-fields'), label = node('label', 'field'); label.append(node('span', '', 'Betriebsart'));
    const mode = node('select'); for (const [value, caption] of [['off','Aus'],['auto','Automatisch'],['manual','Manuell']]) { const option = node('option', '', caption); option.value = value; mode.append(option); }
    label.append(mode);
    const target = field('Solltemperatur', 'number', 0, 999), output = field('Stellwert in %', 'number', 0, 100), submit = node('button', 'submit', 'Am Gerät übernehmen'); submit.type = 'submit';
    form.append(label, target.wrapper, output.wrapper, submit); el.append(heading, form);
    const sync = () => { target.wrapper.hidden = mode.value !== 'auto'; output.wrapper.hidden = mode.value !== 'manual'; target.input.disabled = mode.disabled || mode.value !== 'auto'; output.input.disabled = mode.disabled || mode.value !== 'manual'; };
    mode.addEventListener('change', () => { form.dataset.dirty = 'true'; sync(); });
    form.addEventListener('input', () => { form.dataset.dirty = 'true'; });
    form.addEventListener('submit', event => { event.preventDefault(); if (!form.reportValidity()) return; const values = {id:pm.id, mode:mode.value}; if (mode.value === 'auto') values.target = Number(target.input.value); if (mode.value === 'manual') values.output = Number(output.input.value); send('pitmaster', values); });
    return {el, meta, form, mode, target:target.input, targetLabel:target.wrapper.firstChild, output:output.input, submit, sync};
  }
  function render() {
    const cfg = state.config || {}, active = state.enabled !== false, editable = active && state.canWrite && !pending;
    document.documentElement.dataset.theme = cfg.theme || 'auto';
    document.documentElement.style.setProperty('--accent', /^#[0-9a-f]{6}$/i.test(cfg.accent || '') ? cfg.accent : '#f48941');
    document.body.classList.toggle('compact', Boolean(cfg.compact)); document.body.classList.toggle('no-icons', cfg.icons === false); document.body.classList.toggle('no-details', cfg.details === false); document.body.classList.toggle('no-animation', cfg.animations === false); document.body.classList.toggle('disabled', !active); document.body.classList.toggle('busy', Boolean(pending));
    $('refresh').disabled = !active || !state.canRefresh || Boolean(pending);
    $('connection').textContent = !active ? 'Kachel deaktiviert' : !state.active ? 'Deaktiviert' : state.online ? 'Verbunden' : state.status >= 200 ? 'Fehler' : 'Standby';
    $('connection').classList.toggle('online', Boolean(active && state.online));
    const channels = active && state.online ? (state.channels || []).filter(ch => ch.valid === true && Number.isFinite(ch.temperature) && ch.temperature !== 999) : [];
    if (!channels.some(ch => ch.number === selected)) selected = cfg.primary > 0 ? (channels.some(ch => ch.number === cfg.primary) ? cfg.primary : 0) : channels[0]?.number || 0;
    const main = channels.find(ch => ch.number === (cfg.primary > 0 ? cfg.primary : selected));
    $('hero').hidden = !cfg.hero || !main;
    document.body.classList.toggle('has-hero', !$('hero').hidden);
    $('empty-state').hidden = !active || channels.length > 0;
    $('empty-state').textContent = state.online ? 'Keine gültigen Fühlerwerte verfügbar. Nicht angeschlossene Fühler werden automatisch ausgeblendet.' : 'Keine aktuellen Fühlerwerte – Gerät nicht erreichbar oder noch kein erfolgreicher Abruf.';
    $('hero-channel').textContent = main ? 'KANAL ' + main.number + (main.wireless ? ' · BLUETOOTH' : ' · FÜHLER') : 'TEMPERATUR';
    $('hero-name').textContent = main?.name || (state.online ? 'Keine Fühler ausgewählt' : 'Noch keine Messwerte');
    $('hero-temperature').textContent = fmt(main?.valid ? main.temperature : null); $('hero-unit').textContent = state.unit || '°C';
    $('hero-state').textContent = !active ? 'Kachel in der Instanz deaktiviert.' : main ? describe(main) : 'Auf einen erfolgreichen Datenabruf warten.';
    $('hero-state').classList.toggle('warning', !main?.valid || Boolean(main && aboveMaximum(main)));
    $('hero-forecast').hidden = !main || cfg.forecast === false;
    $('hero-forecast').textContent = main ? forecastText(main.forecast) : '';
    $('hero-range').textContent = main ? 'Obergrenze ' + fmt(main.max) + ' ' + state.unit : '';
    $('gauge-range').textContent = main ? fmt(main.max) + ' ' + state.unit : '—';
    $('gauge').hidden = !main || !Number.isFinite(main.max) || (main.max <= 0 && !Number.isFinite(main.forecast?.progress));
    $('gauge').style.setProperty('--progress', ((main ? fraction(main) : 0) * 360) + 'deg');
    $('gauge').setAttribute('aria-label', main && cfg.forecast !== false && Number.isFinite(main.forecast?.progress) ? 'Temperaturfortschritt seit Start: ' + Math.round(main.forecast.progress * 100) + ' Prozent' : main ? 'Temperatur im Verhältnis zur Obergrenze: ' + describe(main) : 'Keine Obergrenze verfügbar');
    $('probe-count').textContent = channels.length + (channels.length === 1 ? ' gültiger Fühler' : ' gültige Fühler');
    for (const [number, ui] of probes) if (!channels.some(ch => ch.number === number)) { ui.el.remove(); probes.delete(number); }
    channels.forEach(ch => {
      let ui = probes.get(ch.number); if (!ui) { ui = probe(ch); probes.set(ch.number, ui); $('channels').append(ui.el); }
      ui.el.tabIndex = cfg.hero && !cfg.primary ? 0 : -1;
      ui.el.className = 'probe' + (cfg.hero && !cfg.primary ? ' choose' : '') + (cfg.hero && main?.number === ch.number ? ' selected' : '') + (!ch.valid ? ' invalid' : aboveMaximum(ch) ? ' warning' : '');
      ui.kicker.textContent = 'KANAL ' + ch.number + (ch.wireless ? ' · BLUETOOTH' : ''); ui.name.textContent = ch.name;
      ui.value.replaceChildren(document.createTextNode(fmt(ch.valid ? ch.temperature : null)), node('small', '', state.unit));
      ui.label.textContent = describe(ch); ui.bar.style.width = fraction(ch) * 100 + '%'; ui.rail.setAttribute('aria-label', Number.isFinite(ch.forecast?.progress) ? 'Temperaturfortschritt seit Start: ' + Math.round(ch.forecast.progress * 100) + ' Prozent' : 'Temperatur im Verhältnis zur Obergrenze'); ui.rail.hidden = !Number.isFinite(ch.max) || (ch.max <= 0 && !Number.isFinite(ch.forecast?.progress)); ui.high.textContent = 'Obergrenze ' + fmt(ch.max) + ' ' + state.unit;
      renderForecast(ui.forecast, ch, cfg);
      ui.details.hidden = !cfg.controls || !Number.isFinite(ch.max);
      // Offene Eingaben werden von eintreffenden Messwerten nicht überschrieben.
      if (!ui.details.open) { ui.max.value = ch.max ?? ''; }
      ui.max.disabled = ui.submit.disabled = !editable;
    });
    $('channels-section').hidden = channels.length === 0;
    const pms = active ? state.pitmasters || [] : [];
    for (const [id, ui] of pitmasters) if (!pms.some(pm => pm.id === id)) { ui.el.remove(); pitmasters.delete(id); }
    pms.forEach(pm => {
      let ui = pitmasters.get(pm.id); if (!ui) { ui = pitmaster(pm); pitmasters.set(pm.id, ui); $('pitmasters').append(ui.el); }
      ui.meta.textContent = 'Kanal ' + pm.channel + ' · ' + fmt(pm.value) + ' %';
      if (ui.form.dataset.dirty !== 'true') { ui.mode.value = pm.typ; ui.target.value = pm.set; ui.output.value = pm.value; }
      ui.targetLabel.textContent = 'Solltemperatur in ' + state.unit; ui.mode.disabled = !editable; ui.submit.disabled = !editable; ui.submit.hidden = cfg.controls === false; ui.sync();
    });
    $('pitmasters-section').hidden = pms.length === 0;
    $('device').textContent = state.device || 'Mini V3';
    $('telemetry').textContent = [Number.isFinite(state.battery) ? 'Akku ' + fmt(state.battery) + ' %' : '', Number.isFinite(state.rssi) ? 'WLAN ' + fmt(state.rssi) + ' dBm' : ''].filter(Boolean).join(' · ');
    $('updated').textContent = state.lastUpdate ? 'Stand ' + new Date(state.lastUpdate * 1000).toLocaleTimeString('de-DE', {hour:'2-digit', minute:'2-digit', second:'2-digit'}) : 'Noch kein erfolgreicher Abruf';
    $('bluetooth').hidden = typeof state.bluetooth !== 'boolean'; $('bluetooth').textContent = 'Bluetooth ' + (state.bluetooth ? 'an' : 'aus'); $('bluetooth').setAttribute('aria-pressed', String(Boolean(state.bluetooth))); $('bluetooth').disabled = !editable || Boolean(state.bluetoothError);
    $('device-error').textContent = [state.error, cfg.details ? state.bluetoothError : ''].filter(Boolean).join(' · '); $('device-error').hidden = !$('device-error').textContent;
  }
  window.handleMessage = data => {
    try { if (typeof data === 'string') data = JSON.parse(data); if (!data || typeof data !== 'object' || Array.isArray(data)) return;
      if (data.enabled === false) { state = {...state, enabled:false}; if (data.response?.requestId === pending) announce(data.response.message, !data.response.ok); clearTimeout(timeout); pending = null; render(); return; }
      if (data.version !== 1) return;
      if (data.config?.primary !== state.config?.primary) selected = data.config?.primary || 0;
      state = data;
      if (data.response && data.response.requestId === pending) {
        clearTimeout(timeout); pending = null; announce(data.response.message, !data.response.ok);
        if (data.response.ok) { for (const ui of pitmasters.values()) delete ui.form.dataset.dirty; for (const ui of probes.values()) ui.details.open = false; }
      }
      render();
    } catch (error) { announce('Kacheldaten konnten nicht verarbeitet werden.', true); }
  };
  $('refresh').addEventListener('click', () => send('refresh'));
  $('bluetooth').addEventListener('click', () => send('bluetooth', {enabled:!state.bluetooth}));
  try { window.handleMessage(JSON.parse($('initial-state').textContent)); } catch (error) { announce('Initiale Kacheldaten fehlen.', true); }
})();
