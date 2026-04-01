<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait ResponseTrait
{
    public function successResponse($data, $message = '', $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $code);
    }

    public function failResponse($data, $message = '', $code = 200, ?\Exception $e = null): JsonResponse
    {
        Log::error($message, [
            'message' => $message,
            'file' => $e?->getFile(),
            'line' => $e?->getLine(),
            'trace' => $e?->getTraceAsString(),
        ]);

        return response()->json([
            'status' => 'fail',
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $code);
    }

    public function errorResponse($message, $code = 500, ?\Exception $exception = null): JsonResponse
    {
        if (! $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $message,
                'data' => null,
                'errors' => null,
            ], $code);
        }

        $errorCode = $exception->getCode();

        $message = $this->humanReadableMsg($errorCode, $exception);

        Log::error($message, [
            'message' => $message.' => '.$exception->getMessage(),
            'file' => $exception?->getFile(),
            'line' => $exception?->getLine(),
            'trace' => $exception?->getTraceAsString(),
            'code' => $exception?->getCode(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null,
            'errors' => null,
        ], $code);
    }

    protected function humanReadableMsg($code, $e)
    {
        // MySQL and other database error codes with user-friendly messages
        $message = match ($code) {
            // Duplicate entries
            1062, 23000 => 'This record already exists.',

            // Foreign key constraints (delete)
            1451, 2292 => 'This record cannot be deleted as it is referenced by other records.',

            // Foreign key constraints (insert/update)
            1452, 2291 => 'Invalid reference. The referenced record does not exist.',

            // Query syntax
            1064 => 'Invalid query format.',

            // Missing tables
            1146, 942 => 'Required table not found.',

            // Database connection
            1049 => 'Database connection error.',
            1045, 1017 => 'Database authentication failed.',

            // Other common MySQL errors
            1040 => 'Too many connections to the database.',
            1054 => 'Unknown column in the query.',
            1060 => 'Duplicate column name.',
            1068 => 'Multiple primary keys defined.',
            1136 => "Column count doesn't match value count.",
            1213 => 'Deadlock detected. Please try again.',
            1215 => 'Cannot add foreign key constraint.',
            1216 => 'Cannot add or update a child row: a foreign key constraint fails.',
            1217 => 'Cannot delete or update a parent row: a foreign key constraint fails.',
            1364 => 'Field cannot be null.',
            1366 => 'Incorrect data type for column.',

            // Table/database operations
            1046 => 'No database selected.',
            1050 => 'Table already exists.',
            1051 => 'Unknown table.',
            1007 => 'Database already exists.',
            1008 => 'Cannot delete this database.',
            1044 => 'Access denied for database.',

            // Storage issues
            1021 => 'Disk full. Please contact support.',
            1037 => 'Out of memory. Please contact support.',
            1114 => 'Table is full.',

            // Connection issues
            2002 => 'Cannot connect to database server.',
            2003 => 'Database server is not running.',
            2005 => 'Unknown database host.',
            2006 => 'Database server has gone away. Connection lost.',
            2013 => 'Lost connection during query.',

            // Transaction errors
            1205 => 'Lock wait timeout exceeded.',
            1206 => 'Too many locks on table.',

            1292 => 'Incorrect data type provided.',
            1318 => 'Incorrect number of arguments for a function.',
            1406 => 'Data too long for a column.',

            // HTTP Error Codes (Laravel-Specific or Execution Errors)
            500 => 'Internal server error. Please try again later.',
            404 => 'The requested resource was not found.',
            419 => 'Session expired. Please refresh the page and try again.',
            422 => 'Validation error. Please check your input and try again.',
            403 => 'You do not have permission to perform this action.',
            401 => 'Unauthorized. Please log in to continue.',
            429 => 'Too many requests. Please slow down and try again later.',
            503 => 'Service unavailable. Please try again later.',

            // PDO errors
            'HY000' => 'General database error.',
            '42000' => 'Syntax error or access violation.',
            '42S01' => 'Table already exists.',
            '42S02' => 'Table not found.',
            '42S22' => 'Column not found.',
            '23505' => 'Unique constraint violation.',
            '08001' => 'Unable to establish database connection.',

            // Laravel/PHP specific errors
            '08S01' => 'Communication link failure.',
            '08004' => 'Database server rejected connection.',
            '07002' => 'COUNT field incorrect.',
            '3D000' => 'Database does not exist.',
            '28000' => 'Invalid username/password.',

            // Catch-all
            default => $e->getMessage(),
        };

        return $message;
    }
}
