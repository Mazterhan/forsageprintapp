<x-app-layout>
    @php
        $role = $role ?? null;
        $isEditingRole = $role !== null;
        $roleOldBool = static fn (string $key): bool => (string) old($key, ($role?->{$key} ?? false) ? '1' : '0') === '1';
    @endphp
    @section('title', $isEditingRole ? __('Редагування ролі') : __('Додавання ролі'))
    <style>
        [x-cloak] { display: none !important; }

        .permission-switch {
            position: relative;
            width: 182px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            border: 1px solid #d1d5db;
            padding: 0 12px;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s ease, border-color 0.2s ease;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .permission-switch input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .permission-switch .switch-track {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            transition: background-color 0.2s ease;
        }

        .permission-switch .switch-knob {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 30px;
            height: 30px;
            border-radius: 9999px;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease;
            z-index: 2;
        }

        .permission-switch .switch-text {
            position: relative;
            z-index: 3;
            margin-left: 40px;
            transition: color 0.2s ease;
        }

        .permission-switch .switch-text-allow {
            display: none;
            color: #166534;
        }

        .permission-switch .switch-text-deny {
            display: inline;
            color: #991b1b;
        }

        .permission-switch .switch-track {
            background: #fee2e2;
        }

        .permission-switch:has(input:checked) .switch-track {
            background: #dcfce7;
        }

        .permission-switch:has(input:checked) .switch-knob {
            transform: translateX(142px);
        }

        .permission-switch:has(input:checked) .switch-text-allow {
            display: inline;
        }

        .permission-switch:has(input:checked) .switch-text-deny {
            display: none;
        }

        .permission-panel {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background: #f9fafb;
            padding: 0.75rem;
            margin-top: 0.75rem;
            overflow: hidden;
        }

        .sub-setting-level-1 {
            margin-left: 0.75rem;
            padding-left: 0.5rem;
            border-left: 2px solid #e5e7eb;
        }

        .sub-setting-level-2 {
            margin-left: 1.5rem;
            padding-left: 0.5rem;
            border-left: 2px solid #d1d5db;
        }

        .permission-button-toggle {
            width: 182px;
            height: 38px;
            border-radius: 9999px;
            border: 1px solid #9ca3af;
            background: #e5e7eb;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .permission-button-toggle:hover {
            background: #d1d5db;
            border-color: #6b7280;
        }
    </style>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $isEditingRole ? __('Редагування ролі') : __('Додавання ролі') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form
                        method="POST"
                        action="{{ $isEditingRole ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
                        class="space-y-6"
                        x-data="{
                            canAnalytics: @js($roleOldBool('can_analytics')),
                            canOrders: @js($roleOldBool('can_orders')),
                            canPrice: @js($roleOldBool('can_price')),
                            canAdmin: @js($roleOldBool('can_admin')),
                            adminReferenceManage: @js($roleOldBool('admin_reference_manage')),
                            adminUsersOrgManage: @js($roleOldBool('admin_users_org_manage')),
                            analyticsShowKpi: @js($roleOldBool('analytics_show_kpi')),
                            analyticsShowCharts: @js($roleOldBool('analytics_show_charts')),
                            analyticsShowTables: @js($roleOldBool('analytics_show_tables')),
                            analyticsFinanceAccess: @js($roleOldBool('analytics_finance_access')),
                            ordersCalculation: @js($roleOldBool('orders_calculation')),
                            ordersCalcSave: @js($roleOldBool('orders_calc_save')),
                            ordersCalcPurchaseVisible: @js($roleOldBool('orders_calc_purchase_visible')),
                            ordersProposals: @js($roleOldBool('orders_proposals')),
                            ordersListScopeAll: @js(old('orders_list_scope', $role?->orders_list_scope ?? 'own') === 'all'),
                            ordersListPurchaseVisible: @js($roleOldBool('orders_list_purchase_visible')),
                            ordersListEdit: @js($roleOldBool('orders_list_edit')),
                            ordersEdit: @js($roleOldBool('orders_edit')),
                            ordersClientsManage: @js($roleOldBool('orders_clients_manage')),
                            priceCreateItem: @js($roleOldBool('price_create_item')),
                            priceDeactivateItem: @js($roleOldBool('price_deactivate_item')),
                            priceDeleteItem: @js($roleOldBool('price_delete_item')),
                            pricePurchaseAccess: @js($roleOldBool('price_purchase_access')),
                            priceCardAccess: @js($roleOldBool('price_card_access'))
                            ,
                            priceCardEdit: @js($roleOldBool('price_card_edit')),
                            priceCardHistory: @js($roleOldBool('price_card_history'))
                        }"
                        x-init="
                            $watch('canAnalytics', (v) => {
                                if (!v) {
                                    analyticsShowKpi = false;
                                    analyticsShowCharts = false;
                                    analyticsShowTables = false;
                                    analyticsFinanceAccess = false;
                                }
                            });
                            $watch('canOrders', (v) => {
                                if (!v) {
                                    ordersCalculation = false;
                                    ordersCalcSave = false;
                                    ordersCalcPurchaseVisible = false;
                                    ordersProposals = false;
                                    ordersListScopeAll = false;
                                    ordersListPurchaseVisible = false;
                                    ordersListEdit = false;
                                    ordersEdit = false;
                                    ordersClientsManage = false;
                                }
                            });
                            $watch('ordersCalculation', (v) => {
                                if (!v) {
                                    ordersCalcSave = false;
                                    ordersCalcPurchaseVisible = false;
                                }
                            });
                            $watch('ordersProposals', (v) => {
                                if (!v) {
                                    ordersListScopeAll = false;
                                    ordersListPurchaseVisible = false;
                                    ordersListEdit = false;
                                    ordersEdit = false;
                                }
                            });
                            $watch('canPrice', (v) => {
                                if (!v) {
                                    priceCreateItem = false;
                                    priceDeactivateItem = false;
                                    priceDeleteItem = false;
                                    pricePurchaseAccess = false;
                                    priceCardAccess = false;
                                    priceCardEdit = false;
                                    priceCardHistory = false;
                                }
                            });
                            $watch('priceCardAccess', (v) => {
                                if (!v) {
                                    priceCardEdit = false;
                                    priceDeactivateItem = false;
                                    priceDeleteItem = false;
                                    priceCardHistory = false;
                                }
                            });
                            $watch('canAdmin', (v) => {
                                if (!v) {
                                    adminReferenceManage = false;
                                    adminUsersOrgManage = false;
                                }
                            });
                        "
                    >
                        @csrf
                        @if($isEditingRole)
                            @method('PATCH')
                        @endif

                        <div>
                            <x-input-label for="name" :value="__('Назва ролі')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $role?->name) }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div class="space-y-4">
                            <div class="border border-gray-200 rounded-md px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="font-medium text-gray-800">Аналітика</div>
                                    <label class="permission-switch">
                                        <input type="hidden" name="can_analytics" value="0">
                                        <input type="checkbox" name="can_analytics" value="1" x-model="canAnalytics">
                                        <span class="switch-track"></span>
                                        <span class="switch-knob"></span>
                                        <span class="switch-text">
                                            <span class="switch-text-allow">доступно</span>
                                            <span class="switch-text-deny">недоступно</span>
                                        </span>
                                    </label>
                                </div>
                                <div x-show="canAnalytics" x-cloak class="permission-panel space-y-3 sub-setting-level-1">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Відображення KPI</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="analytics_show_kpi" value="0">
                                            <input type="checkbox" name="analytics_show_kpi" value="1" x-model="analyticsShowKpi">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Відображення графіків</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="analytics_show_charts" value="0">
                                            <input type="checkbox" name="analytics_show_charts" value="1" x-model="analyticsShowCharts">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Відображення таблиць</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="analytics_show_tables" value="0">
                                            <input type="checkbox" name="analytics_show_tables" value="1" x-model="analyticsShowTables">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Доступність фінансової аналітики</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="analytics_finance_access" value="0">
                                            <input type="checkbox" name="analytics_finance_access" value="1" x-model="analyticsFinanceAccess">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-md px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="font-medium text-gray-800">Замовлення</div>
                                    <label class="permission-switch">
                                        <input type="hidden" name="can_orders" value="0">
                                        <input type="checkbox" name="can_orders" value="1" x-model="canOrders">
                                        <span class="switch-track"></span>
                                        <span class="switch-knob"></span>
                                        <span class="switch-text">
                                            <span class="switch-text-allow">доступно</span>
                                            <span class="switch-text-deny">недоступно</span>
                                        </span>
                                    </label>
                                </div>
                                <div x-show="canOrders" x-cloak class="permission-panel space-y-3 sub-setting-level-1">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Прорахунок заявки</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="orders_calculation" value="0">
                                            <input type="checkbox" name="orders_calculation" value="1" x-model="ordersCalculation">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                    <div x-show="ordersCalculation" x-cloak class="space-y-3 sub-setting-level-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-800">збереження заявки</div>
                                            <label class="permission-switch">
                                                <input type="hidden" name="orders_calc_save" value="0">
                                                <input type="checkbox" name="orders_calc_save" value="1" x-model="ordersCalcSave">
                                                <span class="switch-track"></span><span class="switch-knob"></span>
                                                <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-800">Видимість собівартості</div>
                                            <label class="permission-switch">
                                                <input type="hidden" name="orders_calc_purchase_visible" value="0">
                                                <input type="checkbox" name="orders_calc_purchase_visible" value="1" x-model="ordersCalcPurchaseVisible">
                                                <span class="switch-track"></span><span class="switch-knob"></span>
                                                <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Заявки/замовлення</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="orders_proposals" value="0">
                                            <input type="checkbox" name="orders_proposals" value="1" x-model="ordersProposals">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                    <div x-show="ordersProposals" x-cloak class="space-y-3 sub-setting-level-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-800">Бачить заявки</div>
                                            <div>
                                                <input type="hidden" name="orders_list_scope" :value="ordersListScopeAll ? 'all' : 'own'">
                                                <button
                                                    type="button"
                                                    class="permission-button-toggle"
                                                    @click="ordersListScopeAll = !ordersListScopeAll"
                                                    x-text="ordersListScopeAll ? 'Всі' : 'Свої'"
                                                ></button>
                                            </div>
                                        </div>
                                        <div class="space-y-3 sub-setting-level-2">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="text-sm text-gray-800">Видимість собівартості</div>
                                                <label class="permission-switch">
                                                    <input type="hidden" name="orders_list_purchase_visible" value="0">
                                                    <input type="checkbox" name="orders_list_purchase_visible" value="1" x-model="ordersListPurchaseVisible">
                                                    <span class="switch-track"></span><span class="switch-knob"></span>
                                                    <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                                </label>
                                            </div>
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="text-sm text-gray-800">Редагування заявок</div>
                                                <label class="permission-switch">
                                                    <input type="hidden" name="orders_edit" value="0">
                                                    <input type="checkbox" name="orders_edit" value="1" x-model="ordersEdit">
                                                    <span class="switch-track"></span><span class="switch-knob"></span>
                                                    <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                                </label>
                                            </div>
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="text-sm text-gray-800">Редагування списку</div>
                                                <label class="permission-switch">
                                                    <input type="hidden" name="orders_list_edit" value="0">
                                                    <input type="checkbox" name="orders_list_edit" value="1" x-model="ordersListEdit">
                                                    <span class="switch-track"></span><span class="switch-knob"></span>
                                                    <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Керування замовниками</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="orders_clients_manage" value="0">
                                            <input type="checkbox" name="orders_clients_manage" value="1" x-model="ordersClientsManage">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-md px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="font-medium text-gray-800">Прайс</div>
                                    <label class="permission-switch">
                                        <input type="hidden" name="can_price" value="0">
                                        <input type="checkbox" name="can_price" value="1" x-model="canPrice">
                                        <span class="switch-track"></span>
                                        <span class="switch-knob"></span>
                                        <span class="switch-text">
                                            <span class="switch-text-allow">доступно</span>
                                            <span class="switch-text-deny">недоступно</span>
                                        </span>
                                    </label>
                                </div>
                                <div x-show="canPrice" x-cloak class="permission-panel space-y-3 sub-setting-level-1">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Доступ до закупівельної ціни</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="price_purchase_access" value="0">
                                            <input type="checkbox" name="price_purchase_access" value="1" x-model="pricePurchaseAccess">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Доступ до картки існуючої позиції</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="price_card_access" value="0">
                                            <input type="checkbox" name="price_card_access" value="1" x-model="priceCardAccess">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                    <div x-show="priceCardAccess" x-cloak class="space-y-3 sub-setting-level-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-800">Редагування позиції</div>
                                            <label class="permission-switch">
                                                <input type="hidden" name="price_card_edit" value="0">
                                                <input type="checkbox" name="price_card_edit" value="1" x-model="priceCardEdit">
                                                <span class="switch-track"></span><span class="switch-knob"></span>
                                                <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-800">Зміна статусу позиції</div>
                                            <label class="permission-switch">
                                                <input type="hidden" name="price_deactivate_item" value="0">
                                                <input type="checkbox" name="price_deactivate_item" value="1" x-model="priceDeactivateItem">
                                                <span class="switch-track"></span><span class="switch-knob"></span>
                                                <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-800">Видалення позиції</div>
                                            <label class="permission-switch">
                                                <input type="hidden" name="price_delete_item" value="0">
                                                <input type="checkbox" name="price_delete_item" value="1" x-model="priceDeleteItem">
                                                <span class="switch-track"></span><span class="switch-knob"></span>
                                                <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="text-sm text-gray-800">Історія зміни ціни</div>
                                            <label class="permission-switch">
                                                <input type="hidden" name="price_card_history" value="0">
                                                <input type="checkbox" name="price_card_history" value="1" x-model="priceCardHistory">
                                                <span class="switch-track"></span><span class="switch-knob"></span>
                                                <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Створення нової позиції</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="price_create_item" value="0">
                                            <input type="checkbox" name="price_create_item" value="1" x-model="priceCreateItem">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-md px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="font-medium text-gray-800">Адміністрування</div>
                                    <label class="permission-switch">
                                        <input type="hidden" name="can_admin" value="0">
                                        <input type="checkbox" name="can_admin" value="1" x-model="canAdmin">
                                        <span class="switch-track"></span>
                                        <span class="switch-knob"></span>
                                        <span class="switch-text">
                                            <span class="switch-text-allow">доступно</span>
                                            <span class="switch-text-deny">недоступно</span>
                                        </span>
                                    </label>
                                </div>
                                <div x-show="canAdmin" x-cloak class="permission-panel space-y-3 sub-setting-level-1">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Керування довідниками</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="admin_reference_manage" value="0">
                                            <input type="checkbox" name="admin_reference_manage" value="1" x-model="adminReferenceManage">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm text-gray-800">Керування користуваами та оргструктурою</div>
                                        <label class="permission-switch">
                                            <input type="hidden" name="admin_users_org_manage" value="0">
                                            <input type="checkbox" name="admin_users_org_manage" value="1" x-model="adminUsersOrgManage">
                                            <span class="switch-track"></span><span class="switch-knob"></span>
                                            <span class="switch-text"><span class="switch-text-allow">доступно</span><span class="switch-text-deny">недоступно</span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ $isEditingRole ? __('Оновити роль') : __('Зберегти роль') }}</x-primary-button>
                            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Скасувати') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

