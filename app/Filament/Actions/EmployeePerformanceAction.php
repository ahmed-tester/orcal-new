<?php

namespace App\Filament\Actions;

use App\Models\Employee;
use Closure;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Route;

class EmployeePerformanceAction
{
    public static function make(string|Closure $resource): Action
    {
        $resolveResource = static function (?Employee $record) use ($resource): ?string {
            if ($record === null || app()->isProduction()) {
                return null;
            }

            $resourceClass = $resource instanceof Closure ? $resource($record) : $resource;

            return $resourceClass::hasPage('performance')
                && Route::has($resourceClass::getRouteBaseName().'.performance')
                ? $resourceClass
                : null;
        };

        return Action::make('performance')
            ->label(__('tasks.performance.action'))
            ->icon('heroicon-o-chart-bar')
            ->visible(function (?Employee $record) use ($resolveResource): bool {
                $resourceClass = $resolveResource($record);

                return $resourceClass !== null
                    && $resourceClass::getPages()['performance']->getPage()::canAccess(['record' => $record]);
            })
            ->url(function (?Employee $record) use ($resolveResource): ?string {
                $resourceClass = $resolveResource($record);

                return $resourceClass === null ? null : $resourceClass::getUrl('performance', ['record' => $record]);
            });
    }
}
