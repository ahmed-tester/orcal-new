<?php

namespace App\Console\Commands;

use App\Services\Tasks\Renewals\DocumentRenewalRequestService;
use App\Services\Tasks\Renewals\RenewalDocumentRegistry;
use Illuminate\Console\Command;

class CheckDocumentRenewalsCommand extends Command
{
    protected $signature = 'documents:check-renewals
                            {--type=* : Restrict scan to specific whitelisted document types}
                            {--dry-run : Preview eligible documents without writing tasks, requests, invoices, or notifications}
                            {--since= : Optional cutoff date (YYYY-MM-DD) to exclude older historical expiries}';

    protected $description = 'Scan all 11 whitelisted document and subscription types and create shared renewal requests';

    public function handle(): int
    {
        if (! config('document_renewals.enabled', false)) {
            $this->info('Document renewals module is disabled in config/document_renewals.php.');

            return self::SUCCESS;
        }

        $requestedTypes = array_values(array_filter((array) $this->option('type')));
        foreach ($requestedTypes as $type) {
            if (! RenewalDocumentRegistry::isValidType((string) $type)) {
                $this->error("Unsupported document type: {$type}");

                return self::INVALID;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $since = $this->option('since');

        $summary = DocumentRenewalRequestService::scan(
            types: $requestedTypes !== [] ? $requestedTypes : null,
            dryRun: $dryRun,
            sinceDate: is_string($since) && trim($since) !== '' ? trim($since) : null,
        );

        $modeLabel = $dryRun ? 'DRY-RUN' : 'LIVE';
        $this->info("[{$modeLabel}] Document renewals scan completed (as of {$summary['as_of']}, lead days: {$summary['lead_days']}).");
        $this->line("Due: {$summary['due_count']} | Existing: {$summary['existing_count']} | "
            .($dryRun ? "Would create: {$summary['created_count']}" : "Created: {$summary['created_count']}")
            ." | Unassignable: {$summary['unassignable_count']} | Excluded historical: {$summary['excluded_historical_count']} | Invalid date: {$summary['missing_or_invalid_date_count']}");

        return self::SUCCESS;
    }
}
