<?php

class EventTask
{
    public string $userAssignedName = '';
    public string $assignerName = '';
    public ?int $taskId;
    public int $eventId;
    public int $userId;
    public string $taskDescription;
    public int $assignedByUserId;
    public ?string $createdAt;

    public function __construct(
        int $eventId,
        int $userId,
        string $taskDescription,
        int $assignedByUserId,
        ?int $taskId = null,
        ?string $createdAt = null
    ) {
        $this->taskId = $taskId;
        $this->eventId = $eventId;
        $this->userId = $userId;
        $this->taskDescription = $taskDescription;
        $this->assignedByUserId = $assignedByUserId;
        $this->createdAt = $createdAt;
    }
}
