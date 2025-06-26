<?php

// namespace App\Model;

class Organization
{
    public int $id;
    public string $name;
    public string $description;
    public ?string $website;
    public ?string $createdAt;
    public int $ownerId;
    // Puteți adăuga și alte proprietăți (created_at, updated_at etc.)

    // Proprietăți temporare pentru a le folosi în view, populat de OrganizationRepository
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
