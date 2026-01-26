<?php

use App\Models\Poly;
use App\Models\QueueType;
use App\Models\QueueTicket;
use App\Enums\QueueStatus;
use function Pest\Laravel\getJson;

describe('Admin - Dashboard', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('GET /api/v1/admin/dashboard', function () {

        it('can get dashboard statistics', function () {
            // Create test data
            $poly = Poly::factory()->create(['is_active' => true]);
            $queueType = QueueType::factory()->create([
                'poly_id' => $poly->id,
                'is_active' => true
            ]);

            QueueTicket::factory()->count(5)->create([
                'queue_type_id' => $queueType->id,
                'service_date' => today(),
                'status' => QueueStatus::WAITING,
            ]);

            QueueTicket::factory()->count(3)->create([
                'queue_type_id' => $queueType->id,
                'service_date' => today(),
                'status' => QueueStatus::DONE,
            ]);

            actingAsAdmin()
                ->getJson('/api/v1/admin/dashboard')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'poly',
                            'total_today',
                            'waiting',
                            'serving',
                            'done',
                            'avg_service_time',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);
        });

        it('requires admin authentication', function () {
            getJson('/api/v1/admin/dashboard')
                ->assertStatus(401);
        });
    });
});
