<?php

namespace App\Repositories\Contracts;

interface QueueTicketRepositoryInterface extends BaseRepositoryInterface
{
    public function getTodayQueueByType($queueTypeId);
    public function getNextQueueNumber($queueTypeId, $serviceDate);
    public function getCurrentQueue($queueTypeId, $serviceDate);
    public function getWaitingQueues($queueTypeId, $serviceDate);
    public function updateStatus($id, $status, $staffId = null);
}