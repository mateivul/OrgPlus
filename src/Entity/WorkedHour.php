<?php

class WorkedHour
{
    public ?int $id;
    public int $userId;
    public int $orgId;
    public float $hours;
    public string $workDate; // Stored as 'YYYY-MM-DD'
    public ?string $description;
    public int $recordedBy;
    public ?string $createdAt;
    public ?string $updatedAt;
    public string $status;

    public string $user_name = '';
    public string $user_prenume = '';
    public string $recorded_by_name = '';
    public string $recorded_by_prenume = '';

    public $recorded_at;

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
}
