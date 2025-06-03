<?php

class Event
{
    // For organization name and owner id (to avoid dynamic property deprecation)
    public string $orgName = '';
    public $orgOwnerId = null;
    private ?int $id;
    private string $name;
    private string $description;
    private string $date; // Preferabil un obiect DateTime, dar pentru simplitate initiala ramane string
    private int $orgId;
    private int $createdBy;
    private string $availableRoles;
    private ?string $createdAt; // Pentru TIMESTAMP DEFAULT CURRENT_TIMESTAMP

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

    public function getId(): ?int
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

    // Returnează data ca string, o poți converti ulterior în DateTime dacă este necesar
    public function getDate(): string
    {
        return $this->date;
    }

    public function getOrgId(): int
    {
        return $this->orgId;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getAvailableRoles(): string
    {
        return $this->availableRoles;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    // Setters (pentru cazurile în care vrei să modifici un obiect Event)
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function setOrgId(int $orgId): self
    {
        $this->orgId = $orgId;
        return $this;
    }

    public function setCreatedBy(int $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function setAvailableRoles(string $availableRoles): self
    {
        $this->availableRoles = $availableRoles;
        return $this;
    }
}
