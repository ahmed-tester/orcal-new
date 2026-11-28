<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Invoice;
use App\Models\UnifiedDeficiencyTask;
use App\Services\CompanyFormService;
use App\Services\EmployeePerformanceAccessService;
use App\Services\Tasks\Renewals\DocumentRenewalAccessService;
use App\Services\Tasks\Renewals\DocumentRenewalApprovalService;
use App\Services\Tasks\Renewals\DocumentRenewalClaimService;
use App\Services\Tasks\Renewals\RenewalDocumentRegistry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class DocumentRenewals extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'document-renewals';

    protected string $view = 'filament.pages.document-renewals';

    /** @var array<string, mixed>|null */
    private ?array $rowContextCache = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.tasks_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('document_renewals.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('document_renewals.title');
    }

    public static function canAccess(): bool
    {
        if (! config('document_renewals.enabled', false)) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isOwner() || $user->can('update_treasury_transaction')) {
            return true;
        }

        $employee = $user->employee;
        if (! $employee instanceof Employee) {
            return false;
        }

        if (DocumentRenewalAccessService::directRenewalPermissions($user) !== []
            || DocumentRenewalAccessService::roleInheritedRenewalPermissions($user) !== []) {
            return true;
        }

        return UnifiedDeficiencyTask::query()
            ->documentRenewals()
            ->where('responsible_employee_id', $employee->id)
            ->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('id', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading(__('document_renewals.empty_state.heading'))
            ->emptyStateDescription(__('document_renewals.empty_state.description'))
            ->columns([
                TextColumn::make('task_code')
                    ->label(__('document_renewals.columns.task_code'))
                    ->weight('bold')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company.trade_name')
                    ->label(__('document_renewals.columns.company'))
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('document_type')
                    ->label(__('document_renewals.columns.document_type'))
                    ->badge()
                    ->color('info')
                    ->state(function (UnifiedDeficiencyTask $record): string {
                        $type = (string) ($record->metadata['document_type'] ?? '');

                        return RenewalDocumentRegistry::isValidType($type)
                            ? RenewalDocumentRegistry::label($type)
                            : $type;
                    }),

                TextColumn::make('source_label')
                    ->label(__('document_renewals.columns.target'))
                    ->wrap()
                    ->state(fn (UnifiedDeficiencyTask $record): string => (string) ($record->metadata['source_label'] ?? '—')),

                TextColumn::make('cycle_expiry')
                    ->label(__('document_renewals.columns.cycle_expiry'))
                    ->badge()
                    ->color('gray')
                    ->state(fn (UnifiedDeficiencyTask $record): string => (string) ($record->metadata['cycle_expiry_date'] ?? '—')),

                TextColumn::make('due_date')
                    ->label(__('document_renewals.columns.due_date'))
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('document_renewals.columns.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __('document_renewals.statuses.'.$state) : '—')
                    ->color(fn (?string $state): string => match ($state) {
                        UnifiedDeficiencyTask::RENEWAL_STATUS_AVAILABLE => 'warning',
                        UnifiedDeficiencyTask::RENEWAL_STATUS_CLAIMED => 'info',
                        UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY => 'primary',
                        UnifiedDeficiencyTask::RENEWAL_STATUS_RETURNED => 'danger',
                        UnifiedDeficiencyTask::RENEWAL_STATUS_COMPLETED => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('responsibleEmployee.user.name')
                    ->label(__('document_renewals.columns.assignee'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('fee_summary')
                    ->label(__('document_renewals.columns.fee_summary'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(function (UnifiedDeficiencyTask $record): string {
                        $mode = $record->metadata['renewal_fee_mode'] ?? null;
                        if ($mode === Invoice::RENEWAL_FEE_NO_FEE) {
                            return __('document_renewals.invoice.no_fee_badge');
                        }
                        $amount = $record->metadata['latest_submission']['amount'] ?? null;

                        return $amount !== null ? number_format((float) $amount, 2) : '—';
                    }),

                TextColumn::make('proposed_expiry')
                    ->label(__('document_renewals.columns.proposed_expiry'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(fn (UnifiedDeficiencyTask $record): string => (string) ($record->metadata['applied_expiry_date'] ?? $record->metadata['proposed_new_expiry_date'] ?? '—')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('document_renewals.filters.status'))
                    ->options([
                        UnifiedDeficiencyTask::RENEWAL_STATUS_AVAILABLE => __('document_renewals.statuses.available'),
                        UnifiedDeficiencyTask::RENEWAL_STATUS_CLAIMED => __('document_renewals.statuses.claimed'),
                        UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY => __('document_renewals.statuses.pending_treasury'),
                        UnifiedDeficiencyTask::RENEWAL_STATUS_RETURNED => __('document_renewals.statuses.returned'),
                        UnifiedDeficiencyTask::RENEWAL_STATUS_COMPLETED => __('document_renewals.statuses.completed'),
                    ]),

                SelectFilter::make('document_type')
                    ->label(__('document_renewals.filters.document_type'))
                    ->options(function (): array {
                        $opts = [];
                        foreach (RenewalDocumentRegistry::allTypes() as $type) {
                            $opts[$type] = RenewalDocumentRegistry::label($type);
                        }

                        return $opts;
                    })
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('metadata->document_type', $data['value'])
                        : $query),

                TernaryFilter::make('mine_only')
                    ->label(__('document_renewals.filters.mine_only'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('responsible_employee_id', auth()->user()?->employee?->id ?? 0),
                        false: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                Action::make('view_details')
                    ->label(__('document_renewals.actions.view_details'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(__('document_renewals.details.modal_heading'))
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('document_renewals.details.close'))
                    ->modalContent(fn (UnifiedDeficiencyTask $record) => view('filament.modals.document-renewal-details', [
                        'record' => $record,
                    ])),

                Action::make('claim')
                    ->label(__('document_renewals.actions.claim'))
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->button()
                    ->size('sm')
                    ->requiresConfirmation()
                    ->visible(fn (UnifiedDeficiencyTask $record): bool => $this->canClaimRow($record))
                    ->action(function (UnifiedDeficiencyTask $record): void {
                        $result = DocumentRenewalClaimService::claim(auth()->user(), $record);

                        Notification::make()
                            ->title($result['already_claimed_by_user']
                                ? __('document_renewals.actions.claim_already_yours')
                                : __('document_renewals.actions.claim_success'))
                            ->success()
                            ->send();
                    }),

                Action::make('submit_treasury')
                    ->label(__('document_renewals.actions.submit_treasury'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->modalWidth('2xl')
                    ->visible(fn (UnifiedDeficiencyTask $record): bool => $this->canEditRow($record))
                    ->form(fn (UnifiedDeficiencyTask $record): array => [
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                Radio::make('fee_mode')
                                    ->label(__('document_renewals.fields.fee_mode'))
                                    ->options([
                                        Invoice::RENEWAL_FEE_WITH_FEE => __('document_renewals.fee_modes.with_fee'),
                                        Invoice::RENEWAL_FEE_NO_FEE => __('document_renewals.fee_modes.no_fee'),
                                    ])
                                    ->inline()
                                    ->default($record->metadata['renewal_fee_mode'] ?? Invoice::RENEWAL_FEE_WITH_FEE)
                                    ->required()
                                    ->live()
                                    ->columnSpanFull(),

                                DatePicker::make('new_expiry_date')
                                    ->label(__('document_renewals.fields.new_expiry_date'))
                                    ->default($record->metadata['proposed_new_expiry_date'] ?? null)
                                    ->required()
                                    ->columnSpan(fn ($get): int => $get('fee_mode') === Invoice::RENEWAL_FEE_WITH_FEE ? 1 : 2),

                                TextInput::make('amount')
                                    ->label(__('document_renewals.fields.amount'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default($record->metadata['latest_submission']['amount'] ?? null)
                                    ->visible(fn ($get): bool => $get('fee_mode') === Invoice::RENEWAL_FEE_WITH_FEE)
                                    ->required(fn ($get): bool => $get('fee_mode') === Invoice::RENEWAL_FEE_WITH_FEE),

                                Select::make('payment_method')
                                    ->label(__('document_renewals.fields.payment_method'))
                                    ->options([
                                        'cash' => __('document_renewals.payment_methods.cash'),
                                        'bank_transfer' => __('document_renewals.payment_methods.bank_transfer'),
                                    ])
                                    ->default($record->metadata['latest_submission']['payment_method'] ?? 'cash')
                                    ->visible(fn ($get): bool => $get('fee_mode') === Invoice::RENEWAL_FEE_WITH_FEE),

                                TextInput::make('reference_number')
                                    ->label(__('document_renewals.fields.reference_number'))
                                    ->default($record->metadata['latest_submission']['reference_number'] ?? null)
                                    ->visible(fn ($get): bool => $get('fee_mode') === Invoice::RENEWAL_FEE_WITH_FEE),

                                FileUpload::make('proof_path')
                                    ->label(__('document_renewals.fields.proof_path'))
                                    ->directory('document-renewals/proofs')
                                    ->visible(fn ($get): bool => $get('fee_mode') === Invoice::RENEWAL_FEE_WITH_FEE),

                                FileUpload::make('document_attachment')
                                    ->label(__('document_renewals.fields.document_attachment'))
                                    ->directory('document-renewals/documents')
                                    ->columnSpan(fn ($get): int => $get('fee_mode') === Invoice::RENEWAL_FEE_WITH_FEE ? 1 : 2),

                                Textarea::make('notes')
                                    ->label(__('document_renewals.fields.notes'))
                                    ->rows(2)
                                    ->default($record->metadata['latest_submission']['notes'] ?? null)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (UnifiedDeficiencyTask $record, array $data): void {
                        DocumentRenewalApprovalService::submitForTreasury(
                            user: auth()->user(),
                            request: $record,
                            payload: [
                                'fee_mode' => $data['fee_mode'],
                                'amount' => $data['fee_mode'] === Invoice::RENEWAL_FEE_NO_FEE ? 0 : ($data['amount'] ?? 0),
                                'new_expiry_date' => $data['new_expiry_date'],
                                'payment_method' => $data['payment_method'] ?? null,
                                'reference_number' => $data['reference_number'] ?? null,
                                'proof_path' => $data['proof_path'] ?? null,
                                'document_attachment' => $data['document_attachment'] ?? null,
                                'notes' => $data['notes'] ?? null,
                            ],
                        );

                        Notification::make()
                            ->title(__('document_renewals.actions.submit_success'))
                            ->success()
                            ->send();
                    }),

                Action::make('approve_treasury')
                    ->label(__('document_renewals.actions.approve_treasury'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->modalWidth('lg')
                    ->visible(fn (UnifiedDeficiencyTask $record): bool => $this->canApproveRow($record))
                    ->form([
                        Hidden::make('expected_version')
                            ->default(fn (UnifiedDeficiencyTask $record): int => (int) ($record->metadata['submission_version'] ?? 0)),
                        Textarea::make('note')
                            ->label(__('document_renewals.fields.approval_note'))
                            ->rows(2),
                    ])
                    ->action(function (UnifiedDeficiencyTask $record, array $data): void {
                        DocumentRenewalApprovalService::approve(
                            reviewer: auth()->user(),
                            request: $record,
                            expectedVersion: (int) ($data['expected_version'] ?? 0),
                            note: $data['note'] ?? null,
                        );

                        Notification::make()
                            ->title(__('document_renewals.actions.approve_success'))
                            ->success()
                            ->send();
                    }),

                Action::make('return_treasury')
                    ->label(__('document_renewals.actions.return_treasury'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->modalWidth('lg')
                    ->visible(fn (UnifiedDeficiencyTask $record): bool => $this->canApproveRow($record))
                    ->form([
                        Hidden::make('expected_version')
                            ->default(fn (UnifiedDeficiencyTask $record): int => (int) ($record->metadata['submission_version'] ?? 0)),
                        Textarea::make('reason')
                            ->label(__('document_renewals.fields.return_reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (UnifiedDeficiencyTask $record, array $data): void {
                        DocumentRenewalApprovalService::returnForCorrection(
                            reviewer: auth()->user(),
                            request: $record,
                            reason: (string) $data['reason'],
                            expectedVersion: (int) ($data['expected_version'] ?? 0),
                        );

                        Notification::make()
                            ->title(__('document_renewals.actions.return_success'))
                            ->warning()
                            ->send();
                    }),

                Action::make('reassign')
                    ->label(__('document_renewals.actions.reassign'))
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('gray')
                    ->modalWidth('lg')
                    ->visible(fn (UnifiedDeficiencyTask $record): bool => $this->canReassignRow($record))
                    ->form([
                        CompanyFormService::makeEmployeeSelect(
                            name: 'target_employee_id',
                            label: __('document_renewals.fields.target_employee'),
                            required: true,
                            saudiOnly: true,
                            ignoreCompanyScope: true,
                        ),
                        Textarea::make('reason')
                            ->label(__('document_renewals.fields.reassignment_reason'))
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (UnifiedDeficiencyTask $record, array $data): void {
                        $target = Employee::with('user')->findOrFail((int) $data['target_employee_id']);
                        DocumentRenewalClaimService::reassign(
                            actor: auth()->user(),
                            request: $record,
                            targetEmployee: $target,
                            reason: (string) $data['reason'],
                        );

                        Notification::make()
                            ->title(__('document_renewals.actions.reassign_success'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $query = UnifiedDeficiencyTask::query()
            ->documentRenewals()
            ->with(['company', 'responsibleEmployee.user']);

        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $employee = $user->employee;
        $coveredCompanyIds = $employee ? DocumentRenewalAccessService::coveredCompanyIdsForManager($employee) : [];
        $ownerCompanyIds = $user->isOwner()
            ? CompanyFormService::getAuthorizedCompanyIds($user)
            : [];
        $treasuryCompanyIds = (! $user->isOwner() && $user->can('update_treasury_transaction'))
            ? CompanyFormService::getAuthorizedCompanyIds($user)
            : [];
        $userBranchId = DocumentRenewalAccessService::userRestrictedBranchId($user);

        $allowedDocTypes = [];
        foreach (RenewalDocumentRegistry::allTypes() as $type) {
            if (DocumentRenewalAccessService::userHasExplicitRenewalPermission($user, RenewalDocumentRegistry::permissionFor($type))) {
                $allowedDocTypes[] = $type;
            }
        }

        return $query->where(function (Builder $q) use ($user, $employee, $coveredCompanyIds, $ownerCompanyIds, $treasuryCompanyIds, $userBranchId, $allowedDocTypes): void {
            $hasCondition = false;

            if ($employee) {
                $q->orWhere('responsible_employee_id', $employee->id);
                $hasCondition = true;
            }

            if ($coveredCompanyIds !== [] && $allowedDocTypes !== []) {
                $q->orWhere(function (Builder $avail) use ($coveredCompanyIds, $allowedDocTypes): void {
                    $avail->where('status', UnifiedDeficiencyTask::RENEWAL_STATUS_AVAILABLE)
                        ->whereNull('responsible_employee_id')
                        ->whereIn('company_id', $coveredCompanyIds)
                        ->whereIn('metadata->document_type', $allowedDocTypes);
                });
                $hasCondition = true;
            }

            if ($ownerCompanyIds !== []) {
                $q->orWhere(function (Builder $authQ) use ($ownerCompanyIds, $userBranchId): void {
                    $authQ->whereIn('company_id', $ownerCompanyIds);
                    if ($userBranchId !== null) {
                        $authQ->where(function (Builder $bq) use ($userBranchId): void {
                            $bq->whereNull('metadata->branch_id')
                                ->orWhere('metadata->branch_id', $userBranchId);
                        });
                    }
                });
                $hasCondition = true;
            }

            if ($treasuryCompanyIds !== []) {
                $q->orWhere(function (Builder $treasQ) use ($treasuryCompanyIds, $userBranchId, $user, $employee): void {
                    $treasQ->where('status', UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY)
                        ->whereIn('company_id', $treasuryCompanyIds)
                        ->where(function (Builder $nq) use ($user, $employee): void {
                            if ($employee) {
                                $nq->whereNull('responsible_employee_id')
                                    ->orWhere('responsible_employee_id', '!=', $employee->id);
                            }
                            $nq->where(function (Builder $sq) use ($user): void {
                                $sq->whereNull('metadata->submitted_by_user_id')
                                    ->orWhere('metadata->submitted_by_user_id', '!=', $user->id);
                            });
                        });

                    if ($userBranchId !== null) {
                        $treasQ->where(function (Builder $bq) use ($userBranchId): void {
                            $bq->whereNull('metadata->branch_id')
                                ->orWhere('metadata->branch_id', $userBranchId);
                        });
                    }
                });
                $hasCondition = true;
            }

            if (! $hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Memoize user-level permissions and scope once per Livewire render to prevent N+1 row checks.
     *
     * @return array{
     *     user_id: int,
     *     employee_id: ?int,
     *     is_active: bool,
     *     is_saudi_eligible: bool,
     *     is_super_admin: bool,
     *     is_owner: bool,
     *     has_treasury: bool,
     *     branch_id: ?int,
     *     authorized_companies: array<int, int>,
     *     covered_companies: array<int, int>,
     *     allowed_doc_types: array<string, bool>
     * }
     */
    private function rowContext(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [
                'user_id' => 0,
                'employee_id' => null,
                'is_active' => false,
                'is_saudi_eligible' => false,
                'is_super_admin' => false,
                'is_owner' => false,
                'has_treasury' => false,
                'branch_id' => null,
                'authorized_companies' => [],
                'covered_companies' => [],
                'allowed_doc_types' => [],
            ];
        }

        if ($this->rowContextCache !== null && $this->rowContextCache['user_id'] === (int) $user->id) {
            return $this->rowContextCache;
        }

        $employee = $user->employee;
        $isSaudiEligible = $employee instanceof Employee
            && EmployeePerformanceAccessService::isEligibleSaudiTaskEmployee($employee);

        $allowedDocTypes = [];
        foreach (RenewalDocumentRegistry::allTypes() as $type) {
            if (DocumentRenewalAccessService::userHasExplicitRenewalPermission($user, RenewalDocumentRegistry::permissionFor($type))) {
                $allowedDocTypes[$type] = true;
            }
        }

        $isSuperAdmin = $user->isSuperAdmin();
        $isOwner = $user->isOwner();
        $hasTreasury = $isSuperAdmin || $user->can('update_treasury_transaction');

        return $this->rowContextCache = [
            'user_id' => (int) $user->id,
            'employee_id' => $employee?->id ? (int) $employee->id : null,
            'is_active' => DocumentRenewalAccessService::isUserActive($user),
            'is_saudi_eligible' => $isSaudiEligible,
            'is_super_admin' => $isSuperAdmin,
            'is_owner' => $isOwner,
            'has_treasury' => $hasTreasury,
            'branch_id' => DocumentRenewalAccessService::userRestrictedBranchId($user),
            'authorized_companies' => ($isOwner || $hasTreasury) ? CompanyFormService::getAuthorizedCompanyIds($user) : [],
            'covered_companies' => ($isSaudiEligible && $employee) ? DocumentRenewalAccessService::coveredCompanyIdsForManager($employee) : [],
            'allowed_doc_types' => $allowedDocTypes,
        ];
    }

    private function canClaimRow(UnifiedDeficiencyTask $record): bool
    {
        if ($record->status !== UnifiedDeficiencyTask::RENEWAL_STATUS_AVAILABLE || $record->responsible_employee_id !== null) {
            return false;
        }

        $ctx = $this->rowContext();
        if (! $ctx['is_saudi_eligible']) {
            return false;
        }

        $type = (string) ($record->metadata['document_type'] ?? '');

        return isset($ctx['allowed_doc_types'][$type])
            && in_array((int) $record->company_id, $ctx['covered_companies'], true);
    }

    private function canEditRow(UnifiedDeficiencyTask $record): bool
    {
        if (! in_array($record->status, [
            UnifiedDeficiencyTask::RENEWAL_STATUS_CLAIMED,
            UnifiedDeficiencyTask::RENEWAL_STATUS_RETURNED,
        ], true)) {
            return false;
        }

        $ctx = $this->rowContext();
        if (! $ctx['is_saudi_eligible'] || $ctx['employee_id'] !== (int) $record->responsible_employee_id) {
            return false;
        }

        $type = (string) ($record->metadata['document_type'] ?? '');

        return isset($ctx['allowed_doc_types'][$type])
            && in_array((int) $record->company_id, $ctx['covered_companies'], true);
    }

    private function canApproveRow(UnifiedDeficiencyTask $record): bool
    {
        if ($record->status !== UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY) {
            return false;
        }

        $ctx = $this->rowContext();
        if (! $ctx['is_active'] || ! $ctx['has_treasury']) {
            return false;
        }

        if ($ctx['employee_id'] !== null && $ctx['employee_id'] === (int) $record->responsible_employee_id) {
            return false;
        }

        if ((int) ($record->metadata['submitted_by_user_id'] ?? 0) === $ctx['user_id']) {
            return false;
        }

        if (! $ctx['is_super_admin'] && ! in_array((int) $record->company_id, $ctx['authorized_companies'], true)) {
            return false;
        }

        $reqBranch = filled($record->metadata['branch_id'] ?? null) ? (int) $record->metadata['branch_id'] : null;

        return ! ($reqBranch !== null && $ctx['branch_id'] !== null && $ctx['branch_id'] !== $reqBranch);
    }

    private function canReassignRow(UnifiedDeficiencyTask $record): bool
    {
        if ($record->responsible_employee_id === null || in_array($record->status, [
            UnifiedDeficiencyTask::RENEWAL_STATUS_COMPLETED,
            UnifiedDeficiencyTask::RENEWAL_STATUS_CANCELLED,
        ], true)) {
            return false;
        }

        $ctx = $this->rowContext();

        return $ctx['is_super_admin']
            || ($ctx['is_owner'] && in_array((int) $record->company_id, $ctx['authorized_companies'], true));
    }
}
