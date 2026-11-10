<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL requires this unique key for the tenant-aware customer FK.
        Schema::table('customers', function (Blueprint $table): void {
            $table->unique(['workshop_id', 'id'], 'customers_workshop_id_id_unique');
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('plate_number', 20);
            $table->string('normalized_plate_number', 20);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->smallInteger('year')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['workshop_id', 'normalized_plate_number']);
            $table->index(['workshop_id', 'customer_id']);
            $table->foreign(['workshop_id', 'customer_id'])->references(['workshop_id', 'id'])
                ->on('customers')->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT vehicles_plate_format CHECK (plate_number ~ '^[A-Z0-9]+( [A-Z0-9]+)*$' AND plate_number ~ '[A-Z]' AND plate_number ~ '[0-9]')");
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT vehicles_normalized_plate_matches CHECK (normalized_plate_number = replace(plate_number, ' ', ''))");
        DB::statement('ALTER TABLE vehicles ADD CONSTRAINT vehicles_year_range CHECK (year BETWEEN 1900 AND 2100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_workshop_id_id_unique');
        });
    }
};
