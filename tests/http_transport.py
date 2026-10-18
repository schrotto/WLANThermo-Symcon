#!/usr/bin/env python3
"""Exercise real PHP cURL against a local HTTP fixture; no device is contacted."""
import http.server, threading, subprocess, sys, pathlib, json, base64, hashlib, re, time
class Handler(http.server.BaseHTTPRequestHandler):
    def log_message(self, *args): pass
    def reply(self, code, body, headers=None):
        self.send_response(code)
        for k,v in (headers or {}).items(): self.send_header(k,v)
        self.end_headers()
        try: self.wfile.write(body.encode())
        except BrokenPipeError: pass
    def do_GET(self):
        if self.path=='/ok': return self.reply(200,'{"ok":true}')
        if self.path=='/denied': return self.reply(401,'unauthorized')
        if self.path=='/redirect': return self.reply(302,'',{'Location':'/ok'})
        if self.path=='/timeout': time.sleep(2); return self.reply(200,'{}')
        if self.path=='/auth':
            if self.headers.get('Authorization') != 'Basic '+base64.b64encode(b'admin:secret').decode():
                return self.reply(401,'',{'WWW-Authenticate':'Basic realm="WT"'})
            return self.reply(200,'{"ok":true}')
        if self.path=='/digest':
            a=self.headers.get('Authorization','')
            fields={}
            for key,quoted,plain in re.findall(r'(\w+)=(?:"([^"]*)"|([^, ]+))',a): fields[key]=quoted or plain
            md=lambda s: hashlib.md5(s.encode()).hexdigest()
            h1=md('admin:WT:secret'); h2=md('GET:/digest')
            expected=md(':'.join([h1,'nonce123',fields.get('nc',''),fields.get('cnonce',''),'auth',h2]))
            if not a.startswith('Digest ') or fields.get('response')!=expected:
                return self.reply(401,'',{'WWW-Authenticate':'Digest realm="WT", nonce="nonce123", qop="auth", algorithm=MD5'})
            return self.reply(200,'{"ok":true}')
        self.reply(404,'not found')
    def do_POST(self):
        p=json.loads(self.rfile.read(int(self.headers['Content-Length'])))
        if self.path=='/post' and p=={'name':'Grillfühler','min':10.5}:
            return self.reply(200,'true')
        self.reply(400,'false')
server=http.server.ThreadingHTTPServer(('127.0.0.1',0),Handler)
threading.Thread(target=server.serve_forever,daemon=True).start()
root=pathlib.Path(__file__).resolve().parent
try:
    result=subprocess.run([*sys.argv[1:],str(root/'transport.php'),'http://127.0.0.1:'+str(server.server_port)],check=False)
finally: server.shutdown()
sys.exit(result.returncode)
