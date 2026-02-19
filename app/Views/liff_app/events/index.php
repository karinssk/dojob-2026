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

function setView(v) {
  CAL.view = v;
  document.querySelectorAll('.calendar-view-tabs .tab-btn').forEach(b => {
    b.classList.toggle('active', b.dataset.view === v);
  });
  renderCalendar();
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
  return d.toISOString().slice(0,10);
}

function getRange() {
  const d = new Date(CAL.date);
  if (CAL.view === 'month') {
    const start = new Date(d.getFullYear(), d.getMonth(), 1);
    const end   = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    return { start, end };
  }
  if (CAL.view === 'week') {
    const day = d.getDay(); // 0..6
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
  CAL.events = res.success ? (res.events || []) : [];
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
  wrap.className = 'calendar-month';

  const weekdays = document.createElement('div');
  weekdays.className = 'calendar-weekdays';
  ['อา','จ','อ','พ','พฤ','ศ','ส'].forEach(d => {
    const el = document.createElement('div');
    el.textContent = d;
    weekdays.appendChild(el);
  });

  const grid = document.createElement('div');
  grid.className = 'calendar-grid';

  const year = start.getFullYear();
  const month = start.getMonth();
  const first = new Date(year, month, 1);
  const last  = new Date(year, month + 1, 0);
  const offset = first.getDay();
  const totalDays = last.getDate();

  for (let i = 0; i < offset; i++) {
    const empty = document.createElement('div');
    empty.className = 'calendar-cell empty';
    grid.appendChild(empty);
  }

  for (let d = 1; d <= totalDays; d++) {
    const date = new Date(year, month, d);
    const cell = document.createElement('div');
    cell.className = 'calendar-cell';
    const label = document.createElement('div');
    label.className = 'calendar-date';
    label.textContent = d;
    cell.appendChild(label);

    const dots = document.createElement('div');
    dots.className = 'calendar-dots';
    const dayEvents = eventsForDate(date);
    dayEvents.slice(0, 3).forEach(ev => {
      const dot = document.createElement('span');
      dot.style.background = ev.color || '#6C8EF5';
      dots.appendChild(dot);
    });
    if (dayEvents.length) {
      cell.appendChild(dots);
    }

    const today = new Date();
    if (date.toDateString() === today.toDateString()) {
      cell.classList.add('today');
    }

    cell.onclick = () => { CAL.view = 'day'; CAL.date = date; setView('day'); fetchEvents(); };
    grid.appendChild(cell);
  }

  wrap.appendChild(weekdays);
  wrap.appendChild(grid);
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
  const d = formatDate(date);
  return CAL.events.filter(e => {
    const start = e.start_date;
    const end = e.end_date || e.start_date;
    return d >= start && d <= end;
  });
}

fetchEvents();
</script>
