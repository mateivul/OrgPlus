<?php

class Event
{
    // For organization name and owner id (to avoid dynamic property deprecation)
    public string $orgName = '';
    public $orgOwnerId = null;
    public ?int $id;
    public string $name;
    public string $description;
    public string $date; // Preferabil un obiect DateTime, dar pentru simplitate initiala ramane string
    public int $orgId;
    public int $createdBy;
    public string $availableRoles;
    public ?string $createdAt; // Pentru TIMESTAMP DEFAULT CURRENT_TIMESTAMP

    public function __construct(
        string $name,
        string $description,
        string $date,
        int $orgId,
        int $createdBy,
        string $availableRoles,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->date = $date;
        $this->orgId = $orgId;
        $this->createdBy = $createdBy;
        $this->availableRoles = $availableRoles;
        $this->createdAt = $createdAt;
    }
}
