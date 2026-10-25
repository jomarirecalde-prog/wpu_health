<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('healthhub_consent_refusals')) {
            Schema::table('healthhub_consent_refusals', function (Blueprint $table) {
                $stringCols = [
                    'refusal_status' => 32,
                    'patient_identifier' => 64,
                    'patient_type_label' => 128,
                    'named_patient' => 255,
                    'representative_relationship' => 128,
                    'signed_capacity' => 32,
                    'interpreter_name' => 255,
                    'witness_name' => 255,
                    'advising_physician' => 255,
                    'physician_printed_name' => 255,
                    'physician_certified_by' => 64,
                    'idempotency_key' => 64,
                    'withdrawn_by' => 64,
                    'refusal_pathway' => 32,
                    'guardian_name' => 255,
                    'created_by' => 64,
                ];

                foreach ($stringCols as $col => $len) {
                    if (! Schema::hasColumn('healthhub_consent_refusals', $col)) {
                        $table->string($col, $len)->nullable();
                    }
                }

                if (! Schema::hasColumn('healthhub_consent_refusals', 'patient_age')) {
                    $table->unsignedTinyInteger('patient_age')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'date_of_birth')) {
                    $table->date('date_of_birth')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'signature_storage_path')) {
                    $table->string('signature_storage_path', 512)->nullable()->after('signature_data');
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'interpreter_signature_path')) {
                    $table->string('interpreter_signature_path', 512)->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'interpreter_signed_at')) {
                    $table->timestamp('interpreter_signed_at')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'witness_signature_path')) {
                    $table->string('witness_signature_path', 512)->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'witness_signed_at')) {
                    $table->timestamp('witness_signed_at')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'physician_signature_path')) {
                    $table->string('physician_signature_path', 512)->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'physician_certified_at')) {
                    $table->timestamp('physician_certified_at')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'superseded_by_id')) {
                    $table->unsignedBigInteger('superseded_by_id')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'superseded_from_id')) {
                    $table->unsignedBigInteger('superseded_from_id')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'withdrawn_at')) {
                    $table->timestamp('withdrawn_at')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'withdrawal_reason')) {
                    $table->text('withdrawal_reason')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'form_reference')) {
                    $table->string('form_reference', 64)->nullable();
                }
                if (! Schema::hasColumn('healthhub_consent_refusals', 'refusal_version')) {
                    $table->string('refusal_version', 64)->nullable();
                }
            });
        }

        if (! Schema::hasTable('healthhub_refusal_audit_log')) {
            Schema::create('healthhub_refusal_audit_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('refusal_id');
                $table->string('action', 64);
                $table->string('actor', 64)->nullable();
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['refusal_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('healthhub_refusal_versions')) {
            Schema::create('healthhub_refusal_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('refusal_id');
                $table->string('version_kind', 32);
                $table->longText('snapshot_json');
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('replaced_by_refusal_id')->nullable();
                $table->string('created_by', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['refusal_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('healthhub_refusal_versions');
        Schema::dropIfExists('healthhub_refusal_audit_log');

        if (Schema::hasTable('healthhub_consent_refusals')) {
            Schema::table('healthhub_consent_refusals', function (Blueprint $table) {
                foreach ([
                    'refusal_status', 'patient_identifier', 'patient_type_label', 'patient_age', 'date_of_birth',
                    'advising_physician', 'named_patient', 'representative_relationship', 'signed_capacity', 'interpreter_name',
                    'interpreter_signature_path', 'interpreter_signed_at', 'witness_name',
                    'witness_signature_path', 'witness_signed_at', 'signature_storage_path',
                    'physician_printed_name', 'physician_signature_path', 'physician_certified_at',
                    'physician_certified_by', 'idempotency_key', 'superseded_by_id', 'superseded_from_id',
                    'withdrawn_at', 'withdrawn_by', 'withdrawal_reason', 'form_reference', 'refusal_version',
                    'refusal_pathway', 'guardian_name', 'created_by',
                ] as $col) {
                    if (Schema::hasColumn('healthhub_consent_refusals', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
