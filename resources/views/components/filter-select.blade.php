@props([
    'label' => null,
    'placeholder' => __('reports.filters.status_all'),
    'options' => [],
    'multiple' => false,
    'searchable' => true,
    'searchPlaceholder' => __('reports.filters.search_placeholder'),
])

@php
    $formattedOptions = [];

    if ($placeholder && ! $multiple) {
        $formattedOptions[] = ['value' => '', 'label' => $placeholder];
    }

    if (is_string($options) && enum_exists($options)) {
        foreach ($options::cases() as $case) {
            $formattedOptions[] = [
                'value' => (string) ($case->value ?? $case->name),
                'label' => method_exists($case, 'getLabel') ? $case->getLabel() : ($case->value ?? $case->name),
            ];
        }
    } elseif (is_iterable($options)) {
        foreach ($options as $key => $item) {
            if (is_object($item)) {
                $val = (string) ($item->id ?? $item->value ?? $key);
                $lbl = (string) ($item->trade_name ?? $item->name ?? $item->title ?? $item->label ?? $val);
            } elseif (is_array($item)) {
                $val = (string) ($item['id'] ?? $item['value'] ?? $key);
                $lbl = (string) ($item['trade_name'] ?? $item['name'] ?? $item['title'] ?? $item['label'] ?? $val);
            } else {
                $val = is_numeric($key) ? (string) $item : (string) $key;
                $lbl = (string) $item;
            }

            // Clean any stray JSON string in label
            if (is_string($lbl) && (str_starts_with(trim($lbl), '{') || str_contains($lbl, '"sub_item"'))) {
                $decoded = json_decode($lbl, true);
                if (is_array($decoded)) {
                    $lbl = $decoded['sub_item'] ?? $decoded['main_item'] ?? $lbl;
                }
            }

            $formattedOptions[] = ['value' => $val, 'label' => $lbl];
        }
    }

    $wireModel = $attributes->wire('model')->value();
@endphp

<div class="fb-col" style="position: relative;">
    @if($label)
        <label class="fb-label">{{ $label }}</label>
    @endif

    <div
        x-data="{
            open: false,
            search: '',
            multiple: {{ $multiple ? 'true' : 'false' }},
            value: @if($wireModel) @entangle($attributes->wire('model')) @else {{ $multiple ? '[]' : 'null' }} @endif,
            options: @js($formattedOptions),
            placeholder: @js($placeholder),
            selectedCountLabel: @js(__('reports.filters.selected_count')),

            init() {
                if (this.multiple) {
                    if (!Array.isArray(this.value)) {
                        this.value = (this.value !== null && this.value !== undefined && this.value !== '') ? [this.value] : [];
                    }
                }
            },

            get selectedLabel() {
                if (this.multiple) {
                    if (!this.value || !Array.isArray(this.value) || this.value.length === 0) {
                        return this.placeholder;
                    }
                    if (this.value.length === 1) {
                        let found = this.options.find(o => String(o.value) === String(this.value[0]));
                        return found ? found.label : this.placeholder;
                    }
                    return this.selectedCountLabel.replace(':count', this.value.length);
                } else {
                    let found = this.options.find(o => String(o.value) === String(this.value ?? ''));
                    return found ? found.label : this.placeholder;
                }
            },

            isSelected(val) {
                if (this.multiple) {
                    return Array.isArray(this.value) && this.value.some(v => String(v) === String(val));
                }
                return String(this.value ?? '') === String(val);
            },

            toggle(val) {
                if (val === '' || val === null || val === 'all') {
                    if (this.multiple) {
                        this.value = [];
                    } else {
                        this.value = null;
                        this.open = false;
                    }
                    this.search = '';
                    return;
                }

                if (this.multiple) {
                    let current = Array.isArray(this.value) ? [...this.value] : [];
                    let sVal = String(val);
                    let idx = current.findIndex(v => String(v) === sVal);
                    if (idx > -1) {
                        current.splice(idx, 1);
                    } else {
                        current.push(val);
                    }
                    this.value = current;
                } else {
                    this.value = (String(this.value) === String(val)) ? null : val;
                    this.open = false;
                    this.search = '';
                }
            },

            selectAll() {
                if (!this.multiple) return;
                this.value = this.options
                    .filter(o => o.value !== '' && o.value !== null && o.value !== 'all')
                    .map(o => o.value);
            },

            clearAll() {
                if (this.multiple) {
                    this.value = [];
                } else {
                    this.value = null;
                    this.open = false;
                }
                this.search = '';
            },

            get filteredOptions() {
                if (!this.search.trim()) return this.options;
                let q = this.normalize(this.search.toLowerCase());
                return this.options.filter(o => this.normalize(o.label.toLowerCase()).includes(q));
            },

            normalize(str) {
                return (str || '')
                    .replace(/[إأآا]/g, 'ا')
                    .replace(/ة/g, 'ه')
                    .replace(/ى/g, 'ي')
                    .trim();
            }
        }"
        class="fb-select-wrapper"
        @click.outside="open = false"
    >
        {{-- Trigger button --}}
        <button
            type="button"
            @click="open = !open; if(open) $nextTick(() => { if($refs.searchInput) $refs.searchInput.focus(); })"
            class="fb-select-trigger"
            :class="{ 'active': open, 'has-value': multiple ? (Array.isArray(value) && value.length > 0) : Boolean(value) }"
        >
            <span class="fb-select-label" x-text="selectedLabel"></span>
            <x-heroicon-m-chevron-up-down class="fb-select-arrow" />
        </button>

        {{-- Dropdown Menu --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="fb-select-dropdown"
            x-cloak
        >
            {{-- Search Box --}}
            @if($searchable)
                <div class="fb-select-search-box">
                    <x-heroicon-m-magnifying-glass class="search-icon" />
                    <input
                        type="text"
                        x-ref="searchInput"
                        x-model="search"
                        placeholder="{{ $searchPlaceholder }}"
                        class="fb-select-search-input"
                        @keydown.escape="open = false"
                    />
                    <button
                        type="button"
                        x-show="search.length > 0"
                        @click="search = ''"
                        class="search-clear-btn"
                    >
                        <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                    </button>
                </div>
            @endif

            {{-- Multi-Select Actions (Select All / Clear) --}}
            <template x-if="multiple">
                <div class="fb-select-bulk-actions">
                    <button type="button" @click="selectAll()" class="fb-bulk-btn">
                        {{ __('reports.filters.select_all') }}
                    </button>
                    <button type="button" @click="clearAll()" class="fb-bulk-btn text-danger">
                        {{ __('reports.filters.clear_selection') }}
                    </button>
                </div>
            </template>

            {{-- Options List --}}
            <ul class="fb-select-list">
                <template x-for="opt in filteredOptions" :key="opt.value">
                    <li
                        @click="toggle(opt.value)"
                        class="fb-select-option"
                        :class="{ 'selected': isSelected(opt.value) }"
                    >
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            {{-- Checkbox icon for multiple --}}
                            <template x-if="multiple && opt.value !== '' && opt.value !== null && opt.value !== 'all'">
                                <div class="fb-checkbox" :class="{ 'checked': isSelected(opt.value) }">
                                    <x-heroicon-m-check x-show="isSelected(opt.value)" class="text-white" style="width: 11px !important; height: 11px !important;" />
                                </div>
                            </template>
                            <span x-text="opt.label"></span>
                        </div>

                        {{-- Checkmark icon for single select --}}
                        <template x-if="!multiple && isSelected(opt.value)">
                            <x-heroicon-m-check class="fb-select-check-icon" />
                        </template>
                    </li>
                </template>

                <li x-show="filteredOptions.length === 0" class="fb-select-empty">
                    {{ __('reports.sidebar.no_results') }}
                </li>
            </ul>
        </div>
    </div>
</div>
