{{-- Universal Native-Smooth Top Scrollbar for all Filament Tables --}}
@php
    use Filament\Tables\View\TablesRenderHook;
@endphp

<div
    wire:ignore
    x-data="globalTableTopScrollbar()"
    class="fi-global-table-top-scroll no-print"
    dir="{{ __('filament-panels::layout.direction') ?? 'rtl' }}"
    style="overflow-x: auto; overflow-y: hidden; height: 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-top: 1px solid #e2e8f0; display: none; width: 100%; box-sizing: border-box;"
>
    <div x-ref="inner" style="height: 1px;"></div>
</div>

<style>
    .fi-global-table-top-scroll {
        scrollbar-width: auto;
    }

    .dark .fi-global-table-top-scroll {
        background: rgba(255, 255, 255, 0.03) !important;
        border-bottom-color: rgba(255, 255, 255, 0.08) !important;
        border-top-color: rgba(255, 255, 255, 0.05) !important;
    }

    .fi-global-table-top-scroll::-webkit-scrollbar {
        height: 14px;
    }

    .fi-global-table-top-scroll::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .dark .fi-global-table-top-scroll::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.04);
    }

    .fi-global-table-top-scroll::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 6px;
        border: 2px solid #f1f5f9;
    }

    .fi-global-table-top-scroll::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    .dark .fi-global-table-top-scroll::-webkit-scrollbar-thumb {
        background: #64748b;
        border: 2px solid rgba(255, 255, 255, 0.04);
    }

    .dark .fi-global-table-top-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    @media print {
        .fi-global-table-top-scroll {
            display: none !important;
        }
    }
</style>

<script>
    if (!window.globalTableTopScrollbarRegistered) {
        window.globalTableTopScrollbarRegistered = true;

        document.addEventListener('alpine:init', () => {
            window.Alpine.data('globalTableTopScrollbar', () => ({
                init() {
                    const track = this.$el;
                    const inner = this.$refs.inner;
                    const ctn = track.closest('.fi-ta-ctn') || track.closest('.fi-ta-main')?.parentElement;
                    if (!ctn) return;

                    let activeSource = null;
                    let rafId = null;

                    const sync = (source, target) => {
                        if (activeSource && activeSource !== source) return;
                        activeSource = source;

                        if (rafId) cancelAnimationFrame(rafId);

                        rafId = requestAnimationFrame(() => {
                            target.scrollLeft = source.scrollLeft;
                            rafId = requestAnimationFrame(() => {
                                activeSource = null;
                            });
                        });
                    };

                    const getContent = () => ctn.querySelector('.fi-ta-content-ctn');

                    const updateSize = () => {
                        const content = getContent();
                        if (!content) {
                            track.style.display = 'none';
                            return;
                        }
                        const scrollWidth = content.scrollWidth;
                        const clientWidth = content.clientWidth;
                        const overflows = scrollWidth > clientWidth + 2;

                        if (overflows) {
                            inner.style.width = scrollWidth + 'px';
                            track.style.display = 'block';
                            if (!activeSource) {
                                track.scrollLeft = content.scrollLeft;
                            }
                        } else {
                            track.style.display = 'none';
                        }
                    };

                    track.addEventListener('scroll', () => {
                        const content = getContent();
                        if (content) sync(track, content);
                    }, { passive: true });

                    track.addEventListener('wheel', (e) => {
                        const content = getContent();
                        if (content && e.deltaY && !e.deltaX) {
                            e.preventDefault();
                            content.scrollLeft += e.deltaY;
                        }
                    }, { passive: false });

                    const attachContent = () => {
                        const content = getContent();
                        if (content && !content.dataset.hasTopScrollSync) {
                            content.dataset.hasTopScrollSync = 'true';
                            content.addEventListener('scroll', () => {
                                sync(content, track);
                            }, { passive: true });

                            if (window.ResizeObserver) {
                                const ro = new ResizeObserver(() => updateSize());
                                ro.observe(content);
                                if (content.firstElementChild) {
                                    ro.observe(content.firstElementChild);
                                }
                            }
                        }
                        updateSize();
                    };

                    setTimeout(attachContent, 60);
                    setTimeout(attachContent, 300);

                    if (window.Livewire) {
                        window.Livewire.hook('morph.updated', () => {
                            setTimeout(attachContent, 60);
                        });
                    }

                    window.addEventListener('resize', () => updateSize());
                }
            }));
        });
    }
</script>
