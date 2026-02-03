<?php

use App\Models\QueueTicket;
use App\Models\QueueType;
use App\Jobs\CleanupOldQueues;
use function Pest\Laravel\{assertDatabaseCount, assertDatabaseHas, assertDatabaseMissing};

describe('CleanupOldQueues Job', function () {

    it('deletes queue tickets from previous days', function () {
        $queueType = QueueType::factory()->create(['is_active' => true]);

        // Create an old ticket (yesterday)
        $oldTicket = QueueTicket::factory()->create([
            'queue_type_id' => $queueType->id,
            'service_date' => now()->subDay()->toDateString(),
        ]);

        // Create a today's ticket
        $todayTicket = QueueTicket::factory()->create([
            'queue_type_id' => $queueType->id,
            'service_date' => today()->toDateString(),
        ]);

        // Run the cleanup job
        CleanupOldQueues::dispatchSync();

        // Assert old ticket is deleted
        assertDatabaseMissing('queue_tickets', ['id' => $oldTicket->id]);

        // Assert today's ticket still exists
        assertDatabaseHas('queue_tickets', ['id' => $todayTicket->id]);
    });

    it('keeps todays queue tickets', function () {
        $queueType = QueueType::factory()->create(['is_active' => true]);

        // Create multiple tickets for today
        QueueTicket::factory()->count(3)->create([
            'queue_type_id' => $queueType->id,
            'service_date' => today()->toDateString(),
        ]);

        // Run the cleanup job
        CleanupOldQueues::dispatchSync();

        // All today's tickets should remain
        assertDatabaseCount('queue_tickets', 3);
    });

    it('deletes tickets from multiple previous days', function () {
        $queueType = QueueType::factory()->create(['is_active' => true]);

        // Create tickets from various past days
        QueueTicket::factory()->create([
            'queue_type_id' => $queueType->id,
            'service_date' => now()->subDays(1)->toDateString(),
        ]);

        QueueTicket::factory()->create([
            'queue_type_id' => $queueType->id,
            'service_date' => now()->subDays(7)->toDateString(),
        ]);

        QueueTicket::factory()->create([
            'queue_type_id' => $queueType->id,
            'service_date' => now()->subMonth()->toDateString(),
        ]);

        // Create a today ticket
        $todayTicket = QueueTicket::factory()->create([
            'queue_type_id' => $queueType->id,
            'service_date' => today()->toDateString(),
        ]);

        // Run the cleanup job
        CleanupOldQueues::dispatchSync();

        // Only today's ticket should remain
        assertDatabaseCount('queue_tickets', 1);
        assertDatabaseHas('queue_tickets', ['id' => $todayTicket->id]);
    });
});
