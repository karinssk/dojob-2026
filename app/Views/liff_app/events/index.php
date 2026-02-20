<div class="page-header">
  <h1>Events</h1>
</div>

<div class="calendar-toolbar">
  <div class="calendar-nav">
    <button class="btn btn-secondary btn-sm" onclick="calPrev()">‹</button>
    <div class="calendar-title" id="cal-title">—</div>
    <button class="btn btn-secondary btn-sm" onclick="calNext()">›</button>
  </div>
  <div class="calendar-view-tabs">
    <button class="tab-btn active" data-view="month" onclick="setView('month')">เดือน</button>
    <button class="tab-btn" data-view="week" onclick="setView('week')">สัปดาห์</button>
    <button class="tab-btn" data-view="day" onclick="setView('day')">วัน</button>
  </div>
</div>

<div id="calendar-container"></div>

<script>
const CAL = {
  view: 'month',
  date: new Date(),
  events: [],
};

function parseDateStr(s) {
  if (!s) return null;
  const parts = s.split('-').map(Number);
  if (parts.length !== 3) return null;
  return new Date(parts[0], parts[1] - 1, parts[2]);
}

function dateOnly(d) {
  return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

function dayDiff(a, b) {
  const ms = dateOnly(b) - dateOnly(a);
  return Math.round(ms / 86400000);
}

function maxDate(a, b) { return a > b ? a : b; }
function minDate(a, b) { return a < b ? a : b; }
function sameDate(a, b) { return a && b && dateOnly(a).getTime() === dateOnly(b).getTime(); }

function setView(v) {
  CAL.view = v;
  document.querySelectorAll('.calendar-view-tabs .tab-btn').forEach(b => {
    b.classList.toggle('active', b.dataset.view === v);
  });
  renderCalendar();
  fetchEvents();
}

function calPrev() {
  const d = new Date(CAL.date);
  if (CAL.view === 'month') d.setMonth(d.getMonth() - 1);
  if (CAL.view === 'week')  d.setDate(d.getDate() - 7);
  if (CAL.view === 'day')   d.setDate(d.getDate() - 1);
  CAL.date = d;
  fetchEvents();
}

function calNext() {
  const d = new Date(CAL.date);
  if (CAL.view === 'month') d.setMonth(d.getMonth() + 1);
  if (CAL.view === 'week')  d.setDate(d.getDate() + 7);
  if (CAL.view === 'day')   d.setDate(d.getDate() + 1);
  CAL.date = d;
  fetchEvents();
}

function formatDate(d) {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function getRange() {
  const d = new Date(CAL.date);
  if (CAL.view === 'month') {
    const start = new Date(d.getFullYear(), d.getMonth(), 1);
    const end   = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    return { start, end };
  }
  if (CAL.view === 'week') {
    const day = (d.getDay() + 6) % 7; // Monday=0
    const start = new Date(d);
    start.setDate(d.getDate() - day);
    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    return { start, end };
  }
  return { start: d, end: d };
}

async function fetchEvents() {
  const { start, end } = getRange();
  const res = await LiffApp.api('liff/api/events/calendar', 'POST', {
    start: formatDate(start),
    end: formatDate(end)
  });
  if (!res.success) {
    LiffApp.toast(res.message || 'โหลดอีเวนต์ไม่สำเร็จ', 'error');
    CAL.events = [];
  } else {
    CAL.events = res.events || [];
  }
  renderCalendar();
}

function renderCalendar() {
  const { start, end } = getRange();
  const title = document.getElementById('cal-title');
  if (CAL.view === 'month') {
    title.textContent = start.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' });
  } else if (CAL.view === 'week') {
    title.textContent = `${start.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' })} – ${end.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' })}`;
  } else {
    title.textContent = start.toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  const container = document.getElementById('calendar-container');
  container.innerHTML = '';

  if (CAL.view === 'month') {
    container.appendChild(renderMonth(start));
  } else if (CAL.view === 'week') {
    container.appendChild(renderList(start, 7));
  } else {
    container.appendChild(renderList(start, 1));
  }
}

function renderMonth(start) {
  const wrap = document.createElement('div');
  wrap.className = 'gcal-month';

  const weekdays = document.createElement('div');
  weekdays.className = 'gcal-weekdays';
  ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(d => {
    const el = document.createElement('div');
    el.textContent = d;
    weekdays.appendChild(el);
  });
  wrap.appendChild(weekdays);

  const year = start.getFullYear();
  const month = start.getMonth();
  const first = new Date(year, month, 1);
  const last  = new Date(year, month + 1, 0);

  const gridStart = new Date(first);
  const firstDay = (first.getDay() + 6) % 7; // Monday=0
  gridStart.setDate(first.getDate() - firstDay);

  const gridEnd = new Date(last);
  const lastDay = (last.getDay() + 6) % 7;
  gridEnd.setDate(last.getDate() + (6 - lastDay));

  for (let wk = new Date(gridStart); wk <= gridEnd; wk.setDate(wk.getDate() + 7)) {
    const weekStart = new Date(wk);
    const weekEnd = new Date(wk);
    weekEnd.setDate(weekStart.getDate() + 6);

    const week = document.createElement('div');
    week.className = 'gcal-week';

    const days = document.createElement('div');
    days.className = 'gcal-days';
    const dayCells = [];

    for (let i = 0; i < 7; i++) {
      const d = new Date(weekStart);
      d.setDate(weekStart.getDate() + i);
      const cell = document.createElement('div');
      cell.className = 'gcal-day';
      if (d.getMonth() !== month) cell.classList.add('outside');
      if (sameDate(d, new Date())) cell.classList.add('today');

      const dateEl = document.createElement('div');
      dateEl.className = 'gcal-date';
      dateEl.textContent = d.getDate();
      cell.appendChild(dateEl);

      cell.onclick = () => { CAL.view = 'day'; CAL.date = d; setView('day'); fetchEvents(); };
      days.appendChild(cell);
      dayCells.push(cell);
    }

    const bars = document.createElement('div');
    bars.className = 'gcal-bars';

    const weekEvents = eventsForRange(weekStart, weekEnd);
    const rows = [];
    const maxRows = 3;
    const moreCount = new Array(7).fill(0);

    weekEvents.forEach(ev => {
      const evStart = parseDateStr(ev.start_date);
      const evEnd = parseDateStr(ev.end_date || ev.start_date);
      if (!evStart || !evEnd) return;

      const segStart = maxDate(evStart, weekStart);
      const segEnd = minDate(evEnd, weekEnd);

      const startIdx = dayDiff(weekStart, segStart);
      const span = dayDiff(segStart, segEnd) + 1;

      let row = 0;
      for (; row < rows.length; row++) {
        let free = true;
        for (let c = startIdx; c < startIdx + span; c++) {
          if (rows[row][c]) { free = false; break; }
        }
        if (free) break;
      }
      if (row === rows.length) rows.push(new Array(7).fill(false));
      if (row >= maxRows) {
        moreCount[startIdx] = (moreCount[startIdx] || 0) + 1;
        return;
      }
      for (let c = startIdx; c < startIdx + span; c++) rows[row][c] = true;

      const bar = document.createElement('div');
      bar.className = 'gcal-bar';
      bar.style.gridColumn = `${startIdx + 1} / span ${span}`;
      bar.style.gridRow = `${row + 1}`;
      bar.style.background = ev.color || '#4F7DF3';
      bar.title = ev.title;
      const isStart = sameDate(evStart, segStart);
      const isEnd = sameDate(evEnd, segEnd);
      if (!isStart) bar.classList.add('no-start');
      if (!isEnd) bar.classList.add('no-end');
      bar.textContent = isStart ? ev.title : '';
      bar.onclick = () => location.href = '<?= get_uri('liff/app/events/') ?>' + ev.id;
      bars.appendChild(bar);
    });

    moreCount.forEach((cnt, idx) => {
      if (!cnt || !dayCells[idx]) return;
      const more = document.createElement('div');
      more.className = 'gcal-more';
      more.textContent = `+${cnt} more`;
      dayCells[idx].appendChild(more);
    });

    week.appendChild(days);
    week.appendChild(bars);
    wrap.appendChild(week);
  }

  return wrap;
}

function renderList(start, days) {
  const wrap = document.createElement('div');
  wrap.className = 'calendar-list';
  for (let i = 0; i < days; i++) {
    const date = new Date(start);
    date.setDate(start.getDate() + i);
    const items = eventsForDate(date);

    const block = document.createElement('div');
    block.className = 'calendar-day-block';
    block.innerHTML = `<div class="calendar-day-title">${date.toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short' })}</div>`;
    if (!items.length) {
      const empty = document.createElement('div');
      empty.className = 'calendar-empty';
      empty.textContent = 'ไม่มีอีเวนต์';
      block.appendChild(empty);
    } else {
      items.forEach(ev => {
        const card = document.createElement('div');
        card.className = 'calendar-event-card';
        card.onclick = () => location.href = '<?= get_uri('liff/app/events/') ?>' + ev.id;
        const time = `${ev.start_time || ''}${ev.end_time ? ' – ' + ev.end_time : ''}`.trim();
        card.innerHTML = `
          <div class="calendar-event-color" style="background:${ev.color || '#6C8EF5'}"></div>
          <div class="calendar-event-body">
            <div class="calendar-event-title">${ev.title}</div>
            <div class="calendar-event-time">${time || 'ทั้งวัน'}</div>
          </div>`;
        block.appendChild(card);
      });
    }
    wrap.appendChild(block);
  }
  return wrap;
}

function eventsForDate(date) {
  return eventsForRange(date, date);
}

function eventsForRange(start, end) {
  const s = formatDate(start);
  const e = formatDate(end);
  return CAL.events.filter(ev => {
    const evStart = ev.start_date;
    const evEnd = ev.end_date || ev.start_date;
    return evStart <= e && evEnd >= s;
  });
}

function initCalendar() {
  renderCalendar(); // render skeleton immediately — no LiffApp needed
  waitAndFetch();
}

function waitAndFetch() {
  if (!window.LiffApp) { setTimeout(waitAndFetch, 50); return; }
  fetchEvents();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initCalendar);
} else {
  initCalendar();
}
</script>
