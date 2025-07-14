<?php

class Event
{
    public string $orgName = '';
    public $orgOwnerId = null;
    public ?int $id;
    public string $name;
    public string $description;
    public string $date;
    public int $orgId;
    public int $createdBy;
    public string $availableRoles;
    public ?string $createdAt;

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
