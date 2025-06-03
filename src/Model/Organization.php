<?php

// namespace App\Model;

class Organization
{
    private int $id;
    private string $name;
    private string $description;
    private ?string $website;
    private ?string $createdAt; // <-- Adaugă asta
    private int $ownerId; // <-- Add this property
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
    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    // Setters (dacă ai nevoie de ele, dar pentru date preluate, nu e neapărat nevoie de toate)
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }
}
