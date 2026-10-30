<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Nav is frontend-owned. Drop DB menu tables and the unused system_actions.menu_item_id FK. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('auth.system_actions', 'menu_item_id')) {
            $this->dropMenuItemForeignKey();

            Schema::table('auth.system_actions', function (Blueprint $table) {
                $table->dropColumn('menu_item_id');
            });
        }

        Schema::dropIfExists('auth.menu_item_roles');
        Schema::dropIfExists('auth.menu_roles');
        Schema::dropIfExists('auth.menu_items');
        Schema::dropIfExists('auth.menus');
    }

    public function down(): void
    {
        Schema::create('auth.menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->string('icon_code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('auth.menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('system_module_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->string('route');
            $table->string('icon_code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('menu_id')->references('id')->on('auth.menus')->cascadeOnDelete();
            $table->index(['menu_id', 'is_active', 'sort_order']);
        });

        Schema::create('auth.menu_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('role_id');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('menu_id')->references('id')->on('auth.menus')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('auth.roles')->cascadeOnDelete();
            $table->index(['menu_id', 'role_id']);
        });

        Schema::create('auth.menu_item_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id');
            $table->unsignedBigInteger('role_id');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('menu_item_id')->references('id')->on('auth.menu_items')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('auth.roles')->cascadeOnDelete();
            $table->index(['menu_item_id', 'role_id']);
        });

        Schema::table('auth.system_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_item_id')->nullable()->after('id');
            $table->foreign('menu_item_id')->references('id')->on('auth.menu_items')->nullOnDelete();
            $table->index(['menu_item_id', 'is_active']);
        });
    }

    private function dropMenuItemForeignKey(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $constraints = DB::select("
                SELECT conname
                FROM pg_constraint
                WHERE conrelid = 'auth.system_actions'::regclass
                  AND contype = 'f'
                  AND pg_get_constraintdef(oid) ILIKE '%menu_item_id%'
            ");

            foreach ($constraints as $constraint) {
                DB::statement(sprintf(
                    'ALTER TABLE auth.system_actions DROP CONSTRAINT IF EXISTS %s',
                    $constraint->conname,
                ));
            }

            DB::statement('DROP INDEX IF EXISTS auth.auth_system_actions_menu_item_id_is_active_index');
            DB::statement('DROP INDEX IF EXISTS auth_system_actions_menu_item_id_is_active_index');

            return;
        }

        Schema::table('auth.system_actions', function (Blueprint $table) {
            $table->dropForeign(['menu_item_id']);
        });
    }
};
