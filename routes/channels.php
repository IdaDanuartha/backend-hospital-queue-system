<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Private channel for authenticated users
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Public channels for queue display screens (no authentication required)
 * - queue.type.{queueTypeId} - Staff monitoring specific queue type
 * - queue.display - Public display screens showing all queues
 * 
 * These are public channels, so no authorization callback needed.
 * Front-end can subscribe using Laravel Echo:
 * 
 * Echo.channel('queue.type.' + queueTypeId)
 *     .listen('.queue.called', (e) => { ... })
 *     .listen('.queue.taken', (e) => { ... })
 *     .listen('.queue.status_updated', (e) => { ... })
 *     .listen('.queue.recalled', (e) => { ... });
 */
