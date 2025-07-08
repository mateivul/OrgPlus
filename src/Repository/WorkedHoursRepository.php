<?php

class WorkedHoursRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(WorkedHour $workedHour): bool|string
    {
        $sql = "INSERT INTO worked_hours (user_id, org_id, hours, work_date, description, recorded_by)
                VALUES (:user_id, :org_id, :hours, :work_date, :description, :recorded_by)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                'user_id' => $workedHour->userId,
                'org_id' => $workedHour->orgId,
                'hours' => $workedHour->hours,
                'work_date' => $workedHour->workDate,
                'description' => $workedHour->description,
                'recorded_by' => $workedHour->recordedBy ?? null,
            ]);

            if (!$success) {
                $errorInfo = $stmt->errorInfo();
                error_log('Error saving worked hour: ' . print_r($errorInfo, true));
                return 'SQLSTATE: ' . $errorInfo[0] . ' - ' . $errorInfo[2];
            }

            if ($success) {
                $workedHour->id = (int) $this->pdo->lastInsertId();
            }
            return $success;
        } catch (PDOException $e) {
            error_log('Error saving worked hour: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function findByOrgId(int $orgId): array
    {
        $sql = "SELECT wh.*, u.name AS user_name, u.prenume AS user_prenume,
                       recorder.name AS recorded_by_name, recorder.prenume AS recorded_by_prenume
                FROM worked_hours wh
                JOIN users u ON wh.user_id = u.id
                LEFT JOIN users recorder ON wh.recorded_by = recorder.id
                WHERE wh.org_id = :org_id
                ORDER BY wh.work_date DESC, u.name, u.prenume";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
        $stmt->execute();

        $workedHoursData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $workedHours = [];
        foreach ($workedHoursData as $data) {
            $workedHour = new WorkedHour(
                $data['user_id'],
                $data['org_id'],
                $data['hours'],
                $data['work_date'],
                $data['recorded_by'],
                $data['description'],
                array_key_exists('status', $data) && $data['status'] !== null ? (string) $data['status'] : '',
                $data['id'],
                null,
                null,
                $data['recorded_at']
            );
            $workedHour->user_name = isset($data['user_name']) ? $data['user_name'] : '';
            $workedHour->user_prenume = isset($data['user_prenume']) ? $data['user_prenume'] : '';
            $workedHour->recorded_by_name = isset($data['recorded_by_name']) ? $data['recorded_by_name'] : '';
            $workedHour->recorded_by_prenume = isset($data['recorded_by_prenume']) ? $data['recorded_by_prenume'] : '';
            $workedHours[] = $workedHour;
        }
        return $workedHours;
    }

    public function findByUserIdAndOrgId(int $userId, int $orgId): array
    {
        $sql = "SELECT wh.*, u.name AS user_name, u.prenume AS user_prenume,
                       recorder.name AS recorded_by_name, recorder.prenume AS recorded_by_prenume
                FROM worked_hours wh
                JOIN users u ON wh.user_id = u.id
                LEFT JOIN users recorder ON wh.recorded_by = recorder.id
                WHERE wh.user_id = :user_id AND wh.org_id = :org_id
                ORDER BY wh.work_date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
        $stmt->execute();

        $workedHoursData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $workedHours = [];
        foreach ($workedHoursData as $data) {
            $workedHour = new WorkedHour(
                $data['user_id'],
                $data['org_id'],
                $data['hours'],
                $data['work_date'],
                $data['recorded_by'],
                $data['description'],
                array_key_exists('status', $data) && $data['status'] !== null ? (string) $data['status'] : '',
                $data['id'],
                null, // created_at is not in the worked_hours table
                null, // updated_at is not in the worked_hours table
                $data['recorded_at']
            );
            $workedHour->user_name = isset($data['user_name']) ? $data['user_name'] : '';
            $workedHour->user_prenume = isset($data['user_prenume']) ? $data['user_prenume'] : '';
            $workedHour->recorded_by_name = isset($data['recorded_by_name']) ? $data['recorded_by_name'] : '';
            $workedHour->recorded_by_prenume = isset($data['recorded_by_prenume']) ? $data['recorded_by_prenume'] : '';
            $workedHours[] = $workedHour;
        }
        return $workedHours;
    }

    public function recalculateTotalContributionHours(int $userId, int $orgId): bool
    {
        $sql = 'UPDATE roles SET total_contribution_hours = (
                    SELECT IFNULL(SUM(hours), 0)
                    FROM worked_hours
                    WHERE user_id = :user_id AND org_id = :org_id
                ),
                last_activity_date = CURRENT_TIMESTAMP
                WHERE user_id = :user_id AND org_id = :org_id';
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'user_id' => $userId,
                'org_id' => $orgId,
            ]);
        } catch (PDOException $e) {
            error_log('Error recalculating total contribution hours: ' . $e->getMessage());
            return false;
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
