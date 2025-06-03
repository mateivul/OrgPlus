<?php

class EventRole
{
    private ?int $id;
    private int $userId;
    private int $eventId;
    private string $role;
    private ?string $assignedAt;

    public function __construct(int $userId, int $eventId, string $role, ?int $id = null, ?string $assignedAt = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->eventId = $eventId;
        $this->role = $role;
        $this->assignedAt = $assignedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getAssignedAt(): ?string
    {
        return $this->assignedAt;
    }

    // Setters (dacă e cazul)
    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }
}
