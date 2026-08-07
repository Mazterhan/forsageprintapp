@php
    $canViewOrdersAnalytics = (bool) ($dashboardPermissions['show_orders_tab'] ?? false);
@endphp

<style>
    .analytics-header {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 2.75rem;
    }

    .analytics-tab-list {
        display: flex;
        height: 72px;
        min-width: 0;
        align-items: flex-end;
        margin-top: -24px;
        margin-bottom: -24px;
        padding: 0 20px;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .analytics-tab {
        --analytics-tab-fill: #f3f4f6;
        isolation: isolate;
        position: relative;
        z-index: 1;
        flex: 0 0 auto;
        min-width: 210px;
        height: 52px;
        margin-left: -20px;
        padding: 0 34px;
        border: 0;
        clip-path: polygon(24px 0, calc(100% - 24px) 0, 100% 100%, 0 100%);
        background: #9ca3af;
        color: #737373;
        font-size: 1.45rem;
        font-weight: 600;
        line-height: 1;
        transition: color 0.15s ease;
    }

    .analytics-tab:first-child {
        margin-left: 0;
    }

    .analytics-tab::before {
        content: '';
        position: absolute;
        z-index: 0;
        inset: 2px 1px 1px;
        clip-path: polygon(22px 0, calc(100% - 22px) 0, 100% 100%, 0 100%);
        background: var(--analytics-tab-fill);
        transition: background-color 0.15s ease;
    }

    .analytics-tab-label {
        position: relative;
        z-index: 1;
    }

    .analytics-tab:hover {
        --analytics-tab-fill: #e5e7eb;
        color: #374151;
    }

    .analytics-tab.is-active {
        --analytics-tab-fill: #FCEEDF;
        z-index: 3;
        color: #525252;
    }

    .analytics-tab.is-active:hover {
        --analytics-tab-fill: #FCEEDF;
    }

    .analytics-tab:focus-visible {
        z-index: 5;
        outline: 2px solid #4f46e5;
        outline-offset: -3px;
    }

    @media (max-width: 767px) {
        .analytics-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 0.75rem;
        }

        .analytics-tab-list {
            width: 100%;
            height: 52px;
            margin-top: 0;
            margin-bottom: -24px;
        }

        .analytics-tab {
            min-width: 180px;
            font-size: 1.1rem;
        }
    }
</style>

<div class="analytics-header">
    <h2 class="shrink-0 font-semibold text-xl text-gray-800 leading-tight">Аналітика</h2>

    <form method="GET" action="{{ route('dashboard') }}" class="analytics-tab-list" role="tablist" aria-label="Розділи аналітики">
        <button
            type="{{ $activeTab === 'proposals' ? 'button' : 'submit' }}"
            name="tab"
            value="proposals"
            role="tab"
            aria-selected="{{ $activeTab === 'proposals' ? 'true' : 'false' }}"
            @class(['analytics-tab', 'is-active' => $activeTab === 'proposals'])
        >
            <span class="analytics-tab-label">Заявки</span>
        </button>
        @if($canViewOrdersAnalytics)
            <button
                type="{{ $activeTab === 'orders' ? 'button' : 'submit' }}"
                name="tab"
                value="orders"
                role="tab"
                aria-selected="{{ $activeTab === 'orders' ? 'true' : 'false' }}"
                @class(['analytics-tab', 'is-active' => $activeTab === 'orders'])
            >
                <span class="analytics-tab-label">Замовлення</span>
            </button>
        @endif
    </form>
</div>
