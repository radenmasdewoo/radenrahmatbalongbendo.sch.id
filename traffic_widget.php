<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Traffic Widget — BP3NU Monitor</title>
<style>
        /* --- FLOATING TRAFFIC REPORT (MODERN REDESIGN) --- */
        .traffic-widget {
            position: fixed; /* traffic_widget.php style */
            bottom: 20px;
            right: 20px;
            z-index: 100;
            width: 250px;
            background: linear-gradient(135deg, rgba(16, 24, 39, 0.9) 0%, rgba(13, 40, 24, 0.85) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 20px;
            color: #f8fafc;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: bottom right; /* Fixed position anchors bottom right */
            cursor: pointer;
            overflow: hidden;
            user-select: none;
        }

        .traffic-widget::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(201, 168, 76, 0.05) 0%, transparent 50%);
            z-index: -1;
            pointer-events: none;
        }

        .traffic-widget:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            border-color: rgba(201, 168, 76, 0.3);
        }
        .traffic-widget.expanded {
            width: 280px;
        }

        .traffic-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .traffic-widget .logo-img {
            width: 50px;
            height: auto;
            max-height: 45px;
            object-fit: contain;
            flex-shrink: 0;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }

        .traffic-header h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #f1f5f9;
            margin: 0;
            letter-spacing: 0.3px;
        }

        .traffic-kpi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .traffic-kpi {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 14px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .traffic-widget:hover .traffic-kpi {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .traffic-kpi .label {
            font-size: 9px;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 1.2px;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .traffic-kpi .value {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #fbbf24;
            text-shadow: 0 0 20px rgba(251, 191, 36, 0.3);
            line-height: 1;
        }

        .traffic-online {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
            padding: 8px;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .traffic-online .dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 10px #22c55e, 0 0 20px #22c55e;
            animation: pulse-online 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse-online {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .5; transform: scale(1.2); box-shadow: 0 0 15px #22c55e, 0 0 30px #22c55e; }
        }

        .traffic-online span {
            font-size: 11px;
            font-weight: 600;
            color: #4ade80;
            letter-spacing: 0.3px;
        }

        .traffic-chart {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 55px;
            margin-bottom: 8px;
            padding-top: 10px;
            border-top: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .traffic-bar {
            flex: 1;
            background: linear-gradient(to top, rgba(59, 130, 246, 0.4), rgba(147, 197, 253, 0.8));
            border-radius: 4px 4px 0 0;
            min-height: 4px;
            position: relative;
            cursor: default;
            transition: height 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.3s ease;
        }

        .traffic-bar:hover {
            background: linear-gradient(to top, #3b82f6, #93c5fd);
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
        }

        .traffic-bar.active {
            background: linear-gradient(to top, #f59e0b, #fcd34d);
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.5);
        }

        .traffic-bar[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            font-size: 10px;
            padding: 5px 8px;
            border-radius: 6px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 99999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-weight: 500;
        }
        
        .bar-label {
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            margin-top: 4px;
            font-weight: 500;
        }
        
        .bar-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            justify-content: flex-end;
        }

        .traffic-footer {
            font-size: 8px;
            text-align: center;
            color: #64748b;
            margin-top: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

  /* Loading shimmer */
  .shimmer {
    background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.12) 50%, rgba(255,255,255,0.05) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 4px;
    display: inline-block;
  }
  @keyframes shimmer {
    0%   { background-position: -200% 0; }
    100% { background-position:  200% 0; }
  }
</style>
</head>
<body>

<!-- ─── TRAFFIC WIDGET ─────────────────────────────────────────────────── -->
<div class="traffic-widget" id="trafficWidget" onclick="this.classList.toggle('expanded')">
                    <div class="traffic-header">
                        <img src="/logo-header.webp" alt="Logo BP3NU Raden Rahmat Balongbendo" class="logo-img" width="80" height="60">
                        <div>
                            <h4>Traffic Report</h4>
                            <div style="font-size: 8px; color: rgba(255,255,255,0.4);">radenrahmatbalongbendo.sch.id
                            </div>
                        </div>
                    </div>

                    <div class="traffic-online">
                        <div class="dot"></div>
                        <span><span id="widget-online">1</span> Online Sekarang</span>
                    </div>

                    <div class="traffic-kpi-grid">
                        <div class="traffic-kpi">
                            <div class="label">Hari Ini</div>
                            <div class="value" id="widget-today">0</div>
                        </div>
                        <div class="traffic-kpi">
                            <div class="label">Total</div>
                            <div class="value" id="widget-total">1</div>
                        </div>
                    </div>

                    <div style="font-size: 9px; color: rgba(255,255,255,0.4); margin-bottom: 5px;">Trafik 7 Hari
                        Terakhir</div>
                    <div class="traffic-chart" id="widgetChart"><div class="traffic-bar" title="Hari 1: 0" style="height: 5%;"></div><div class="traffic-bar" title="Hari 2: 0" style="height: 5%;"></div><div class="traffic-bar" title="Hari 3: 0" style="height: 5%;"></div><div class="traffic-bar" title="Hari 4: 0" style="height: 5%;"></div><div class="traffic-bar" title="Hari 5: 1" style="height: 20%;"></div><div class="traffic-bar" title="Hari 6: 0" style="height: 5%;"></div><div class="traffic-bar active" title="Hari 7: 0" style="height: 5%;"></div></div>

                    <div class="traffic-footer">
                        BP3NU Monitor © 2026
                    </div>
                </div>

<!-- ─── TRAFFIC WIDGET SCRIPT ─────────────────────────────────────────── -->
<script>
(function () {
  'use strict';

  const API_URL       = 'api/live_stats.php';
  const REFRESH_MS    = 120_000; // 2 menit

  const elOnline  = document.getElementById('widget-online');
  const elToday   = document.getElementById('widget-today');
  const elTotal   = document.getElementById('widget-total');
  const elChart   = document.getElementById('widgetChart');

  /**
   * Render bar chart dari array chart_7d.
   * @param {Array} bars  [{label, sessions, height_pct, date}]
   */
  function renderChart(bars) {
    if (!elChart || !Array.isArray(bars) || bars.length === 0) return;
    elChart.innerHTML = bars.map(bar => {
      const pct   = bar.height_pct || 5;
      const tip   = `${bar.label || ''}: ${bar.sessions ?? 0} sesi`;
      const color = pct >= 70
        ? 'linear-gradient(to top, #16a34a, #4ade80)'   // Tinggi → hijau
        : pct >= 40
          ? 'linear-gradient(to top, #2563eb, #60a5fa)' // Sedang → biru
          : 'linear-gradient(to top, #475569, #94a3b8)'; // Rendah → abu
      return `
        <div class="bar-wrap">
          <div class="traffic-bar" title="${tip}"
               style="height:${pct}%; background:${color};">
          </div>
          <div class="bar-label">${bar.label || '?'}</div>
        </div>`;
    }).join('');
  }

  /**
   * Fetch data dari API dan update widget.
   */
  async function refreshStats() {
    try {
      const res  = await fetch(API_URL + '?_=' + Date.now());
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();

      // Update elemen
      if (elOnline) elOnline.textContent = data.active_users_fmt ?? '0';
      if (elToday)  elToday.textContent  = data.today_fmt ?? '0';
      if (elTotal)  elTotal.textContent  = data.total_fmt ?? '0';

      renderChart(data.chart_7d || []);

      // Animate nilai baru
      [elOnline, elToday, elTotal].forEach(el => {
        if (!el) return;
        el.style.transition = 'color 0.3s ease';
        el.style.color = '#4ade80';
        setTimeout(() => { el.style.color = ''; }, 600);
      });

    } catch (err) {
      console.warn('[TrafficWidget] Gagal memuat data:', err.message);
      // Tunjukkan tanda error ringan tanpa merusak tampilan
      if (elOnline && elOnline.textContent === '') elOnline.textContent = '—';
      if (elToday  && elToday.textContent === '')  elToday.textContent  = '—';
      if (elTotal  && elTotal.textContent === '')  elTotal.textContent  = '—';
    }
  }

  // Load pertama kali
  refreshStats();

  // Auto-refresh setiap 2 menit
  setInterval(refreshStats, REFRESH_MS);

})();
</script>

</body>
</html>