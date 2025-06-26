<?php

class EventRole
{
    public ?int $id;
    public int $userId;
    public int $eventId;
    public string $role;
    public ?string $assignedAt;

    public function __construct(int $userId, int $eventId, string $role, ?int $id = null, ?string $assignedAt = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->eventId = $eventId;
        $this->role = $role;
        $this->assignedAt = $assignedAt;
    }
}
