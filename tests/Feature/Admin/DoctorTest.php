<?php

use App\Models\Doctor;
use App\Models\Poly;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

describe('Admin - Doctor Management', function () {

    beforeEach(function () {
        \App\Models\SystemSetting::create(['key' => 'geofencing_enabled', 'value' => 'false']);
    });

    describe('GET /api/v1/admin/doctors', function () {

        it('can list all doctors', function () {
            $poly = Poly::factory()->create();
            Doctor::factory()->count(3)->create(['poly_id' => $poly->id]);

            actingAsAdmin()
                ->getJson('/api/v1/admin/doctors')
                ->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'poly_id',
                            'sip_number',
                            'name',
                            'specialization',
                            'poly',
                        ],
                    ],
                ])
                ->assertJson(['success' => true]);

            expect(Doctor::count())->toBe(3);
        });

        it('can filter doctors by poly_id', function () {
            $poly1 = Poly::factory()->create();
            $poly2 = Poly::factory()->create();

            Doctor::factory()->count(2)->create(['poly_id' => $poly1->id]);
            Doctor::factory()->count(3)->create(['poly_id' => $poly2->id]);

            actingAsAdmin()
                ->getJson("/api/v1/admin/doctors?poly_id={$poly1->id}")
                ->assertStatus(200)
                ->assertJsonCount(2, 'data');
        });

        it('requires authentication', function () {
            getJson('/api/v1/admin/doctors')
                ->assertStatus(401);
        });
    });

    describe('POST /api/v1/admin/doctors', function () {

        it('can create a new doctor', function () {
            $poly = Poly::factory()->create();

            actingAsAdmin()
                ->postJson('/api/v1/admin/doctors', [
                    'poly_id' => $poly->id,
                    'sip_number' => 'SIP.123.456.789',
                    'name' => 'Dr. John Doe, Sp.PD',
                    'specialization' => 'Spesialis Penyakit Dalam',
                ])
                ->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'poly_id',
                        'sip_number',
                        'name',
                        'specialization',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Doctor created successfully',
                    'data' => [
                        'sip_number' => 'SIP.123.456.789',
                        'name' => 'Dr. John Doe, Sp.PD',
                    ],
                ]);

            expect(Doctor::count())->toBe(1);
        });

        it('validates required fields', function () {
            actingAsAdmin()
                ->postJson('/api/v1/admin/doctors', [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['poly_id', 'sip_number', 'name']);
        });

        it('validates unique sip_number', function () {
            $poly = Poly::factory()->create();
            Doctor::factory()->create(['sip_number' => 'SIP.123', 'poly_id' => $poly->id]);

            actingAsAdmin()
                ->postJson('/api/v1/admin/doctors', [
                    'poly_id' => $poly->id,
                    'sip_number' => 'SIP.123',
                    'name' => 'Dr. Jane',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['sip_number']);
        });

        it('validates poly_id exists', function () {
            actingAsAdmin()
                ->postJson('/api/v1/admin/doctors', [
                    'poly_id' => '99999999-9999-9999-9999-999999999999',
                    'sip_number' => 'SIP.123',
                    'name' => 'Dr. John',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['poly_id']);
        });

        it('requires authentication', function () {
            postJson('/api/v1/admin/doctors', [
                'sip_number' => 'SIP.123',
                'name' => 'Dr. John',
            ])
                ->assertStatus(401);
        });
    });

    describe('GET /api/v1/admin/doctors/{id}', function () {

        it('can show doctor detail', function () {
            $poly = Poly::factory()->create();
            $doctor = Doctor::factory()->create(['poly_id' => $poly->id]);

            actingAsAdmin()
                ->getJson("/api/v1/admin/doctors/{$doctor->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $doctor->id,
                        'sip_number' => $doctor->sip_number,
                        'name' => $doctor->name,
                    ],
                ]);
        });

        it('returns 404 for non-existent doctor', function () {
            $fakeUuid = '99999999-9999-9999-9999-999999999999';

            actingAsAdmin()
                ->getJson("/api/v1/admin/doctors/{$fakeUuid}")
                ->assertStatus(404);
        });
    });

    describe('PUT /api/v1/admin/doctors/{id}', function () {

        it('can update a doctor', function () {
            $poly = Poly::factory()->create();
            $doctor = Doctor::factory()->create(['poly_id' => $poly->id]);

            actingAsAdmin()
                ->putJson("/api/v1/admin/doctors/{$doctor->id}", [
                    'poly_id' => $poly->id,
                    'sip_number' => $doctor->sip_number,
                    'name' => 'Dr. Updated Name',
                    'specialization' => 'Updated Specialization',
                ])
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Doctor updated successfully',
                    'data' => [
                        'name' => 'Dr. Updated Name',
                        'specialization' => 'Updated Specialization',
                    ],
                ]);

            $doctor->refresh();
            expect($doctor->name)->toBe('Dr. Updated Name');
        });

        it('validates unique sip_number on update', function () {
            $poly = Poly::factory()->create();
            $doctor1 = Doctor::factory()->create(['sip_number' => 'SIP.111', 'poly_id' => $poly->id]);
            $doctor2 = Doctor::factory()->create(['sip_number' => 'SIP.222', 'poly_id' => $poly->id]);

            actingAsAdmin()
                ->putJson("/api/v1/admin/doctors/{$doctor2->id}", [
                    'poly_id' => $poly->id,
                    'sip_number' => 'SIP.111', // Already taken by doctor1
                    'name' => 'Dr. Test',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['sip_number']);
        });
    });

    describe('DELETE /api/v1/admin/doctors/{id}', function () {

        it('can delete a doctor', function () {
            $poly = Poly::factory()->create();
            $doctor = Doctor::factory()->create(['poly_id' => $poly->id]);

            actingAsAdmin()
                ->deleteJson("/api/v1/admin/doctors/{$doctor->id}")
                ->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Doctor deleted successfully',
                ]);

            expect(Doctor::count())->toBe(0);
        });

        it('returns 404 when deleting non-existent doctor', function () {
            $fakeUuid = '99999999-9999-9999-9999-999999999999';

            actingAsAdmin()
                ->deleteJson("/api/v1/admin/doctors/{$fakeUuid}")
                ->assertStatus(404);
        });
    });
});
