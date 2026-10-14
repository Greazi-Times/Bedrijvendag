<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the request and security metadata the audit log filters on, copies the
 * entries of the earlier audit_logs table and creates the alerts table.
 * audit_logs itself is left in place; drop it once the copy is verified.
 */
return new class extends Migration
{
    /** Marks the copied entries, so a rollback removes exactly those. */
    private const LEGACY_BATCH = '00000000-0000-4000-8000-00000000a0d1';

    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('severity', 16)->default('info')->after('event');
            $table->string('result', 16)->default('success')->after('severity');
            $table->string('actor_type', 16)->default('system')->after('causer_id');
            // The actor's name at the time, so entries stay readable after the account is deleted.
            $table->string('actor_label')->nullable()->after('actor_type');
            $table->string('subject_label')->nullable()->after('subject_id');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('route_name')->nullable();
            $table->string('path')->nullable();
            $table->uuid('request_id')->nullable();

            $table->index('created_at');
            $table->index(['event', 'created_at']);
            $table->index(['severity', 'created_at']);
            $table->index('ip_address');
            $table->index('request_id');
        });

        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('rule');
            $table->string('group_key');
            $table->string('severity', 16);
            $table->unsignedInteger('count')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rule', 'group_key', 'acknowledged_at']);
        });

        $this->copyLegacyAuditLogs();
    }

    public function down(): void
    {
        DB::table('activity_log')->where('batch_uuid', self::LEGACY_BATCH)->delete();
        Schema::dropIfExists('security_alerts');

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['event', 'created_at']);
            $table->dropIndex(['severity', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['ip_address']);
            $table->dropIndex(['request_id']);
            $table->dropColumn([
                'severity', 'result', 'actor_type', 'actor_label', 'subject_label', 'ip_address',
                'user_agent', 'http_method', 'route_name', 'path', 'request_id',
            ]);
        });
    }

    private function copyLegacyAuditLogs(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $names = DB::table('users')->pluck('name', 'id');
        $renamed = ['user.' => 'users.', 'role.' => 'roles.'];

        DB::table('audit_logs')->orderBy('id')->chunk(500, function ($rows) use ($names, $renamed): void {
            DB::table('activity_log')->insert($rows->map(function ($row) use ($names, $renamed): array {
                $event = strtr($row->action, $renamed);

                return [
                    'log_name' => str($event)->before('.')->toString(),
                    'description' => $event,
                    'event' => $event,
                    'severity' => str_starts_with($event, 'roles.') ? 'warning' : 'info',
                    'result' => 'success',
                    'subject_type' => $row->subject_type,
                    'subject_id' => $row->subject_id,
                    'subject_label' => $row->subject_label,
                    'causer_type' => $row->user_id ? 'App\\Models\\User' : null,
                    'causer_id' => $row->user_id,
                    'actor_type' => $row->user_id ? 'user' : 'system',
                    'actor_label' => $row->user_id ? $names[$row->user_id] ?? null : null,
                    'properties' => $row->changes ? json_encode(['legacy' => json_decode($row->changes, true)]) : null,
                    'batch_uuid' => self::LEGACY_BATCH,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->created_at,
                ];
            })->all());
        });
    }
};
