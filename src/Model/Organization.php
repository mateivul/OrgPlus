<?php

class Organization
{
    public int $id;
    public string $name;
    public string $description;
    public ?string $website;
    public ?string $createdAt;
    public int $ownerId;

    public bool $isEnrolled;
    public bool $isRequestPending;
    public ?string $userRole;
    public int $memberCount;

    public function __construct(
        int $id,
        string $name,
        string $description,
        ?string $website,
        ?string $createdAt = null,
        int $ownerId = 0
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->website = $website;
        $this->createdAt = $createdAt;
        $this->ownerId = $ownerId;
    }
}
