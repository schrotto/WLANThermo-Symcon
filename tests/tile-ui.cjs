// Ausführung des echten Kachel-JavaScripts mit kleinem DOM-Vertrag, kein Browsertest.
const vm = require('node:vm');
const fs = require('node:fs');
const assert = require('node:assert/strict');
class Element {
  constructor(tag) { this.tagName = tag; this.children = []; this.attrs = {}; this.events = {}; this.dataset = {}; this.style = {setProperty:(key,value) => {this.style[key] = value;}}; this.className = ''; this.value = ''; this.hidden = false; this.open = false; this.disabled = false; this._text = ''; }
  append(...children) { for (const child of children) { child.parent = this; this.children.push(child); } }
  replaceChildren(...children) { this.children = []; this._text = ''; this.append(...children); }
  remove() { if (this.parent) this.parent.children = this.parent.children.filter(child => child !== this); }
  set textContent(text) { this._text = String(text); this.children = []; }
  get textContent() { return this._text + this.children.map(child => child.textContent).join(''); }
  get firstChild() { return this.children[0]; }
  setAttribute(key, value) { this.attrs[key] = value; }
  addEventListener(name, handler) { this.events[name] = handler; }
  dispatch(name, extra={}) { return this.events[name]?.({target:this, preventDefault(){}, ...extra}); }
  reportValidity() { return true; }
  get classList() { return {add:cls => { this.className = (this.className + ' ' + cls).trim(); },toggle:(cls,on) => { const classes = new Set(this.className.split(' ').filter(Boolean)); if (on) classes.add(cls); else classes.delete(cls); this.className = [...classes].join(' '); }}; }
}
const fixture = JSON.parse(fs.readFileSync(__dirname + '/tile-state.generated.json','utf8'));
// Bestehende Vertragsfälle prüfen weiterhin die vollständige Fühlerübersicht.
fixture.config.headerEnabled = false;
const ids = [...fs.readFileSync(__dirname + '/../WLANThermo/module.html','utf8').matchAll(/\bid="([^"]+)"/g)].map(match => match[1]);
const elements = Object.fromEntries(ids.map(id => [id, new Element('div')]));
elements['initial-state'].textContent = JSON.stringify(fixture);
const document = { getElementById:id => {assert(elements[id], 'Fehlendes Element: '+id); return elements[id];}, createElement:tag => new Element(tag), createElementNS:(_namespace,tag) => new Element(tag), createTextNode:text => {const el = new Element('#text');el.textContent = text; return el;}, documentElement:new Element('html'), body:new Element('body') };
let requested = [], timers = [];
const window = {requestAction:(ident,value) => requested.push({ident,...JSON.parse(value)})};
const context = vm.createContext({document,window,console,Intl,Date,Math,JSON,Number,Boolean,String,Map,Set,setTimeout:callback => {timers.push(callback);return timers.length;},clearTimeout:()=>{}});
vm.runInContext(fs.readFileSync(__dirname + '/../WLANThermo/tile.js','utf8'), context);
let count = 0;
function check(ok,message){assert(ok,message);console.log('UI OK '+ ++count + ': '+message);}
const clone = () => JSON.parse(JSON.stringify(fixture));
check(elements['hero-name'].textContent === 'Meater · Kerntemperatur' && elements['hero-temperature'].textContent === '60,3', 'Initiale Haupttemperatur nutzt den konfigurierten Kanal');
check(elements.channels.children.length === 2 && elements.pitmasters.children.length === 2, 'Fühler und Pitmaster werden ohne Variablen gerendert');
check(elements.hero.hidden, 'Große Hauptanzeige ist standardmäßig unsichtbar');
let heroData = clone(); heroData.config.hero = true; window.handleMessage(heroData);
check(!elements.hero.hidden && elements['hero-name'].textContent === 'Meater · Kerntemperatur', 'Hauptanzeige lässt sich für einen festen Fühler einschalten');
heroData.config.primary = 0; window.handleMessage(heroData);
const probe13 = elements.channels.children[1];
probe13.dispatch('keydown',{key:'Enter'});
check(elements['hero-name'].textContent === 'Meater · Kerntemperatur', 'Bei automatischer Hauptanzeige lässt sich der Hauptfühler per Tastatur wählen');
elements.refresh.dispatch('click');
check(requested.length === 1 && requested[0].action === 'refresh' && elements.refresh.disabled, 'Aktualisieren sendet genau eine SDK-Anfrage und sperrt Doppelaktionen');
elements.refresh.dispatch('click');
check(requested.length === 1, 'Ausstehende Anfrage verhindert Doppelsenden');
let data = clone(); data.response = {requestId:requested[0].requestId,ok:true,message:'Messwerte aktualisiert.'}; window.handleMessage(data);
check(!elements.refresh.disabled && elements.notice.textContent === 'Messwerte aktualisiert.', 'Passende Serverbestätigung hebt die Sperre auf');
const pmForm = elements.pitmasters.children[0].children[1], mode = pmForm.children[0].children[1];
mode.value = 'manual'; mode.dispatch('change');
check(pmForm.children[1].hidden && !pmForm.children[2].hidden, 'Manueller Modus zeigt den Stellwert statt Solltemperatur');
pmForm.children[2].children[1].value = '55'; pmForm.dispatch('submit');
check(requested[1].action === 'pitmaster' && requested[1].output === 55 && requested[1].mode === 'manual', 'Pitmaster sendet strukturierte ganzzahlige Werte');
data = clone();data.response = {requestId:requested[1].requestId,ok:false,message:'Bedienung gesperrt.'}; window.handleMessage(data);
check(elements.notice.className === 'error' && !elements.refresh.disabled, 'Fehlerquittierung zeigt verständlichen Fehler und entsperrt');
data = clone();data.online = false;data.canWrite = false;data.status = 106;data.channels.forEach(ch => {ch.valid=false;ch.temperature=null;});window.handleMessage(data);
check(elements['hero-temperature'].textContent === '—' && elements.connection.textContent === 'Standby' && mode.disabled, 'Offline zeigt keine scheinbar gültige Temperatur und sperrt Eingaben');
data = clone();data.config.theme = 'light';data.config.compact = true;data.config.animations = false;window.handleMessage(JSON.stringify(data));
check(document.documentElement.dataset.theme === 'light' && document.body.className.includes('compact') && document.body.className.includes('no-animation'), 'Live-Konfiguration setzt helles Design, Kompaktmodus und reduzierte Animation');
data = clone();data.channels = [data.channels[0]];data.pitmasters=[];window.handleMessage(data);
check(elements.channels.children.length === 1 && elements.pitmasters.children.length === 0, 'Entfernte Fühler und Pitmaster verschwinden aus der Ansicht');
data = clone();data.channels[0].name = '<img src=x onerror=alert(1)>';window.handleMessage(data);
check(elements.channels.children[0].children[0].children[0].children[1].textContent === '<img src=x onerror=alert(1)>', 'Dynamische Namen werden als Text eingefügt');
elements.refresh.dispatch('click');timers.at(-1)();
check(!elements.refresh.disabled && elements.notice.className === 'error', 'Fehlende Bestätigung endet mit entsperrter Timeout-Rückmeldung');
data = clone(); data.config.hero = true; data.channels[1].valid = false; data.channels[1].temperature = null; data.channels[2].temperature = 999; window.handleMessage(data);
check(elements.channels.children.length === 1 && elements.hero.hidden, 'Ungültige Fühler verschwinden; ein fehlender fester Hauptfühler wird nicht ersetzt');
data = clone(); data.config.hero = true; data.config.primary = 1; window.handleMessage(data);
check(elements.channels.children.length === 2 && !elements.hero.hidden && elements['hero-name'].textContent === 'Garraum', 'Gültige Fühler und optionale Garraum-Hauptanzeige erscheinen wieder');
data.config.hero = false; window.handleMessage(data);
check(elements.hero.hidden && elements.channels.children.length === 2, 'Ausschalten der Hauptanzeige erhält die normale Fühlerübersicht');
data.channels.forEach(ch => {ch.valid = false; ch.temperature = null;}); window.handleMessage(data);
check(elements.channels.children.length === 0 && !elements['empty-state'].hidden, 'Ohne gültige Fühler erscheint ein Hinweis statt leerer Fühlerkarten');
data = clone(); data.channels[0].temperature = 5; data.channels[0].min = 20; data.channels[0].outOfRange = true; window.handleMessage(data);
check(elements.channels.children[0].children[2].textContent.includes('bis zur Obergrenze') && !elements.channels.children[0].className.includes('warning'), 'Frühere Untergrenze beeinflusst Status und Warnfarbe in der Visu nicht');
const upperDetails = elements.channels.children[0].children.at(-1), upperForm = upperDetails.children[1];
check(upperForm.children.length === 2 && upperForm.children[0].firstChild.textContent === 'Obergrenze', 'Fühlerformular enthält ausschließlich die Obergrenze und die Schaltfläche');
upperForm.children[0].children[1].value = '145'; upperForm.dispatch('submit');
check(requested.at(-1).max === 145 && !('min' in requested.at(-1)), 'Kachel sendet bei Änderung keinen versteckten Min-Wert');
data.response = {requestId:requested.at(-1).requestId,ok:true,message:'Bestätigt'}; window.handleMessage(data);
data.config.hero = true; data.config.primary = 1; window.handleMessage(data);
check(elements['hero-range'].textContent.startsWith('Obergrenze ') && !elements['gauge-range'].textContent.includes('–'), 'Optionale Hauptanzeige und Ring zeigen nur die Obergrenze');
data.channels[0].max = -5; data.channels[0].forecast = {status:'learning',progress:null}; window.handleMessage(data);
check(elements.gauge.hidden && elements.channels.children[0].children[3].hidden, 'Für nichtpositive Obergrenzen wird keine irreführende Verhältnisgrafik dargestellt');
data = clone(); data.config.forecast = true; data.config.forecastChart = true; data.config.hero = true; data.config.primary = 1;
const forecastNow = Math.floor(Date.now()/1000);
data.channels[0].temperature = 75; data.channels[0].max = 82;
data.channels[0].forecast = {status:'estimated',seconds:1400,eta:forecastNow+1400,rate:0.3,progress:0.3,points:[[forecastNow-600,72],[forecastNow,75]]};
window.handleMessage(data);
const forecastUI = elements.channels.children[0].children[5];
const forecastSummary = forecastUI.children[0], forecastContent = forecastUI.children[1];
const forecastTitle = forecastSummary.children[0], forecastEta = forecastSummary.children[1];
const forecastDetail = forecastContent.children[0], forecastChart = forecastContent.children[1];
check(forecastTitle.textContent === 'ca. 23 Min.' && elements['hero-forecast-value'].textContent === 'Noch ca. 23 Min.', 'Restzeit erscheint in Fühlerkarte und optionaler Hauptanzeige');
check(!elements['hero-forecast-eta'].hidden && elements['hero-forecast-eta'].textContent.startsWith('Obergrenze voraussichtlich gegen '), 'Hauptbereich zeigt den geschätzten Zielzeitpunkt zusätzlich zur Restdauer');
check(forecastDetail.textContent.includes('Ziel voraussichtlich gegen') && forecastDetail.textContent.includes('0,3') && forecastEta.textContent.startsWith('gegen '), 'Kachel zeigt Zielzeitpunkt und geglätteten Anstieg');
check(!forecastChart.hidden && forecastChart.children[1].attrs.points.includes(',') && !forecastChart.children[1].attrs.points.includes('NaN') && forecastChart.children[2].attrs.visibility === 'visible', 'Verlauf, Obergrenze und gestrichelte Prognose erhalten gültige Diagrammkoordinaten');
check(elements.channels.children[0].children[3].children[0].style.width === '30%', 'Balken zeigt Temperaturfortschritt seit Start statt verstrichener Garzeit');
check(forecastUI.tagName === 'details' && forecastUI.open === false && forecastSummary.tagName === 'summary', 'Restzeit ist standardmäßig eine geschlossene kompakte Detailzeile');
forecastUI.open = true; window.handleMessage(data);
check(forecastUI.open === true && forecastContent.children.includes(forecastChart), 'Geöffnete Verlaufsdetails bleiben bei Live-Daten geöffnet');
check(elements.channels.children[0].children[3].className.includes('is-running'), 'Laufende Aufheizprognose aktiviert die Fortschrittsanimation');
data.config.forecastChart = false; window.handleMessage(data);
check(forecastChart.hidden && !forecastUI.hidden && forecastTitle.textContent.includes('23 Min.'), 'Diagramm lässt sich ohne Abschalten der Restzeit ausblenden');
data.config.forecastChart = true; data.channels[0].forecast.status = 'stalled'; window.handleMessage(data);
check(forecastTitle.textContent.toLowerCase().includes('plateau') && forecastChart.children[2].attrs.visibility === 'hidden', 'Plateau entfernt die alte Restzeit und den Prognoseabschnitt');
check(elements['hero-forecast-eta'].hidden && elements['hero-forecast-value'].textContent.includes('Temperaturplateau') && !elements['hero-forecast'].className.includes('estimated'), 'Plateau ersetzt die Hauptrestzeit und entfernt den alten Zielzeitpunkt');
check(!elements.channels.children[0].children[3].className.includes('is-running'), 'Temperaturplateau stoppt die Fortschrittsanimation');
data.canWrite = false; window.handleMessage(data);
const resetButton = forecastContent.children.at(-1); resetButton.dispatch('click');
check(requested.at(-1).action === 'forecastReset' && requested.at(-1).number === 1 && !('max' in requested.at(-1)), 'Verlauf-Reset sendet eine interne Aktion ohne Geräteeinstellungen');
data.response = {requestId:requested.at(-1).requestId,ok:true,message:'Temperaturverlauf neu gestartet.'}; window.handleMessage(data);
data.config.forecast = false; window.handleMessage(data);
check(forecastUI.hidden && elements['hero-forecast'].hidden, 'Prognose lässt sich vollständig ausblenden');
// Gruppierung und reine Garraumanzeige verwenden die Metadaten des Moduls.
data = clone(); data.config.hero = true; window.handleMessage(data);
let meaterCard = elements.channels.children.find(el => el.children[0].children[0].children[1].textContent === 'Meater');
check(elements.channels.children.length === 2 && meaterCard.children[1].textContent.includes('Kerntemperatur') && meaterCard.children[1].textContent.includes('Garraumtemperatur') && meaterCard.children[1].textContent.includes('118,4'), 'Ein MEATER zeigt beide korrekt beschrifteten Temperaturen in genau einer Kachel');
check(meaterCard.children[6].children[1].children.length === 2, 'Gemeinsame MEATER-Kachel besitzt nur ein Obergrenzenformular für den Kern');
meaterCard.children[6].children[1].children[0].children[1].value = '78';
meaterCard.children[6].children[1].dispatch('submit');
check(requested.at(-1).action === 'channel' && requested.at(-1).number === 13 && requested.at(-1).max === 78, 'Obergrenze einer MEATER-Kachel schreibt ausschließlich auf den Kernkanal');
data.response = {requestId:requested.at(-1).requestId,ok:true,message:'Bestätigt'}; window.handleMessage(data);
data.channels = data.channels.filter(ch => ch.number !== 13); data.config.primary = 14; window.handleMessage(data);
let ambientCard = elements.channels.children.find(el => el.children[0].children[0].children[1].textContent === 'Meater');
check(ambientCard.children[1].textContent.includes('Garraumtemperatur') && !ambientCard.children[1].textContent.includes('Kerntemperatur') && ambientCard.children[6].hidden && ambientCard.children[5].hidden && ambientCard.children[3].hidden, 'Fehlender Kern lässt nur die gültige Garraumtemperatur ohne Ziel, Prognose oder Eingabe stehen');
check(elements['hero-range'].hidden && elements['hero-forecast'].hidden && elements.gauge.hidden && !elements['hero-state'].className.includes('warning'), 'Garraum als Hauptkanal zeigt nur den Messwert ohne Zielwarnung oder Prognose');
data = clone(); data.channels = data.channels.filter(ch => ch.number !== 14); window.handleMessage(data);
meaterCard = elements.channels.children[1];
check(meaterCard.children[1].textContent.includes('Kerntemperatur') && !meaterCard.children[1].textContent.includes('Garraumtemperatur'), 'Ausgeblendeter oder ungültiger Garraum wird nicht als alter Wert angezeigt');
data = clone();
const secondCore = {...data.channels[1], number:21, pairKey:'meater_21', probeName:'MEATER 2', temperature:42};
const secondAmbient = {...data.channels[2], number:22, pairKey:'meater_21', probeName:'MEATER 2', temperature:150};
data.channels = [data.channels[0], secondAmbient, data.channels[2], secondCore, data.channels[1]]; window.handleMessage(data);
check(elements.channels.children.length === 3 && elements.channels.children.find(el => el.children[0].children[0].children[1].textContent === 'MEATER 2').children[1].textContent.includes('150'), 'Zwei MEATER bleiben auch bei vertauschter Reihenfolge getrennt zugeordnet');
data = clone(); delete data.channels[1].pairKey; delete data.channels[2].pairKey; window.handleMessage(data);
check(elements.channels.children.length === 3, 'Ohne eindeutige Paarzuordnung werden keine benachbarten Kanäle zusammengefasst');
data = clone(); data.config.headerEnabled = true; data.config.headerChannel = 1; data.config.headerName = 'Räucherkammertemperatur'; data.config.title = 'Mein Smoker'; window.handleMessage(data);
check(elements['tile-title'].textContent === 'Mein Smoker' && elements['header-temperature-name'].textContent === 'Räucherkammertemperatur' && elements['header-temperature-value'].textContent === '110,5', 'Freier Titel und beschriftete Kopfkachel zeigen den gewählten Temperaturkanal');
check(!elements['header-temperature'].hidden && elements.channels.children.length === 1 && !elements.channels.children.some(el => el.attrs['aria-label']?.includes('Kanal 1 ')) && elements['probe-count'].textContent === '1 Fühler · 2 Messwerte', 'Kopfkanal entfällt unten und die Fühlerzählung beschreibt nur die Übersicht');
data.config.headerEnabled = false; window.handleMessage(data);
check(elements['header-temperature'].hidden && elements.channels.children.length === 2, 'Abschalten der Kopfkachel bringt den Kanal ohne Datenverlust in die Übersicht zurück');
data.config.headerEnabled = true; data.config.headerChannel = 99; window.handleMessage(data);
check(elements['header-temperature'].hidden && elements.channels.children.length === 2, 'Fehlender Kopfkanal wird nicht durch einen anderen Messwert ersetzt');
data.config.headerChannel = 1; data.channels = [data.channels[0]]; window.handleMessage(data);
check(!elements['header-temperature'].hidden && elements['channels-section'].hidden && elements.channels.children.length === 0 && elements['empty-state'].hidden, 'Wenn nur der Kopfkanal vorhanden ist, entfällt die leere Fühlerübersicht');
data = clone(); data.config.headerEnabled = true; data.config.headerChannel = 14; window.handleMessage(data);
check(elements['header-temperature-value'].textContent === '118,4' && !elements.channels.children[1].children[1].textContent.includes('Garraumtemperatur'), 'Aus MEATER-Paar gewählter Kopfkanal wird auch innerhalb der gemeinsamen Kachel nicht doppelt angezeigt');
data.config.headerChannel = 13; window.handleMessage(data);
check(elements.channels.children[1].children[1].textContent.includes('Garraumtemperatur') && !elements.channels.children[1].children[1].textContent.includes('Kerntemperatur') && elements.channels.children[1].children[6].hidden, 'Kern im Kopfbereich lässt unten ausschließlich die reine Garraumanzeige übrig');
data = clone(); data.config.headerEnabled = true; data.config.headerChannel = 1; data.channels[0].temperature = 999; window.handleMessage(data);
check(elements['header-temperature'].hidden, 'Ungültiger Kopfkanal verschwindet vollständig');
data = clone(); data.config.title = '<img src=x onerror=alert(1)>'; data.config.headerName = '<script>test</script>'; data.config.headerEnabled = true; data.config.headerChannel = 1; window.handleMessage(data);
check(elements['tile-title'].textContent === data.config.title && elements['header-temperature-name'].textContent === data.config.headerName, 'Frei eingegebene Titel werden als Text und nicht als HTML gerendert');
data = clone(); data.config.headerEnabled = true; data.config.headerChannel = 1; data.canWrite = true; window.handleMessage(data);
elements['header-limits'].open = true; elements['header-maximum'].value = '145'; window.handleMessage(data);
check(elements['header-maximum'].value === '145' && !elements['header-limits'].hidden, 'Kopfkanal behält Obergrenzenbedienung und offene Eingaben bei Live-Daten');
elements['header-limits-form'].dispatch('submit');
check(requested.at(-1).number === 1 && requested.at(-1).max === 145, 'Obergrenze aus der Kopfkachel schreibt auf den gewählten Kanal');
data.response = {requestId:requested.at(-1).requestId,ok:true,message:'Bestätigt'}; window.handleMessage(data);
data.config.headerChannel = 14; window.handleMessage(data);
check(elements['header-limits'].hidden && !elements['header-limits'].open && elements['header-maximum'].value === data.channels[2].max, 'MEATER-Garraum besitzt auch oben kein Obergrenzenformular; Kanalwechsel verwirft alte Eingabe');
data.config.headerChannel = 1; data.canWrite = false; window.handleMessage(data);
check(elements['header-maximum'].disabled && elements['header-limit-submit'].disabled, 'Schreibschutz sperrt die Obergrenzenbedienung im Kopfbereich');
window.handleMessage({enabled:false});
check(elements.refresh.disabled && elements.connection.textContent === 'Kachel deaktiviert', 'Deaktivierung sperrt die bereits geöffnete Kachel');
console.log(count + ' UI-Vertragsprüfungen erfolgreich. Keine Aussage zu Browserlayout oder echtem Symcon-Host.');
