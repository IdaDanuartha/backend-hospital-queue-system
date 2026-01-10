<?php

use App\Models\Poly;
use App\Models\Doctor;
use App\Models\QueueType;
use App\Models\DoctorSchedule;
use App\Enums\DayOfWeek;
use function Pest\Laravel\getJson;

describe('Customer - Info API', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('GET /api/v1/customer/info/polys', function () {

        it('can get list of active polyclinics', function () {
            Poly::factory()->count(3)->create(['is_active' => true]);
            Poly::factory()->create(['is_active' => false]); // Inactive poly

            getJson('/api/v1/customer/info/polys')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'code',
                            'is_active',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);
        });

        it('does not require authentication', function () {
            getJson('/api/v1/customer/info/polys')
                ->assertStatus(200);
        });
    });

    describe('GET /api/v1/customer/info/queue-types', function () {

        it('can get list of active queue types', function () {
            $poly = Poly::factory()->create(['is_active' => true]);
            QueueType::factory()->count(2)->create([
                'poly_id' => $poly->id,
                'is_active' => true
            ]);
            QueueType::factory()->create(['is_active' => false]); // Inactive

            getJson('/api/v1/customer/info/queue-types')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'code_prefix',
                            'poly',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);
        });
    });

    describe('GET /api/v1/customer/info/doctors', function () {

        it('can get doctor schedules', function () {
            $poly = Poly::factory()->create();
            $doctor = Doctor::factory()->create(['poly_id' => $poly->id]);
            DoctorSchedule::factory()->count(2)->create([
                'doctor_id' => $doctor->id,
            ]);

            getJson('/api/v1/customer/info/doctors')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'specialization',
                            'schedules',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);
        });

        it('can filter by poly_id', function () {
            $poly1 = Poly::factory()->create();
            $poly2 = Poly::factory()->create();

            Doctor::factory()->create(['poly_id' => $poly1->id]);
            Doctor::factory()->create(['poly_id' => $poly2->id]);

            getJson("/api/v1/customer/info/doctors?poly_id={$poly1->id}")
                ->assertStatus(200)
                ->assertJsonCount(1, 'data');
        });
    });
});
