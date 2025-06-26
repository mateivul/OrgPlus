<?php

// src/Entity/Request.php

class Request
{
    public ?int $id;
    public string $requestType; // e.g., 'organization_join_request', 'organization_invite_manual', 'event_join_request'
    public int $senderUserId;
    public int $receiverUserId; // Can be null if it's a broadcast request, but for invites it's required
    public int $organizationId; // Can be null for general requests
    public ?int $eventId; // *** Adăugat: Proprietatea eventId ***
    public string $status; // e.g., 'pending', 'accepted', 'rejected', 'cancelled'
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
        // *** Adăugat: Parametru eventId în constructor ***
        $this->id = $id;
        $this->requestType = $requestType;
        $this->senderUserId = $senderUserId;
        $this->receiverUserId = $receiverUserId;
        $this->organizationId = $organizationId;
        $this->status = $status;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->respondedAt = $respondedAt;
        $this->eventId = $eventId; // *** Adăugat: Inițializarea proprietății eventId ***
    }
}
