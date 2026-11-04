<x-filament-panels::page>
    <style>
        .nav-order-wrapper {
            direction: rtl;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            font-family: inherit;
        }

        /* Top Bar & Banners */
        .nav-order-card {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e2e8f0;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .dark .nav-order-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .nav-order-title-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .nav-order-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 0.65rem;
            background: #e0f2fe;
            color: #0284c7;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dark .nav-order-icon-badge {
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
        }
        .nav-order-title {
            font-size: 1.125rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }
        .dark .nav-order-title { color: #f8fafc; }
        .nav-order-desc {
            font-size: 0.825rem;
            color: #64748b;
            margin: 0.2rem 0 0 0;
        }
        .dark .nav-order-desc { color: #94a3b8; }

        .nav-order-actions-bar {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .nav-order-tip {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1.15rem;
            border-radius: 0.75rem;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .dark .nav-order-tip {
            background: rgba(14, 165, 233, 0.08);
            border-color: rgba(14, 165, 233, 0.2);
            color: #7dd3fc;
        }

        /* Group Card */
        .nav-order-group-card {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease;
        }
        .dark .nav-order-group-card {
            background: #111827;
            border-color: #1f2937;
        }
        .nav-order-group-card.is-dragging {
            opacity: 0.45;
            border: 2px dashed #0284c7 !important;
        }

        .nav-order-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1.15rem;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            user-select: none;
        }
        .dark .nav-order-group-header {
            background: #1a2234;
            border-bottom-color: #1f2937;
        }

        .nav-order-group-meta {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            cursor: pointer;
            flex: 1;
        }
        .nav-order-seq-badge {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #0284c7;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .nav-order-group-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
        }
        .dark .nav-order-group-name { color: #f8fafc; }

        .nav-order-count-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            background: #e2e8f0;
            color: #475569;
        }
        .dark .nav-order-count-badge {
            background: #334155;
            color: #cbd5e1;
        }

        /* Buttons & Controls */
        .nav-order-ctrl-group {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .nav-order-icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .dark .nav-order-icon-btn {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }
        .nav-order-icon-btn:hover:not(:disabled) {
            background: #f1f5f9;
            color: #0284c7;
            border-color: #cbd5e1;
        }
        .dark .nav-order-icon-btn:hover:not(:disabled) {
            background: #334155;
            color: #38bdf8;
            border-color: #475569;
        }
        .nav-order-icon-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .nav-order-handle {
            color: #94a3b8;
            cursor: grab;
            padding: 0.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nav-order-handle:active { cursor: grabbing; }

        /* Items Body */
        .nav-order-items-body {
            padding: 0.85rem 1.15rem;
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }
        .nav-order-empty-items {
            text-align: center;
            padding: 1.5rem 0;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .nav-order-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.6rem 0.85rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 0.625rem;
            transition: all 0.15s ease;
        }
        .dark .nav-order-item-row {
            background: #151d2e;
            border-color: #1f2937;
        }
        .nav-order-item-row:hover {
            background: #f1f5f9;
            border-color: #e2e8f0;
        }
        .dark .nav-order-item-row:hover {
            background: #1e293b;
            border-color: #334155;
        }
        .nav-order-item-row.is-dragging {
            opacity: 0.4;
            border: 2px dashed #0284c7 !important;
        }

        .nav-order-item-left {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex: 1;
        }
        .nav-order-item-num {
            width: 22px;
            height: 22px;
            border-radius: 0.35rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            font-size: 0.725rem;
            font-weight: 700;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dark .nav-order-item-num {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }
        .nav-order-item-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
        }
        .dark .nav-order-item-label { color: #f1f5f9; }
    </style>

    <div
        x-data="{
            collapsedGroups: {},
            dragGroupIndex: null,
            dragItem: null,

            toggleGroup(key) {
                this.collapsedGroups[key] = !this.collapsedGroups[key];
            },
            isCollapsed(key) {
                return !!this.collapsedGroups[key];
            },

            onGroupDragStart(index, event) {
                this.dragGroupIndex = index;
                event.dataTransfer.effectAllowed = 'move';
            },
            onGroupDragOver(event) {
                if (this.dragGroupIndex !== null) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                }
            },
            onGroupDrop(targetIndex, event) {
                event.preventDefault();
                if (this.dragGroupIndex === null || this.dragGroupIndex === targetIndex) {
                    this.dragGroupIndex = null;
                    return;
                }
                let catalog = JSON.parse(JSON.stringify($wire.catalog));
                const moved = catalog.splice(this.dragGroupIndex, 1)[0];
                catalog.splice(targetIndex, 0, moved);
                $wire.applyCatalogOrder(catalog);
                this.dragGroupIndex = null;
            },

            onItemDragStart(groupIndex, itemIndex, event) {
                event.stopPropagation();
                this.dragItem = { groupIndex, itemIndex };
                event.dataTransfer.effectAllowed = 'move';
            },
            onItemDragOver(groupIndex, event) {
                if (this.dragItem !== null && this.dragItem.groupIndex === groupIndex) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                }
            },
            onItemDrop(targetGroupIndex, targetItemIndex, event) {
                event.preventDefault();
                event.stopPropagation();
                if (!this.dragItem || this.dragItem.groupIndex !== targetGroupIndex) {
                    this.dragItem = null;
                    return;
                }
                const sourceItemIndex = this.dragItem.itemIndex;
                if (sourceItemIndex === targetItemIndex) {
                    this.dragItem = null;
                    return;
                }
                let catalog = JSON.parse(JSON.stringify($wire.catalog));
                let items = catalog[targetGroupIndex].items;
                const moved = items.splice(sourceItemIndex, 1)[0];
                items.splice(targetItemIndex, 0, moved);
                catalog[targetGroupIndex].items = items;
                $wire.applyCatalogOrder(catalog);
                this.dragItem = null;
            }
        }"
        class="nav-order-wrapper"
    >
        {{-- Top Info & Action Bar --}}
        <div class="nav-order-card">
            <div class="nav-order-title-box">
                <span class="nav-order-icon-badge">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                </span>
                <div>
                    <h2 class="nav-order-title">{{ __('navigation_order.page_title') }}</h2>
                    <p class="nav-order-desc">{{ __('navigation_order.page_description') }}</p>
                </div>
            </div>

            <div class="nav-order-actions-bar">
                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    icon="heroicon-o-arrow-path"
                    wire:click="resetOrder"
                    wire:confirm="{{ __('navigation_order.reset_confirm') }}"
                >
                    {{ __('navigation_order.reset_default') }}
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="primary"
                    icon="heroicon-o-check"
                    wire:click="saveOrder"
                >
                    {{ __('navigation_order.save_changes') }}
                </x-filament::button>
            </div>
        </div>

        {{-- Tip Box --}}
        <div class="nav-order-tip">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>{{ __('navigation_order.group_order_tip') }}</div>
        </div>

        {{-- Groups List --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @forelse ($catalog as $gIndex => $group)
                <div
                    wire:key="group-card-{{ $group['key'] }}"
                    draggable="true"
                    x-on:dragstart="onGroupDragStart({{ $gIndex }}, $event)"
                    x-on:dragover="onGroupDragOver($event)"
                    x-on:drop="onGroupDrop({{ $gIndex }}, $event)"
                    class="nav-order-group-card"
                    :class="{ 'is-dragging': dragGroupIndex === {{ $gIndex }} }"
                >
                    {{-- Group Header --}}
                    <div class="nav-order-group-header">
                        <div class="nav-order-group-meta" x-on:click="toggleGroup('{{ $group['key'] }}')">
                            {{-- Drag Handle --}}
                            <span class="nav-order-handle" title="{{ __('navigation_order.drag_to_reorder') }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
                                    <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                    <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
                                </svg>
                            </span>

                            {{-- Sequence Badge --}}
                            <span class="nav-order-seq-badge">
                                #{{ $gIndex + 1 }}
                            </span>

                            {{-- Group Name --}}
                            <span class="nav-order-group-name">
                                {{ $group['label'] }}
                            </span>

                            {{-- Items Count Badge --}}
                            <span class="nav-order-count-badge">
                                {{ count($group['items']) }} {{ __('navigation_order.items_count') }}
                            </span>
                        </div>

                        {{-- Action Controls --}}
                        <div class="nav-order-ctrl-group">
                            {{-- Move Up --}}
                            <button
                                type="button"
                                wire:click.stop="moveGroupUp({{ $gIndex }})"
                                @disabled($gIndex === 0)
                                class="nav-order-icon-btn"
                                title="{{ __('navigation_order.move_up') }}"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="18 15 12 9 6 15"></polyline>
                                </svg>
                            </button>

                            {{-- Move Down --}}
                            <button
                                type="button"
                                wire:click.stop="moveGroupDown({{ $gIndex }})"
                                @disabled($gIndex === count($catalog) - 1)
                                class="nav-order-icon-btn"
                                title="{{ __('navigation_order.move_down') }}"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>

                            {{-- Toggle Expand/Collapse --}}
                            <button
                                type="button"
                                x-on:click="toggleGroup('{{ $group['key'] }}')"
                                class="nav-order-icon-btn"
                                style="margin-inline-start: 0.25rem;"
                            >
                                <span x-show="!isCollapsed('{{ $group['key'] }}')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </span>
                                <span x-show="isCollapsed('{{ $group['key'] }}')" style="display: none;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                    {{-- Group Items Body --}}
                    <div
                        x-show="!isCollapsed('{{ $group['key'] }}')"
                        class="nav-order-items-body"
                    >
                        @if (empty($group['items']))
                            <div class="nav-order-empty-items">
                                {{ __('navigation_order.empty_group') }}
                            </div>
                        @else
                            @foreach ($group['items'] as $iIndex => $item)
                                <div
                                    wire:key="item-{{ $group['key'] }}-{{ $iIndex }}"
                                    draggable="true"
                                    x-on:dragstart="onItemDragStart({{ $gIndex }}, {{ $iIndex }}, $event)"
                                    x-on:dragover="onItemDragOver({{ $gIndex }}, $event)"
                                    x-on:drop="onItemDrop({{ $gIndex }}, {{ $iIndex }}, $event)"
                                    class="nav-order-item-row"
                                    :class="{ 'is-dragging': dragItem && dragItem.groupIndex === {{ $gIndex }} && dragItem.itemIndex === {{ $iIndex }} }"
                                >
                                    <div class="nav-order-item-left">
                                        {{-- Item Drag Handle --}}
                                        <span class="nav-order-handle" title="{{ __('navigation_order.drag_to_reorder') }}">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
                                                <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                                <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
                                            </svg>
                                        </span>

                                        {{-- Item Number --}}
                                        <span class="nav-order-item-num">
                                            {{ $iIndex + 1 }}
                                        </span>

                                        {{-- Item Label --}}
                                        <span class="nav-order-item-label">
                                            {{ $item['label'] }}
                                        </span>
                                    </div>

                                    {{-- Item Up / Down Controls --}}
                                    <div class="nav-order-ctrl-group">
                                        <button
                                            type="button"
                                            wire:click="moveItemUp({{ $gIndex }}, {{ $iIndex }})"
                                            @disabled($iIndex === 0)
                                            class="nav-order-icon-btn"
                                            style="width: 28px; height: 28px;"
                                            title="{{ __('navigation_order.move_up') }}"
                                        >
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="18 15 12 9 6 15"></polyline>
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="moveItemDown({{ $gIndex }}, {{ $iIndex }})"
                                            @disabled($iIndex === count($group['items']) - 1)
                                            class="nav-order-icon-btn"
                                            style="width: 28px; height: 28px;"
                                            title="{{ __('navigation_order.move_down') }}"
                                        >
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="6 9 12 15 18 9"></polyline>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @empty
                <div class="nav-order-card" style="justify-content: center; color: #94a3b8;">
                    {{ __('navigation_order.no_groups_found') }}
                </div>
            @endforelse
        </div>

        {{-- Bottom Action Bar --}}
        <div class="nav-order-card" style="justify-content: flex-end;">
            <x-filament::button
                type="button"
                color="gray"
                outlined
                icon="heroicon-o-arrow-path"
                wire:click="resetOrder"
                wire:confirm="{{ __('navigation_order.reset_confirm') }}"
            >
                {{ __('navigation_order.reset_default') }}
            </x-filament::button>

            <x-filament::button
                type="button"
                color="primary"
                icon="heroicon-o-check"
                wire:click="saveOrder"
            >
                {{ __('navigation_order.save_changes') }}
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
