<?php

class EventParticipant
{
    public int $userId;
    public string $userName;
    public string $userPrenume;
    public string $role;
    public int $eventId;

    public function __construct(int $userId, string $userName, string $userPrenume, string $role, int $eventId)
    {
        $this->userId = $userId;
        $this->userName = $userName;
        $this->userPrenume = $userPrenume;
        $this->role = $role;
        $this->eventId = $eventId;
    }
}
