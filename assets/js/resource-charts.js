(function () {
  'use strict';

  if (typeof Chart === 'undefined') return;

  var palette = {
    disk: { line: '#059669', fill: 'rgba(5,150,105,.12)' },
    memory: { line: '#2563eb', fill: 'rgba(37,99,235,.1)' },
    cpu: { line: '#d97706', fill: 'rgba(217,119,6,.1)' },
    bandwidth: { line: '#7c3aed', fill: 'rgba(124,58,237,.15)' },
    inodes: { line: '#0891b2', fill: 'rgba(8,145,178,.1)' },
    clients: ['#059669', '#2563eb', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#4f46e5', '#ea580c']
  };

  var fontFamily = '"DM Sans", system-ui, sans-serif';

  function baseOptions() {
    return {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          labels: { font: { family: fontFamily, size: 12 }, usePointStyle: true, boxWidth: 8 }
        },
        tooltip: {
          backgroundColor: '#1e293b',
          titleFont: { family: fontFamily },
          bodyFont: { family: fontFamily },
          padding: 10,
          cornerRadius: 8
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { family: fontFamily, size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(148,163,184,.2)' },
          ticks: { font: { family: fontFamily, size: 11 } }
        }
      }
    };
  }

  function lineDataset(label, data, color, fill) {
    return {
      label: label,
      data: data,
      borderColor: color.line,
      backgroundColor: fill ? color.fill : 'transparent',
      fill: !!fill,
      tension: 0.35,
      borderWidth: 2.5,
      pointRadius: data.length <= 14 ? 3 : 0,
      pointHoverRadius: 5
    };
  }

  function mountLineChart(id, labels, datasets) {
    var el = document.getElementById(id);
    if (!el) return;
    var opts = baseOptions();
    opts.plugins.legend.display = datasets.length > 1;
    new Chart(el, { type: 'line', data: { labels: labels, datasets: datasets }, options: opts });
  }

  function mountBarChart(id, labels, label, data, color) {
    var el = document.getElementById(id);
    if (!el) return;
    var opts = baseOptions();
    opts.plugins.legend.display = false;
    new Chart(el, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: label,
          data: data,
          backgroundColor: color.fill,
          borderColor: color.line,
          borderWidth: 1.5,
          borderRadius: 6,
          maxBarThickness: 36
        }]
      },
      options: opts
    });
  }

  function mountDoughnut(id, labels, data, colors) {
    var el = document.getElementById(id);
    if (!el) return;
    new Chart(el, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: colors,
          borderWidth: 0,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { font: { family: fontFamily, size: 11 }, usePointStyle: true, boxWidth: 8, padding: 14 }
          },
          tooltip: {
            backgroundColor: '#1e293b',
            bodyFont: { family: fontFamily }
          }
        }
      }
    });
  }

  function hexAlpha(hex, alpha) {
    var h = hex.replace('#', '');
    if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
    var r = parseInt(h.slice(0, 2), 16);
    var g = parseInt(h.slice(2, 4), 16);
    var b = parseInt(h.slice(4, 6), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  function mountSparkline(id, labels, data, color) {
    var el = document.getElementById(id);
    if (!el) return;
    new Chart(el, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          borderColor: color,
          backgroundColor: hexAlpha(color, 0.12),
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointRadius: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: true } },
        scales: { x: { display: false }, y: { display: false } }
      }
    });
  }

  function initClientCharts(cfg) {
    if (!cfg || !cfg.series) return;
    var s = cfg.series;
    var labels = s.labels || [];
    var i18n = cfg.i18n || {};

    mountLineChart('chart-disk-memory', labels, [
      lineDataset(i18n.disk || 'Disk (MB)', s.disk || [], palette.disk, true),
      lineDataset(i18n.memory || 'Memory (MB)', s.memory || [], palette.memory, false)
    ]);

    mountLineChart('chart-cpu', labels, [
      lineDataset(i18n.cpu || 'CPU %', s.cpu || [], palette.cpu, true)
    ]);

    mountBarChart('chart-bandwidth', labels, i18n.bandwidth || 'Bandwidth (GB)', s.bandwidth || [], palette.bandwidth);

    if (cfg.donut) {
      var d = cfg.donut;
      var used = Math.max(0, d.disk_used_mb || 0);
      var max = Math.max(used, d.disk_max_mb || 1);
      var free = Math.max(0, max - used);
      mountDoughnut('chart-disk-donut', [i18n.used || 'Used', i18n.free || 'Free'], [used, free], ['#059669', '#e2e8f0']);
    }
  }

  function initAdminCharts(cfg) {
    if (!cfg || !cfg.clients) return;
    var clients = cfg.clients;
    var i18n = cfg.i18n || {};
    var labels = cfg.labels || [];
    var datasets = [];
    clients.forEach(function (c, idx) {
      var color = palette.clients[idx % palette.clients.length];
      datasets.push({
        label: c.name,
        data: c.disk || [],
        borderColor: color,
        backgroundColor: 'transparent',
        tension: 0.35,
        borderWidth: 2,
        pointRadius: 0
      });
    });
    mountLineChart('admin-chart-all-disk', labels, datasets);

    clients.forEach(function (c, idx) {
      var color = palette.clients[idx % palette.clients.length];
      if (c.sparkId && c.spark) {
        mountSparkline(c.sparkId, c.spark.labels || [], c.spark.disk || [], color);
      }
    });
  }

  function initDashChart(cfg) {
    if (!cfg || !cfg.series) return;
    var s = cfg.series;
    var labels = s.labels || [];
    var i18n = cfg.i18n || {};
    mountLineChart('dash-usage-24h', labels, [
      lineDataset(i18n.disk || 'Disk (MB)', s.disk || [], palette.disk, true),
      lineDataset(i18n.cpu || 'CPU %', s.cpu || [], palette.cpu, false)
    ]);
  }

  var clientCfg = window.HS_USAGE_CHARTS;
  if (clientCfg) initClientCharts(clientCfg);

  var adminCfg = window.HS_ADMIN_USAGE_CHARTS;
  if (adminCfg) initAdminCharts(adminCfg);

  var dashCfg = window.HS_DASH_USAGE_CHART;
  if (dashCfg) initDashChart(dashCfg);
})();