<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workshop_id', 'name', 'id']);
        });

        DB::statement('ALTER TABLE customers ADD CONSTRAINT customers_name_not_blank CHECK (char_length(btrim(name)) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
