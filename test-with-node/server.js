/**
 * DoJob LIFF Notification Cron Trigger
 * Runs on port 3259 — calls PHP cron every minute
 *
 * Usage:
 *   node server.js
 *
 * Endpoints:
 *   GET /         → status page (last runs, next run)
 *   GET /trigger  → trigger cron immediately
 *   GET /log      → last 50 log entries as JSON
 */

const http = require('http');
const https = require('https');

const PORT = process.env.DOJOB_PORT || 3259;

// BASE_URL can be set via env:
//   BASE_URL=https://dojob.rubyshop.co.th node server.js       ← production
//   BASE_URL=http://localhost:8888/dojob-2026 node server.js   ← dev (default)
const BASE_URL = (process.env.BASE_URL || 'http://localhost:8888/dojob-2026').replace(/\/$/, '');
const CRON_URL = `${BASE_URL}/index.php/cron`;
const requester = CRON_URL.startsWith('https') ? https : http;

const INTERVAL_MS = 60 * 1000; // 1 minute

const log = [];
let lastRun = null;
let lastResult = null;
let runCount = 0;
let errorCount = 0;
let timer = null;

// ── Logger ──────────────────────────────────────────────────────────────────

function addLog(level, msg) {
  const entry = { time: new Date().toISOString(), level, msg };
  log.unshift(entry);
  if (log.length > 200) log.pop();
  const prefix = level === 'ERROR' ? '❌' : level === 'WARN' ? '⚠️' : '✅';
  console.log(`[${entry.time}] ${prefix} ${msg}`);
}

// ── Call PHP cron ────────────────────────────────────────────────────────────

function callCron() {
  return new Promise((resolve) => {
    const start = Date.now();
    const req = requester.get(CRON_URL, { timeout: 30000 }, (res) => {
      let body = '';
      res.on('data', (chunk) => { body += chunk; });
      res.on('end', () => {
        const ms = Date.now() - start;
        const ok = res.statusCode === 200;
        resolve({ ok, status: res.statusCode, body: body.trim(), ms });
      });
    });
    req.on('error', (err) => {
      resolve({ ok: false, status: 0, body: err.message, ms: Date.now() - start });
    });
    req.on('timeout', () => {
      req.destroy();
      resolve({ ok: false, status: 0, body: 'Request timed out after 30s', ms: 30000 });
    });
  });
}

async function runCron(source = 'scheduler') {
  runCount++;
  lastRun = new Date();
  addLog('INFO', `[${source}] Calling cron... (#${runCount})`);

  const result = await callCron();
  lastResult = result;

  if (result.ok) {
    addLog('INFO', `[${source}] Done in ${result.ms}ms — "${result.body}"`);
  } else {
    errorCount++;
    addLog('ERROR', `[${source}] HTTP ${result.status} in ${result.ms}ms — "${result.body}"`);
  }
}

// ── Scheduler ────────────────────────────────────────────────────────────────

function startScheduler() {
  // Run immediately on startup
  runCron('startup').then(() => {
    // Then align to next full minute
    const msUntilNextMinute = INTERVAL_MS - (Date.now() % INTERVAL_MS);
    addLog('INFO', `Next run in ${(msUntilNextMinute / 1000).toFixed(1)}s`);
    setTimeout(() => {
      runCron('scheduler');
      timer = setInterval(() => runCron('scheduler'), INTERVAL_MS);
    }, msUntilNextMinute);
  });
}

// ── HTTP Server ───────────────────────────────────────────────────────────────

function formatUptime() {
  const s = Math.floor(process.uptime());
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const sec = s % 60;
  return `${h}h ${m}m ${sec}s`;
}

const server = http.createServer(async (req, res) => {
  const url = req.url.split('?')[0];

  if (url === '/log') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify(log.slice(0, 50), null, 2));
  }

  if (url === '/trigger') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: true, message: 'Triggering cron...' }));
    runCron('manual-trigger');
    return;
  }

  // Status page
  res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
  const logHtml = log.slice(0, 30).map(e => {
    const color = e.level === 'ERROR' ? '#e74c3c' : e.level === 'WARN' ? '#f39c12' : '#2ecc71';
    return `<tr>
      <td style="color:#999;white-space:nowrap;padding-right:12px">${e.time.replace('T',' ').replace('Z','')}</td>
      <td style="color:${color};padding-right:8px">${e.level}</td>
      <td>${e.msg.replace(/</g,'&lt;')}</td>
    </tr>`;
  }).join('');

  res.end(`<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>DoJob Cron Trigger</title>
  <meta http-equiv="refresh" content="30">
  <style>
    body { font-family: monospace; background:#111; color:#eee; padding:24px; }
    h2 { color:#3498db; margin-bottom:4px }
    .stat { display:inline-block; background:#1e1e1e; border:1px solid #333; border-radius:6px; padding:12px 20px; margin:8px 8px 8px 0 }
    .stat .val { font-size:1.6em; font-weight:bold; color:#2ecc71 }
    .stat .lbl { font-size:.8em; color:#888; margin-top:2px }
    .err .val { color:#e74c3c }
    a.btn { display:inline-block; background:#2980b9; color:#fff; text-decoration:none; padding:8px 18px; border-radius:5px; margin-top:12px }
    a.btn:hover { background:#3498db }
    table { width:100%; border-collapse:collapse; margin-top:16px }
    td { padding:3px 6px; border-bottom:1px solid #222; font-size:.85em; vertical-align:top }
    .tag { background:#1a3a4a; color:#5bc8f5; padding:1px 6px; border-radius:3px; font-size:.8em }
  </style>
</head>
<body>
  <h2>🔔 DoJob Cron Trigger</h2>
  <p style="color:#888">Port <b style="color:#fff">${PORT}</b> &nbsp;|&nbsp; Uptime <b style="color:#fff">${formatUptime()}</b> &nbsp;|&nbsp; Interval <b style="color:#fff">every 1 min</b></p>
  <p style="color:#666">Target: <span style="color:#aaa">${CRON_URL}</span></p>

  <div>
    <div class="stat"><div class="val">${runCount}</div><div class="lbl">Total runs</div></div>
    <div class="stat ${errorCount > 0 ? 'err' : ''}"><div class="val">${errorCount}</div><div class="lbl">Errors</div></div>
    <div class="stat"><div class="val">${lastRun ? lastRun.toLocaleTimeString('th-TH') : '-'}</div><div class="lbl">Last run</div></div>
    <div class="stat"><div class="val">${lastResult ? lastResult.status || 'ERR' : '-'}</div><div class="lbl">Last status</div></div>
  </div>

  <a class="btn" href="/trigger">▶ Trigger now</a>
  <a class="btn" href="/log" style="background:#444">📋 Raw log JSON</a>

  <h3 style="margin-top:24px;color:#888">Recent log (auto-refresh 30s)</h3>
  <table>${logHtml || '<tr><td style="color:#555">No logs yet</td></tr>'}</table>
</body>
</html>`);
});

// ── Start ─────────────────────────────────────────────────────────────────────

server.listen(PORT, () => {
  addLog('INFO', `Server started on http://localhost:${PORT}`);
  addLog('INFO', `Cron target: ${CRON_URL}`);
  startScheduler();
});

process.on('SIGINT', () => {
  addLog('WARN', 'Shutting down...');
  if (timer) clearInterval(timer);
  server.close(() => process.exit(0));
});
