<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_event') && ! Schema::hasTable('event_stands')) {
            Schema::rename('company_event', 'event_stands');
        }

        $this->dropForeignKeyIfExists('event_stands', 'company_id');
        $this->dropForeignKeyIfExists('event_stands', 'partner_id');

        Schema::table('event_stands', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('partner_id')->nullable()->change();
        });

        Schema::table('event_stands', function (Blueprint $table) {
            $table->foreign('company_id', 'event_stands_company_id_foreign')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();

            $table->foreign('partner_id', 'event_stands_partner_id_foreign')
                ->references('id')
                ->on('partners')
                ->nullOnDelete();
        });

        if (! $this->indexExists('event_stands', 'event_stands_event_type_stand_number_unique')) {
            Schema::table('event_stands', function (Blueprint $table) {
                $table->unique(
                    ['event_id', 'type', 'stand_number'],
                    'event_stands_event_type_stand_number_unique'
                );
            });
        }

        if (Schema::hasTable('events') && ! Schema::hasColumn('events', 'partner_stand_count')) {
            Schema::table('events', function (Blueprint $table) {
                $table->unsignedInteger('partner_stand_count')
                    ->default(0)
                    ->after('max_stands');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'partner_stand_count')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('partner_stand_count');
            });
        }

        if ($this->indexExists('event_stands', 'event_stands_event_type_stand_number_unique')) {
            Schema::table('event_stands', function (Blueprint $table) {
                $table->dropUnique('event_stands_event_type_stand_number_unique');
            });
        }

        $this->dropForeignKeyIfExists('event_stands', 'company_id');
        $this->dropForeignKeyIfExists('event_stands', 'partner_id');

        Schema::table('event_stands', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->unsignedBigInteger('partner_id')->nullable()->change();
        });

        Schema::table('event_stands', function (Blueprint $table) {
            $table->foreign('company_id', 'event_stands_company_id_foreign')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign('partner_id', 'event_stands_partner_id_foreign')
                ->references('id')
                ->on('partners')
                ->nullOnDelete();
        });

        if (Schema::hasTable('event_stands') && ! Schema::hasTable('company_event')) {
            Schema::rename('event_stands', 'company_event');
        }
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $databaseName = DB::getDatabaseName();
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraint));
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $databaseName = DB::getDatabaseName();

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
