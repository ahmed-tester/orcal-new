<div style="display: flex; flex-direction: column; gap: 0.7rem; font-size: 0.85rem; line-height: 1.45;">
    {{-- Top Row: Selected Department(s) + Blocking Reason side by side --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.7rem; align-items: stretch;">
        <div style="border-radius: 0.75rem; border: 1px solid #e2e8f0; background: #f8fafc; padding: 0.75rem 0.9rem;">
            <div style="font-size: 0.76rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem;">
                {{ __('organization.departments.deletion.modal.selected_departments') }}
            </div>
            <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 0.3rem;">
                @foreach ($impact['roots'] as $root)
                    <li style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.45rem; color: #0f172a;">
                        <span style="font-weight: 700;">{{ $root['name'] }}</span>
                        <span style="border-radius: 9999px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 0.04rem 0.5rem; font-size: 0.72rem; font-weight: 600;">{{ $root['level_label'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($impact['has_blockers'])
            <div style="border-radius: 0.75rem; border: 1px solid #fcd34d; background: #fffbeb; padding: 0.75rem 0.9rem; color: #92400e; display: flex; align-items: center;">
                <div style="font-weight: 700; font-size: 0.82rem;">
                    ⚠️ {{ __('organization.departments.deletion.modal.blocked_intro', [
                        'reasons' => collect($impact['blockers'])
                            ->map(fn (string $blocker) => __('organization.departments.hierarchy.delete_blockers.' . $blocker))
                            ->implode(' '),
                    ]) }}
                </div>
            </div>
        @endif
    </div>

    @if ($impact['has_blockers'])
        {{-- 3-Column Impact Counts --}}
        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.6rem;">
            <div style="border-radius: 0.65rem; border: 1px solid #e2e8f0; background: #ffffff; padding: 0.55rem 0.75rem;">
                <div style="font-size: 0.72rem; font-weight: 600; color: #64748b;">{{ __('organization.departments.deletion.counts.children') }}</div>
                <div style="margin-top: 0.15rem; font-weight: 700; font-size: 0.8rem; color: #0f172a;">
                    {{ __('organization.departments.deletion.counts.direct_vs_total', [
                        'direct' => $impact['counts']['direct_children'],
                        'total' => $impact['counts']['total_descendants'],
                    ]) }}
                </div>
            </div>
            <div style="border-radius: 0.65rem; border: 1px solid #e2e8f0; background: #ffffff; padding: 0.55rem 0.75rem;">
                <div style="font-size: 0.72rem; font-weight: 600; color: #64748b;">{{ __('organization.departments.deletion.counts.employees') }}</div>
                <div style="margin-top: 0.15rem; font-weight: 700; font-size: 0.8rem; color: #0f172a;">
                    {{ __('organization.departments.deletion.counts.direct_vs_total', [
                        'direct' => $impact['counts']['direct_employees'],
                        'total' => $impact['counts']['total_employees'],
                    ]) }}
                </div>
            </div>
            <div style="border-radius: 0.65rem; border: 1px solid #e2e8f0; background: #ffffff; padding: 0.55rem 0.75rem;">
                <div style="font-size: 0.72rem; font-weight: 600; color: #64748b;">{{ __('organization.departments.deletion.counts.job_titles') }}</div>
                <div style="margin-top: 0.15rem; font-weight: 700; font-size: 0.8rem; color: #0f172a;">
                    {{ __('organization.departments.deletion.counts.direct_vs_total', [
                        'direct' => $impact['counts']['direct_job_titles'],
                        'total' => $impact['counts']['total_job_titles'],
                    ]) }}
                </div>
            </div>
        </div>

        @if (($impact['counts']['financial_records'] ?? 0) > 0 || ! empty($impact['warnings']))
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.6rem;">
                @if (($impact['counts']['financial_records'] ?? 0) > 0)
                    <div style="border-radius: 0.65rem; border: 1px solid #bfdbfe; background: #eff6ff; padding: 0.55rem 0.8rem; font-size: 0.76rem; font-weight: 600; color: #1e40af;">
                        ℹ️ {{ __('organization.departments.deletion.modal.financial_preserved_notice', [
                            'count' => $impact['counts']['financial_records'],
                        ]) }}
                    </div>
                @endif

                @if (! empty($impact['warnings']))
                    <div style="border-radius: 0.65rem; border: 1px solid #fecaca; background: #fef2f2; padding: 0.55rem 0.8rem; font-size: 0.76rem; color: #991b1b;">
                        <ul style="margin: 0; padding-inline-start: 1.1rem; display: flex; flex-direction: column; gap: 0.2rem;">
                            @foreach ($impact['warnings'] as $warning)
                                <li style="font-weight: 600;">{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- Detailed Affected Items Card (Multi-column & Scrollable) --}}
        <div style="border-radius: 0.75rem; border: 1px solid #e2e8f0; background: #f8fafc; padding: 0.75rem 0.9rem; font-size: 0.78rem;">
            <div style="font-weight: 700; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.35rem; margin-bottom: 0.55rem;">
                {{ __('organization.departments.deletion.modal.affected_lists_heading') }}
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; max-height: 150px; overflow-y: auto;">
                @if (! empty($impact['affected_departments']))
                    <div>
                        <div style="font-weight: 700; color: #334155; margin-bottom: 0.2rem;">{{ __('organization.departments.deletion.modal.affected_departments_label') }}:</div>
                        <ul style="margin: 0; padding-inline-start: 1.1rem; display: flex; flex-direction: column; gap: 0.15rem; color: #475569;">
                            @foreach (array_slice($impact['affected_departments'], 0, 10) as $deptItem)
                                <li style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem;">
                                    <span style="font-weight: 600; color: #0f172a;">{{ $deptItem['name'] }}</span>
                                    <span style="border-radius: 9999px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 0.02rem 0.45rem; font-size: 0.7rem; font-weight: 600;">{{ $deptItem['level_label'] }}</span>
                                </li>
                            @endforeach
                            @if (count($impact['affected_departments']) > 10)
                                <li style="font-weight: 600; color: #64748b;">{{ __('organization.departments.deletion.modal.more_items_suffix', ['count' => count($impact['affected_departments']) - 10]) }}</li>
                            @endif
                        </ul>
                    </div>
                @endif

                @if (! empty($impact['affected_employees']))
                    <div>
                        <div style="font-weight: 700; color: #334155; margin-bottom: 0.2rem;">{{ __('organization.departments.deletion.modal.affected_employees_label') }}:</div>
                        <ul style="margin: 0; padding-inline-start: 1.1rem; display: flex; flex-direction: column; gap: 0.15rem; color: #475569;">
                            @foreach (array_slice($impact['affected_employees'], 0, 10) as $empItem)
                                <li>
                                    <span style="font-weight: 600; color: #0f172a;">{{ $empItem['name'] }}</span>
                                    @if (! empty($empItem['company_name']))
                                        <span style="color: #64748b;">({{ $empItem['company_name'] }})</span>
                                    @endif
                                    @if (! empty($empItem['has_other_capacity']))
                                        <span style="color: #1d4ed8; font-weight: 600;">— {{ __('organization.departments.deletion.modal.owner_capacity_badge') }}</span>
                                    @endif
                                </li>
                            @endforeach
                            @if (count($impact['affected_employees']) > 10)
                                <li style="font-weight: 600; color: #64748b;">{{ __('organization.departments.deletion.modal.more_items_suffix', ['count' => count($impact['affected_employees']) - 10]) }}</li>
                            @endif
                        </ul>
                    </div>
                @endif

                @if (! empty($impact['affected_job_titles']))
                    <div>
                        <div style="font-weight: 700; color: #334155; margin-bottom: 0.2rem;">{{ __('organization.departments.deletion.modal.affected_job_titles_label') }}:</div>
                        <ul style="margin: 0; padding-inline-start: 1.1rem; display: flex; flex-direction: column; gap: 0.15rem; color: #475569;">
                            @foreach (array_slice($impact['affected_job_titles'], 0, 10) as $jtItem)
                                <li style="font-weight: 600; color: #0f172a;">{{ $jtItem['name'] }}</li>
                            @endforeach
                            @if (count($impact['affected_job_titles']) > 10)
                                <li style="font-weight: 600; color: #64748b;">{{ __('organization.departments.deletion.modal.more_items_suffix', ['count' => count($impact['affected_job_titles']) - 10]) }}</li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div style="border-radius: 0.75rem; border: 1px solid #bbf7d0; background: #f0fdf4; padding: 0.75rem 0.9rem; color: #166534; font-weight: 600;">
            {{ __('organization.departments.deletion.modal.leaf_confirm_notice') }}
        </div>
    @endif
</div>
