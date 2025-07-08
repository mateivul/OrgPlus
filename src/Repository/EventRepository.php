<?php
class EventRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function countEventsByOrganization(int $orgId): int
    {
        $sql = 'SELECT COUNT(*) as total FROM events WHERE org_id = :orgId';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':orgId', $orgId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findLastEventByOrganization(int $orgId): ?array
    {
        $sql = 'SELECT name, date FROM events WHERE org_id = :orgId ORDER BY date DESC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':orgId', $orgId, PDO::PARAM_INT);
        $stmt->execute();
        $eventData = $stmt->fetch(PDO::FETCH_ASSOC);

        return $eventData ?: null;
    }

    public function save(Event $event)
    {
        $sql = "INSERT INTO events (name, description, date, org_id, created_by, available_roles)
                VALUES (:name, :description, :date, :org_id, :created_by, :available_roles)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':name', $event->name);
            $stmt->bindValue(':description', $event->description);
            $stmt->bindValue(':date', $event->date);
            $stmt->bindValue(':org_id', $event->orgId);
            $stmt->bindValue(':created_by', $event->createdBy);
            $stmt->bindValue(':available_roles', $event->availableRoles);

            if ($stmt->execute()) {
                $event->id = (int) $this->pdo->lastInsertId();
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('Error saving event: ' . $e->getMessage());
            return false;
        }
    }

    public function find(int $id): ?Event
    {
        $sql = 'SELECT * FROM events WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Event(
            $data['name'],
            $data['description'],
            $data['date'],
            (int) $data['org_id'],
            (int) $data['created_by'],
            $data['available_roles'],
            (int) $data['id'],
            $data['created_at'] ?? null
        );
    }

    public function findByOrgId(int $orgId): array
    {
        $sql = 'SELECT * FROM events WHERE org_id = :org_id ORDER BY date DESC';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
            $stmt->execute();
            $events = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $events[] = new Event(
                    $data['name'],
                    $data['description'],
                    $data['date'],
                    (int) $data['org_id'],
                    (int) $data['created_by'],
                    $data['available_roles'],
                    (int) $data['id'],
                    $data['created_at'] ?? null
                );
            }
            return $events;
        } catch (PDOException $e) {
            error_log('Error getting events for organization ' . $orgId . ': ' . $e->getMessage());
            return [];
        }
    }

    public function update(Event $event): bool
    {
        $sql = "UPDATE events SET name = :name, description = :description, date = :date, available_roles = :available_roles
                WHERE id = :id AND org_id = :org_id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':name', $event->name);
            $stmt->bindValue(':description', $event->description);
            $stmt->bindValue(':date', $event->date);
            $stmt->bindValue(':available_roles', $event->availableRoles);
            $stmt->bindValue(':id', $event->id, PDO::PARAM_INT);
            $stmt->bindValue(':org_id', $event->orgId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error updating event: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id, int $orgId): bool
    {
        $sql = 'DELETE FROM events WHERE id = :id AND org_id = :org_id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error deleting event: ' . $e->getMessage());
            return false;
        }
    }

    public function getTasksForEvent(int $eventId): array
    {
        $sql = "
            SELECT
                et.task_id,
                et.event_id,
                et.user_id,
                et.task_description,
                et.assigned_by_user_id,
                et.created_at,
                u_assigned.name AS user_assigned_name,
                u_assigned.prenume AS user_assigned_prenume,
                u_assigner.name AS assigner_name,
                u_assigner.prenume AS assigner_prenume
            FROM
                event_tasks et
            JOIN
                users u_assigned ON et.user_id = u_assigned.id
            JOIN
                users u_assigner ON et.assigned_by_user_id = u_assigner.id
            WHERE
                et.event_id = :eventId
            ORDER BY
                et.created_at ASC;
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $stmt->execute();

            $tasks = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $task = new EventTask(
                    (int) $data['event_id'],
                    (int) $data['user_id'],
                    $data['task_description'],
                    (int) $data['assigned_by_user_id'],
                    (int) $data['task_id'],
                    $data['created_at'] ?? null
                );
                $task->userAssignedName = $data['user_assigned_name'] . ' ' . $data['user_assigned_prenume'];
                $task->assignerName = $data['assigner_name'] . ' ' . $data['assigner_prenume'];

                $tasks[] = $task;
            }
            return $tasks;
        } catch (PDOException $e) {
            error_log('Error getting tasks for event ' . $eventId . ': ' . $e->getMessage());
            return [];
        }
    }

    public function updateEventTask(int $taskId, string $taskDescription, int $assignedByUserId)
    {
        $sql =
            'UPDATE event_tasks SET task_description = :taskDescription, assigned_by_user_id = :assignedByUserId WHERE task_id = :taskId';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':taskDescription', $taskDescription, PDO::PARAM_STR);
        $stmt->bindParam(':taskId', $taskId, PDO::PARAM_INT);
        $stmt->bindParam(':assignedByUserId', $assignedByUserId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function saveEventTask(EventTask $task): int|false
    {
        $sql = 'INSERT INTO event_tasks (event_id, user_id, task_description, assigned_by_user_id) 
                VALUES (:event_id, :user_id, :task_description, :assigned_by_user_id)';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':event_id', $task->eventId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $task->userId, PDO::PARAM_INT);
            $stmt->bindValue(':task_description', $task->taskDescription, PDO::PARAM_STR);
            $stmt->bindValue(':assigned_by_user_id', $task->assignedByUserId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('Error saving event task: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteEventTask(int $taskId): bool
    {
        $sql = 'DELETE FROM event_tasks WHERE task_id = :taskId';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':taskId', $taskId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error deleting event task: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteAllTasksForEvent(int $eventId): bool
    {
        $sql = 'DELETE FROM event_tasks WHERE event_id = :event_id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Eroare la ștergerea task-urilor pentru evenimentul ' . $eventId . ': ' . $e->getMessage());
            return false;
        }
    }

    public function updateAvailableRoles(int $eventId, string $availableRolesString): bool
    {
        $sql = 'UPDATE events SET available_roles = :available_roles WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':available_roles', $availableRolesString, PDO::PARAM_STR);
        $stmt->bindValue(':id', $eventId, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error updating available roles for event: ' . $e->getMessage());
            return false;
        }
    }

    public function countEventsParticipatedByUserInOrganization(int $userId, int $orgId): int
    {
        $sql = "SELECT COUNT(DISTINCT ep.event_id)
                FROM event_participants ep
                JOIN events e ON ep.event_id = e.id
                WHERE ep.user_id = :user_id AND e.org_id = :org_id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log(
                'Error counting events for user ' . $userId . ' in organization ' . $orgId . ': ' . $e->getMessage()
            );
            return 0;
        }
    }

    public function deleteAllParticipantsForEvent(int $eventId): bool
    {
        $sql = 'DELETE FROM event_participants WHERE event_id = :event_id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error deleting all participants for event: ' . $e->getMessage());
            return false;
        }
    }
}
