'use strict';
(() => {
  const $ = id => document.getElementById(id);
  let state = {}, selected = 0, pending = null, timeout = null;
  const probes = new Map(), pitmasters = new Map();
  const fmt = value => typeof value === 'number' && Number.isFinite(value) ? new Intl.NumberFormat('de-DE', {maximumFractionDigits:1}).format(value) : '—';
  const node = (tag, cls, text) => { const el = document.createElement(tag); if (cls) el.className = cls; if (text !== undefined) el.textContent = text; return el; };
  const announce = (message, error = false) => { $('notice').textContent = message; $('notice').className = error ? 'error' : ''; $('notice').hidden = !message; };
  const fraction = ch => ch.valid && Number.isFinite(ch.min) && Number.isFinite(ch.max) && ch.max > ch.min ? Math.max(0, Math.min(1, (ch.temperature - ch.min) / (ch.max - ch.min))) : 0;
  const describe = ch => !ch.valid ? 'Kein aktueller Messwert' : ch.outOfRange ? (ch.temperature > ch.max ? 'Über der oberen Alarmgrenze' : 'Unter der unteren Alarmgrenze') : 'Innerhalb der Alarmgrenzen';
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
    const value = node('div', 'probe-value'), label = node('div', 'probe-label'), rail = node('div', 'rail'), bar = node('span'), limits = node('div', 'rail-labels'), low = node('span'), high = node('span');
    rail.append(bar); limits.append(low, high);
    const details = node('details'), summary = node('summary', '', 'Alarmgrenzen bearbeiten'), form = node('form', 'limits-form');
    const min = field('Minimum', 'number', -30, 999), max = field('Maximum', 'number', -30, 999), submit = node('button', '', 'Grenzen übernehmen'); submit.type = 'submit';
    form.append(min.wrapper, max.wrapper, submit); details.append(summary, form);
    el.append(head, value, label, rail, limits, details);
    const choose = () => { selected = ch.number; render(); };
    head.addEventListener('click', choose); value.addEventListener('click', choose);
    el.addEventListener('keydown', event => { if (event.target === el && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); choose(); } });
    form.addEventListener('submit', event => { event.preventDefault(); if (form.reportValidity()) send('channel', {number:ch.number, min:Number(min.input.value), max:Number(max.input.value)}); });
    return {el, kicker, name, value, label, bar, low, high, details, min:min.input, max:max.input, submit};
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
    const channels = active ? state.channels || [] : [];
    if (!channels.some(ch => ch.number === selected)) selected = channels.some(ch => ch.number === cfg.primary) ? cfg.primary : channels[0]?.number || 0;
    const main = channels.find(ch => ch.number === selected);
    $('hero-channel').textContent = main ? 'KANAL ' + main.number + (main.wireless ? ' · BLUETOOTH' : ' · FÜHLER') : 'TEMPERATUR';
    $('hero-name').textContent = main?.name || (state.online ? 'Keine Fühler ausgewählt' : 'Noch keine Messwerte');
    $('hero-temperature').textContent = fmt(main?.valid ? main.temperature : null); $('hero-unit').textContent = state.unit || '°C';
    $('hero-state').textContent = !active ? 'Kachel in der Instanz deaktiviert.' : main ? describe(main) : 'Auf einen erfolgreichen Datenabruf warten.';
    $('hero-state').classList.toggle('warning', !main?.valid || Boolean(main?.outOfRange));
    $('hero-range').textContent = main ? 'Alarmgrenzen ' + fmt(main.min) + ' – ' + fmt(main.max) + ' ' + state.unit : '';
    $('gauge-range').textContent = main ? fmt(main.min) + '–' + fmt(main.max) + ' ' + state.unit : '—';
    $('gauge').style.setProperty('--progress', ((main ? fraction(main) : 0) * 360) + 'deg');
    $('gauge').setAttribute('aria-label', main ? 'Temperatur relativ zu den Alarmgrenzen: ' + describe(main) : 'Keine Alarmgrenzen verfügbar');
    $('probe-count').textContent = channels.filter(ch => ch.valid).length + ' / ' + channels.length + ' aktuell';
    for (const [number, ui] of probes) if (!channels.some(ch => ch.number === number)) { ui.el.remove(); probes.delete(number); }
    channels.forEach(ch => {
      let ui = probes.get(ch.number); if (!ui) { ui = probe(ch); probes.set(ch.number, ui); $('channels').append(ui.el); }
      ui.el.className = 'probe choose' + (ch.number === selected ? ' selected' : '') + (!ch.valid ? ' invalid' : ch.outOfRange ? ' warning' : '');
      ui.kicker.textContent = 'KANAL ' + ch.number + (ch.wireless ? ' · BLUETOOTH' : ''); ui.name.textContent = ch.name;
      ui.value.replaceChildren(document.createTextNode(fmt(ch.valid ? ch.temperature : null)), node('small', '', state.unit));
      ui.label.textContent = describe(ch); ui.bar.style.width = fraction(ch) * 100 + '%'; ui.low.textContent = fmt(ch.min) + ' ' + state.unit; ui.high.textContent = fmt(ch.max) + ' ' + state.unit;
      ui.details.hidden = !cfg.controls || !Number.isFinite(ch.min) || !Number.isFinite(ch.max);
      // Offene Eingaben werden von eintreffenden Messwerten nicht überschrieben.
      if (!ui.details.open) { ui.min.value = ch.min ?? ''; ui.max.value = ch.max ?? ''; }
      ui.min.disabled = ui.max.disabled = ui.submit.disabled = !editable;
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
