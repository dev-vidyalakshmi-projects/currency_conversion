<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;


    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'Vidyalakshmi',
            'email'                 => 'developer.vidyalakshmi.s@gmail.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'user'         => ['id', 'name', 'email'],
                     'access_token',
                     'token_type',
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'developer.vidyalakshmi.s@gmail.com',
        ]);
    }


    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'developer.vidyalakshmi.s@gmail.com']);

        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'Another User',
            'email'                 => 'developer.vidyalakshmi.s@gmail.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }


    public function test_register_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'Test User',
            'email'                 => 'developer.vidyalakshmi.s@gmail.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }


    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email'    => 'developer.vidyalakshmi.s@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'developer.vidyalakshmi.s@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'user',
                 ]);
    }


    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'developer.vidyalakshmi.s@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'developer.vidyalakshmi.s@gmail.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }


    public function test_user_can_get_own_profile(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->getJson('/api/v1/me');

        $response->assertStatus(200)
                 ->assertJson(['email' => $user->email]);
    }


    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }


    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->postJson('/api/v1/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Successfully logged out.']);
    }
}
