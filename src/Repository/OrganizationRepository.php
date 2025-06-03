
<?php // No namespace or use statements

class OrganizationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obține toate organizațiile, fără a lua în considerare starea de înscriere a unui utilizator specific.
     * Utila pentru utilizatorii neautentificați.
     *
     * @return array Un array de obiecte Organization.
     */
    public function findAll(): array
    {
        $sql = "
            SELECT
                o.id,
                o.name,
                o.description,
                o.website,
                (SELECT COUNT(*) FROM roles WHERE org_id = o.id) AS member_count
            FROM
                organizations o
            ORDER BY
                member_count DESC, o.name ASC
        "; // Am ajustat sortarea pentru a fi relevantă fără statusul utilizatorului

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $organizations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $org = new Organization($row['id'], $row['name'], $row['description'], $row['website']);
            // Setează proprietățile temporare, dar nu cele specifice utilizatorului
            $org->isEnrolled = false; // Implicit fals pentru neautentificați
            $org->isRequestPending = false; // Implicit fals pentru neautentificați
            $org->userRole = null; // Nu există rol pentru neautentificați
            $org->memberCount = (int) $row['member_count'];

            $organizations[] = $org;
        }

        return $organizations;
    }

    /**
     * Obține toate organizațiile cu starea de înscriere și cerere pending pentru un utilizator dat.
     *
     * @param int $userId ID-ul utilizatorului autentificat.
     * @return array Un array de obiecte Organization, extinse cu statusuri.
     */
    public function findAllOrganizationsWithUserStatus(int $userId): array
    {
        // ... (codul existent al acestei metode, neschimbat)
        $sql = "
            SELECT
                o.id,
                o.name,
                o.description,
                o.website,
                r.role,
                CASE WHEN r.user_id IS NOT NULL THEN 1 ELSE 0 END AS enrolled,
                (SELECT 1 FROM requests WHERE sender_user_id = :userId1 AND organization_id = o.id AND request_type = 'organization_join_request' AND status = 'pending') AS request_pending,
                (SELECT COUNT(*) FROM roles WHERE org_id = o.id) AS member_count
            FROM
                organizations o
            LEFT JOIN
                roles r ON o.id = r.org_id AND r.user_id = :userId2
            ORDER BY
                enrolled DESC,
                request_pending DESC,
                CASE WHEN enrolled = 1 OR request_pending = 1 THEN o.created_at ELSE NULL END DESC,
                member_count DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':userId1', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':userId2', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $organizations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $org = new Organization($row['id'], $row['name'], $row['description'], $row['website']);
            $org->isEnrolled = (bool) $row['enrolled'];
            $org->isRequestPending = (bool) $row['request_pending'];
            $org->userRole = $row['role'];
            $org->memberCount = (int) $row['member_count'];

            $organizations[] = $org;
        }

        return $organizations;
    }
    public function findById(int $id): ?Organization
    {
        $sql = 'SELECT id, name, description, owner_id, created_at FROM organizations WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            return new Organization(
                $data['id'],
                $data['name'],
                $data['description'],
                $data['owner_id'],
                $data['created_at']
            );
        }
        return null;
    }
}
