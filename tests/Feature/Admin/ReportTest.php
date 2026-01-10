<?php

use App\Models\Poly;
use App\Models\QueueType;
use App\Models\QueueTicket;
use App\Enums\QueueStatus;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\getJson;

describe('Admin - Reports', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);

        // Create test data for reports
        $poly = Poly::factory()->create(['is_active' => true]);
        $queueType = QueueType::factory()->create([
            'poly_id' => $poly->id,
            'is_active' => true
        ]);

        // Create various queue tickets for testing
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
    });

    describe('GET /api/v1/admin/reports/statistics', function () {

        it('can get queue statistics', function () {
            // Skip on SQLite as it doesn't support TIMESTAMPDIFF
            if (DB::connection()->getDriverName() === 'sqlite') {
                $this->markTestSkipped('This test requires MySQL TIMESTAMPDIFF function');
            }

            actingAsAdmin()
                ->getJson('/api/v1/admin/reports/statistics?start_date=2026-01-01&end_date=2026-01-31')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'period',
                        'statistics',
                    ],
                ])
                ->assertJson(['success' => true]);
        });

        it('requires admin authentication', function () {
            getJson('/api/v1/admin/reports/statistics?start_date=2026-01-01&end_date=2026-01-31')
                ->assertStatus(401);
        });
    });

    describe('GET /api/v1/admin/reports/busiest-polys', function () {

        it('can get busiest polyclinics report', function () {
            // Skip on SQLite as it doesn't support TIMESTAMPDIFF
            if (DB::connection()->getDriverName() === 'sqlite') {
                $this->markTestSkipped('This test requires MySQL TIMESTAMPDIFF function');
            }

            actingAsAdmin()
                ->getJson('/api/v1/admin/reports/busiest-polys?start_date=2026-01-01&end_date=2026-01-31')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'total_queues',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);
        });

        it('can filter by date range', function () {
            // Skip on SQLite as it doesn't support TIMESTAMPDIFF
            if (DB::connection()->getDriverName() === 'sqlite') {
                $this->markTestSkipped('This test requires MySQL TIMESTAMPDIFF function');
            }

            actingAsAdmin()
                ->getJson('/api/v1/admin/reports/busiest-polys?start_date=2026-01-01&end_date=2026-01-31')
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    describe('GET /api/v1/admin/reports/busiest-hours', function () {

        it('can get busiest hours report', function () {
            // Skip on SQLite as it doesn't support HOUR function
            if (DB::connection()->getDriverName() === 'sqlite') {
                $this->markTestSkipped('This test requires MySQL HOUR function');
            }

            actingAsAdmin()
                ->getJson('/api/v1/admin/reports/busiest-hours?start_date=2026-01-01&end_date=2026-01-31')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'hour',
                            'total_queues',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);
        });
    });

    describe('GET /api/v1/admin/reports/daily-count', function () {

        it('can get daily queue count', function () {
            actingAsAdmin()
                ->getJson('/api/v1/admin/reports/daily-count?start_date=2026-01-01&end_date=2026-01-31')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'service_date',
                            'total_queues',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);
        });

        it('can filter by date range', function () {
            actingAsAdmin()
                ->getJson('/api/v1/admin/reports/daily-count?start_date=2026-01-01&end_date=2026-01-31')
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    describe('GET /api/v1/admin/reports/waiting-time-trend', function () {

        it('can get waiting time trend', function () {
            // Skip on SQLite as it doesn't support TIMESTAMPDIFF
            if (DB::connection()->getDriverName() === 'sqlite') {
                $this->markTestSkipped('This test requires MySQL TIMESTAMPDIFF function');
            }

            actingAsAdmin()
                ->getJson('/api/v1/admin/reports/waiting-time-trend?start_date=2026-01-01&end_date=2026-01-31')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'service_date',
                            'avg_waiting_minutes',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);
        });

        it('can filter by poly_id', function () {
            // Skip on SQLite as it doesn't support TIMESTAMPDIFF
            if (DB::connection()->getDriverName() === 'sqlite') {
                $this->markTestSkipped('This test requires MySQL TIMESTAMPDIFF function');
            }

            $poly = Poly::factory()->create();

            actingAsAdmin()
                ->getJson("/api/v1/admin/reports/waiting-time-trend?start_date=2026-01-01&end_date=2026-01-31&poly_id={$poly->id}")
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });
});
