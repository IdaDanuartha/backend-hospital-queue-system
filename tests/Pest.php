<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

// Create admin user
function createAdmin(array $attributes = [])
{
    $user = \App\Models\User::factory()->create(array_merge([
        'username' => 'admin',
        'email' => 'admin@hospital.com',
        'password' => bcrypt('123456'),
        'is_active' => true,
    ], $attributes));

    \App\Models\Admin::factory()->create([
        'user_id' => $user->id,
        'position' => 'Administrator',
        'department' => 'IT',
    ]);

    return $user;
}

// Create staff user
function createStaff(\App\Models\Poly $poly = null, array $attributes = [])
{
    if (!$poly) {
        $poly = \App\Models\Poly::factory()->create();
    }

    $user = \App\Models\User::factory()->create(array_merge([
        'username' => 'staff001',
        'email' => 'staff@hospital.com',
        'password' => bcrypt('123456'),
        'is_active' => true,
    ], $attributes));

    \App\Models\Staff::factory()->create([
        'user_id' => $user->id,
        'poly_id' => $poly->id,
        'code' => 'STF-001',
        'is_active' => true,
    ]);

    return $user;
}

// Login and get token
function loginAsAdmin()
{
    $admin = createAdmin();

    $response = test()->postJson('/api/v1/auth/login', [
        'username' => 'admin',
        'password' => '123456',
    ]);

    return [
        'user' => $admin,
        'token' => $response->json('data.access_token'),
    ];
}

function loginAsStaff(\App\Models\Poly $poly = null)
{
    $staff = createStaff($poly);

    $response = test()->postJson('/api/v1/auth/login', [
        'username' => 'staff001',
        'password' => '123456',
    ]);

    return [
        'user' => $staff,
        'token' => $response->json('data.access_token'),
    ];
}

// Acting as authenticated user
function actingAsAdmin()
{
    $auth = loginAsAdmin();
    return test()->withToken($auth['token']);
}

function actingAsStaff(\App\Models\Poly $poly = null)
{
    $auth = loginAsStaff($poly);
    return test()->withToken($auth['token']);
}
