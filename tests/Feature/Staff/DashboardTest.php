<?php

use App\Models\Poly;
use App\Models\QueueType;
use App\Models\QueueTicket;
use App\Models\Staff;
use App\Enums\QueueStatus;
use Tymon\JWTAuth\Facades\JWTAuth;
use function Pest\Laravel\{getJson, withToken};

describe('Staff - Dashboard', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('GET /api/v1/staff/dashboard', function () {

        it('can get staff dashboard statistics', function () {
            $poly = Poly::factory()->create();
            $staff = Staff::factory()->create(['poly_id' => $poly->id]);
            $user = $staff->user;
            $token = JWTAuth::fromUser($user);

            $queueType = QueueType::factory()->create([
                'poly_id' => $poly->id,
                'is_active' => true
            ]);

            QueueTicket::factory()->count(3)->create([
                'queue_type_id' => $queueType->id,
                'service_date' => today(),
                'status' => QueueStatus::WAITING,
            ]);

            withToken($token)
                ->getJson('/api/v1/staff/dashboard')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                ])
                ->assertJson(['success' => true]);
        });

        it('requires staff authentication', function () {
            getJson('/api/v1/staff/dashboard')
                ->assertStatus(401);
        });
    });
});
