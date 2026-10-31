<?php

namespace Modules\BookCatalogue\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;
use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookCatalogue\Enums\PhysicalBookCopyStatusEnum;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Models\Category;
use Modules\BookCatalogue\Models\PhysicalBook;
use Modules\BookCatalogue\Models\PhysicalBookCopy;
use Modules\BookCatalogue\Models\Shelf;
use Modules\BookCatalogue\Models\Tag;
use Tests\TestCase;

/**
 * V1-030 / TC-CAT-01 — search filters + availability counts.
 */
class CatalogueSearchAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['audit.enabled' => false]);
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'book_has_tags',
            'book_has_sub_categories',
            'book_has_categories',
            'physical_book_copies',
            'physical_books',
            'digital_books',
            'reading_histories',
            'book_details',
            'tags',
            'sub_categories',
            'categories',
            'shelves',
            'user_system_actions',
            'system_action_roles',
            'system_actions',
            'revoked_roles',
            'user_roles',
            'roles',
            'users',
            'personal_access_tokens',
            'activity_log',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_search_matches_title_author_and_isbn(): void
    {
        $member = $this->seedMemberWithCatalogueView();
        $this->seedPhysicalBook([
            'title' => 'Networks Primer',
            'authors' => 'Ada Lovelace',
            'isbn' => '978-1111111111',
        ]);
        $this->seedPhysicalBook([
            'title' => 'Other Title',
            'authors' => 'Grace Hopper',
            'isbn' => '978-2222222222',
        ]);

        Sanctum::actingAs($member);

        $this->getJson('/api/book-catalogue/books?search=Networks')
            ->assertOk()
            ->assertJsonPath('data.items.0.title', 'Networks Primer')
            ->assertJsonCount(1, 'data.items');

        $this->getJson('/api/book-catalogue/books?search=Hopper')
            ->assertOk()
            ->assertJsonPath('data.items.0.authors', 'Grace Hopper')
            ->assertJsonCount(1, 'data.items');

        $this->getJson('/api/book-catalogue/books?isbn=222222')
            ->assertOk()
            ->assertJsonPath('data.items.0.isbn', '978-2222222222')
            ->assertJsonCount(1, 'data.items');
    }

    public function test_filters_by_category_tag_type_and_availability(): void
    {
        $member = $this->seedMemberWithCatalogueView();
        $category = Category::query()->create([
            'name' => 'Science',
            'code' => 'SCI',
            'description' => null,
        ]);
        $tag = Tag::query()->create([
            'name' => 'Classic',
            'code' => 'CLS',
            'description' => null,
        ]);

        $available = $this->seedPhysicalBook([
            'title' => 'Available Book',
            'authors' => 'Author A',
            'isbn' => '978-3333333333',
            'copy_status' => PhysicalBookCopyStatusEnum::Available,
        ]);
        $available->categories()->sync([$category->id]);
        $available->tags()->sync([$tag->id]);

        $this->seedPhysicalBook([
            'title' => 'On Loan Book',
            'authors' => 'Author B',
            'isbn' => '978-4444444444',
            'copy_status' => PhysicalBookCopyStatusEnum::OnLoan,
        ]);

        BookDetail::query()->create([
            'type_code' => BookTypeEnum::Digital,
            'title' => 'Digital Only',
            'description' => null,
            'authors' => 'Author C',
            'isbn' => '978-5555555555',
            'publisher' => 'Press',
            'pub_year' => 2020,
            'edition' => null,
            'language' => 'en',
            'pages' => 10,
        ]);

        Sanctum::actingAs($member);

        $this->getJson('/api/book-catalogue/books?category_id='.$category->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.title', 'Available Book');

        $this->getJson('/api/book-catalogue/books?tag_id='.$tag->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.items');

        $this->getJson('/api/book-catalogue/books?type_code=PHYSICAL')
            ->assertOk()
            ->assertJsonCount(2, 'data.items');

        $this->getJson('/api/book-catalogue/books?availability=available')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.title', 'Available Book');

        $this->getJson('/api/book-catalogue/books?availability=unavailable')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.title', 'On Loan Book');
    }

    public function test_detail_includes_availability_counts_and_hold_placeholder(): void
    {
        $member = $this->seedMemberWithCatalogueView();
        $book = $this->seedPhysicalBook([
            'title' => 'Counted Book',
            'authors' => 'Author D',
            'isbn' => '978-6666666666',
            'copies' => [
                PhysicalBookCopyStatusEnum::Available,
                PhysicalBookCopyStatusEnum::Available,
                PhysicalBookCopyStatusEnum::OnLoan,
            ],
        ]);

        Sanctum::actingAs($member);

        $this->getJson('/api/book-catalogue/books/'.$book->id)
            ->assertOk()
            ->assertJsonPath('data.availability.availableCopies', 2)
            ->assertJsonPath('data.availability.onLoanCopies', 1)
            ->assertJsonPath('data.availability.holdsQueueLength', 0)
            ->assertJsonPath('data.availability.isAvailable', true)
            ->assertJsonPath('data.availability.canRead', false);
    }

    public function test_catalogue_view_forbidden_without_permission(): void
    {
        $user = User::query()->create([
            'username' => 'noperm',
            'email' => 'noperm@example.com',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/book-catalogue/books')->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function seedPhysicalBook(array $attributes): BookDetail
    {
        $shelf = Shelf::query()->first() ?? Shelf::query()->create([
            'name' => 'A1',
            'code' => 'A1',
            'description' => null,
        ]);

        $book = BookDetail::query()->create([
            'type_code' => BookTypeEnum::Physical,
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
            'authors' => $attributes['authors'],
            'isbn' => $attributes['isbn'],
            'publisher' => 'Press',
            'pub_year' => 2024,
            'edition' => null,
            'language' => 'en',
            'pages' => 100,
        ]);

        $physical = PhysicalBook::query()->create([
            'book_detail_id' => $book->id,
            'shelf_id' => $shelf->id,
            'code_no' => 'PB-'.$book->id,
            'copies' => 0,
        ]);

        $statuses = $attributes['copies']
            ?? [$attributes['copy_status'] ?? PhysicalBookCopyStatusEnum::Available];

        foreach ($statuses as $index => $status) {
            PhysicalBookCopy::query()->create([
                'physical_book_id' => $physical->id,
                'accession_number' => sprintf('ACC-%d-%02d', $book->id, $index + 1),
                'status' => $status,
            ]);
        }

        $physical->update(['copies' => count($statuses)]);

        return $book->fresh(['physicalBooks.copyRecords', 'categories', 'tags']);
    }

    protected function seedMemberWithCatalogueView(): User
    {
        $role = Role::query()->create([
            'name' => 'Student',
            'code' => 'student',
            'status' => 'active',
            'is_system' => true,
        ]);

        $view = SystemAction::query()->create([
            'name' => 'View catalogue',
            'code' => 'catalogue.view',
            'role_id' => $role->id,
            'action_type' => 'permission',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'username' => 'member-cat',
            'email' => 'member-cat@example.com',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
        ]);

        $assignment = UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        UserSystemAction::query()->create([
            'user_id' => $user->id,
            'system_action_id' => $view->id,
            'user_role_id' => $assignment->id,
        ]);

        return $user;
    }

    protected function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('locked_until')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status')->default('active');
            $table->boolean('is_system')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('revoked_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_role_id');
            $table->foreignId('revoked_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('system_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('role_id')->nullable();
            $table->string('action_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('menu_item_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('system_action_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_action_id');
            $table->foreignId('role_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('user_system_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('system_action_id');
            $table->foreignId('user_role_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });

        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('book_details', function (Blueprint $table) {
            $table->id();
            $table->string('type_code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('authors');
            $table->string('isbn');
            $table->string('publisher');
            $table->unsignedSmallInteger('pub_year');
            $table->string('edition')->nullable();
            $table->string('language');
            $table->integer('pages');
            $table->string('file_cover')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('physical_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_detail_id');
            $table->unsignedBigInteger('shelf_id');
            $table->string('code_no')->unique();
            $table->integer('copies');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('physical_book_copies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('physical_book_id');
            $table->string('accession_number')->unique();
            $table->string('status', 20)->default('AVAILABLE');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('digital_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_detail_id');
            $table->string('file_cover')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('format')->nullable();
            $table->boolean('is_downloadable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('checksum')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('book_has_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_detail_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('book_has_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_detail_id');
            $table->unsignedBigInteger('sub_category_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('book_has_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_detail_id');
            $table->unsignedBigInteger('tag_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('reading_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_detail_id');
            $table->decimal('progress_percent', 5, 2)->nullable();
            $table->string('current_location')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('device')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
