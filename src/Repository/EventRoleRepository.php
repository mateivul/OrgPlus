<?php

class EventRoleRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(EventRole $eventRole): bool
    {
        $sql = "INSERT INTO event_roles (user_id, event_id, role)
                VALUES (:user_id, :event_id, :role)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $eventRole->userId);
            $stmt->bindValue(':event_id', $eventRole->eventId);
            $stmt->bindValue(':role', $eventRole->role);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                error_log('Attempt to insert duplicate event role (ignored): ' . $e->getMessage());
                return true;
            }
            error_log('Error saving event role: ' . $e->getMessage());
            return false;
        }
    }

    public function findRolesByUserIdAndEventId(int $userId, int $eventId): array
    {
        $sql = 'SELECT * FROM event_roles WHERE user_id = :user_id AND event_id = :event_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        $roles = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = new EventRole(
                $data['user_id'],
                $data['event_id'],
                $data['role'],
                $data['id'],
                $data['assigned_at']
            );
        }
        return $roles;
    }

    public function findUsersWithRolesByEventId(int $eventId): array
    {
        $sql =
            'SELECT er.*, u.username FROM event_roles er JOIN users u ON er.user_id = u.id WHERE er.event_id = :event_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $eventRole = new EventRole(
                $data['user_id'],
                $data['event_id'],
                $data['role'],
                $data['id'],
                $data['assigned_at']
            );
            $results[] = [
                'event_role' => $eventRole,
                'username' => $data['username'],
            ];
        }
        return $results;
    }

    public function deleteRole(int $userId, int $eventId, string $role): bool
    {
        $sql = 'DELETE FROM event_roles WHERE user_id = :user_id AND event_id = :event_id AND role = :role';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error deleting event role: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteAllUserRolesForEvent(int $userId, int $eventId): bool
    {
        $sql = 'DELETE FROM event_roles WHERE user_id = :user_id AND event_id = :event_id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error deleting all user roles for event: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteAllRolesForEvent(int $eventId): bool
    {
        $sql = 'DELETE FROM event_roles WHERE event_id = :event_id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error deleting all roles for event: ' . $e->getMessage());
            return false;
        }
    }

    public function findParticipantsByEventId(int $eventId, string $searchTerm = ''): array
    {
        $sql = "
            SELECT
                er.user_id,
                u.name as user_name,
                u.prenume as user_prenume,
                er.role
            FROM
                event_roles er
            JOIN
                users u ON er.user_id = u.id
            WHERE
                er.event_id = :event_id
        ";

        if (!empty($searchTerm)) {
            $sql .= ' AND (u.name LIKE :search OR u.prenume LIKE :search OR er.role LIKE :search)';
        }

        $sql .= ' ORDER BY u.name, u.prenume';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);

            if (!empty($searchTerm)) {
                $stmt->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
            }

            $stmt->execute();
            $participants = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $participants[] = new EventParticipant(
                    $row['user_id'],
                    $row['user_name'],
                    $row['user_prenume'],
                    $row['role'],
                    $eventId
                );
            }
            return $participants;
        } catch (PDOException $e) {
            error_log('Error in findParticipantsByEventId: ' . $e->getMessage());
            return [];
        }
    }

    public function isUserAssignedToEventRole(int $userId, int $eventId): bool
    {
        $sql = 'SELECT COUNT(*) FROM event_roles WHERE user_id = :user_id AND event_id = :event_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function findEligibleMembersWithEventRoles(int $orgId, int $eventId, int $eventCreatorId): array
    {
        $sql = "
            SELECT
                u.id,
                u.name,
                u.prenume,
                COALESCE(er.role, '') AS role,
                (u.id = :eventCreatorId) AS is_creator
            FROM users u
            -- Participanți proveniți fie din invitații ACCEPTATE, fie deja existenți în event_roles
            INNER JOIN (
                SELECT receiver_user_id AS user_id
                FROM requests
                WHERE event_id = :eventId
                  AND request_type = 'event_invitation'
                  AND status = 'accepted'
                UNION
                SELECT user_id
                FROM event_roles
                WHERE event_id = :eventId2
            ) participants ON participants.user_id = u.id
            -- Rolul curent (dacă există)
            LEFT JOIN event_roles er ON u.id = er.user_id AND er.event_id = :eventId3

            UNION

            -- Adaugă explicit creatorul evenimentului
            SELECT
                u.id,
                u.name,
                u.prenume,
                COALESCE(er.role, '') AS role,
                1 AS is_creator
            FROM users u
            LEFT JOIN event_roles er ON u.id = er.user_id AND er.event_id = :eventId4
            WHERE u.id = :eventCreatorId2

            ORDER BY is_creator DESC, name ASC, prenume ASC
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':eventCreatorId', $eventCreatorId, PDO::PARAM_INT);
            $stmt->bindParam(':eventCreatorId2', $eventCreatorId, PDO::PARAM_INT);
            $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':eventId2', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':eventId3', $eventId, PDO::PARAM_INT);
            $stmt->bindParam(':eventId4', $eventId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Eroare în findEligibleMembersWithEventRoles: ' . $e->getMessage());
            return [];
        }
    }

    public function assignOrUpdateEventRole(int $eventId, int $userId, string $role): bool
    {
        $sql = 'INSERT INTO event_roles (event_id, user_id, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = ?';
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([$eventId, $userId, $role, $role]);
        } catch (\PDOException $e) {
            error_log('Error assigning/updating event role: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteEventRole(int $eventId, int $userId): bool
    {
        $sql = 'DELETE FROM event_roles WHERE event_id = ? AND user_id = ?';
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([$eventId, $userId]);
        } catch (\PDOException $e) {
            error_log('Error deleting event role: ' . $e->getMessage());
            return false;
        }
    }
}
