<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Resolve schema-qualified table names for pgsql, bare names for sqlite tests.
 */
final class QualifiedTable
{
    public static function name(string $schema, string $table): string
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? $schema.'.'.$table
            : $table;
    }
}
