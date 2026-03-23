<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_users', function (Blueprint $table) {
            $table->string('portal_mode', 32)->default('individual')->after('role');
            $table->string('group_type', 32)->nullable()->after('portal_mode');
            $table->boolean('has_individual_access')->default(false)->after('group_type');
            $table->boolean('has_group_access')->default(false)->after('has_individual_access');
        });

        DB::table('portal_users')
            ->where('role', 'individual')
            ->update([
                'portal_mode' => 'individual',
                'group_type' => null,
                'has_individual_access' => true,
                'has_group_access' => false,
            ]);

        DB::table('portal_users')
            ->where('role', 'corporate')
            ->update([
                'portal_mode' => 'group',
                'group_type' => 'corporate',
                'has_individual_access' => false,
                'has_group_access' => true,
            ]);

        DB::table('portal_users')
            ->where('role', 'wholesale')
            ->update([
                'portal_mode' => 'group',
                'group_type' => 'wholesale',
                'has_individual_access' => false,
                'has_group_access' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('portal_users', function (Blueprint $table) {
            $table->dropColumn([
                'portal_mode',
                'group_type',
                'has_individual_access',
                'has_group_access',
            ]);
        });
    }
};
