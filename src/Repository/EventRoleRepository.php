<?php // No namespace or use statements

class EventRoleRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Salvează un obiect EventRole nou în baza de date.
     *
     * @param EventRole $eventRole
     * @return bool
     */
    public function save(EventRole $eventRole): bool
    {
        $sql = "INSERT INTO event_roles (user_id, event_id, role)
                VALUES (:user_id, :event_id, :role)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $eventRole->getUserId());
            $stmt->bindValue(':event_id', $eventRole->getEventId());
            $stmt->bindValue(':role', $eventRole->getRole());
            return $stmt->execute();
        } catch (PDOException $e) {
            // Ignoră eroarea de duplicat (UNIQUE constraint)
            if ($e->getCode() === '23000') {
                // SQLSTATE for Integrity Constraint Violation
                error_log('Tentativă de a insera rol de eveniment duplicat (ignorat): ' . $e->getMessage());
                return true; // Considerăm succes chiar dacă a fost duplicat
            }
            error_log('Eroare la salvarea rolului de eveniment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Găsește rolurile unui utilizator pentru un eveniment specific.
     *
     * @param int $userId
     * @param int $eventId
     * @return EventRole[]
     */
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

    /**
     * Găsește toți utilizatorii și rolurile lor pentru un eveniment.
     *
     * @param int $eventId
     * @return EventRole[]
     */
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

    /**
     * Șterge un rol specific pentru un utilizator într-un eveniment.
     *
     * @param int $userId
     * @param int $eventId
     * @param string $role
     * @return bool
     */
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
            error_log('Eroare la ștergerea rolului de eveniment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Șterge toate rolurile unui utilizator pentru un eveniment.
     *
     * @param int $userId
     * @param int $eventId
     * @return bool
     */
    public function deleteAllUserRolesForEvent(int $userId, int $eventId): bool
    {
        $sql = 'DELETE FROM event_roles WHERE user_id = :user_id AND event_id = :event_id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Eroare la ștergerea tuturor rolurilor utilizatorului pentru eveniment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Șterge toate rolurile pentru un eveniment.
     *
     * @param int $eventId
     * @return bool
     */
    public function deleteAllRolesForEvent(int $eventId): bool
    {
        $sql = 'DELETE FROM event_roles WHERE event_id = :event_id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Eroare la ștergerea tuturor rolurilor pentru eveniment: ' . $e->getMessage());
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
    /**
     * Verifică dacă un utilizator este participant la un eveniment (are un rol atribuit în event_roles).
     *
     * @param int $userId
     * @param int $eventId
     * @return bool
     */
    public function isUserAssignedToEventRole(int $userId, int $eventId): bool
    {
        $sql = 'SELECT COUNT(*) FROM event_roles WHERE user_id = :user_id AND event_id = :event_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // pt assign roles:
    public function findEligibleMembersWithEventRoles(int $orgId, int $eventId, int $eventCreatorId): array
    {
        $sql = "
            SELECT
                u.id,
                u.name,
                u.prenume,
                COALESCE(er.role, '') AS role,
                (u.id = :eventCreatorId1) AS is_creator
            FROM users u
            INNER JOIN roles r ON u.id = r.user_id AND r.org_id = :orgId
            LEFT JOIN event_roles er ON u.id = er.user_id AND er.event_id = :eventId1
            
            UNION
            
            -- Adaugă explicit creatorul evenimentului, chiar dacă nu e membru al organizației (caz excepțional)
            SELECT
                u.id,
                u.name,
                u.prenume,
                COALESCE(er.role, '') AS role,
                1 AS is_creator
            FROM users u
            LEFT JOIN event_roles er ON u.id = er.user_id AND er.event_id = :eventId2
            WHERE u.id = :eventCreatorId2 AND u.id NOT IN (SELECT user_id FROM roles WHERE org_id = :orgId2)
            
            ORDER BY is_creator DESC, name, prenume
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':orgId', $orgId, PDO::PARAM_INT);
        $stmt->bindValue(':eventId1', $eventId, PDO::PARAM_INT);
        $stmt->bindValue(':eventCreatorId1', $eventCreatorId, PDO::PARAM_INT);
        $stmt->bindValue(':eventId2', $eventId, PDO::PARAM_INT);
        $stmt->bindValue(':eventCreatorId2', $eventCreatorId, PDO::PARAM_INT);
        $stmt->bindValue(':orgId2', $orgId, PDO::PARAM_INT); // Pentru subquery
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
