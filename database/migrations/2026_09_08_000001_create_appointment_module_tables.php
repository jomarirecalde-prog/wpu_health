<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('contact_number', 50)->nullable();
            $table->unsignedBigInteger('patient_type_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('employee_student_id', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('status');
            $table->index('employee_student_id');
        });

        Schema::create('physicians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_signature_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('professional_title')->nullable();
            $table->string('specialization')->nullable();
            $table->string('license_no', 100)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('consultation_location')->nullable();
            $table->unsignedSmallInteger('consultation_duration')->default(30);
            $table->unsignedSmallInteger('max_daily_appointments')->default(20);
            $table->json('consultation_types')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->rememberToken();
            $table->timestamps();

            $table->index('status');
            $table->index('department_id');
        });

        Schema::create('physician_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physician_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday .. 6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(false);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['physician_id', 'day_of_week']);
        });

        Schema::create('physician_schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physician_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('type', [
                'unavailable',
                'blocked',
                'leave',
                'meeting',
                'holiday',
                'special_schedule',
            ]);
            $table->string('reason')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['physician_id', 'date']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number', 50)->unique();
            $table->foreignId('portal_user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('patient_record_id')->nullable();
            $table->foreignId('physician_id')->constrained()->restrictOnDelete();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('consultation_type', 100)->default('general');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', [
                'pending',
                'confirmed',
                'rejected',
                'cancelled',
                'rescheduled',
                'completed',
                'no_show',
            ])->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->string('created_by')->nullable();
            $table->string('confirmed_by')->nullable();
            $table->string('completed_by')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('appointment_date');
            $table->index('physician_id');
            $table->index('portal_user_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['physician_id', 'appointment_date', 'status']);
            $table->index(['appointment_date', 'status']);
        });

        Schema::create('appointment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('action', 50);
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->date('old_date')->nullable();
            $table->date('new_date')->nullable();
            $table->time('old_start_time')->nullable();
            $table->time('new_start_time')->nullable();
            $table->text('reason')->nullable();
            $table->string('changed_by')->nullable();
            $table->string('changed_by_type', 30)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index('appointment_id');
            $table->index('changed_at');
        });

        Schema::create('appointment_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type', 50);
            $table->unsignedBigInteger('notifiable_id');
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 80);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('read_at');
        });

        Schema::create('appointment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_settings');
        Schema::dropIfExists('appointment_notifications');
        Schema::dropIfExists('appointment_history');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('physician_schedule_exceptions');
        Schema::dropIfExists('physician_schedules');
        Schema::dropIfExists('physicians');
        Schema::dropIfExists('portal_users');
    }
};
