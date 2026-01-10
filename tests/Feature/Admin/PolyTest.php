<?php

use App\Models\Poly;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

describe('Admin - Polyclinic Management', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('GET /api/v1/admin/polys', function () {

        it('can list all polyclinics', function () {
            Poly::factory()->count(3)->create();

            actingAsAdmin()
                ->getJson('/api/v1/admin/polys')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'code',
                            'name',
                            'location',
                            'is_active',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);

            expect(Poly::count())->toBe(3);
        });

        it('requires authentication', function () {
            getJson('/api/v1/admin/polys')
                ->assertStatus(401);
        });
    });

    describe('POST /api/v1/admin/polys', function () {

        it('can create a polyclinic', function () {
            actingAsAdmin()
                ->postJson('/api/v1/admin/polys', [
                    'code' => 'POLI-001',
                    'name' => 'Poliklinik Umum',
                    'location' => 'Gedung A Lantai 1',
                    'is_active' => true,
                ])
                ->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'code',
                        'name',
                        'location',
                        'is_active',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Polyclinic created successfully',
                    'data' => [
                        'code' => 'POLI-001',
                        'name' => 'Poliklinik Umum',
                    ],
                ]);

            expect(Poly::count())->toBe(1);
        });

        it('validates required fields', function () {
            actingAsAdmin()
                ->postJson('/api/v1/admin/polys', [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['code', 'name']);
        });

        it('validates unique code', function () {
            Poly::factory()->create(['code' => 'POLI-001']);

            actingAsAdmin()
                ->postJson('/api/v1/admin/polys', [
                    'code' => 'POLI-001',
                    'name' => 'Test Poly',
                    'location' => 'Test Location',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['code']);
        });

        it('requires authentication', function () {
            postJson('/api/v1/admin/polys', [
                'code' => 'POLI-001',
                'name' => 'Test',
            ])
                ->assertStatus(401);
        });
    });

    describe('GET /api/v1/admin/polys/{id}', function () {

        it('can show polyclinic detail', function () {
            $poly = Poly::factory()->create();

            actingAsAdmin()
                ->getJson("/api/v1/admin/polys/{$poly->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $poly->id,
                        'code' => $poly->code,
                        'name' => $poly->name,
                    ],
                ]);
        });

        it('returns 404 for non-existent polyclinic', function () {
            $fakeUuid = '99999999-9999-9999-9999-999999999999';

            actingAsAdmin()
                ->getJson("/api/v1/admin/polys/{$fakeUuid}")
                ->assertStatus(404);
        });
    });

    describe('PUT /api/v1/admin/polys/{id}', function () {

        it('can update a polyclinic', function () {
            $poly = Poly::factory()->create();

            actingAsAdmin()
                ->putJson("/api/v1/admin/polys/{$poly->id}", [
                    'code' => $poly->code,
                    'name' => 'Updated Name',
                    'location' => 'Updated Location',
                    'is_active' => true,
                ])
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Polyclinic updated successfully',
                    'data' => [
                        'name' => 'Updated Name',
                        'location' => 'Updated Location',
                    ],
                ]);

            $poly->refresh();
            expect($poly->name)->toBe('Updated Name');
        });

        it('validates unique code on update', function () {
            $poly1 = Poly::factory()->create(['code' => 'POLI-001']);
            $poly2 = Poly::factory()->create(['code' => 'POLI-002']);

            actingAsAdmin()
                ->putJson("/api/v1/admin/polys/{$poly2->id}", [
                    'code' => 'POLI-001', // Already taken
                    'name' => 'Test',
                    'location' => 'Test',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['code']);
        });
    });

    describe('DELETE /api/v1/admin/polys/{id}', function () {

        it('can delete a polyclinic', function () {
            $poly = Poly::factory()->create();

            actingAsAdmin()
                ->deleteJson("/api/v1/admin/polys/{$poly->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Polyclinic deleted successfully',
                ]);

            expect(Poly::count())->toBe(0);
        });

        it('returns 404 when deleting non-existent polyclinic', function () {
            $fakeUuid = '99999999-9999-9999-9999-999999999999';

            actingAsAdmin()
                ->deleteJson("/api/v1/admin/polys/{$fakeUuid}")
                ->assertStatus(404);
        });
    });
});
