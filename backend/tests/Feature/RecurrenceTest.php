<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Recurrence;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class RecurrenceTest extends TestCase
{
    protected User $user;
    protected Category $categoryDespesa;
    protected Category $categoryReceita;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $this->categoryDespesa = Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Subscription',
            'type' => 'DESPESA',
        ]);

        $this->categoryReceita = Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Salary',
            'type' => 'RECEITA',
        ]);

        $this->token = $this->getToken();
    }

    protected function getToken(): string
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        return $response->json('access_token');
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_create_recurrence(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 50.00,
            'description' => 'Netflix subscription',
            'type' => 'DESPESA',
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('amount', '50.00')
            ->assertJsonPath('frequency', 'monthly')
            ->assertJsonPath('type', 'DESPESA');

        $this->assertDatabaseHas('recurrences', [
            'user_id' => $this->user->id,
            'amount' => 50.00,
        ]);
    }

    public function test_create_recurrence_without_end_date(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 50.00,
            'description' => 'Netflix subscription',
            'type' => 'DESPESA',
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('end_date', null);
    }

    public function test_create_recurrence_invalid_frequency_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 50.00,
            'description' => 'Test',
            'type' => 'DESPESA',
            'frequency' => 'invalid',
            'start_date' => '2026-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency']);
    }

    public function test_create_recurrence_invalid_type_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 50.00,
            'description' => 'Test',
            'type' => 'INVALID',
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_create_recurrence_category_type_mismatch_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryReceita->id,
            'amount' => 50.00,
            'description' => 'Test',
            'type' => 'DESPESA',
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Category type does not match recurrence type');
    }

    public function test_create_recurrence_category_other_user_rejected(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => 'DESPESA']);

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $otherCategory->id,
            'amount' => 50.00,
            'description' => 'Test',
            'type' => 'DESPESA',
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_create_recurrence_end_date_before_start_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 50.00,
            'description' => 'Test',
            'type' => 'DESPESA',
            'frequency' => 'monthly',
            'start_date' => '2026-06-01',
            'end_date' => '2026-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_list_recurrences(): void
    {
        Recurrence::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/recurrences');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_show_recurrence(): void
    {
        $recurrence = Recurrence::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson("/api/recurrences/{$recurrence->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $recurrence->id);
    }

    public function test_update_recurrence(): void
    {
        $recurrence = Recurrence::factory()->create(['user_id' => $this->user->id, 'description' => 'Old']);

        $response = $this->withHeaders($this->authHeaders())->putJson("/api/recurrences/{$recurrence->id}", [
            'description' => 'Updated',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('description', 'Updated');
    }

    public function test_delete_recurrence(): void
    {
        $recurrence = Recurrence::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/recurrences/{$recurrence->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Recurrence deleted successfully']);

        $this->assertDatabaseMissing('recurrences', ['id' => $recurrence->id]);
    }

    public function test_user_cannot_access_other_user_recurrence(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $recurrence = Recurrence::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson("/api/recurrences/{$recurrence->id}");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_cannot_access_recurrences(): void
    {
        $response = $this->getJson('/api/recurrences');
        $response->assertStatus(401);
    }

    public function test_generate_transactions_from_recurrence_daily(): void
    {
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-10',
            'amount' => 10.00,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-01-05',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 5); // Jan 1-5

        $this->assertDatabaseCount('transactions', 5);
    }

    public function test_generate_transactions_from_recurrence_weekly(): void
    {
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'weekly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-01',
            'amount' => 50.00,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-01-22',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 4); // Jan 1, 8, 15, 22

        $this->assertDatabaseCount('transactions', 4);
    }

    public function test_generate_transactions_from_recurrence_monthly(): void
    {
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-15',
            'end_date' => '2026-06-15',
            'amount' => 100.00,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-04-15',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 4); // Jan 15, Feb 15, Mar 15, Apr 15

        $this->assertDatabaseCount('transactions', 4);
    }

    public function test_generate_transactions_from_recurrence_yearly(): void
    {
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'yearly',
            'start_date' => '2024-01-01',
            'end_date' => '2028-01-01',
            'amount' => 500.00,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2027-01-01',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 4); // 2024, 2025, 2026, 2027

        $this->assertDatabaseCount('transactions', 4);
    }

    public function test_generate_transactions_respects_end_date(): void
    {
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-05',
            'amount' => 10.00,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-01-10',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 5); // Only until Jan 5
    }

    public function test_generate_transactions_does_not_duplicate(): void
    {
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-10',
            'amount' => 10.00,
        ]);

        $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-01-05',
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-01-10',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 5); // Only Jan 6-10 (5 new)

        $this->assertDatabaseCount('transactions', 10);
    }

    public function test_generate_transactions_unauthenticated_rejected(): void
    {
        $recurrence = Recurrence::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson("/api/recurrences/{$recurrence->id}/generate");

        $response->assertStatus(401);
    }

    public function test_generate_transactions_other_user_rejected(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $recurrence = Recurrence::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate");

        $response->assertStatus(404);
    }

    // Business Day Recurrence Tests

    public function test_create_business_day_recurrence_monthly(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 100.00,
            'description' => 'Monthly on 2nd business day',
            'type' => 'DESPESA',
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_business_day' => true,
            'business_day_number' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('is_business_day', true)
            ->assertJsonPath('business_day_number', 2);
    }

    public function test_create_business_day_recurrence_yearly(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 500.00,
            'description' => 'Yearly on 1st business day',
            'type' => 'DESPESA',
            'frequency' => 'yearly',
            'start_date' => '2026-01-01',
            'end_date' => '2028-12-31',
            'is_business_day' => true,
            'business_day_number' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('is_business_day', true)
            ->assertJsonPath('business_day_number', 1);
    }

    public function test_create_business_day_recurrence_daily_without_number(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 10.00,
            'description' => 'Daily business day',
            'type' => 'DESPESA',
            'frequency' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_business_day' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('is_business_day', true)
            ->assertJsonPath('business_day_number', null);
    }

    public function test_business_day_number_only_allowed_for_monthly_yearly(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 10.00,
            'description' => 'Weekly with business day number',
            'type' => 'DESPESA',
            'frequency' => 'weekly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_business_day' => true,
            'business_day_number' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'business_day_number is only allowed for monthly or yearly frequencies');
    }

    public function test_business_day_number_required_for_monthly_yearly(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/recurrences', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 100.00,
            'description' => 'Monthly business day without number',
            'type' => 'DESPESA',
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_business_day' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'business_day_number is required when is_business_day is true for monthly/yearly frequencies');
    }

    public function test_generate_business_day_recurrence_monthly_second_business_day(): void
    {
        // January 2026: 1st business day = Jan 1 (Thu), 2nd = Jan 2 (Fri)
        // February 2026: 1st = Feb 2 (Mon), 2nd = Feb 3 (Tue) (Feb 1 is Sun)
        // March 2026: 1st = Mar 2 (Mon), 2nd = Mar 3 (Tue) (Mar 1 is Sun)
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'amount' => 100.00,
            'is_business_day' => true,
            'business_day_number' => 2,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-03-31',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 3); // Jan, Feb, Mar

        $transactions = \App\Models\Transaction::where('recurrence_id', $recurrence->id)
            ->orderBy('date')
            ->get();

        $this->assertCount(3, $transactions);
        // 2nd business day of Jan 2026 = Jan 2 (Fri)
        $this->assertEquals('2026-01-02', $transactions[0]->date->format('Y-m-d'));
        // 2nd business day of Feb 2026 = Feb 3 (Tue)
        $this->assertEquals('2026-02-03', $transactions[1]->date->format('Y-m-d'));
        // 2nd business day of Mar 2026 = Mar 3 (Tue)
        $this->assertEquals('2026-03-03', $transactions[2]->date->format('Y-m-d'));
    }

    public function test_generate_business_day_recurrence_skips_sunday(): void
    {
        // Create a monthly recurrence on 1st business day
        // January 2026: 1st business day = Jan 1 (Thu)
        // February 2026: 1st business day = Feb 2 (Mon) (Feb 1 is Sun)
        // March 2026: 1st business day = Mar 2 (Mon) (Mar 1 is Sun)
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'amount' => 100.00,
            'is_business_day' => true,
            'business_day_number' => 1,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-03-31',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 3);

        $transactions = \App\Models\Transaction::where('recurrence_id', $recurrence->id)
            ->orderBy('date')
            ->get();

        $this->assertCount(3, $transactions);
        // 1st business day of Jan 2026 = Jan 1 (Thu)
        $this->assertEquals('2026-01-01', $transactions[0]->date->format('Y-m-d'));
        // 1st business day of Feb 2026 = Feb 2 (Mon)
        $this->assertEquals('2026-02-02', $transactions[1]->date->format('Y-m-d'));
        // 1st business day of Mar 2026 = Mar 2 (Mon)
        $this->assertEquals('2026-03-02', $transactions[2]->date->format('Y-m-d'));
        // None should fall on Sunday
        foreach ($transactions as $t) {
            $this->assertNotEquals(Carbon::SUNDAY, $t->date->dayOfWeek);
        }
    }

    public function test_generate_business_day_recurrence_skips_holiday(): void
    {
        // Add a holiday on Feb 2, 2026 (Monday)
        \App\Models\Holiday::create([
            'date' => '2026-02-02',
            'name' => 'Test Holiday',
        ]);

        // Monthly on 1st business day
        // Jan 2026: 1st = Jan 1 (Thu)
        // Feb 2026: 1st = Feb 2 (Mon) but holiday -> Feb 3 (Tue)
        // Mar 2026: 1st = Mar 2 (Mon)
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'amount' => 100.00,
            'is_business_day' => true,
            'business_day_number' => 1,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-03-31',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 3);

        $transactions = \App\Models\Transaction::where('recurrence_id', $recurrence->id)
            ->orderBy('date')
            ->get();

        $this->assertCount(3, $transactions);
        // Jan transaction = Jan 1 (Thu)
        $this->assertEquals('2026-01-01', $transactions[0]->date->format('Y-m-d'));
        // Feb transaction should be on Feb 3 (Tue) instead of Feb 2 (Mon - holiday)
        $this->assertEquals('2026-02-03', $transactions[1]->date->format('Y-m-d'));
        // Mar transaction = Mar 2 (Mon)
        $this->assertEquals('2026-03-02', $transactions[2]->date->format('Y-m-d'));
    }

    public function test_generate_business_day_recurrence_counts_saturday(): void
    {
        // Monthly on 3rd business day (to test Saturday)
        // January 2026: 1st = Jan 1 (Thu), 2nd = Jan 2 (Fri), 3rd = Jan 3 (Sat)
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'amount' => 100.00,
            'is_business_day' => true,
            'business_day_number' => 3,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-01-31',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('generated_count', 1);

        $transactions = \App\Models\Transaction::where('recurrence_id', $recurrence->id)->get();
        $this->assertCount(1, $transactions);
        // 3rd business day of Jan 2026 = Jan 3 (Saturday)
        $this->assertEquals('2026-01-03', $transactions[0]->date->format('Y-m-d'));
        $this->assertEquals(Carbon::SATURDAY, $transactions[0]->date->dayOfWeek);
    }

    public function test_business_day_recurrence_daily_skips_weekends(): void
    {
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-10',
            'amount' => 10.00,
            'is_business_day' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-01-10',
        ]);

        $response->assertStatus(200);

        $transactions = \App\Models\Transaction::where('recurrence_id', $recurrence->id)
            ->orderBy('date')
            ->get();

        // Jan 1 (Thu), 2 (Fri), 3 (Sat), 5 (Mon), 6 (Tue), 7 (Wed), 8 (Thu), 9 (Fri), 10 (Sat)
        // Skip Jan 4 (Sun)
        $this->assertCount(9, $transactions);
        foreach ($transactions as $t) {
            $this->assertNotEquals(Carbon::SUNDAY, $t->date->dayOfWeek);
        }
    }

    public function test_business_day_recurrence_weekly_skips_weekends(): void
    {
        $recurrence = Recurrence::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa->id,
            'frequency' => 'weekly',
            'start_date' => '2026-01-02', // Friday
            'end_date' => '2026-02-01',
            'amount' => 50.00,
            'is_business_day' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson("/api/recurrences/{$recurrence->id}/generate", [
            'until' => '2026-02-01',
        ]);

        $response->assertStatus(200);

        $transactions = \App\Models\Transaction::where('recurrence_id', $recurrence->id)
            ->orderBy('date')
            ->get();

        // Jan 2 (Fri), Jan 9 (Fri), Jan 16 (Fri), Jan 23 (Fri), Jan 30 (Fri)
        // Should not include weekends
        foreach ($transactions as $t) {
            $this->assertNotEquals(Carbon::SUNDAY, $t->date->dayOfWeek);
            $this->assertNotEquals(Carbon::SATURDAY, $t->date->dayOfWeek);
        }
    }
}