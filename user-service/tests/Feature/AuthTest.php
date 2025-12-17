<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User can register successfully
     */
    public function test_user_can_register_successfully(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Aurora Test',
            'email' => 'aurora@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'role',
                     ],
                     'token',
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'Registrasi berhasil',
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'aurora@test.com',
            'name' => 'Aurora Test',
            'role' => 'admin',
        ]);
    }

    /**
     * Test: Registration validation fails for invalid data
     */
    public function test_registration_validation_fails(): void
    {
        // Test without required fields
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);

        // Test with invalid email
        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);

        // Test with password mismatch
        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test: Cannot register with duplicate email
     */
    public function test_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@test.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: User can login with valid credentials
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'user',
                     'token',
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'Login berhasil',
                 ]);
    }

    /**
     * Test: Login fails with invalid credentials
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('password123'),
        ]);

        // Wrong password
        $response = $this->postJson('/api/login', [
            'email' => 'user@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Email atau password salah',
                 ]);

        // Non-existent email
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: User can access profile with valid token
     */
    public function test_user_can_access_profile_with_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/profile');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Profile berhasil diambil',
                     'data' => [
                         'id' => $user->id,
                         'name' => 'Test User',
                         'email' => 'test@example.com',
                     ],
                 ]);
    }

    /**
     * Test: Cannot access profile without token
     */
    public function test_cannot_access_profile_without_token(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthenticated',
                 ]);
    }

    /**
     * Test: User can update profile
     */
    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Profile berhasil diupdate',
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    /**
     * Test: User can logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logout berhasil',
                 ]);

        // Token should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    /**
     * Test: Correlation ID is present in response
     */
    public function test_correlation_id_present_in_response(): void
    {
        $correlationId = 'test-correlation-12345';

        $response = $this->withHeaders([
            'X-Correlation-ID' => $correlationId,
        ])->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertHeader('X-Correlation-ID', $correlationId);
    }
}
