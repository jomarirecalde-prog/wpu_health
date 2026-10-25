<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admins')) {
            Schema::table('admins', function (Blueprint $table) {
                if (! Schema::hasColumn('admins', 'email')) {
                    $table->string('email', 191)->nullable()->after('username');
                    $table->index('email');
                }
            });
        }

        if (Schema::hasTable('patient_records')) {
            Schema::table('patient_records', function (Blueprint $table) {
                $columns = [
                    'transaction_type' => fn () => $table->string('transaction_type', 64)->nullable()->after('visit_date'),
                    'case_category' => fn () => $table->string('case_category', 64)->nullable()->after('case_type_id'),
                    'case_subcategory' => fn () => $table->string('case_subcategory', 128)->nullable()->after('case_category'),
                    'case_other_specify' => fn () => $table->string('case_other_specify', 255)->nullable()->after('case_subcategory'),
                    'organizational_unit_id' => fn () => $table->unsignedBigInteger('organizational_unit_id')->nullable()->after('department_id'),
                    'dependent_of_type' => fn () => $table->string('dependent_of_type', 32)->nullable()->after('organizational_unit_id'),
                    'dependent_of_patient_id' => fn () => $table->unsignedBigInteger('dependent_of_patient_id')->nullable()->after('dependent_of_type'),
                    'agency_affiliation' => fn () => $table->string('agency_affiliation', 255)->nullable()->after('dependent_of_patient_id'),
                    'chief_complaint' => fn () => $table->text('chief_complaint')->nullable()->after('doctor'),
                    'remarks' => fn () => $table->text('remarks')->nullable()->after('chief_complaint'),
                    'examination_data' => fn () => $table->json('examination_data')->nullable()->after('remarks'),
                    'consent_record_id' => fn () => $table->unsignedBigInteger('consent_record_id')->nullable()->after('examination_data'),
                ];

                foreach ($columns as $name => $adder) {
                    if (! Schema::hasColumn('patient_records', $name)) {
                        $adder();
                    }
                }
            });
        }

        if (! Schema::hasTable('organizational_units')) {
            Schema::create('organizational_units', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->enum('unit_kind', ['college', 'unit', 'entity'])->default('college');
                $table->timestamps();
                $table->unique(['name', 'unit_kind']);
            });
        }

        if (! Schema::hasTable('healthhub_consents')) {
            Schema::create('healthhub_consents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_record_id')->nullable();
                $table->string('patient_name', 255);
                $table->unsignedTinyInteger('patient_age')->nullable();
                $table->enum('consent_pathway', ['adult', 'minor_guardian']);
                $table->string('guardian_name', 255)->nullable();
                $table->string('purpose', 500)->nullable();
                $table->longText('consent_body')->nullable();
                $table->string('signed_by_name', 255)->nullable();
                $table->string('signed_capacity', 64)->nullable();
                $table->longText('signature_data')->nullable();
                $table->string('consent_version', 32)->default('1.0');
                $table->timestamp('signed_at')->nullable();
                $table->string('created_by', 64)->nullable();
                $table->timestamps();
                $table->index(['patient_record_id', 'signed_at']);
            });
        }

        if (! Schema::hasTable('healthhub_nurse_notes')) {
            Schema::create('healthhub_nurse_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_record_id');
                $table->date('assisted_date')->nullable();
                $table->text('assessment_management')->nullable();
                $table->string('author_username', 64)->nullable();
                $table->timestamps();
                $table->index('patient_record_id');
            });
        }

        if (! Schema::hasTable('healthhub_nurse_note_revisions')) {
            Schema::create('healthhub_nurse_note_revisions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('nurse_note_id');
                $table->text('snapshot');
                $table->string('edited_by', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('nurse_note_id');
            });
        }

        if (! Schema::hasTable('dispensary_items')) {
            Schema::create('dispensary_items', function (Blueprint $table) {
                $table->id();
                $table->string('item_name', 255);
                $table->string('category', 100)->nullable();
                $table->string('unit_of_measure', 32)->default('pcs');
                $table->decimal('quantity_on_hand', 12, 2)->default(0);
                $table->string('batch_lot', 64)->nullable();
                $table->date('expiration_date')->nullable();
                $table->string('supplier', 255)->nullable();
                $table->decimal('reorder_threshold', 12, 2)->default(0);
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->index(['category', 'item_name']);
            });
        }

        if (! Schema::hasTable('dispensary_stock_movements')) {
            Schema::create('dispensary_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('item_id');
                $table->enum('movement_type', ['receive', 'dispense', 'adjustment', 'expired']);
                $table->decimal('quantity_delta', 12, 2);
                $table->decimal('quantity_after', 12, 2);
                $table->unsignedBigInteger('patient_record_id')->nullable();
                $table->string('performed_by', 64)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['item_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('interagency_referrals')) {
            Schema::create('interagency_referrals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_record_id')->nullable();
                $table->string('patient_name', 255);
                $table->string('receiving_unit', 150);
                $table->text('reason')->nullable();
                $table->string('status', 32)->default('pending');
                $table->text('action_taken')->nullable();
                $table->text('outcome')->nullable();
                $table->string('created_by', 64)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('external_referrals')) {
            Schema::create('external_referrals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_record_id')->nullable();
                $table->string('patient_name', 255);
                $table->string('receiving_agency', 150);
                $table->date('referral_date')->nullable();
                $table->text('reason')->nullable();
                $table->text('clinical_summary')->nullable();
                $table->string('status', 32)->default('pending');
                $table->text('follow_up_notes')->nullable();
                $table->text('outcome')->nullable();
                $table->string('created_by', 64)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('healthhub_consent_refusals')) {
            Schema::create('healthhub_consent_refusals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('patient_record_id')->nullable();
                $table->string('patient_name', 255);
                $table->string('declined_procedure', 500)->nullable();
                $table->longText('refusal_body')->nullable();
                $table->longText('signature_data')->nullable();
                $table->string('signed_by_name', 255)->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->string('recorded_by', 64)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('medical_certificate_sequences')) {
            Schema::create('medical_certificate_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('purpose_group', 64);
                $table->unsignedSmallInteger('year');
                $table->unsignedInteger('last_number')->default(0);
                $table->unique(['purpose_group', 'year']);
            });
        }

        if (Schema::hasTable('patient_types')) {
            DB::table('patient_types')->where('type_name', 'Outsider')->update(['type_name' => 'Private Individual']);
            if (! DB::table('patient_types')->where('type_name', 'Dependent')->exists()) {
                DB::table('patient_types')->insert([
                    'type_name' => 'Dependent',
                    'color_code' => '#8E24AA',
                    'created_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('organizational_units') && DB::table('organizational_units')->count() === 0) {
            $colleges = ['Computer Science', 'Engineering', 'Agriculture', 'Fisheries', 'CAS', 'Education', 'ASILS', 'BELS'];
            foreach ($colleges as $name) {
                DB::table('organizational_units')->insert([
                    'name' => $name,
                    'unit_kind' => in_array($name, ['ASILS', 'BELS'], true) ? 'entity' : 'college',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $units = ['HRMO', 'Guidance Unit', 'Admission', 'Health Services Unit'];
            foreach ($units as $name) {
                DB::table('organizational_units')->insert([
                    'name' => $name,
                    'unit_kind' => 'unit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('departments')) {
            foreach (['ASILS', 'BELS'] as $dept) {
                if (! DB::table('departments')->where('name', $dept)->exists()) {
                    DB::table('departments')->insert(['name' => $dept, 'created_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_certificate_sequences');
        Schema::dropIfExists('healthhub_consent_refusals');
        Schema::dropIfExists('external_referrals');
        Schema::dropIfExists('interagency_referrals');
        Schema::dropIfExists('dispensary_stock_movements');
        Schema::dropIfExists('dispensary_items');
        Schema::dropIfExists('healthhub_nurse_note_revisions');
        Schema::dropIfExists('healthhub_nurse_notes');
        Schema::dropIfExists('healthhub_consents');

        Schema::table('patient_records', function (Blueprint $table) {
            foreach ([
                'transaction_type', 'case_category', 'case_subcategory', 'case_other_specify',
                'organizational_unit_id', 'dependent_of_type', 'dependent_of_patient_id',
                'agency_affiliation', 'chief_complaint', 'remarks', 'examination_data', 'consent_record_id',
            ] as $col) {
                if (Schema::hasColumn('patient_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('organizational_units');

        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'email')) {
                $table->dropIndex(['email']);
                $table->dropColumn('email');
            }
        });
    }
};
