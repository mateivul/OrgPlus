<?php
class OrganizationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

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
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $organizations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $org = new Organization($row['id'], $row['name'], $row['description'], $row['website']);
            $org->isEnrolled = false;
            $org->isRequestPending = false;
            $org->userRole = null;
            $org->memberCount = (int) $row['member_count'];

            $organizations[] = $org;
        }

        return $organizations;
    }

    public function findAllOrganizationsWithUserStatus(int $userId): array
    {
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
        $sql = 'SELECT id, name, description, owner_id, created_at, website FROM organizations WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            return new Organization(
                (int) $data['id'],
                $data['name'],
                $data['description'],
                $data['website'],
                $data['created_at'],
                (int) $data['owner_id']
            );
        }
        return null;
    }
}
