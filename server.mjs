/**
 * Thin Node.js proxy that:
 *  1. Starts immediately on PORT so Replit detects the port
 *  2. Spawns PHP built-in server on PORT+1
 *  3. Serves static files (CSS/JS/images) directly with Cache-Control headers
 *  4. Forwards all other requests to PHP
 */
import { spawn }                            from 'child_process';
import http                                 from 'http';
import { createReadStream, existsSync, statSync } from 'fs';
import { extname, join }                    from 'path';

const PORT      = parseInt(process.env.PORT || '22286', 10);
const PHP_PORT  = PORT + 1;
const PHP_ROUTER  = new URL('./router.php', import.meta.url).pathname;
const PHP_DOCROOT = '/home/runner/workspace/quiniela-php';

// ── Static file config ──────────────────────────────────────────────
const STATIC_MIME = {
  '.css':   'text/css; charset=utf-8',
  '.js':    'application/javascript; charset=utf-8',
  '.png':   'image/png',
  '.jpg':   'image/jpeg',
  '.jpeg':  'image/jpeg',
  '.gif':   'image/gif',
  '.svg':   'image/svg+xml',
  '.ico':   'image/x-icon',
  '.webp':  'image/webp',
  '.woff':  'font/woff',
  '.woff2': 'font/woff2',
  '.ttf':   'font/ttf',
};

// Cache duration by type: code files short (dev-friendly), assets long
const CACHE_CONTROL = {
  '.css':  'public, max-age=300',        // 5 min — pick up style changes quickly
  '.js':   'public, max-age=300',
  '.png':  'public, max-age=86400',      // 1 day — flags/images change rarely
  '.jpg':  'public, max-age=86400',
  '.jpeg': 'public, max-age=86400',
  '.gif':  'public, max-age=86400',
  '.svg':  'public, max-age=86400',
  '.ico':  'public, max-age=86400',
  '.webp': 'public, max-age=86400',
  '.woff': 'public, max-age=604800',     // 1 week — fonts never change
  '.woff2':'public, max-age=604800',
  '.ttf':  'public, max-age=604800',
};

function resolveStatic(url) {
  const path = url.split('?')[0];
  // Strip the /quiniela-php prefix
  const rel  = path.replace(/^\/quiniela-php/, '') || '/';
  return join(PHP_DOCROOT, rel);
}

// ── Start PHP server ──────────────────────────────────────────────
const php = spawn('php', ['-S', `0.0.0.0:${PHP_PORT}`, '-t', PHP_DOCROOT, PHP_ROUTER], {
  env:   { ...process.env, PORT: String(PHP_PORT) },
  stdio: ['ignore', 'inherit', 'inherit'],
});

php.on('error', (err) => { console.error('PHP spawn error:', err.message); process.exit(1); });
php.on('exit',  (code) => { console.error(`PHP exited (${code})`); process.exit(code ?? 1); });

// ── Proxy server ──────────────────────────────────────────────────
const server = http.createServer((req, res) => {
  const ext = extname(req.url.split('?')[0]).toLowerCase();

  // ── Serve static files directly (no PHP overhead, with caching) ──
  if (ext && STATIC_MIME[ext]) {
    const filePath = resolveStatic(req.url);
    if (existsSync(filePath)) {
      let stat;
      try { stat = statSync(filePath); } catch { /* fall through to PHP */ }
      if (stat) {
        const etag    = `"${stat.mtimeMs.toString(36)}-${stat.size}"`;
        const mtime   = new Date(stat.mtimeMs).toUTCString();

        // Conditional request — return 304 if unchanged
        if (req.headers['if-none-match'] === etag) {
          res.writeHead(304, {
            'ETag':          etag,
            'Cache-Control': CACHE_CONTROL[ext] || 'public, max-age=300',
          });
          res.end();
          return;
        }

        res.writeHead(200, {
          'Content-Type':   STATIC_MIME[ext],
          'Cache-Control':  CACHE_CONTROL[ext] || 'public, max-age=300',
          'ETag':           etag,
          'Last-Modified':  mtime,
          'Content-Length': stat.size,
        });
        createReadStream(filePath).pipe(res);
        return;
      }
    }
  }

  // ── Forward everything else to PHP ─────────────────────────────
  const opts = {
    hostname: '127.0.0.1',
    port: PHP_PORT,
    path: req.url,
    method: req.method,
    headers: { ...req.headers, host: `127.0.0.1:${PHP_PORT}` },
  };

  const proxy = http.request(opts, (phpRes) => {
    res.writeHead(phpRes.statusCode ?? 200, phpRes.headers);
    phpRes.pipe(res, { end: true });
  });

  req.pipe(proxy, { end: true });

  proxy.on('error', () => {
    if (!res.headersSent) {
      res.writeHead(502);
      res.end('PHP server unavailable — retrying…');
    }
  });
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`Quiniela PHP proxy  :${PORT}  →  PHP :${PHP_PORT}`);
});
