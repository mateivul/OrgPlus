<?php

// src/Entity/Request.php

class Request
{
    private ?int $id;
    private string $requestType; // e.g., 'organization_join_request', 'organization_invite_manual', 'event_join_request'
    private int $senderUserId;
    private int $receiverUserId; // Can be null if it's a broadcast request, but for invites it's required
    private int $organizationId; // Can be null for general requests
    private ?int $eventId; // *** Adăugat: Proprietatea eventId ***
    private string $status; // e.g., 'pending', 'accepted', 'rejected', 'cancelled'
    private string $createdAt;
    private ?string $respondedAt;

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

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function getSenderUserId(): int
    {
        return $this->senderUserId;
    }

    public function getReceiverUserId(): int
    {
        return $this->receiverUserId;
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

    // *** Adăugat: Metoda getEventId() ***
    public function getEventId(): ?int
    {
        return $this->eventId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getRespondedAt(): ?string
    {
        return $this->respondedAt;
    }

    // Setters (if needed, or handle immutable objects)
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setRespondedAt(?string $respondedAt): self
    {
        $this->respondedAt = $respondedAt;
        return $this;
    }

    // *** Puteți adăuga un setter pentru eventId dacă este necesar ***
    public function setEventId(?int $eventId): self
    {
        $this->eventId = $eventId;
        return $this;
    }
}
