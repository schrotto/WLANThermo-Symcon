"""Lokale Strukturprüfung; ersetzt keinen Test mit der Symcon-Konsole."""
from pathlib import Path
import json
import re
import uuid
root = Path(__file__).resolve().parent.parent
files = [root/'library.json'] + list((root/'WLANThermo').glob('*.json')) + list((root/'tests').glob('*.json')) + list((root/'tests/fixtures').glob('*.json'))
for path in files:
    json.loads(path.read_text())
library = json.loads((root / 'library.json').read_text())
module = json.loads((root / 'WLANThermo/module.json').read_text())
for metadata in [library, module]:
    uuid.UUID(metadata['id'].strip('{}'))
assert library['id'] != module['id']
code = (root / 'WLANThermo/module.php').read_text() + ''.join(p.read_text() for p in (root/'libs').glob('*.php'))
assert re.search(r'class\s+' + module['name'] + r'\s+extends\s+IPSModuleStrict', code)
assert module['type'] == 3 and module['prefix'] == 'WLT'
assert not module['parentRequirements'] and not module['childRequirements'] and not module['implemented']
properties = set(re.findall(r"RegisterProperty\w+\('([^']+)'", code))
# Gruppeneigenschaften werden in einer Schleife registriert.
properties.update(['ShowSystem', 'ShowSettings', 'ShowBluetoothDetails', 'ShowChannelDetails', 'ShowPitmaster', 'ShowDevice'])
registered = set()
def visit(elements):
    for element in elements:
        if 'name' in element and element['type'] != 'Label':
            assert element['name'] in properties, element['name']
            assert element['name'] not in registered
            registered.add(element['name'])
        if 'items' in element:
            visit(element['items'])
for path in [root / 'WLANThermo/form.json', root / 'tests/baseline-form.generated.json', root / 'tests/dynamic-form.generated.json']:
    registered.clear()
    form = json.loads(path.read_text())
    visit(form['elements'])
    assert properties - registered == {'DisabledVariables'}
    assert len({s['code'] for s in form['status']}) == len(form['status'])
assert code.count('UnregisterVariable(') == 1
assert 'if (!$Confirmed)' in code
assert not re.search(r'MaintainVariable\([^;]+,\s*false\)', code, re.S)
print(f'{len(files)} JSON-Dateien gültig; GUIDs, Modulklasse, Formularfelder und datenerhaltende Variablenverwaltung geprüft.')
