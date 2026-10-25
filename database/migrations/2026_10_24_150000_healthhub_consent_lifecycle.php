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
                if (! Schema::hasColumn('healthhub_consents', 'superseded_from_id')) {
                    $table->unsignedBigInteger('superseded_from_id')->nullable()->after('superseded_by_id');
                }
                if (! Schema::hasColumn('healthhub_consents', 'withdrawal_reason')) {
                    $table->text('withdrawal_reason')->nullable()->after('withdrawn_by');
                }
            });
        }

        if (! Schema::hasTable('healthhub_consent_versions')) {
            Schema::create('healthhub_consent_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('consent_id');
                $table->string('version_kind', 32);
                $table->longText('snapshot_json');
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('replaced_by_consent_id')->nullable();
                $table->string('created_by', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['consent_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('healthhub_consent_versions');

        if (Schema::hasTable('healthhub_consents')) {
            Schema::table('healthhub_consents', function (Blueprint $table) {
                foreach (['superseded_from_id', 'withdrawal_reason'] as $col) {
                    if (Schema::hasColumn('healthhub_consents', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
