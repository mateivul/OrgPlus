<?php

class Request
{
    public ?int $id;
    public string $requestType;
    public int $senderUserId;
    public int $receiverUserId;
    public int $organizationId;
    public ?int $eventId;
    public string $status;
    public string $createdAt;
    public ?string $respondedAt;

    public function __construct(
        string $requestType,
        int $senderUserId,
        int $receiverUserId,
        int $organizationId,
        string $status = 'pending',
        ?string $createdAt = null,
        ?int $id = null,
        ?string $respondedAt = null,
        ?int $eventId = null
    ) {
        $this->id = $id;
        $this->requestType = $requestType;
        $this->senderUserId = $senderUserId;
        $this->receiverUserId = $receiverUserId;
        $this->organizationId = $organizationId;
        $this->status = $status;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->respondedAt = $respondedAt;
        $this->eventId = $eventId;
    }
}
