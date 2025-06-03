<?php
class RoleRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Găsește toți utilizatorii (cu ID-urile și rolurile lor) dintr-o organizație.
     *
     * @param int $organizationId
     * @return array Un array de array-uri, fiecare conținând 'user_id' și 'role'.
     */
    public function findUsersByOrganizationId(int $organizationId): array
    {
        $sql = 'SELECT user_id, role FROM roles WHERE org_id = :org_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':org_id', $organizationId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrganizationMembersWithRolesAndContribution(int $orgId): array
    {
        $sql = "SELECT 
                u.id,
                u.name,
                u.prenume,
                r.role,
                r.role AS role_name,
                r.total_contribution_hours,
                r.is_active
            FROM roles r
            JOIN users u ON u.id = r.user_id
            WHERE r.org_id = :orgId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['orgId' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Găsește un rol după ID-urile utilizatorului și organizației
    public function findByUserIdAndOrgId(int $userId, int $orgId): ?Role
    {
        $stmt = $this->pdo->prepare('SELECT * FROM roles WHERE user_id = :user_id AND org_id = :org_id');
        $stmt->execute(['user_id' => $userId, 'org_id' => $orgId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Role($data['user_id'], $data['org_id'], $data['role'], $data['join_date']);
    }

    // Salvează un nou rol în baza de date (sau actualizează-l dacă există)
    public function save(Role $role): bool
    {
        // Verifică dacă rolul există deja
        $existingRole = $this->findByUserIdAndOrgId($role->getUserId(), $role->getOrgId());

        if ($existingRole) {
            // Actualizează rolul existent
            $stmt = $this->pdo->prepare(
                'UPDATE roles SET role = :role, join_date = :join_date WHERE user_id = :user_id AND org_id = :org_id'
            );
            return $stmt->execute([
                'role' => $role->getRole(),
                'join_date' => $role->getJoinDate(),
                'user_id' => $role->getUserId(),
                'org_id' => $role->getOrgId(),
            ]);
        } else {
            // Inserează un nou rol
            $stmt = $this->pdo->prepare(
                'INSERT INTO roles (user_id, org_id, role, join_date) VALUES (:user_id, :org_id, :role, :join_date)'
            );
            return $stmt->execute([
                'user_id' => $role->getUserId(),
                'org_id' => $role->getOrgId(),
                'role' => $role->getRole(),
                'join_date' => $role->getJoinDate(),
            ]);
        }
    }

    // Verifică dacă un utilizator este membru al unei organizații (are un rol definit)
    public function isUserMemberOfOrganization(int $userId, int $orgId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM roles WHERE user_id = :user_id AND org_id = :org_id');
        $stmt->execute(['user_id' => $userId, 'org_id' => $orgId]);
        return $stmt->fetchColumn() > 0;
    }

    // Obține rolul unui utilizator într-o organizație
    public function getUserRoleInOrganization(int $userId, int $orgId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT role FROM roles WHERE user_id = :user_id AND org_id = :org_id');
        $stmt->execute(['user_id' => $userId, 'org_id' => $orgId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['role'] ?? null;
    }

    // Verifică dacă un utilizator este admin sau owner într-o organizație
    public function isUserAdminOrOwner(int $userId, int $orgId): bool
    {
        $role = $this->getUserRoleInOrganization($userId, $orgId);
        return $role === 'admin' || $role === 'owner';
    }

    // Verifică dacă un utilizator este owner într-o organizație
    public function isUserOwner(int $userId, int $orgId): bool
    {
        $role = $this->getUserRoleInOrganization($userId, $orgId);
        return $role === 'owner';
    }

    // Numără membrii dintr-o organizație
    public function countMembersInOrganization(int $orgId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM roles WHERE org_id = :org_id');
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchColumn();
    }

    /**
     * Obține lista membrilor unei organizații cu detalii despre utilizator și numărul de evenimente participate.
     *
     * @param int $orgId ID-ul organizației.
     * @return array O matrice de array-uri asociative cu detalii despre membri.
     */
    public function getMembersWithDetailsByOrganization(int $orgId): array
    {
        $sql = "SELECT u.id, u.name, u.prenume, r.role, r.join_date,
                       (SELECT COUNT(er.event_id)
                        FROM event_roles er
                        WHERE er.user_id = u.id AND er.event_id IN (SELECT id FROM events WHERE org_id = :org_id_events)) AS events_participated
                FROM users u
                JOIN roles r ON u.id = r.user_id
                WHERE r.org_id = :org_id_roles
                ORDER BY
                    CASE
                        WHEN r.role = 'owner' THEN 1
                        WHEN r.role = 'admin' THEN 2
                        WHEN r.role = 'member' THEN 3
                        ELSE 4
                    END,
                    u.name, u.prenume";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':org_id_events', $orgId, PDO::PARAM_INT);
        $stmt->bindValue(':org_id_roles', $orgId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimină un rol (un membru) dintr-o organizație.
     *
     * @param int $userId ID-ul utilizatorului de eliminat.
     * @param int $orgId ID-ul organizației.
     * @return bool True la succes, false altfel.
     */
    public function removeRole(int $userId, int $orgId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM roles WHERE user_id = :user_id AND org_id = :org_id');
            return $stmt->execute(['user_id' => $userId, 'org_id' => $orgId]);
        } catch (PDOException $e) {
            error_log('Eroare la eliminarea rolului: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizează rolul unui membru într-o organizație.
     *
     * @param int $userId ID-ul utilizatorului al cărui rol va fi actualizat.
     * @param int $orgId ID-ul organizației.
     * @param string $newRole Noul rol ('member', 'admin', 'viewer').
     * @return bool True la succes, false altfel.
     */
    public function updateRole(int $userId, int $orgId, string $newRole): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE roles SET role = :new_role WHERE user_id = :user_id AND org_id = :org_id'
            );
            return $stmt->execute([
                'new_role' => $newRole,
                'user_id' => $userId,
                'org_id' => $orgId,
            ]);
        } catch (PDOException $e) {
            error_log('Eroare la actualizarea rolului: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Return all members of an organization with their status and basic info.
     *
     * @param int $orgId
     * @return array
     */
    public function getMembersByOrganization(int $orgId): array
    {
        $sql = "SELECT 
                    u.id as user_id, 
                    u.name, 
                    u.prenume, 
                    r.role, 
                    r.is_active,
                    r.total_contribution_hours,
                    r.join_date,
                    (
                        SELECT MAX(wh.work_date)
                        FROM worked_hours wh
                        WHERE wh.user_id = u.id AND wh.org_id = :org_id_sub
                    ) as last_activity_date
                FROM users u
                JOIN roles r ON u.id = r.user_id
                WHERE r.org_id = :org_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':org_id', $orgId, PDO::PARAM_INT);
        $stmt->bindValue(':org_id_sub', $orgId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizează statusul (is_active) unui membru într-o organizație.
     *
     * @param int $memberId
     * @param int $orgId
     * @param int $newStatus
     * @return bool
     */
    public function updateMemberStatus(int $memberId, int $orgId, int $newStatus): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE roles SET is_active = :status WHERE user_id = :user_id AND org_id = :org_id'
        );
        return $stmt->execute([
            ':status' => $newStatus,
            ':user_id' => $memberId,
            ':org_id' => $orgId,
        ]);
    }
}
