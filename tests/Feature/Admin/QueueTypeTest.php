<?php

use App\Models\QueueType;
use App\Models\Poly;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

describe('Admin - Queue Type Management', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('GET /api/v1/admin/queue-types', function () {

        it('can list all queue types', function () {
            QueueType::factory()->count(3)->create();

            actingAsAdmin()
                ->getJson('/api/v1/admin/queue-types')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'poly_id',
                            'code_prefix',
                            'name',
                            'service_unit',
                            'avg_service_minutes',
                            'is_active',
                            'poly',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);

            expect(QueueType::count())->toBe(3);
        });

        it('requires authentication', function () {
            getJson('/api/v1/admin/queue-types')
                ->assertStatus(401);
        });
    });

    describe('POST /api/v1/admin/queue-types', function () {

        it('can create a queue type', function () {
            $poly = Poly::factory()->create();

            actingAsAdmin()
                ->postJson('/api/v1/admin/queue-types', [
                    'poly_id' => $poly->id,
                    'code_prefix' => 'QT',
                    'name' => 'Antrian Umum',
                    'service_unit' => 'Loket 1',
                    'avg_service_minutes' => 15,
                    'is_active' => true,
                ])
                ->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'poly_id',
                        'code_prefix',
                        'name',
                        'service_unit',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Queue type created successfully',
                ]);

            expect(QueueType::count())->toBe(1);
        });

        it('validates required fields', function () {
            actingAsAdmin()
                ->postJson('/api/v1/admin/queue-types', [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'code_prefix']);
        });
    });

    describe('GET /api/v1/admin/queue-types/{id}', function () {

        it('can show queue type detail', function () {
            $queueType = QueueType::factory()->create();

            actingAsAdmin()
                ->getJson("/api/v1/admin/queue-types/{$queueType->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $queueType->id,
                        'code_prefix' => $queueType->code_prefix,
                        'name' => $queueType->name,
                    ],
                ]);
        });
    });

    describe('PUT /api/v1/admin/queue-types/{id}', function () {

        it('can update a queue type', function () {
            $queueType = QueueType::factory()->create();

            actingAsAdmin()
                ->putJson("/api/v1/admin/queue-types/{$queueType->id}", [
                    'name' => 'Updated Name',
                    'code_prefix' => 'UP',
                    'is_active' => false,
                ])
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Queue type updated successfully',
                ]);

            $queueType->refresh();
            expect($queueType->name)->toBe('Updated Name');
            expect($queueType->code_prefix)->toBe('UP');
            expect($queueType->is_active)->toBeFalse();
        });
    });

    describe('DELETE /api/v1/admin/queue-types/{id}', function () {

        it('can delete a queue type', function () {
            $queueType = QueueType::factory()->create();

            actingAsAdmin()
                ->deleteJson("/api/v1/admin/queue-types/{$queueType->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Queue type deleted successfully',
                ]);

            expect(QueueType::count())->toBe(0);
        });
    });
});
