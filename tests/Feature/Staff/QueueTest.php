<?php

use App\Models\QueueType;
use App\Models\QueueTicket;
use App\Models\Poly;
use App\Models\Staff;
use App\Models\User;
use App\Enums\QueueStatus;
use Tymon\JWTAuth\Facades\JWTAuth;
use function Pest\Laravel\{getJson, postJson, withToken};

describe('Staff - Queue Management', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('GET /api/v1/staff/queue/today', function () {

        it('can get today queues', function () {
            $poly = Poly::factory()->create();
            $staff = Staff::factory()->create(['poly_id' => $poly->id]);
            $user = $staff->user;
            $token = JWTAuth::fromUser($user);

            $queueType = QueueType::factory()->create(['poly_id' => $poly->id, 'is_active' => true]);
            QueueTicket::factory()->count(3)->create([
                'queue_type_id' => $queueType->id,
                'service_date' => today(),
            ]);

            withToken($token)
                ->getJson('/api/v1/staff/queue/today')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                ])
                ->assertJson(['success' => true]);
        });
    });

    describe('POST /api/v1/staff/queue/call-next', function () {

        it('can call next queue', function () {
            $poly = Poly::factory()->create();
            $staff = Staff::factory()->create(['poly_id' => $poly->id]);
            $user = $staff->user;
            $token = JWTAuth::fromUser($user);

            $queueType = QueueType::factory()->create(['poly_id' => $poly->id, 'is_active' => true]);
            $ticket = QueueTicket::factory()->create([
                'queue_type_id' => $queueType->id,
                'status' => QueueStatus::WAITING,
                'service_date' => today(),
            ]);

            withToken($token)
                ->postJson('/api/v1/staff/queue/call-next', [
                    'queue_type_id' => $queueType->id,
                ])
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Queue called successfully',
                    'data' => [
                        'id' => $ticket->id,
                        'status' => QueueStatus::CALLED->value,
                    ]
                ]);

            expect($ticket->fresh()->status)->toBe(QueueStatus::CALLED);
            expect($ticket->fresh()->handled_by_staff_id)->toBe($staff->id);
        });

        it('fails if no waiting queue', function () {
            $poly = Poly::factory()->create();
            $staff = Staff::factory()->create(['poly_id' => $poly->id]);
            $user = $staff->user;
            $token = JWTAuth::fromUser($user);
            $queueType = QueueType::factory()->create(['poly_id' => $poly->id]);

            withToken($token)
                ->postJson('/api/v1/staff/queue/call-next', [
                    'queue_type_id' => $queueType->id,
                ])
                ->assertStatus(400)
                ->assertJson(['success' => false]);
        });
    });

    describe('Queue Actions', function () {

        it('can start service', function () {
            $poly = Poly::factory()->create();
            $staff = Staff::factory()->create(['poly_id' => $poly->id]);
            $user = $staff->user;
            $token = JWTAuth::fromUser($user);

            $queueType = QueueType::factory()->create(['poly_id' => $poly->id]);
            $ticket = QueueTicket::factory()->create([
                'queue_type_id' => $queueType->id,
                'status' => QueueStatus::CALLED,
                'handled_by_staff_id' => $staff->id,
            ]);

            withToken($token)
                ->postJson("/api/v1/staff/queue/{$ticket->id}/start-service")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Service started',
                    'data' => [
                        'status' => QueueStatus::SERVING->value,
                    ]
                ]);

            expect($ticket->fresh()->status)->toBe(QueueStatus::SERVING);
        });

        it('can finish service', function () {
            $poly = Poly::factory()->create();
            $staff = Staff::factory()->create(['poly_id' => $poly->id]);
            $user = $staff->user;
            $token = JWTAuth::fromUser($user);

            $queueType = QueueType::factory()->create(['poly_id' => $poly->id]);
            $ticket = QueueTicket::factory()->create([
                'queue_type_id' => $queueType->id,
                'status' => QueueStatus::SERVING,
                'handled_by_staff_id' => $staff->id,
            ]);

            withToken($token)
                ->postJson("/api/v1/staff/queue/{$ticket->id}/finish-service", [
                    'notes' => 'Patient treated',
                ])
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Service finished',
                    'data' => [
                        'status' => QueueStatus::DONE->value,
                    ]
                ]);

            expect($ticket->fresh()->status)->toBe(QueueStatus::DONE);
            expect($ticket->fresh()->notes)->toBe('Patient treated');
        });

        it('can skip queue', function () {
            $poly = Poly::factory()->create();
            $staff = Staff::factory()->create(['poly_id' => $poly->id]);
            $user = $staff->user;
            $token = JWTAuth::fromUser($user);

            $queueType = QueueType::factory()->create(['poly_id' => $poly->id]);
            $ticket = QueueTicket::factory()->create([
                'queue_type_id' => $queueType->id,
                'status' => QueueStatus::CALLED,
                'handled_by_staff_id' => $staff->id,
            ]);

            withToken($token)
                ->postJson("/api/v1/staff/queue/{$ticket->id}/skip", [
                    'remark' => 'Patient not responding',
                ])
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Queue skipped',
                    'data' => [
                        'status' => QueueStatus::SKIPPED->value,
                    ]
                ]);

            expect($ticket->fresh()->status)->toBe(QueueStatus::SKIPPED);
        });
    });
});
