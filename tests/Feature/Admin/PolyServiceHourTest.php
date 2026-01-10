<?php

use App\Models\Poly;
use App\Models\PolyServiceHour;
use App\Enums\DayOfWeek;
use function Pest\Laravel\{postJson, putJson, deleteJson};

describe('Admin - Poly Service Hours Management', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('POST /api/v1/admin/poly-service-hours', function () {

        it('can create a service hour', function () {
            $poly = Poly::factory()->create();

            actingAsAdmin()
                ->postJson('/api/v1/admin/poly-service-hours', [
                    'poly_id' => $poly->id,
                    'day_of_week' => DayOfWeek::MONDAY->value,
                    'open_time' => '08:00',
                    'close_time' => '16:00',
                    'is_active' => true,
                ])
                ->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'poly_id',
                        'day_of_week',
                        'open_time',
                        'close_time',
                        'is_active',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Service hour created successfully',
                ]);

            expect(PolyServiceHour::count())->toBe(1);
        });

        it('validates required fields', function () {
            actingAsAdmin()
                ->postJson('/api/v1/admin/poly-service-hours', [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['poly_id', 'day_of_week', 'open_time', 'close_time']);
        });
    });

    describe('PUT /api/v1/admin/poly-service-hours/{id}', function () {

        it('can update a service hour', function () {
            $serviceHour = PolyServiceHour::factory()->create();

            actingAsAdmin()
                ->putJson("/api/v1/admin/poly-service-hours/{$serviceHour->id}", [
                    'day_of_week' => DayOfWeek::TUESDAY->value,
                    'open_time' => '09:00',
                    'close_time' => '17:00',
                    'is_active' => false,
                ])
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Service hour updated successfully',
                ]);

            $serviceHour->refresh();
            expect($serviceHour->day_of_week->value)->toBe(DayOfWeek::TUESDAY->value);
            expect($serviceHour->is_active)->toBeFalse();
        });
    });

    describe('DELETE /api/v1/admin/poly-service-hours/{id}', function () {

        it('can delete a service hour', function () {
            $serviceHour = PolyServiceHour::factory()->create();

            actingAsAdmin()
                ->deleteJson("/api/v1/admin/poly-service-hours/{$serviceHour->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Service hour deleted successfully',
                ]);

            expect(PolyServiceHour::count())->toBe(0);
        });
    });
});
