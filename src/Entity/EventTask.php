<?php

class EventTask
{
    // For display names (avoid dynamic property deprecation)
    public string $userAssignedName = '';
    public string $assignerName = '';
    public ?int $taskId;
    public int $eventId;
    public int $userId; // User who is assigned the task
    public string $taskDescription;
    public int $assignedByUserId; // User who assigned the task
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
