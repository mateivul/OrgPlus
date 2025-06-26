<?php // No namespace or use statements
// No namespace or use statements
// No namespace or use statements
// No namespace or use statements
// No namespace or use statements
// No namespace or use statements
// No namespace or use statements
// No namespace or use statements
// No namespace or use statements
class EventRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Numără evenimentele pentru o anumită organizație.
     *
     * @param int $orgId ID-ul organizației.
     * @return int Numărul total de evenimente.
     */
    public function countEventsByOrganization(int $orgId): int
    {
        $sql = 'SELECT COUNT(*) as total FROM events WHERE org_id = :orgId';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':orgId', $orgId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Obține cel mai recent eveniment pentru o anumită organizație.
     *
     * @param int $orgId ID-ul organizației.
     * @return array|null Un array asociativ cu detaliile evenimentului (name, date) sau null dacă nu există.
     */
    public function findLastEventByOrganization(int $orgId): ?array
    {
        $sql = 'SELECT name, date FROM events WHERE org_id = :orgId ORDER BY date DESC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':orgId', $orgId, PDO::PARAM_INT);
        $stmt->execute();
        $eventData = $stmt->fetch(PDO::FETCH_ASSOC);

        return $eventData ?: null;
    }

    /**
     * Salvează un obiect Event nou în baza de date.
     * Returnează ID-ul evenimentului creat sau false la eșec.
     *
     * @param Event $event
     * @return int|false
     */
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
                // Setează ID-ul pentru obiectul Event după inserare
                $event->id = (int) $this->pdo->lastInsertId();
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('Eroare la salvarea evenimentului: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Găsește un eveniment după ID.
     *
     * @param int $id
     * @return Event|null
     */
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

        // CORECTARE AICI: Ordinea și tipurile argumentelor pentru constructorul Event
        return new Event(
            $data['name'],
            $data['description'],
            $data['date'],
            (int) $data['org_id'],
            (int) $data['created_by'], // <-- AICI E CORECTAREA (converte la int)
            $data['available_roles'], // <-- Mutat aici
            (int) $data['id'], // <-- ID-ul este acum al 7-lea argument
            $data['created_at'] ?? null // <-- created_at este al 8-lea argument opțional
        );
    }

    /**
     * Găsește toate evenimentele pentru o anumită organizație.
     *
     * @param int $orgId
     * @return Event[]
     */
    public function findByOrgId(int $orgId): array
    {
        $sql = 'SELECT * FROM events WHERE org_id = :org_id ORDER BY date ASC';
        try {
            // Am adăugat try-catch pentru o gestionare mai robustă a erorilor
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
            $stmt->execute();
            $events = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // CORECTARE AICI: Ordinea și tipurile argumentelor pentru constructorul Event
                $events[] = new Event(
                    $data['name'],
                    $data['description'],
                    $data['date'],
                    (int) $data['org_id'],
                    (int) $data['created_by'], // <-- AICI E CORECTAREA (converte la int)
                    $data['available_roles'], // <-- Mutat aici
                    (int) $data['id'], // <-- ID-ul este acum al 7-lea argument
                    $data['created_at'] ?? null // <-- created_at este al 8-lea argument opțional
                );
            }
            return $events;
        } catch (PDOException $e) {
            error_log('Eroare la obținerea evenimentelor pentru organizația ' . $orgId . ': ' . $e->getMessage());
            return []; // Returnează un array gol în caz de eroare
        }
    }
    /**
     * Actualizează un eveniment existent.
     *
     * @param Event $event
     * @return bool
     */
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
            error_log('Eroare la actualizarea evenimentului: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Șterge un eveniment după ID.
     *
     * @param int $id
     * @param int $orgId
     * @return bool
     */
    public function delete(int $id, int $orgId): bool
    {
        $sql = 'DELETE FROM events WHERE id = :id AND org_id = :org_id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Eroare la ștergerea evenimentului: ' . $e->getMessage());
            return false;
        }
    }

    public function getTasksForEvent(int $eventId): array
    {
        // SELECTăm coloanele necesare, inclusiv numele utilizatorilor
        // Folosim alias-uri pentru a evita conflictele de nume de coloane (ex: 'name' din users)
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
                // Poți adăuga și numele utilizatorilor în obiectul task sau un array separat
                // De exemplu, poți adăuga proprietăți temporare sau o altă structură de date
                // sau le poți lăsa pentru un Service să le adauge
                $task->userAssignedName = $data['user_assigned_name'] . ' ' . $data['user_assigned_prenume'];
                $task->assignerName = $data['assigner_name'] . ' ' . $data['assigner_prenume'];

                $tasks[] = $task;
            }
            return $tasks;
        } catch (PDOException $e) {
            error_log('Eroare la obținerea task-urilor pentru evenimentul ' . $eventId . ': ' . $e->getMessage());
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

    // pt assign roles:
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
}
