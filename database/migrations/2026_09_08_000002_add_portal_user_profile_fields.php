<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_users', function (Blueprint $table) {
            if (! Schema::hasColumn('portal_users', 'profile_photo_path')) {
                $table->string('profile_photo_path', 255)->nullable()->after('address');
            }
            if (! Schema::hasColumn('portal_users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('portal_users', function (Blueprint $table) {
            if (Schema::hasColumn('portal_users', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
            if (Schema::hasColumn('portal_users', 'password_changed_at')) {
                $table->dropColumn('password_changed_at');
            }
        });
    }
};
