<?php

use App\Models\User;
use function Pest\Laravel\postJson;

describe('Authentication API', function () {

    beforeEach(function () {
        // Seed system settings
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
        \App\Models\SystemSetting::create(['key' => 'geofencing_radius_meters', 'value' => '100']);
    });

    describe('POST /api/v1/auth/login', function () {

        it('can login with valid credentials', function () {
            $admin = createAdmin();

            $response = postJson('/api/v1/auth/login', [
                'username' => 'admin',
                'password' => '123456',
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                        'user',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'token_type' => 'bearer',
                    ],
                ]);

            expect($response->json('data.access_token'))->not->toBeEmpty();
        });

        it('fails with invalid username', function () {
            $response = postJson('/api/v1/auth/login', [
                'username' => 'invalid',
                'password' => '123456',
            ]);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid username or password',
                ]);
        });

        it('fails with invalid password', function () {
            createAdmin();

            $response = postJson('/api/v1/auth/login', [
                'username' => 'admin',
                'password' => 'wrongpassword',
            ]);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                ]);
        });

        it('fails with inactive account', function () {
            createAdmin(['is_active' => false]);

            $response = postJson('/api/v1/auth/login', [
                'username' => 'admin',
                'password' => '123456',
            ]);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Your account has been deactivated',
                ]);
        });

        it('requires username field', function () {
            $response = postJson('/api/v1/auth/login', [
                'password' => '123456',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['username']);
        });

        it('requires password field', function () {
            $response = postJson('/api/v1/auth/login', [
                'username' => 'admin',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('updates last_login_at on successful login', function () {
            $admin = createAdmin();

            postJson('/api/v1/auth/login', [
                'username' => 'admin',
                'password' => '123456',
            ]);

            $admin->refresh();
            expect($admin->last_login_at)->not->toBeNull();
        });
    });

    describe('POST /api/v1/auth/refresh', function () {

        it('can refresh token with valid token', function () {
            $auth = loginAsAdmin();

            $response = test()->withToken($auth['token'])
                ->postJson('/api/v1/auth/refresh');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Token refreshed successfully',
                ]);

            expect($response->json('data.access_token'))->not->toBeEmpty();
            expect($response->json('data.access_token'))->not->toBe($auth['token']);
        });

        it('fails without token', function () {
            $response = postJson('/api/v1/auth/refresh');

            $response->assertStatus(401)
                ->assertJsonFragment(['message' => 'Unauthorized']);
        });
    });

    describe('GET /api/v1/auth/me', function () {

        it('returns authenticated user profile', function () {
            $auth = loginAsAdmin();

            $response = test()->withToken($auth['token'])
                ->getJson('/api/v1/auth/me');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'username',
                        'email',
                        'is_active',
                        'admin',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'username' => 'admin',
                        'email' => 'admin@hospital.com',
                    ],
                ]);
        });

        it('fails without token', function () {
            $response = test()->getJson('/api/v1/auth/me');

            $response->assertStatus(401)
                ->assertJsonFragment(['message' => 'Unauthorized']);
        });
    });

    describe('POST /api/v1/auth/logout', function () {

        it('can logout successfully', function () {
            $auth = loginAsAdmin();

            $response = test()->withToken($auth['token'])
                ->postJson('/api/v1/auth/logout');

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logged out successfully',
                ]);
        });

        it('fails without token', function () {
            $response = postJson('/api/v1/auth/logout');

            $response->assertStatus(401)
                ->assertJsonFragment(['message' => 'Unauthorized']);
        });
    });
});
