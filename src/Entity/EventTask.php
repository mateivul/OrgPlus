<?php

class EventTask
{
    // For display names (avoid dynamic property deprecation)
    public string $userAssignedName = '';
    public string $assignerName = '';
    private ?int $taskId;
    private int $eventId;
    private int $userId; // User who is assigned the task
    private string $taskDescription;
    private int $assignedByUserId; // User who assigned the task
    private ?string $createdAt;

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

    // --- Getters ---
    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTaskDescription(): string
    {
        return $this->taskDescription;
    }

    public function getAssignedByUserId(): int
    {
        return $this->assignedByUserId;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    // --- Setters (dacă e necesar) ---
    public function setTaskId(int $taskId): self
    {
        $this->taskId = $taskId;
        return $this;
    }

    public function setTaskDescription(string $taskDescription): self
    {
        $this->taskDescription = $taskDescription;
        return $this;
    }
    // Poți adăuga și alte settere dacă ai nevoie să modifici task-ul după creare
}
