<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('needs_password_setup')->default(false);
        });
        Schema::table('workshop_user', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('workshop_user', fn (Blueprint $table) => $table->dropColumn(['is_active', 'version']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('needs_password_setup'));
    }
};
