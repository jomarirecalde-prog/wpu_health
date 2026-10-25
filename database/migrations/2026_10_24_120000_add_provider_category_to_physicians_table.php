<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('physicians', function (Blueprint $table) {
            if (! Schema::hasColumn('physicians', 'provider_category')) {
                $table->enum('provider_category', ['physician', 'dentist', 'others'])
                    ->default('physician')
                    ->after('status');
                $table->index('provider_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('physicians', function (Blueprint $table) {
            if (Schema::hasColumn('physicians', 'provider_category')) {
                $table->dropIndex(['provider_category']);
                $table->dropColumn('provider_category');
            }
        });
    }
};
