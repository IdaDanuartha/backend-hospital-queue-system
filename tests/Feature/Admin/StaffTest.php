<?php

use App\Models\Staff;
use App\Models\Poly;
use App\Models\User;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

describe('Admin - Staff Management', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('GET /api/v1/admin/staff', function () {

        it('can list all staff', function () {
            Staff::factory()->count(3)->create();

            actingAsAdmin()
                ->getJson('/api/v1/admin/staff')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'poly_id',
                            'code',
                            'is_active',
                            'user',
                            'poly',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);

            expect(Staff::count())->toBe(3);
        });

        it('requires authentication', function () {
            getJson('/api/v1/admin/staff')
                ->assertStatus(401);
        });
    });

    describe('POST /api/v1/admin/staff', function () {

        it('can create a staff member', function () {
            $poly = Poly::factory()->create();

            actingAsAdmin()
                ->postJson('/api/v1/admin/staff', [
                    'name' => 'John Doe',
                    'username' => 'staff001',
                    'email' => 'staff001@hospital.com',
                    'password' => 'password123',
                    'poly_id' => $poly->id,
                    'code' => 'STF-001',
                    'is_active' => true,
                ])
                ->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'user_id',
                        'poly_id',
                        'code',
                        'user',
                        'poly',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Staff created successfully',
                ]);

            expect(Staff::count())->toBe(1);
            expect(User::where('username', 'staff001')->exists())->toBeTrue();
        });

        it('validates required fields', function () {
            actingAsAdmin()
                ->postJson('/api/v1/admin/staff', [])
                ->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'username',
                    'email',
                    'password',
                    'poly_id',
                    'code'
                ]);
        });

        it('validates unique username', function () {
            User::factory()->create(['username' => 'staff001']);

            actingAsAdmin()
                ->postJson('/api/v1/admin/staff', [
                    'name' => 'John',
                    'username' => 'staff001',
                    'email' => 'unique@test.com',
                    'password' => 'password',
                    'poly_id' => Poly::factory()->create()->id,
                    'code' => 'STF-001',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['username']);
        });

        it('validates unique email', function () {
            User::factory()->create(['email' => 'test@hospital.com']);

            actingAsAdmin()
                ->postJson('/api/v1/admin/staff', [
                    'name' => 'John',
                    'username' => 'uniqueuser',
                    'email' => 'test@hospital.com',
                    'password' => 'password',
                    'poly_id' => Poly::factory()->create()->id,
                    'code' => 'STF-001',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates unique code', function () {
            Staff::factory()->create(['code' => 'STF-001']);

            actingAsAdmin()
                ->postJson('/api/v1/admin/staff', [
                    'name' => 'John',
                    'username' => 'staff002',
                    'email' => 'staff002@test.com',
                    'password' => 'password',
                    'poly_id' => Poly::factory()->create()->id,
                    'code' => 'STF-001',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['code']);
        });

        it('requires authentication', function () {
            postJson('/api/v1/admin/staff', [
                'name' => 'John',
                'username' => 'staff001',
            ])
                ->assertStatus(401);
        });
    });

    describe('GET /api/v1/admin/staff/{id}', function () {

        it('can show staff detail', function () {
            $staff = Staff::factory()->create();

            actingAsAdmin()
                ->getJson("/api/v1/admin/staff/{$staff->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $staff->id,
                        'code' => $staff->code,
                    ],
                ]);
        });

        it('returns 404 for non-existent staff', function () {
            $fakeUuid = '99999999-9999-9999-9999-999999999999';

            actingAsAdmin()
                ->getJson("/api/v1/admin/staff/{$fakeUuid}")
                ->assertStatus(404);
        });
    });

    describe('PUT /api/v1/admin/staff/{id}', function () {

        it('can update a staff member', function () {
            $staff = Staff::factory()->create();

            actingAsAdmin()
                ->putJson("/api/v1/admin/staff/{$staff->id}", [
                    'name' => 'Updated Name',
                    'username' => $staff->user->username,
                    'email' => $staff->user->email,
                    'poly_id' => $staff->poly_id,
                    'code' => $staff->code,
                    'is_active' => true,
                ])
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Staff updated successfully',
                ]);

            expect($staff->user->fresh()->name)->toBe('Updated Name');
        });

        it('can update password', function () {
            $staff = Staff::factory()->create();
            $oldPassword = $staff->user->password;

            actingAsAdmin()
                ->putJson("/api/v1/admin/staff/{$staff->id}", [
                    'name' => $staff->user->name,
                    'username' => $staff->user->username,
                    'email' => $staff->user->email,
                    'password' => 'newpassword123',
                    'poly_id' => $staff->poly_id,
                    'code' => $staff->code,
                ])
                ->assertStatus(200);

            expect($staff->user->fresh()->password)->not->toBe($oldPassword);
        });
    });

    describe('DELETE /api/v1/admin/staff/{id}', function () {

        it('can delete a staff member', function () {
            $staff = Staff::factory()->create();
            $userId = $staff->user_id;

            actingAsAdmin()
                ->deleteJson("/api/v1/admin/staff/{$staff->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Staff deleted successfully',
                ]);

            expect(Staff::count())->toBe(0);
            expect(User::find($userId))->toBeNull();
        });

        it('returns 404 when deleting non-existent staff', function () {
            $fakeUuid = '99999999-9999-9999-9999-999999999999';

            actingAsAdmin()
                ->deleteJson("/api/v1/admin/staff/{$fakeUuid}")
                ->assertStatus(404);
        });
    });
});
