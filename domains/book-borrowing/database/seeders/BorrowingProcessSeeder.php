<?php

namespace Modules\BookBorrowing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Models\Role;
use Modules\BookBorrowing\Enums\BorrowingProcessCodeEnum;
use Modules\BookBorrowing\Models\BorrowingProcess;

class BorrowingProcessSeeder extends Seeder
{
    public function run(): void
    {
        $student = Role::query()->where('code', 'student')->firstOrFail();
        $librarian = Role::query()->where('code', 'librarian')->firstOrFail();

        $processes = [
            [
                'name' => 'Create Borrowing',
                'description' => 'Student submits a borrow request for a physical or digital book.',
                'index_no' => 0,
                'status_name' => 'Borrowing Created',
                'status_color' => 'blue',
                'status_code' => BorrowingProcessCodeEnum::Created->value,
                'sender_role_id' => $student->id,
                'receiver_role_id' => $librarian->id,
                'is_final' => false,
            ],
            [
                'name' => 'Approve Borrowing',
                'description' => 'Librarian approves the request and issues the book to the student.',
                'index_no' => 1,
                'status_name' => 'Borrowing Approved',
                'status_color' => 'green',
                'status_code' => BorrowingProcessCodeEnum::Approved->value,
                'sender_role_id' => $librarian->id,
                'receiver_role_id' => null,
                'is_final' => true,
            ],
            [
                'name' => 'Reject Borrowing',
                'description' => 'Librarian rejects the borrow request.',
                'index_no' => 2,
                'status_name' => 'Borrowing Rejected',
                'status_color' => 'red',
                'status_code' => BorrowingProcessCodeEnum::Rejected->value,
                'sender_role_id' => $librarian->id,
                'receiver_role_id' => $student->id,
                'is_final' => true,
            ],
            // [
            //     'name' => 'Return Borrowing',
            //     'description' => 'Librarian initiates return of an issued book.',
            //     'index_no' => 3,
            //     'status_name' => 'Return Initiated',
            //     'status_color' => 'orange',
            //     'status_code' => BorrowingProcessCodeEnum::ReturnInitiated->value,
            //     'sender_role_id' => $librarian->id,
            //     'receiver_role_id' => $student->id,
            //     'is_final' => false,
            // ],
            [
                'name' => 'Book Returned',
                'description' => 'Librarian confirms the book is back in the store/shelf.',
                'index_no' => 3,
                'status_name' => 'Book Returned',
                'status_color' => 'teal',
                'status_code' => BorrowingProcessCodeEnum::BookReturned->value,
                'sender_role_id' => $librarian->id,
                'receiver_role_id' => null,
                'is_final' => true,
            ],
        ];

        foreach ($processes as $process) {
            BorrowingProcess::query()->updateOrCreate(
                ['status_code' => $process['status_code']],
                $process,
            );
        }
    }
}
