<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('healthhub_consents')) {
            Schema::table('healthhub_consents', function (Blueprint $table) {
                $stringCols = [
                    'consent_status' => 32,
                    'patient_identifier' => 64,
                    'patient_type_label' => 128,
                    'named_patient' => 255,
                    'representative_relationship' => 128,
                    'interpreter_name' => 255,
                    'witness_name' => 255,
                    'physician_printed_name' => 255,
                    'physician_certified_by' => 64,
                    'idempotency_key' => 64,
                    'withdrawn_by' => 64,
                ];

                foreach ($stringCols as $col => $len) {
                    if (! Schema::hasColumn('healthhub_consents', $col)) {
                        $table->string($col, $len)->nullable();
                    }
                }

                if (! Schema::hasColumn('healthhub_consents', 'date_of_birth')) {
                    $table->date('date_of_birth')->nullable()->after('patient_age');
                }
                if (! Schema::hasColumn('healthhub_consents', 'interpreter_signature_path')) {
                    $table->string('interpreter_signature_path', 512)->nullable();
                }
                if (! Schema::hasColumn('healthhub_consents', 'interpreter_signed_at')) {
                    $table->timestamp('interpreter_signed_at')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consents', 'witness_signature_path')) {
                    $table->string('witness_signature_path', 512)->nullable();
                }
                if (! Schema::hasColumn('healthhub_consents', 'witness_signed_at')) {
                    $table->timestamp('witness_signed_at')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consents', 'signature_storage_path')) {
                    $table->string('signature_storage_path', 512)->nullable()->after('signature_data');
                }
                if (! Schema::hasColumn('healthhub_consents', 'physician_signature_path')) {
                    $table->string('physician_signature_path', 512)->nullable();
                }
                if (! Schema::hasColumn('healthhub_consents', 'physician_certified_at')) {
                    $table->timestamp('physician_certified_at')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consents', 'superseded_by_id')) {
                    $table->unsignedBigInteger('superseded_by_id')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consents', 'withdrawn_at')) {
                    $table->timestamp('withdrawn_at')->nullable();
                }
                if (! Schema::hasColumn('healthhub_consents', 'form_reference')) {
                    $table->string('form_reference', 64)->nullable();
                }
            });
        }

        if (! Schema::hasTable('healthhub_consent_audit_log')) {
            Schema::create('healthhub_consent_audit_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('consent_id');
                $table->string('action', 64);
                $table->string('actor', 64)->nullable();
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['consent_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('healthhub_consent_audit_log');

        if (Schema::hasTable('healthhub_consents')) {
            Schema::table('healthhub_consents', function (Blueprint $table) {
                foreach ([
                    'consent_status', 'patient_identifier', 'patient_type_label', 'date_of_birth',
                    'named_patient', 'representative_relationship', 'interpreter_name',
                    'interpreter_signature_path', 'interpreter_signed_at', 'witness_name',
                    'witness_signature_path', 'witness_signed_at', 'signature_storage_path',
                    'physician_printed_name', 'physician_signature_path', 'physician_certified_at',
                    'physician_certified_by', 'idempotency_key', 'superseded_by_id',
                    'withdrawn_at', 'withdrawn_by', 'form_reference',
                ] as $col) {
                    if (Schema::hasColumn('healthhub_consents', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
