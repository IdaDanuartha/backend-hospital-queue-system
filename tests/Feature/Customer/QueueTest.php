<?php

use App\Models\QueueType;
use App\Models\QueueTicket;
use App\Models\Poly;
use App\Models\PublicQueueToken;
use App\Models\SystemSetting;
use function Pest\Laravel\{getJson, postJson};

describe('Customer - Queue Management', function () {

    beforeEach(function () {
        SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('POST /api/v1/customer/queue/take', function () {

        it('can take a queue number', function () {
            $queueType = QueueType::factory()->create(['is_active' => true]);

            postJson('/api/v1/customer/queue/take', [
                'queue_type_id' => $queueType->id,
                'patient_name' => 'John Doe',
            ])
                ->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'ticket' => [
                            'id',
                            'queue_number',
                            'status',
                            'patient_name',
                        ],
                        'token',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Queue taken successfully',
                ]);

            expect(QueueTicket::count())->toBe(1);
            expect(QueueTicket::first()->patient_name)->toBe('John Doe');
        });

        it('validates queue_type_id and patient_name', function () {
            postJson('/api/v1/customer/queue/take', [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['queue_type_id', 'patient_name']);

            postJson('/api/v1/customer/queue/take', ['queue_type_id' => 'invalid-uuid'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['queue_type_id', 'patient_name']);
        });

        it('fails if queue type is inactive', function () {
            $queueType = QueueType::factory()->create(['is_active' => false]);

            // QueueService might throw exception or return error
            // Need to verify response
            postJson('/api/v1/customer/queue/take', [
                'queue_type_id' => $queueType->id,
                'patient_name' => 'John Doe',
            ])
                ->assertStatus(400)
                ->assertJson([
                    'success' => false,
                ]);
        });

        it('prevents duplicate queue for same patient and queue type per day', function () {
            $queueType = QueueType::factory()->create(['is_active' => true]);

            // First queue should succeed
            postJson('/api/v1/customer/queue/take', [
                'queue_type_id' => $queueType->id,
                'patient_name' => 'John Doe',
            ])->assertStatus(201);

            // Second queue with same patient name (case-insensitive) should fail
            postJson('/api/v1/customer/queue/take', [
                'queue_type_id' => $queueType->id,
                'patient_name' => 'john doe',
            ])
                ->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Anda sudah mengambil antrian jenis ini hari ini',
                ]);

            expect(QueueTicket::count())->toBe(1);
        });

        it('allows same patient to take different queue types', function () {
            $queueType1 = QueueType::factory()->create(['is_active' => true]);
            $queueType2 = QueueType::factory()->create(['is_active' => true]);

            postJson('/api/v1/customer/queue/take', [
                'queue_type_id' => $queueType1->id,
                'patient_name' => 'John Doe',
            ])->assertStatus(201);

            postJson('/api/v1/customer/queue/take', [
                'queue_type_id' => $queueType2->id,
                'patient_name' => 'John Doe',
            ])->assertStatus(201);

            expect(QueueTicket::count())->toBe(2);
        });
    });

    describe('GET /api/v1/customer/queue/status/{token}', function () {

        it('can get queue status by token', function () {
            $ticket = QueueTicket::factory()->create();
            // Need to generate a public token for this ticket
            $token = PublicQueueToken::generate($ticket->id);

            getJson("/api/v1/customer/queue/status/{$token->token}")
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'ticket' => [
                            'id',
                            'queue_number',
                            'status',
                        ],
                        'current_queue',
                        'remaining_queues',
                        'estimated_waiting_minutes',
                    ],
                ]);
        });

        it('returns 404 for invalid token', function () {
            getJson('/api/v1/customer/queue/status/invalid-token')
                ->assertStatus(404);
        });
    });
});
