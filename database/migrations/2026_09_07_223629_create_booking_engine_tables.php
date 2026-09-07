<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->unsignedSmallInteger('slot_duration_minutes')->default(30);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_amount')->default(0);
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('barber_service', function (Blueprint $table) {
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['barber_id', 'service_id']);
        });

        Schema::create('barber_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['barber_id', 'weekday', 'is_active']);
            $table->unique(['barber_id', 'weekday', 'starts_at', 'ends_at'], 'barber_schedule_window_unique');
        });

        Schema::create('barber_time_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['barber_id', 'starts_at', 'ends_at'], 'barber_time_off_window_index');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('mobile', 20)->unique();
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->char('reference', 26)->unique();
            $table->foreignId('barber_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 32)->index();
            $table->string('payment_status', 32)->index();
            $table->unsignedBigInteger('base_price_amount');
            $table->unsignedBigInteger('services_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->unsignedTinyInteger('deposit_percentage');
            $table->unsignedBigInteger('deposit_amount');
            $table->char('currency', 3);
            $table->dateTime('expires_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['barber_id', 'starts_at', 'ends_at'], 'reservation_barber_window_index');
        });

        Schema::create('reservation_service', function (Blueprint $table) {
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('name_snapshot');
            $table->unsignedBigInteger('price_amount');
            $table->unsignedSmallInteger('duration_minutes');
            $table->timestamps();
            $table->unique(['reservation_id', 'service_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 64)->nullable();
            $table->string('authority', 128)->nullable();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('status', 32)->index();
            $table->string('transaction_id', 128)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'authority'], 'payment_provider_authority_unique');
        });

        Schema::create('slot_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->dateTime('slot_start');
            $table->dateTime('expires_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['barber_id', 'slot_start'], 'slot_claim_barber_start_unique');
            $table->index(['reservation_id', 'slot_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slot_claims');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservation_service');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('barber_time_offs');
        Schema::dropIfExists('barber_schedules');
        Schema::dropIfExists('barber_service');
        Schema::dropIfExists('services');
        Schema::dropIfExists('barbers');
    }
};
