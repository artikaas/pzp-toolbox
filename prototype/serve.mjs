/** Eenvoudige webserver voor prototype/dist: http://localhost:4400 (andere poort: PORT=4500 npm run prototype:serve). */
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), 'dist');
const PORT = Number(process.env.PORT ?? 4400);
const TYPES = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.svg': 'image/svg+xml', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.png': 'image/png', '.webp': 'image/webp', '.woff2': 'font/woff2', '.mp4': 'video/mp4', '.txt': 'text/plain', '.json': 'application/json' };

export function start(port = PORT) {
  const server = http.createServer((req, res) => {
    let p = path.join(ROOT, decodeURIComponent(new URL(req.url, 'http://x').pathname));
    if (!p.startsWith(ROOT)) { res.writeHead(403).end(); return; }
    if (fs.existsSync(p) && fs.statSync(p).isDirectory()) p = path.join(p, 'index.html');
    if (!fs.existsSync(p)) {
      res.writeHead(404, { 'content-type': TYPES['.html'] }).end(fs.existsSync(path.join(ROOT, '404.html')) ? fs.readFileSync(path.join(ROOT, '404.html')) : 'Niet gevonden');
      return;
    }
    res.writeHead(200, { 'content-type': TYPES[path.extname(p).toLowerCase()] ?? 'application/octet-stream' });
    fs.createReadStream(p).pipe(res);
  });
  return new Promise((resolve) => server.listen(port, () => resolve(server)));
}

if (import.meta.url === `file://${process.argv[1]}`) {
  if (!fs.existsSync(ROOT)) { console.error('Nog geen prototype/dist. Draai eerst `npm run prototype`.'); process.exit(1); }
  await start();
  console.log(`Prototype op http://localhost:${PORT} (stoppen: Ctrl+C)`);
}
