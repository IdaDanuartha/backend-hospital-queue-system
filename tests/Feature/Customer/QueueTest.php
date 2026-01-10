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
                        ],
                        'token',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Queue taken successfully',
                ]);

            expect(QueueTicket::count())->toBe(1);
        });

        it('validates queue_type_id', function () {
            postJson('/api/v1/customer/queue/take', [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['queue_type_id']);

            postJson('/api/v1/customer/queue/take', ['queue_type_id' => 'invalid-uuid'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['queue_type_id']);
        });

        it('fails if queue type is inactive', function () {
            $queueType = QueueType::factory()->create(['is_active' => false]);

            // QueueService might throw exception or return error
            // Need to verify response
            postJson('/api/v1/customer/queue/take', [
                'queue_type_id' => $queueType->id,
            ])
                ->assertStatus(400)
                ->assertJson([
                    'success' => false,
                ]);
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
