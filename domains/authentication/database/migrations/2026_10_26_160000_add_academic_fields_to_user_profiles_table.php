<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = $this->tableName();

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->unsignedBigInteger('faculty_id')->nullable()->after('profile_picture');
            $blueprint->unsignedBigInteger('department_id')->nullable()->after('faculty_id');
            $blueprint->unsignedBigInteger('programme_id')->nullable()->after('department_id');
            $blueprint->unsignedTinyInteger('year_of_study')->nullable()->after('programme_id');
            $blueprint->string('photo_path')->nullable()->after('year_of_study');
            $blueprint->string('photo_thumb_path')->nullable()->after('photo_path');
            $blueprint->string('source', 20)->default('manual')->after('photo_thumb_path');
            $blueprint->timestampTz('core_synced_at')->nullable()->after('source');
        });

        if (Schema::hasColumn($table, 'profile_picture')) {
            DB::table($table)
                ->whereNotNull('profile_picture')
                ->where('profile_picture', '!=', '')
                ->whereNull('photo_path')
                ->update([
                    'photo_path' => DB::raw('profile_picture'),
                ]);
        }

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS user_profiles_faculty_id_index ON '.$table.' (faculty_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS user_profiles_department_id_index ON '.$table.' (department_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS user_profiles_programme_id_index ON '.$table.' (programme_id)');
        } else {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->index('faculty_id');
                $blueprint->index('department_id');
                $blueprint->index('programme_id');
            });
        }
    }

    public function down(): void
    {
        $table = $this->tableName();

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropIndex(['faculty_id']);
            $blueprint->dropIndex(['department_id']);
            $blueprint->dropIndex(['programme_id']);
            $blueprint->dropColumn([
                'faculty_id',
                'department_id',
                'programme_id',
                'year_of_study',
                'photo_path',
                'photo_thumb_path',
                'source',
                'core_synced_at',
            ]);
        });
    }

    protected function tableName(): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? 'auth.user_profiles'
            : 'user_profiles';
    }
};
