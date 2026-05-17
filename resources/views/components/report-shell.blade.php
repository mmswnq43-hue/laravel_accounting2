@props([
    'title'       => '',
    'subtitle'    => '',
    'printRoute'  => null,
    'exportRoute' => null,
])

{{--
    report-shell — shared layout component for all 14 reports.

    Named slots:
        kpis     — row of KPI stat-cards (wrapped in row g-3 automatically)
        filters  — filter form rendered inside a card
        chart    — optional chart panel (omit the slot to hide it)
    Default slot ($slot):
        — the main data grid card body content
--}}

@push('styles')
<style>
    /* ── Design tokens (all reports share these) ──────────────────── */
    .rpt-shell {
        --rpt-surface:      #ffffff;
        --rpt-surface-soft: #f7fafc;
        --rpt-border:       #dce5ee;
        --rpt-text:         #243b53;
        --rpt-muted:        #74859a;
        --rpt-primary:      #2563eb;
        --rpt-success:      #10b981;
        --rpt-warning:      #f59e0b;
        --rpt-danger:       #ef4444;
        --rpt-shadow:       0 10px 28px rgba(31, 57, 88, 0.08);
    }

    /* ── Page header ──────────────────────────────────────────────── */
    .rpt-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.75rem;
        flex-wrap: wrap;
    }
    .rpt-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--rpt-text);
    }
    .rpt-subtitle {
        margin: 0.2rem 0 0;
        font-size: 0.875rem;
        color: var(--rpt-muted);
    }

    /* ── KPI stat-card ────────────────────────────────────────────── */
    .rpt-stat-card {
        background: var(--rpt-surface);
        border: 1px solid var(--rpt-border);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: var(--rpt-shadow);
        transition: transform 0.18s ease;
    }
    .rpt-stat-card:hover { transform: translateY(-2px); }

    .rpt-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        margin-bottom: 1rem;
    }
    .rpt-stat-icon.blue   { background: #dbeafe; color: var(--rpt-primary); }
    .rpt-stat-icon.green  { background: #d1fae5; color: var(--rpt-success); }
    .rpt-stat-icon.orange { background: #ffedd5; color: #ea580c; }
    .rpt-stat-icon.purple { background: #f3e8ff; color: #9333ea; }
    .rpt-stat-icon.red    { background: #fee2e2; color: var(--rpt-danger); }
    .rpt-stat-icon.yellow { background: #fef3c7; color: #d97706; }

    .rpt-stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--rpt-text);
        margin-bottom: 0.2rem;
        line-height: 1;
    }
    .rpt-stat-label {
        font-size: 0.875rem;
        color: var(--rpt-muted);
    }
    .rpt-stat-delta {
        font-size: 0.8rem;
        font-weight: 600;
        margin-top: 0.4rem;
    }
    .rpt-stat-delta.up   { color: var(--rpt-success); }
    .rpt-stat-delta.down { color: var(--rpt-danger); }

    /* ── Filters card ─────────────────────────────────────────────── */
    .rpt-filters {
        border: 1px solid var(--rpt-border);
        border-radius: 12px;
        box-shadow: none;
    }
    .rpt-filters .card-body { padding: 1rem 1.25rem; }

    /* ── Data grid ────────────────────────────────────────────────── */
    .rpt-grid-card {
        border: 1px solid var(--rpt-border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--rpt-shadow);
    }
    .rpt-grid-card .card-header {
        background: var(--rpt-surface);
        border-bottom: 1px solid var(--rpt-border);
        padding: 1rem 1.25rem;
    }
    .rpt-table th {
        background: var(--rpt-surface-soft);
        font-weight: 700;
        font-size: 0.8rem;
        letter-spacing: 0.3px;
        padding: 0.875rem 1rem;
        border-bottom: 2px solid var(--rpt-border);
        white-space: nowrap;
        color: var(--rpt-muted);
        text-transform: uppercase;
    }
    .rpt-table td {
        padding: 0.875rem 1rem;
        vertical-align: middle;
        color: var(--rpt-text);
        border-bottom: 1px solid var(--rpt-border);
    }
    .rpt-table tbody tr:last-child td { border-bottom: none; }
    .rpt-table tbody tr:hover { background: var(--rpt-surface-soft); }

    /* ── Empty state ──────────────────────────────────────────────── */
    .rpt-empty {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--rpt-muted);
    }
    .rpt-empty i { font-size: 3rem; margin-bottom: 0.75rem; opacity: 0.4; }

    /* ── Badge helpers ────────────────────────────────────────────── */
    .rpt-badge-success { background: #d1fae5; color: var(--rpt-success); }
    .rpt-badge-danger  { background: #fee2e2; color: var(--rpt-danger); }
    .rpt-badge-warning { background: #fef3c7; color: #d97706; }
    .rpt-badge-neutral { background: var(--rpt-surface-soft); color: var(--rpt-muted); }

    /* ── Amount helpers ───────────────────────────────────────────── */
    .rpt-positive { color: var(--rpt-success); font-weight: 600; }
    .rpt-negative { color: var(--rpt-danger);  font-weight: 600; }
    .rpt-muted-val { color: var(--rpt-muted); }
</style>
@endpush

<div class="container-fluid rpt-shell py-4">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="rpt-header">
        <div>
            <h1 class="rpt-title">{{ $title }}</h1>
            @if($subtitle)
                <p class="rpt-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($exportRoute)
                <a href="{{ $exportRoute }}" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i> تصدير Excel
                </a>
            @endif
            @if($printRoute)
                <a href="{{ $printRoute }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                    <i class="fas fa-print me-1"></i> طباعة
                </a>
            @endif
            <a href="{{ route('reports') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> التقارير
            </a>
        </div>
    </div>

    {{-- ── Filters ──────────────────────────────────────────────────── --}}
    @isset($filters)
        @if($filters->isNotEmpty())
            <div class="card rpt-filters mb-4">
                <div class="card-body">
                    {{ $filters }}
                </div>
            </div>
        @endif
    @endisset

    {{-- ── KPI Cards ───────────────────────────────────────────────── --}}
    @isset($kpis)
        @if($kpis->isNotEmpty())
            <div class="row g-3 mb-4">
                {{ $kpis }}
            </div>
        @endif
    @endisset

    {{-- ── Chart (optional) ───────────────────────────────────────── --}}
    @isset($chart)
        @if($chart->isNotEmpty())
            <div class="card rpt-grid-card mb-4">
                <div class="card-body">
                    {{ $chart }}
                </div>
            </div>
        @endif
    @endisset

    {{-- ── Data Grid (default slot) ───────────────────────────────── --}}
    <div class="card rpt-grid-card">
        {{ $slot }}
    </div>

</div>
