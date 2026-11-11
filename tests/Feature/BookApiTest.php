<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
            'publisher' => 'Bentang Pustaka',
            'isbn' => '9789793062792',
            'published_year' => 2005,
            'stock' => 10,
            'description' => 'Novel tentang anak-anak Belitung.',
        ], $override);
    }

    public function test_guest_cannot_access_books(): void
    {
        $book = Book::factory()->create();

        $this->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
        $this->getJson('/api/books')->assertUnauthorized();
        $this->postJson('/api/books', $this->payload())->assertUnauthorized();
        $this->getJson("/api/books/{$book->id}")->assertUnauthorized();
        $this->putJson("/api/books/{$book->id}", $this->payload())->assertUnauthorized();
        $this->patchJson("/api/books/{$book->id}", ['stock' => 5])->assertUnauthorized();
        $this->deleteJson("/api/books/{$book->id}")->assertUnauthorized();
    }

    public function test_can_list_and_search_books(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Book::factory()->create(['title' => 'Bumi Manusia', 'author' => 'Pramoedya']);
        Book::factory()->count(3)->create(['title' => 'Buku Lain', 'author' => 'Penulis Lain']);

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonCount(4, 'data');

        $this->getJson('/api/books?search=Pramoedya')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Bumi Manusia');
    }

    public function test_book_pagination_limits_page_size(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Book::factory()->count(3)->create();

        $this->getJson('/api/books?per_page=0')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/books?per_page=200')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_book_list_rejects_malformed_search_and_pagination_parameters(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/books?search%5B%5D=judul')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');

        $this->getJson('/api/books?per_page=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');

        $this->getJson('/api/books?page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('page');
    }

    public function test_can_create_book_and_validation_rejects_duplicate_isbn(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/books', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Laskar Pelangi');

        $this->assertDatabaseHas('books', ['isbn' => '9789793062792']);

        $this->postJson('/api/books', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('isbn');

        $this->postJson('/api/books', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'author', 'isbn', 'published_year', 'stock']);
    }

    public function test_can_show_and_update_book(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $book = Book::factory()->create();

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $book->id);

        $this->putJson("/api/books/{$book->id}", $this->payload(['isbn' => $book->isbn, 'title' => 'Judul Baru']))
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul Baru');

        $this->patchJson("/api/books/{$book->id}", ['stock' => 99])
            ->assertOk()
            ->assertJsonPath('data.stock', 99);

        $this->getJson('/api/books/999999')->assertNotFound();
    }

    public function test_update_rejects_an_isbn_assigned_to_another_book(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $bookToUpdate = Book::factory()->create();
        $otherBook = Book::factory()->create();

        $this->patchJson("/api/books/{$bookToUpdate->id}", ['isbn' => $otherBook->isbn])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isbn');
    }

    public function test_can_delete_book(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $book = Book::factory()->create();

        $this->deleteJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJson(['message' => 'Buku berhasil dihapus']);

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }
}
