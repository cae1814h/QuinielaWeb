<?php
// Shared prediction modal — include ONCE per page that uses match_row.php cards.
// Requires: csrf_token(), logged_in(), APP_URL
$_pm_csrf       = csrf_token();
$_pm_user_first = logged_in() ? htmlspecialchars(explode(' ', trim($_SESSION['name']))[0]) : '';
?>
<div id="pm-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:900;backdrop-filter:blur(3px)" onclick="closePredModal()"></div>

<div id="pm-box" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:901;
     width:min(96vw,500px);background:var(--bg2);border:1px solid var(--border-lg);border-radius:7px;
     box-shadow:0 28px 80px rgba(0,0,0,.7);overflow:hidden;flex-direction:column">

  <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 16px;border-bottom:1px solid var(--border)">
    <div id="pm-round" style="font-size:.68rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em"></div>
    <button onclick="closePredModal()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;padding:3px;border-radius:4px;display:flex;align-items:center">
      <?= icon('x', 16) ?>
    </button>
  </div>

  <div style="padding:20px;display:flex;align-items:center;gap:10px">
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:8px">
      <img id="pm-home-flag" src="" alt="" style="width:56px;height:38px;object-fit:cover;border-radius:7px;box-shadow:0 2px 10px rgba(0,0,0,.4)">
      <div id="pm-home-name" style="font-size:.95rem;font-weight:700;text-align:center"></div>
    </div>
    <div style="flex-shrink:0;text-align:center;min-width:80px">
      <div id="pm-score" style="font-size:2.2rem;font-weight:900"></div>
      <div id="pm-vs"    style="font-size:1.1rem;color:var(--text-muted);font-weight:800"></div>
      <div id="pm-date"  style="font-size:.72rem;color:var(--text-muted);margin-top:4px"></div>
      <div id="pm-venue" style="font-size:.68rem;color:var(--text-muted)"></div>
    </div>
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:8px">
      <img id="pm-away-flag" src="" alt="" style="width:56px;height:38px;object-fit:cover;border-radius:7px;box-shadow:0 2px 10px rgba(0,0,0,.4)">
      <div id="pm-away-name" style="font-size:.95rem;font-weight:700;text-align:center"></div>
    </div>
  </div>

  <div id="pm-pred-area" style="padding:0 20px 20px"></div>
</div>

<script>
const PM_CSRF       = <?= json_encode($_pm_csrf) ?>;
const PM_LOGIN      = <?= json_encode(logged_in()) ?>;
const PM_URL        = <?= json_encode(APP_URL) ?>;
const PM_USER_FIRST = <?= json_encode($_pm_user_first) ?>;

function openPredModal(card) {
  const d = JSON.parse(card.dataset.match);

  document.getElementById('pm-round').textContent     = d.round + (d.group ? ' · Grupo ' + d.group : '');
  document.getElementById('pm-home-name').textContent = d.home;
  document.getElementById('pm-away-name').textContent = d.away;
  document.getElementById('pm-date').textContent      = d.date;
  document.getElementById('pm-venue').textContent     = d.venue;

  const hf = document.getElementById('pm-home-flag');
  const af = document.getElementById('pm-away-flag');
  hf.src = d.home_flag || ''; hf.style.display = d.home_flag ? 'block' : 'none';
  af.src = d.away_flag || ''; af.style.display = d.away_flag ? 'block' : 'none';

  const scoreEl = document.getElementById('pm-score');
  const vsEl    = document.getElementById('pm-vs');
  if (d.status === 'finished' && d.home_score !== null && d.home_score !== '') {
    scoreEl.textContent = d.home_score + ' – ' + d.away_score;
    scoreEl.style.display = 'block'; vsEl.style.display = 'none';
  } else {
    scoreEl.style.display = 'none'; vsEl.textContent = 'VS'; vsEl.style.display = 'block';
  }

  const area = document.getElementById('pm-pred-area');

  if (!PM_LOGIN) {
    area.innerHTML = `
      <div style="text-align:center;padding:16px 0">
        <p style="color:var(--text-muted);margin-bottom:12px;font-size:.88rem">Inicia sesión para predecir este partido</p>
        <a href="${PM_URL}/login.php" class="btn btn-primary">Iniciar Sesión</a>
      </div>`;
  } else if (d.status === 'finished') {
    const pts = d.pred_pts;
    const hasPred = d.pred_home !== '' && d.pred_away !== '';
    const hasPoints = hasPred && pts !== null && pts !== '';

    const SVG_CHECK2 = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`;
    const SVG_TROPHY  = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>`;

    let color, bg, border, ptsLabel, congrats;
    if (hasPoints && pts == 3) {
      color    = '#00d896'; bg = 'rgba(0,216,150,.13)'; border = 'rgba(0,216,150,.28)';
      ptsLabel = '+3 puntos';
      congrats = `¡Felicidades ${PM_USER_FIRST}! Acertaste el ganador y marcador de este partido, ganaste 3 puntos.`;
    } else if (hasPoints && pts == 1) {
      color    = '#fbbf24'; bg = 'rgba(251,191,36,.13)'; border = 'rgba(251,191,36,.28)';
      ptsLabel = '+1 punto';
      congrats = `¡Felicidades ${PM_USER_FIRST}! Acertaste el ganador de este partido, ganaste 1 punto.`;
    } else if (hasPoints) {
      color    = '#ef4444'; bg = 'rgba(239,68,68,.13)'; border = 'rgba(239,68,68,.28)';
      ptsLabel = '0 puntos';
      congrats = `Lo sentimos ${PM_USER_FIRST} esta vez no se pudo. Seguro que a la proxima te ira bien, sigue pronosticando!`;
    }

    area.innerHTML = `<div style="border-top:1px solid var(--border);margin:0 -20px 16px"></div>` + (
      hasPred && hasPoints
        ? `<div style="display:flex;flex-direction:column;align-items:center;gap:14px;padding:4px 0 8px">
             <div style="display:flex;flex-direction:column;align-items:center;gap:8px">
               <div style="width:42px;height:42px;border-radius:50%;border:1.5px solid #00d896;background:rgba(0,216,150,.13);display:flex;align-items:center;justify-content:center;color:#00d896">${SVG_CHECK2}</div>
               <div style="font-size:1rem;font-weight:800;color:var(--text)">Partido finalizado</div>
             </div>
             <div style="display:flex;flex-direction:column;align-items:center;gap:6px">
               <div style="font-size:.72rem;color:var(--text-muted);font-weight:600">Mi pronóstico</div>
               <div style="border:1.5px solid ${color};background:${bg};border-radius:12px;padding:12px 28px;text-align:center;box-shadow:0 0 18px ${bg}">
                 <div style="display:flex;justify-content:center;margin-bottom:5px;color:${color}">${SVG_TROPHY}</div>
                 <div style="font-size:2rem;font-weight:900;color:${color};line-height:1;letter-spacing:-.02em">${d.pred_home} – ${d.pred_away}</div>
                 <div style="margin-top:9px">
                   <span style="display:inline-flex;align-items:center;border-radius:7px;padding:2px 12px;font-size:.78rem;font-weight:800;color:${color};background:${bg};border:1px solid ${border}">${ptsLabel}</span>
                 </div>
               </div>
             </div>
             <div style="width:100%;border-radius:10px;border:1px solid ${border};background:rgba(0,0,0,.15);padding:11px 14px;text-align:center;font-size:.82rem;font-weight:600;color:${color};line-height:1.5">
               ${congrats}
             </div>
           </div>`
        : hasPred
          ? `<div style="text-align:center;padding:8px 0">
               <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:9px">Tu predicción</div>
               <span class="mc-pred-badge mc-pred-pending" style="display:inline-flex;font-size:.9rem;padding:5px 14px;gap:6px">${d.pred_home} – ${d.pred_away}</span>
               <div style="margin-top:10px;font-size:.75rem;color:var(--text-muted)">Puntos pendientes de calcular</div>
             </div>`
          : `<div style="text-align:center;padding:8px 0;color:var(--text-muted);font-size:.82rem">No realizaste predicción para este partido</div>`
    );
  } else if (d.locked == 1) {
    const hasPred = d.pred_home !== '' && d.pred_away !== '';
    area.innerHTML = `
      <div style="display:flex;align-items:center;gap:12px;background:var(--surface2);border-radius:7px;padding:14px">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#00d896;flex-shrink:0"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <div>
          <div style="font-weight:600;font-size:.9rem">Predicciones cerradas</div>
          <div style="font-size:.78rem;color:var(--text-muted)">
            ${d.status === 'scheduled' ? 'Cierran 10 minutos antes del inicio' : 'El partido ya ha comenzado o finalizado'}
          </div>
        </div>
        ${hasPred ? `<div style="margin-left:auto;text-align:right;font-weight:700;font-size:.9rem">${d.pred_home} – ${d.pred_away}</div>` : ''}
      </div>`;
  } else if (d.pred_home !== '' && d.pred_away !== '') {
    area.innerHTML = `
      <div style="border-top:1px solid var(--border);margin:0 -20px 16px"></div>
      <div style="text-align:center;padding:0 0 4px">
        <div style="font-size:.65rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:9px">Tu pronóstico</div>
        <span class="mc-pred-badge mc-pred-pending" style="display:inline-flex;font-size:1rem;padding:6px 18px;gap:6px;font-weight:800;border-radius:7px">
          ${d.pred_home} – ${d.pred_away}
        </span>
        <div style="margin-top:13px;display:flex;align-items:center;justify-content:center;gap:7px;font-size:.75rem;color:var(--text-muted)">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          El pronóstico ya fue registrado y no puede modificarse.
        </div>
      </div>`;
  } else {
    area.innerHTML = `
      <div id="pm-feedback" style="margin-bottom:10px;display:none"></div>
      <form id="pm-form" style="display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;align-items:center;gap:10px">
          <div style="flex:1;text-align:center">
            <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:6px">${d.home}</div>
            <input type="number" id="pm-ph" class="score-input" min="0" max="20" value="0" required>
          </div>
          <div style="font-size:1.6rem;color:var(--text-muted);font-weight:300;padding:0 4px">–</div>
          <div style="flex:1;text-align:center">
            <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:6px">${d.away}</div>
            <input type="number" id="pm-pa" class="score-input" min="0" max="20" value="0" required>
          </div>
        </div>
        <button type="submit" id="pm-submit" class="btn btn-primary w-full" style="gap:8px">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Guardar Predicción
        </button>
      </form>
      <div style="font-size:.68rem;color:var(--text-muted);text-align:center;margin-top:10px">
        Cierran 10 min antes del inicio · 3 pts marcador exacto · 1 pt ganador correcto
      </div>`;

    document.getElementById('pm-form').dataset.matchId = d.id;
    document.getElementById('pm-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      const btn = document.getElementById('pm-submit');
      btn.disabled = true; btn.textContent = 'Guardando…';
      const fb = document.getElementById('pm-feedback');
      try {
        const body = new URLSearchParams({
          csrf_token: PM_CSRF, match_id: d.id,
          predicted_home: document.getElementById('pm-ph').value,
          predicted_away: document.getElementById('pm-pa').value,
        });
        const res  = await fetch(PM_URL + '/save_pronostico.php', { method: 'POST', body });
        const json = await res.json();
        if (json.ok) {
          fb.style.cssText = 'display:flex;align-items:center;gap:8px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);color:#6ee7b7;border-radius:7px;padding:10px 14px;font-size:.85rem;font-weight:600';
          fb.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> ' + json.message;
          btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Guardado';
          btn.style.background = 'rgba(34,197,94,.2)'; btn.style.color = '#6ee7b7';
          const nd = JSON.parse(card.dataset.match);
          nd.pred_home = json.home; nd.pred_away = json.away; nd.locked = 1;
          card.dataset.match = JSON.stringify(nd);
          const actionBtn = card.querySelector('.mc-btn-pred');
          if (actionBtn) actionBtn.outerHTML = `<div class="mc-pred-badge mc-pred-pending">${json.home} – ${json.away}</div>`;
        } else {
          fb.style.cssText = 'display:flex;align-items:center;gap:8px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fca5a5;border-radius:7px;padding:10px 14px;font-size:.85rem;font-weight:600';
          fb.textContent = json.error || 'Error al guardar';
          btn.disabled = false; btn.textContent = 'Reintentar';
        }
      } catch(err) {
        fb.textContent = 'Error de red. Intenta de nuevo.';
        btn.disabled = false;
      }
    });
  }

  document.getElementById('pm-overlay').style.display = 'block';
  document.getElementById('pm-box').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closePredModal() {
  document.getElementById('pm-overlay').style.display = 'none';
  document.getElementById('pm-box').style.display = 'none';
  document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closePredModal(); });
</script>
