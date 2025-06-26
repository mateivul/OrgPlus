<?php
class RequestRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hasPendingOrAcceptedJoinRequest(int $userId, int $organizationId): bool
    {
        $sql = "
            SELECT 1 FROM requests
            WHERE sender_user_id = :userId
            AND organization_id = :organizationId
            AND request_type = 'organization_join_request'
            AND status IN ('pending', 'accepted')
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':organizationId', $organizationId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }

    public function createJoinRequest(int $userId, int $organizationId): bool
    {
        $sql = "
            INSERT INTO requests (request_type, sender_user_id, organization_id, status)
            VALUES (?, ?, ?, 'pending')
        ";
        $stmt = $this->pdo->prepare($sql);
        $requestType = 'organization_join_request';
        return $stmt->execute([$requestType, $userId, $organizationId]);
    }

    public function save(Request $request): bool
    {
        $sql = "INSERT INTO requests (request_type, sender_user_id, receiver_user_id, organization_id, event_id, status, created_at)
                VALUES (:request_type, :sender_user_id, :receiver_user_id, :organization_id, :event_id, :status, NOW())";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'request_type' => $request->requestType,
                'sender_user_id' => $request->senderUserId,
                'receiver_user_id' => $request->receiverUserId,
                'organization_id' => $request->organizationId,
                'event_id' => $request->eventId,
                'status' => $request->status,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                // SQLSTATE for Integrity Constraint Violation
                error_log('Tentativă de a insera cerere duplicat (ignorat): ' . $e->getMessage());
                return true; // Considerăm succes chiar dacă a fost duplicat
            }
            error_log('Eroare la salvarea cererii: ' . $e->getMessage());
            return false;
        }
    }

    public function findExistingRequest(
        string $requestType,
        int $senderUserId,
        int $receiverUserId,
        int $organizationId,
        ?int $eventId = null,
        array $statuses = []
    ): ?Request {
        $sql =
            'SELECT * FROM requests WHERE request_type = :request_type AND sender_user_id = :sender_user_id AND receiver_user_id = :receiver_user_id AND organization_id = :organization_id';

        if ($eventId !== null) {
            $sql .= ' AND event_id = :event_id';
        } else {
            $sql .= ' AND event_id IS NULL';
        }

        $inPlaceholders = [];
        if (!empty($statuses)) {
            foreach ($statuses as $i => $status) {
                $inPlaceholders[] = ":status{$i}";
            }
            $sql .= ' AND status IN (' . implode(',', $inPlaceholders) . ')';
        }

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindParam(':request_type', $requestType, PDO::PARAM_STR);
        $stmt->bindParam(':sender_user_id', $senderUserId, PDO::PARAM_INT);
        $stmt->bindParam(':receiver_user_id', $receiverUserId, PDO::PARAM_INT);
        $stmt->bindParam(':organization_id', $organizationId, PDO::PARAM_INT);

        if ($eventId !== null) {
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        }

        if (!empty($statuses)) {
            foreach ($statuses as $i => $status) {
                $stmt->bindValue(":status{$i}", $status, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Request(
            $data['request_type'],
            $data['sender_user_id'],
            $data['receiver_user_id'],
            $data['organization_id'],
            $data['status'],
            $data['created_at'],
            $data['id'],
            $data['responded_at'] ?? null,
            $data['event_id']
        );
    }

    public function findEventInvitationsForUser(int $receiverUserId, int $orgId, array $statuses = ['pending']): array
    {
        $sql = "SELECT r.*, e.name AS event_name, u.username AS sender_username
                FROM requests r
                JOIN events e ON r.event_id = e.id
                JOIN users u ON r.sender_user_id = u.id
                WHERE r.request_type = 'event_invitation'
                AND r.receiver_user_id = :receiver_user_id
                AND r.organization_id = :org_id";

        $inPlaceholders = [];
        if (!empty($statuses)) {
            foreach ($statuses as $i => $status) {
                $inPlaceholders[] = ":status{$i}";
            }
            $sql .= ' AND r.status IN (' . implode(',', $inPlaceholders) . ')';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':receiver_user_id', $receiverUserId, PDO::PARAM_INT);
        $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);

        if (!empty($statuses)) {
            foreach ($statuses as $i => $status) {
                $stmt->bindValue(":status{$i}", $status, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        $requests = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $request = new Request(
                $data['request_type'],
                $data['sender_user_id'],
                $data['receiver_user_id'],
                $data['organization_id'],
                $data['status'],
                $data['created_at'],
                $data['id'],
                $data['responded_at'] ?? null,
                $data['event_id']
            );
            $request->event_name = $data['event_name'];
            $request->sender_username = $data['sender_username'];
            $requests[] = $request;
        }
        return $requests;
    }

    public function updateRequestStatus(int $requestId, string $status): bool
    {
        $sql = 'UPDATE requests SET status = :status WHERE id = :id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':id', $requestId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Eroare la actualizarea stării cererii: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteRequestsRelatedToUserAndOrg(int $orgId, int $userId): bool
    {
        $sql = "DELETE FROM requests
                WHERE organization_id = :org_id
                  AND (
                          (request_type = 'organization_join_request' AND sender_user_id = :user_id) OR
                          (request_type = 'organization_invite_manual' AND receiver_user_id = :user_id_receiver) OR
                          (request_type = 'organization_join_request_response' AND (sender_user_id = :user_id_sender_response OR receiver_user_id = :user_id_receiver_response)) OR
                          (request_type = 'event_invitation' AND (sender_user_id = :user_id_event_sender OR receiver_user_id = :user_id_event_receiver))
                      )";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'org_id' => $orgId,
                'user_id' => $userId,
                'user_id_receiver' => $userId,
                'user_id_sender_response' => $userId,
                'user_id_receiver_response' => $userId,
                'user_id_event_sender' => $userId,
                'user_id_event_receiver' => $userId,
            ]);
        } catch (PDOException $e) {
            error_log('Eroare la ștergerea cererilor asociate: ' . $e->getMessage());
            return false;
        }
    }
}
