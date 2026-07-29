<?php

namespace Modules\BookBorrowing\Database\Seeders;

use Illuminate\Database\Seeder;

class BookBorrowingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BorrowingProcessSeeder::class,
        ]);
    }
}
