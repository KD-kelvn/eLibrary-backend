<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('auth.system_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('menu_item_id');
            $table->index(['role_id', 'is_active']);
        });

        // Own each action by its first linked role (legacy many-to-many → single owner).
        $links = DB::table('auth.system_action_roles')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['system_action_id', 'role_id']);

        $assigned = [];
        foreach ($links as $link) {
            if (isset($assigned[$link->system_action_id])) {
                continue;
            }
            $assigned[$link->system_action_id] = $link->role_id;
            DB::table('auth.system_actions')
                ->where('id', $link->system_action_id)
                ->update(['role_id' => $link->role_id]);
        }

        Schema::table('auth.system_actions', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')
                ->on('auth.roles')
                ->restrictOnDelete();
        });

        Schema::create('auth.user_system_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('system_action_id');
            $table->unsignedBigInteger('user_role_id')->nullable();
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('auth.users')->cascadeOnDelete();
            $table->foreign('system_action_id')
                ->references('id')
                ->on('auth.system_actions')
                ->cascadeOnDelete();
            $table->foreign('user_role_id')
                ->references('id')
                ->on('auth.user_roles')
                ->nullOnDelete();
            $table->unique(['user_id', 'system_action_id']);
            $table->index(['user_id', 'system_action_id']);
        });

        // Preserve previous behaviour: every active role assignment receives all of that role's actions.
        $activeAssignments = DB::table('auth.user_roles as ur')
            ->leftJoin('auth.revoked_roles as rr', 'rr.user_role_id', '=', 'ur.id')
            ->whereNull('ur.deleted_at')
            ->whereNull('rr.id')
            ->where(function ($query) {
                $query->whereNull('ur.expires_at')
                    ->orWhereDate('ur.expires_at', '>=', now()->toDateString());
            })
            ->get(['ur.id', 'ur.user_id', 'ur.role_id']);

        $now = now();
        foreach ($activeAssignments as $assignment) {
            $actionIds = DB::table('auth.system_actions')
                ->where('role_id', $assignment->role_id)
                ->whereNull('deleted_at')
                ->pluck('id');

            foreach ($actionIds as $actionId) {
                $exists = DB::table('auth.user_system_actions')
                    ->where('user_id', $assignment->user_id)
                    ->where('system_action_id', $actionId)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('auth.user_system_actions')->insert([
                    'user_id' => $assignment->user_id,
                    'system_action_id' => $actionId,
                    'user_role_id' => $assignment->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auth.user_system_actions');

        Schema::table('auth.system_actions', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id', 'is_active']);
            $table->dropColumn('role_id');
        });
    }
};
