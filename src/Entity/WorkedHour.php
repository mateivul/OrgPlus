<?php

class WorkedHour
{
    private ?int $id;
    private int $userId;
    private int $orgId;
    private float $hours;
    private string $workDate; // Stored as 'YYYY-MM-DD'
    private ?string $description;
    private int $recordedBy;
    private ?string $createdAt;
    private ?string $updatedAt;
    private string $status;

    private string $user_name = '';
    private string $user_prenume = '';
    private string $recorded_by_name = '';
    private string $recorded_by_prenume = '';

    private $recorded_at;

    public function __construct(
        int $userId,
        int $orgId,
        float $hours,
        string $workDate,
        int $recordedBy,
        ?string $description = null,
        string $status = 'pending',
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        $recorded_at = null
    ) {
        $this->userId = $userId;
        $this->orgId = $orgId;
        $this->hours = $hours;
        $this->workDate = $workDate;
        $this->recordedBy = $recordedBy;
        $this->description = $description;
        $this->status = $status;
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->recorded_at = $recorded_at;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUserId(): int
    {
        return $this->userId;
    }
    public function getOrgId(): int
    {
        return $this->orgId;
    }
    public function getHours(): float
    {
        return $this->hours;
    }
    public function getWorkDate(): string
    {
        return $this->workDate;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function getRecordedBy(): int
    {
        return $this->recordedBy;
    }
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getUserName(): string
    {
        return $this->user_name;
    }
    public function getUserPrenume(): string
    {
        return $this->user_prenume;
    }
    public function getRecordedByName(): string
    {
        return $this->recorded_by_name;
    }
    public function getRecordedByPrenume(): string
    {
        return $this->recorded_by_prenume;
    }
    public function getRecordedAt(): string
    {
        return $this->recorded_at ?? '';
    }

    // Setters (only for mutable fields or fields set after creation like ID)
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
    public function setUserName(string $name): void
    {
        $this->user_name = $name;
    }
    public function setUserPrenume(string $prenume): void
    {
        $this->user_prenume = $prenume;
    }
    public function setRecordedByName(string $name): void
    {
        $this->recorded_by_name = $name;
    }
    public function setRecordedByPrenume(string $prenume): void
    {
        $this->recorded_by_prenume = $prenume;
    }
    // You might add setters for hours, workDate if you allow editing, but for now, keep it simple.
}
