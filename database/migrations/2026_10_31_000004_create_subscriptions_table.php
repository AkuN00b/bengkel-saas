<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['trialing', 'active', 'expired', 'cancelled']);
            $table->timestampTz('starts_at');
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->timestampsTz();
            $table->index(['workshop_id', 'status']);
            $table->index('trial_ends_at');
        });
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_trial_end_required CHECK (status <> 'trialing' OR trial_ends_at IS NOT NULL)");
        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_trial_order CHECK (trial_ends_at > starts_at)');
        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_end_order CHECK (ends_at >= starts_at)');
        // Keep history, while preventing concurrent current subscriptions per tenant.
        DB::statement("CREATE UNIQUE INDEX subscriptions_one_current_per_workshop ON subscriptions (workshop_id) WHERE status IN ('trialing', 'active')");
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
