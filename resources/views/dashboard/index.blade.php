@extends('layouts.crm')

@section('title', 'Dashboard')

@section('content')
@php
    $stats = $console['stats'];
    $metrics = $console['metrics'];
    $trend = $console['monthly_trend'];
    $sources = $console['lead_sources'];
    $health = $console['operational_health'];
    $fyStart = now()->month >= 4 ? now()->year : now()->year - 1;
    $fyLabel = 'FY '.substr($fyStart, 2).'-'.substr($fyStart + 1, 2);
@endphp

<div class="crm-console">
    <div class="crm-console-header">
        <div>
            <h1 class="crm-console-title">Lead Console</h1>
            <p class="crm-console-subtitle">Real-time sales performance, pipeline health, and lead source analytics.</p>
        </div>
        <div class="crm-console-actions">
            <div class="crm-segmented" id="period-filter">
                <button type="button" data-period="30d">30D</button>
                <button type="button" data-period="q1">{{ $fyLabel }}</button>
                <button type="button" class="active" data-period="ytd">YTD</button>
            </div>
            <button type="button" class="crm-btn" id="export-btn"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
            <button type="button" class="crm-btn" id="sync-btn"><i class="bi bi-arrow-repeat"></i> Sync Leads</button>
        </div>
    </div>

    <div class="crm-stats-grid crm-stats-grid-7">
        @foreach ([
            'today_leads' => ["TODAY'S LEAD", 'bi-calendar-day', 'blue'],
            'open_leads' => ['OPEN LEAD', 'bi-folder2-open', 'indigo'],
            'contacted_leads' => ['CONTACTED LEAD', 'bi-telephone-check', 'teal'],
            'today_rejected_leads' => ["TODAY'S REJECTED LEAD", 'bi-x-circle', 'orange'],
            'today_followups' => ["TODAY'S LEAD FOLLOWUP", 'bi-calendar2-check', 'green'],
            'overdue_followups' => ['OVERDUE LEAD FOLLOWUP', 'bi-clock-history', 'purple'],
            'unassigned_leads' => ['UNASSIGNED LEAD', 'bi-person-exclamation', 'navy'],
        ] as $key => [$label, $icon, $tone])
            <div class="crm-stat-card crm-stat-{{ $tone }}">
                <div class="crm-stat-card-top">
                    <p>{{ $label }}</p>
                    <span class="crm-stat-icon"><i class="bi {{ $icon }}"></i></span>
                </div>
                <h3>{{ number_format($stats[$key] ?? 0) }}</h3>
            </div>
        @endforeach
    </div>

    <div class="crm-metric-grid">
        @foreach ($metrics as $index => $metric)
            <div class="crm-metric-card crm-metric-{{ $metric['tone'] }}">
                <div class="crm-metric-top">
                    <span class="crm-metric-label">{{ strtoupper($metric['label']) }}</span>
                    <span class="crm-metric-icon"><i class="bi {{ $metric['icon'] }}"></i></span>
                </div>
                <div class="crm-metric-value">{{ $metric['value'] }}</div>
                <div class="crm-metric-change {{ $metric['trend'] === 'up' ? 'is-up' : 'is-down' }}">
                    <i class="bi bi-arrow-{{ $metric['trend'] === 'up' ? 'up' : 'down' }}-right"></i>
                    {{ abs($metric['change']) }}%
                </div>
                <div class="crm-metric-spark" id="spark-{{ $metric['key'] }}"></div>
            </div>
        @endforeach
    </div>

    <div class="crm-chart-row crm-chart-row-main">
        <div class="crm-chart-card crm-chart-wide">
            <div class="crm-chart-card-head">
                <div>
                    <h3>Revenue vs Lost Pipeline</h3>
                    <p>Monthly won revenue compared with lost lead volume.</p>
                </div>
                <div class="crm-chart-legend">
                    <span><i class="dot dot-blue"></i> Revenue</span>
                    <span><i class="dot dot-red"></i> Lost Leads</span>
                </div>
            </div>
            <div id="chart-revenue-expense" class="crm-chart-area"></div>
        </div>

        <div class="crm-chart-card crm-chart-narrow">
            <div class="crm-chart-card-head">
                <div>
                    <h3>Lead Source Portfolios</h3>
                    <p>Distribution across acquisition channels.</p>
                </div>
            </div>
            <div class="crm-donut-wrap">
                <div id="chart-sources" class="crm-chart-donut"></div>
                <div class="crm-donut-legend" id="source-legend"></div>
            </div>
        </div>
    </div>

    <div class="crm-chart-row crm-chart-row-bottom">
        <div class="crm-chart-card crm-chart-wide">
            <div class="crm-chart-card-head">
                <div>
                    <h3>Monthly Inflow vs Outflow</h3>
                    <p>New leads acquired versus deals won each month.</p>
                </div>
                <div class="crm-chart-legend">
                    <span><i class="dot dot-navy"></i> New Leads</span>
                    <span><i class="dot dot-green"></i> Won Leads</span>
                </div>
            </div>
            <div id="chart-inflow-outflow" class="crm-chart-area"></div>
        </div>

        <div class="crm-chart-card crm-chart-narrow">
            <div class="crm-chart-card-head">
                <div>
                    <h3>Operational Health Monitor</h3>
                    <p>Composite score from conversion and follow-up status.</p>
                </div>
            </div>
            <div id="chart-health" class="crm-chart-gauge"></div>
            <div class="crm-health-label">
                <strong>{{ $health['score'] }}%</strong>
                <span>{{ $health['label'] }}</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"></script>
<script>
const chartData = @json($console);
const sparkColors = { blue: '#2563eb', red: '#ef4444', green: '#16a34a', orange: '#f59e0b' };
const chartFont = 'Inter, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif';

function renderSparklines() {
    chartData.metrics.forEach(metric => {
        const el = document.querySelector('#spark-' + metric.key);
        if (!el) return;
        new ApexCharts(el, {
            chart: { type: 'area', height: 48, sparkline: { enabled: true }, animations: { enabled: false } },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            series: [{ data: metric.spark }],
            colors: [sparkColors[metric.tone] || '#2563eb'],
            tooltip: { enabled: false },
        }).render();
    });
}

function renderMainCharts() {
    const trend = chartData.monthly_trend;
    const sources = chartData.lead_sources;
    const health = chartData.operational_health;

    new ApexCharts(document.querySelector('#chart-revenue-expense'), {
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: chartFont },
        series: [
            { name: 'Revenue', data: trend.revenue },
            { name: 'Lost Leads', data: trend.lost_leads },
        ],
        colors: ['#1e293b', '#ef4444'],
        stroke: { curve: 'smooth', width: 3 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0.02, stops: [0, 90, 100] },
        },
        markers: { size: 4, strokeWidth: 2, hover: { size: 6 } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4, xaxis: { lines: { show: false } } },
        xaxis: { categories: trend.labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#94a3b8' } } },
        yaxis: { labels: { style: { colors: '#94a3b8' }, formatter: v => v >= 100000 ? '₹'+(v/100000).toFixed(1)+'L' : v } },
        legend: { show: false },
        tooltip: { theme: 'light' },
    }).render();

    const sourceTotal = sources.reduce((s, i) => s + i.total, 0) || 1;
    const palette = ['#1e293b', '#2563eb', '#16a34a', '#f59e0b', '#8b5cf6', '#06b6d4'];

    new ApexCharts(document.querySelector('#chart-sources'), {
        chart: { type: 'donut', height: 280, fontFamily: chartFont },
        series: sources.length ? sources.map(s => s.total) : [1],
        labels: sources.length ? sources.map(s => s.source) : ['No Data'],
        colors: sources.length ? sources.map((s, i) => s.color || palette[i % palette.length]) : ['#e2e8f0'],
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: { show: true, fontSize: '11px', color: '#64748b', offsetY: -8 },
                        value: { show: true, fontSize: '22px', fontWeight: 700, color: '#0f172a', formatter: () => sourceTotal },
                        total: { show: true, label: 'Consolidated', fontSize: '11px', color: '#64748b', formatter: () => sourceTotal },
                    },
                },
            },
        },
        legend: { show: false },
        dataLabels: { enabled: false },
    }).render();

    const legend = document.getElementById('source-legend');
    if (legend && sources.length) {
        legend.innerHTML = sources.map((s, i) => `
            <div class="crm-donut-legend-item">
                <span class="dot" style="background:${s.color || palette[i % palette.length]}"></span>
                <span class="name">${s.source}</span>
                <span class="val">${s.total}</span>
            </div>
        `).join('');
    }

    new ApexCharts(document.querySelector('#chart-inflow-outflow'), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: chartFont },
        series: [
            { name: 'New Leads', data: trend.new_leads },
            { name: 'Won Leads', data: trend.won_leads },
        ],
        colors: ['#1e293b', '#16a34a'],
        plotOptions: { bar: { columnWidth: '42%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        xaxis: { categories: trend.labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#94a3b8' } } },
        yaxis: { labels: { style: { colors: '#94a3b8' } } },
        legend: { show: false },
    }).render();

    new ApexCharts(document.querySelector('#chart-health'), {
        chart: { type: 'radialBar', height: 260, fontFamily: chartFont },
        series: [health.score],
        colors: ['#2563eb'],
        plotOptions: {
            radialBar: {
                hollow: { size: '62%' },
                track: { background: '#e2e8f0', strokeWidth: '100%' },
                dataLabels: {
                    name: { show: false },
                    value: { show: false },
                },
            },
        },
        stroke: { lineCap: 'round' },
    }).render();
}

document.addEventListener('DOMContentLoaded', () => {
    renderSparklines();
    renderMainCharts();

    document.getElementById('sync-btn')?.addEventListener('click', async () => {
        const btn = document.getElementById('sync-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Syncing...';
        try {
            const res = await fetch('{{ route('api.dashboard.stats') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (res.ok) location.reload();
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync Leads';
        }
    });

    document.getElementById('export-btn')?.addEventListener('click', () => {
        window.print();
    });

    document.querySelectorAll('#period-filter button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#period-filter button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
});
</script>
@endpush
