<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Enums\UserType;
use App\Filament\Resources\Leaves\LeaveResource;
use App\Models\Leave;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('summaryView')
                ->label(__('leaves.summary.summary_view'))
                ->icon('heroicon-o-users')
                ->color('gray')
                ->url(fn (): string => LeaveResource::getUrl('index')),

            CreateAction::make()->label(__('admin.add_new')),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $pendingQuery = Leave::where('status', 'pending');
        if ($user && ! $user->hasRole('Super Admin') && $user->user_type !== UserType::SUPER_ADMIN && $user->user_type !== UserType::OWNER && $user->branch_id) {
            $pendingQuery->whereHas('employee', fn ($q) => $q->where('branch_id', $user->branch_id));
        }

        return [
            'all' => Tab::make(__('admin.all')),
            'pending' => Tab::make(__('leaves.statuses.pending_short'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge($pendingQuery->count())
                ->badgeColor('warning'),
            'approved' => Tab::make(__('leaves.statuses.approved_short'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badgeColor('success'),
            'rejected' => Tab::make(__('leaves.statuses.rejected'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badgeColor('danger'),
        ];
    }
}
