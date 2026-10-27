<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Qualify Postgres schema.table names for Laravel unique / exists rules.
 *
 * Laravel's validator parses "a.b" as connection.table. This app uses Postgres
 * schemas (auth, book_catalog, book_borrowing, settings), so unqualified
 * "book_catalog.tags" wrongly looks up a connection named book_catalog.
 *
 * Prefixing the default connection yields:
 *   pgsql.book_catalog.tags → connection=pgsql, table=book_catalog.tags
 */
final class SchemaTable
{
    public static function forValidation(string $schemaTable): string
    {
        $connection = (string) config('database.default');

        if (Schema::getConnection()->getDriverName() !== 'pgsql' && str_contains($schemaTable, '.')) {
            $schemaTable = explode('.', $schemaTable, 2)[1];
        }

        if ($schemaTable === '' || str_starts_with($schemaTable, $connection.'.')) {
            return $schemaTable;
        }

        return $connection.'.'.$schemaTable;
    }
}
