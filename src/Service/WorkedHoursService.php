<?php

class WorkedHoursService
{
    private WorkedHoursRepository $workedHoursRepository;
    private OrganizationRepository $organizationRepository;
    private RoleRepository $roleRepository;

    public function __construct(
        WorkedHoursRepository $workedHoursRepository,
        OrganizationRepository $organizationRepository,
        RoleRepository $roleRepository
    ) {
        $this->workedHoursRepository = $workedHoursRepository;
        $this->organizationRepository = $organizationRepository;
        $this->roleRepository = $roleRepository;
    }

    public function addWorkedHours(
        int $memberId,
        int $orgId,
        float $hours,
        string $workDate,
        int $recordedByUserId,
        ?string $description = null
    ): array {
        if ($hours <= 0 || $hours > 24) {
            return ['success' => false, 'message' => 'Ore invalide.'];
        }
        if (empty($workDate)) {
            return ['success' => false, 'message' => 'Data invalida.'];
        }

        $pdo = $this->workedHoursRepository->getPdo();
        $pdo->beginTransaction();

        try {
            $roleExists = $this->roleRepository->isUserMemberOfOrganization($memberId, $orgId);
            if (!$roleExists) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Nu există acest membru și organizație. Adăugați mai întâi membrul în organizație.',
                ];
            }

            $workedHour = new WorkedHour($memberId, $orgId, $hours, $workDate, $recordedByUserId, $description);

            $result = $this->workedHoursRepository->save($workedHour);

            if ($result === true) {
                $updateSql = "UPDATE roles SET total_contribution_hours = (
                                SELECT IFNULL(SUM(hours), 0)
                                FROM worked_hours
                                WHERE worked_hours.user_id = roles.user_id AND worked_hours.org_id = roles.org_id
                              )
                              WHERE user_id = ? AND org_id = ?";
                $stmt = $pdo->prepare($updateSql);
                $ok = $stmt->execute([$memberId, $orgId]);
                if ($ok) {
                    $pdo->commit();
                    return ['success' => true, 'message' => 'Ore adăugate cu succes!'];
                } else {
                    $pdo->rollBack();
                    return [
                        'success' => false,
                        'message' =>
                            'Eroare la actualizarea totalului orelor (update direct roles.total_contribution_hours).',
                    ];
                }
            } else {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Eroare la adăugarea orelor: ' . (is_string($result) ? $result : ''),
                ];
            }
        } catch (\Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Eroare la adăugarea orelor: ' . $e->getMessage()];
        }
    }

    public function getMembersWithContribution(int $orgId): array
    {
        return $this->roleRepository->getOrganizationMembersWithRolesAndContribution($orgId);
    }

    public function getWorkedHoursForOrganization(int $orgId): array
    {
        return $this->workedHoursRepository->findByOrgId($orgId);
    }

    public function getWorkedHoursForUserInOrganization(int $userId, int $orgId): array
    {
        return $this->workedHoursRepository->findByUserIdAndOrgId($userId, $orgId);
    }
}
