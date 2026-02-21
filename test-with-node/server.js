/**
 * DoJob LIFF Notification Scheduler
 * Runs on port 3259 — reads schedule from DB, triggers PHP at the right time.
 *
 * Usage:
 *   node server.js                                               ← dev (default)
 *   BASE_URL=https://dojob.rubyshop.co.th node server.js        ← production
 *
 * Env vars (all optional):
 *   BASE_URL      Base URL of PHP app  (default: http://localhost:8888/dojob-2026)
 *   DB_HOST       MySQL host           (default: 127.0.0.1)
 *   DB_PORT       MySQL port           (default: 8889)
 *   DB_USER       MySQL user           (default: root)
 *   DB_PASS       MySQL password       (default: root)
 *   DB_NAME       MySQL database       (default: rubyshop.co.th_dojob)
 *   DOJOB_PORT    HTTP port for this server (default: 3259)
 *
 * Endpoints:
 *   GET /              → status dashboard (auto-refresh)
 *   GET /trigger/reminder  → force-send reminder now
 *   GET /trigger/summary   → force-send summary now
 *   GET /log           → last 80 log entries as JSON
 */

const http     = require('http');
const https    = require('https');
const mysql    = require('mysql2/promise');

// ── Config ───────────────────────────────────────────────────────────────────

const PORT     = process.env.DOJOB_PORT || 3259;
const BASE_URL = (process.env.BASE_URL || 'http://localhost:8888/dojob-2026').replace(/\/$/, '');
const DB_CFG   = {
  host    : process.env.DB_HOST || '127.0.0.1',
  port    : parseInt(process.env.DB_PORT || '8889'),
  user    : process.env.DB_USER || 'root',
  password: process.env.DB_PASS || 'root',
  database: process.env.DB_NAME || 'rubyshop.co.th_dojob',
};

const requester = BASE_URL.startsWith('https') ? https : http;

// ── State ─────────────────────────────────────────────────────────────────────

const log = [];
let db    = null;
let cronSecret = null;

// Track last trigger per type to prevent double-firing within same minute
const lastFired = { reminder: 0, summary: 0 };

// ── Logger ───────────────────────────────────────────────────────────────────

function addLog(level, msg) {
  const entry = { time: new Date().toISOString(), level, msg };
  log.unshift(entry);
  if (log.length > 200) log.pop();
  const icon = level === 'ERROR' ? '❌' : level === 'WARN' ? 'WARN' : 'OK';
  console.log(`[${entry.time}] ${icon} ${msg}`);
}

// ── DB helpers ────────────────────────────────────────────────────────────────

async function connectDb() {
  db = await mysql.createConnection(DB_CFG);
  addLog('INFO', `DB connected → ${DB_CFG.host}:${DB_CFG.port}/${DB_CFG.database}`);
}

async function getSetting(key) {
  const [rows] = await db.query(
    'SELECT setting_value FROM rise_settings WHERE setting_name = ? AND deleted = 0 LIMIT 1', [key]
  );
  return rows.length ? rows[0].setting_value : null;
}

async function saveSetting(key, value) {
  await db.query(
    'INSERT INTO rise_settings (setting_name, setting_value, type) VALUES (?, ?, ?) ' +
    'ON DUPLICATE KEY UPDATE setting_value = ?',
    [key, value, 'app', value]
  );
}

/** Get or auto-create the shared secret used to authenticate Node → PHP calls */
async function getOrCreateSecret() {
  let secret = await getSetting('liff_cron_secret');
  if (!secret) {
    secret = require('crypto').randomBytes(24).toString('hex');
    await saveSetting('liff_cron_secret', secret);
    addLog('INFO', `Generated new liff_cron_secret and saved to DB`);
  }
  return secret;
}

/** Load all relevant schedule settings in one round-trip */
async function loadSchedule() {
  const keys = [
    'liff_reminder_enabled', 'liff_reminder_times', 'liff_reminder_days', 'liff_reminder_last_sent',
    'liff_summary_enabled',  'liff_summary_time',   'liff_summary_days',  'liff_summary_last_sent',
    'liff_cron_secret',
  ];
  const placeholders = keys.map(() => '?').join(',');
  const [rows] = await db.query(
    `SELECT setting_name, setting_value FROM rise_settings WHERE setting_name IN (${placeholders}) AND deleted = 0`, keys
  );
  const s = {};
  rows.forEach(r => { s[r.setting_name] = r.setting_value; });
  return s;
}

// ── Schedule check ────────────────────────────────────────────────────────────

/**
 * Returns true if NOW matches one of the configured time slots.
 * Slot window: ±2 minutes around the configured HH:MM.
 * Cooldown: won't re-fire if last_sent was within 40 minutes.
 */
function isTimeNow(times, days, lastSent) {
  if (!times || !times.length || !days || !days.length) return false;

  const now      = new Date();
  const dowIso   = now.getDay() === 0 ? 7 : now.getDay(); // 1=Mon, 7=Sun
  if (!days.includes(dowIso)) return false;

  const nowMin   = now.getHours() * 60 + now.getMinutes();

  // Cooldown: skip if sent within last 40 minutes
  if (lastSent) {
    const lastMs = new Date(lastSent).getTime();
    if ((Date.now() - lastMs) < 40 * 60 * 1000) return false;
  }

  for (const t of times) {
    const [h, m] = t.split(':').map(Number);
    const slotMin = h * 60 + m;
    if (Math.abs(nowMin - slotMin) <= 2) return true;
  }
  return false;
}

// ── Call PHP endpoint ─────────────────────────────────────────────────────────

function callPhp(path, secret) {
  return new Promise((resolve) => {
    const sep  = path.includes('?') ? '&' : '?';
    const url  = `${BASE_URL}/index.php/${path}${sep}secret=${encodeURIComponent(secret)}`;
    const start = Date.now();

    const req = requester.get(url, { timeout: 30000 }, (res) => {
      let body = '';
      res.on('data', c => body += c);
      res.on('end', () => {
        resolve({ ok: res.statusCode === 200, status: res.statusCode, body: body.trim(), ms: Date.now() - start });
      });
    });
    req.on('error', err => resolve({ ok: false, status: 0, body: err.message, ms: Date.now() - start }));
    req.on('timeout', () => { req.destroy(); resolve({ ok: false, status: 0, body: 'timeout', ms: 30000 }); });
  });
}

async function fireNotification(type, secret, source = 'scheduler') {
  addLog('INFO', `[${source}] Firing ${type}…`);
  const result = await callPhp(`cron/run_liff?type=${type}`, secret);
  lastFired[type] = Date.now();

  if (result.ok) {
    try {
      const json = JSON.parse(result.body);
      addLog('INFO', `[${source}] ${type} → count=${json.count ?? '?'} in ${result.ms}ms`);
    } catch {
      addLog('INFO', `[${source}] ${type} → HTTP 200 in ${result.ms}ms`);
    }
  } else {
    addLog('ERROR', `[${source}] ${type} → HTTP ${result.status}: ${result.body.slice(0, 120)}`);
  }
}

// ── Main scheduler tick (runs every minute) ───────────────────────────────────

async function tick() {
  try {
    const s = await loadSchedule();
    cronSecret = s.liff_cron_secret || cronSecret;

    if (!cronSecret) {
      addLog('WARN', 'No liff_cron_secret in DB — run once with DB connected to generate it');
      return;
    }

    const now = Date.now();
    const DEBOUNCE = 55 * 1000; // don't fire same type twice within 55s

    // ── Reminder ──
    const reminderEnabled = s.liff_reminder_enabled === '1';
    const reminderTimes   = JSON.parse(s.liff_reminder_times || '[]');
    const reminderDays    = JSON.parse(s.liff_reminder_days  || '[]');
    const reminderLast    = s.liff_reminder_last_sent || null;

    if (reminderEnabled) {
      if (isTimeNow(reminderTimes, reminderDays, reminderLast)) {
        if (now - lastFired.reminder > DEBOUNCE) {
          await fireNotification('reminder', cronSecret);
        }
      }
    }

    // ── Summary ──
    const summaryEnabled = s.liff_summary_enabled === '1';
    const summaryTimes   = s.liff_summary_time ? [s.liff_summary_time] : [];
    const summaryDays    = JSON.parse(s.liff_summary_days || '[]');
    const summaryLast    = s.liff_summary_last_sent || null;

    if (summaryEnabled) {
      if (isTimeNow(summaryTimes, summaryDays, summaryLast)) {
        if (now - lastFired.summary > DEBOUNCE) {
          await fireNotification('summary', cronSecret);
        }
      }
    }

    if (!reminderEnabled && !summaryEnabled) {
      addLog('INFO', 'Tick — both reminder and summary are disabled in DB settings');
    }

  } catch (err) {
    addLog('ERROR', 'tick() error: ' + err.message);
    // Try to reconnect DB on connection errors
    if (err.code === 'PROTOCOL_CONNECTION_LOST' || err.code === 'ECONNREFUSED') {
      addLog('WARN', 'DB disconnected — reconnecting…');
      try { await connectDb(); await getOrCreateSecret(); } catch (_) {}
    }
  }
}

// ── HTTP status server ────────────────────────────────────────────────────────

function formatUptime() {
  const s = Math.floor(process.uptime());
  return `${Math.floor(s/3600)}h ${Math.floor((s%3600)/60)}m ${s%60}s`;
}

const server = http.createServer(async (req, res) => {
  const url = req.url.split('?')[0];

  if (url === '/log') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify(log.slice(0, 80), null, 2));
  }

  if (url === '/trigger/reminder' || url === '/trigger/summary') {
    const type = url === '/trigger/reminder' ? 'reminder' : 'summary';
    if (!cronSecret) {
      res.writeHead(503, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ error: 'Secret not loaded yet — retry in a moment' }));
    }
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: true, message: `Triggering ${type}…` }));
    fireNotification(type, cronSecret, 'manual');
    return;
  }

  // ── Status dashboard ──
  const logHtml = log.slice(0, 40).map(e => {
    const color = e.level === 'ERROR' ? '#e74c3c' : e.level === 'WARN' ? '#f39c12' : '#2ecc71';
    return `<tr>
      <td style="color:#888;white-space:nowrap;padding-right:12px">${e.time.replace('T',' ').replace('Z','')}</td>
      <td style="color:${color};padding-right:8px">${e.level}</td>
      <td>${e.msg.replace(/</g,'&lt;')}</td>
    </tr>`;
  }).join('');

  res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
  res.end(`<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>DoJob Scheduler</title>
  <meta http-equiv="refresh" content="30">
  <style>
    body{font-family:monospace;background:#111;color:#eee;padding:24px}
    h2{color:#3498db;margin-bottom:4px}
    .stat{display:inline-block;background:#1e1e1e;border:1px solid #333;border-radius:6px;padding:12px 20px;margin:8px 8px 8px 0}
    .stat .val{font-size:1.4em;font-weight:bold;color:#2ecc71}
    .stat .lbl{font-size:.8em;color:#888;margin-top:2px}
    a.btn{display:inline-block;background:#2980b9;color:#fff;text-decoration:none;padding:8px 16px;border-radius:5px;margin:4px 4px 4px 0}
    a.btn:hover{background:#3498db}
    a.btn.green{background:#27ae60}
    table{width:100%;border-collapse:collapse;margin-top:16px}
    td{padding:3px 6px;border-bottom:1px solid #222;font-size:.85em;vertical-align:top}
  </style>
</head>
<body>
  <h2>DoJob LIFF Scheduler</h2>
  <p style="color:#888">Port <b style="color:#fff">${PORT}</b> &nbsp;|&nbsp;
     Uptime <b style="color:#fff">${formatUptime()}</b> &nbsp;|&nbsp;
     DB <b style="color:#fff">${DB_CFG.host}:${DB_CFG.port}</b> &nbsp;|&nbsp;
     Target <b style="color:#fff">${BASE_URL}</b></p>

  <div>
    <div class="stat"><div class="val">${lastFired.reminder ? new Date(lastFired.reminder).toLocaleTimeString('th-TH') : '-'}</div><div class="lbl">Reminder fired</div></div>
    <div class="stat"><div class="val">${lastFired.summary  ? new Date(lastFired.summary ).toLocaleTimeString('th-TH') : '-'}</div><div class="lbl">Summary fired</div></div>
    <div class="stat"><div class="val">${cronSecret ? 'OK' : '...'}</div><div class="lbl">Secret</div></div>
  </div>

  <a class="btn green" href="/trigger/reminder">Send Reminder now</a>
  <a class="btn" href="/trigger/summary">Send Summary now</a>
  <a class="btn" href="/log" style="background:#444">Raw log JSON</a>

  <h3 style="margin-top:24px;color:#888">Log (auto-refresh 30s)</h3>
  <table>${logHtml || '<tr><td style="color:#555">No logs yet</td></tr>'}</table>
</body>
</html>`);
});

// ── Start ─────────────────────────────────────────────────────────────────────

async function start() {
  try {
    await connectDb();
    cronSecret = await getOrCreateSecret();
    addLog('INFO', `Secret ready (${cronSecret.slice(0,8)}…)`);
  } catch (err) {
    addLog('ERROR', 'DB connect failed: ' + err.message);
    addLog('WARN', `Will retry DB on next tick. Check DB_HOST/PORT/USER/PASS`);
  }

  server.listen(PORT, () => {
    addLog('INFO', `Server started → http://localhost:${PORT}`);
    addLog('INFO', `PHP target → ${BASE_URL}/index.php/cron/run_liff`);
  });

  // Align to next full minute, then tick every minute
  const msUntilNext = 60000 - (Date.now() % 60000);
  addLog('INFO', `First tick in ${(msUntilNext/1000).toFixed(1)}s (aligned to clock minute)`);

  setTimeout(() => {
    tick();
    setInterval(tick, 60000);
  }, msUntilNext);
}

start();

process.on('SIGINT', () => {
  addLog('WARN', 'Shutting down…');
  if (db) db.end();
  server.close(() => process.exit(0));
});
