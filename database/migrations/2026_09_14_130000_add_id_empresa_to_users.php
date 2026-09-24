<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'id_empresa')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('id_empresa')->nullable();
                $table->index('id_empresa', 'users_id_empresa_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'id_empresa')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_id_empresa_idx');
                $table->dropColumn('id_empresa');
            });
        }
    }
};
