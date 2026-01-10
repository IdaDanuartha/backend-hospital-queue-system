<?php

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Enums\DayOfWeek;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

describe('Admin - Doctor Schedule Management', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('POST /api/v1/admin/schedules', function () {

        it('can create a schedule', function () {
            $doctor = Doctor::factory()->create();

            actingAsAdmin()
                ->postJson('/api/v1/admin/schedules', [
                    'doctor_id' => $doctor->id,
                    'day_of_week' => DayOfWeek::MONDAY->value,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'max_quota' => 20,
                ])
                ->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'doctor_id',
                        'day_of_week',
                        'start_time',
                        'end_time',
                        'max_quota',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Schedule created successfully',
                ]);

            expect(DoctorSchedule::count())->toBe(1);
        });

        it('validates required fields', function () {
            actingAsAdmin()
                ->postJson('/api/v1/admin/schedules', [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['doctor_id', 'day_of_week', 'start_time', 'end_time']);
        });

        it('validates time order', function () {
            $doctor = Doctor::factory()->create();

            actingAsAdmin()
                ->postJson('/api/v1/admin/schedules', [
                    'doctor_id' => $doctor->id,
                    'day_of_week' => 1,
                    'start_time' => '17:00',
                    'end_time' => '09:00',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['end_time']);
        });
    });

    describe('PUT /api/v1/admin/schedules/{id}', function () {

        it('can update a schedule', function () {
            $schedule = DoctorSchedule::factory()->create();

            actingAsAdmin()
                ->putJson("/api/v1/admin/schedules/{$schedule->id}", [
                    'day_of_week' => DayOfWeek::TUESDAY->value,
                    'start_time' => '10:00',
                    'end_time' => '16:00',
                    'max_quota' => 30,
                ])
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Schedule updated successfully',
                ]);

            $schedule->refresh();
            // Compare enum value
            expect($schedule->day_of_week->value)->toBe(DayOfWeek::TUESDAY->value);
            // Time casting might include seconds? Model casts to H:i.
            // Let's check assertJson handles it or we assert specific fields
        });
    });

    describe('DELETE /api/v1/admin/schedules/{id}', function () {

        it('can delete a schedule', function () {
            $schedule = DoctorSchedule::factory()->create();

            actingAsAdmin()
                ->deleteJson("/api/v1/admin/schedules/{$schedule->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Schedule deleted successfully',
                ]);

            expect(DoctorSchedule::count())->toBe(0);
        });
    });
});
