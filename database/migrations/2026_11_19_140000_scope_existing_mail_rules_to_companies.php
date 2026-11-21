<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['trusted_emails' => 'email', 'trusted_domains' => 'domain_pattern', 'importance_keywords' => 'keyword', 'exclusion_keywords' => 'keyword'] as $table => $column) {
            foreach (Schema::getIndexes($table) as $index) {
                if ($index['unique'] && $index['columns'] === [$column]) {
                    Schema::table($table, function (Blueprint $blueprint) use ($index, $column): void {
                        $blueprint->dropUnique($index['name']);
                        $blueprint->unique(['company_id', $column]);
                    });
                }
            }

            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if ($foreignKey['columns'] === ['company_id'] && strtolower($foreignKey['on_delete']) !== 'cascade') {
                    Schema::table($table, function (Blueprint $blueprint): void {
                        $blueprint->dropForeign(['company_id']);
                    });
                    Schema::table($table, function (Blueprint $blueprint): void {
                        $blueprint->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                    });
                }
            }
        }
    }

    public function down(): void
    {
        // Tenant-scoped rules can contain the same value in several companies.
        // Keep these constraints during rollback to preserve those records.
    }
};
