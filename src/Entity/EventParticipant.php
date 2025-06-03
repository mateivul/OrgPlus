<?php

class EventParticipant
{
    private int $userId;
    private string $userName;
    private string $userPrenume;
    private string $role;
    private int $eventId;

    public function __construct(int $userId, string $userName, string $userPrenume, string $role, int $eventId)
    {
        $this->userId = $userId;
        $this->userName = $userName;
        $this->userPrenume = $userPrenume;
        $this->role = $role;
        $this->eventId = $eventId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
    public function getUserName(): string
    {
        return $this->userName;
    }
    public function getUserPrenume(): string
    {
        return $this->userPrenume;
    }
    public function getRole(): string
    {
        return $this->role;
    }
    public function getEventId(): int
    {
        return $this->eventId;
    }
}
