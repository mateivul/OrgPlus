<?php

class EventService
{
    private EventRepository $eventRepository;
    private EventRoleRepository $eventRoleRepository;
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private RequestRepository $requestRepository;
    private OrganizationService $organizationService; // Declară proprietatea

    public function __construct(
        EventRepository $eventRepository,
        EventRoleRepository $eventRoleRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        RequestRepository $requestRepository,
        OrganizationService $organizationService
    ) {
        // Injectează OrganizationService prin constructor
        $this->eventRepository = $eventRepository;
        $this->eventRoleRepository = $eventRoleRepository;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->requestRepository = $requestRepository;
        $this->organizationService = $organizationService; // Asignează instanța injectată
    }

    /**
     * Creează un eveniment nou și trimite invitații membrilor organizației.
     *
     * @param string $name
     * @param string $description
     * @param string $date
     * @param int $orgId
     * @param int $createdByUserId
     * @param string $availableRolesStr
     * @return array Response array containing success/error message and event ID
     */
    public function createEvent(
        string $name,
        string $description,
        string $date,
        int $orgId,
        int $createdByUserId,
        string $availableRolesStr
    ): array {
        $event = new Event($name, $description, $date, $orgId, $createdByUserId, $availableRolesStr);

        // 1. Salvează evenimentul
        $eventId = $this->eventRepository->save($event);
        if (!$eventId) {
            return ['error' => true, 'message' => 'Eroare la salvarea evenimentului în baza de date.'];
        }

        // 2. Adaugă creatorul evenimentului ca "Organizator" în event_roles
        $organizerRole = new EventRole($createdByUserId, $eventId, 'Organizator');
        if (!$this->eventRoleRepository->save($organizerRole)) {
            // Nu returnăm eroare fatală, doar logăm, deoarece evenimentul a fost deja creat
            error_log(
                'Eroare la adăugarea creatorului (' .
                    $createdByUserId .
                    ') ca Organizator pentru evenimentul ' .
                    $eventId
            );
        }

        // 3. Trimite invitații tuturor membrilor organizației (cu excepția creatorului)
        $members = $this->organizationService->getOrganizationMembers($orgId);
        foreach ($members as $member) {
            if ($member->getId() !== $createdByUserId) {
                $existingRequest = $this->requestRepository->findExistingRequest(
                    'event_invitation',
                    $createdByUserId,
                    $member->getId(),
                    $orgId,
                    $eventId, // Pass $eventId here for the find check
                    ['pending', 'accepted']
                );

                if (!$existingRequest) {
                    $eventInvitationRequest = new Request(
                        'event_invitation',
                        $createdByUserId,
                        $member->getId(),
                        $orgId,
                        'pending',
                        null, // created_at (let DB handle, or pass date('Y-m-d H:i:s'))
                        null, // id (let DB handle)
                        null, // <-- ADD THIS: respondedAt (it's the 8th parameter in Request constructor)
                        $eventId // <-- This is now correctly the 9th parameter: eventId
                    );
                    if (!$this->requestRepository->save($eventInvitationRequest)) {
                        error_log(
                            'Eroare la trimiterea invitației evenimentului ' .
                                $eventId .
                                ' către utilizatorul ' .
                                $member->getId()
                        );
                    }
                } else {
                    error_log(
                        'Invitație la eveniment ' .
                            $eventId .
                            ' pentru utilizatorul ' .
                            $member->getId() .
                            ' deja existentă sau acceptată.'
                    );
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Evenimentul a fost creat cu succes și invitațiile au fost trimise!',
            'event_id' => $eventId,
        ];
    }

    /**
     * Obține participanții unui eveniment cu rolurile lor.
     *
     * @param int $eventId
     * @param string $searchTerm
     * @return array (probabil un array de EventRole objects cu detalii de utilizator)
     */
    public function getEventParticipantsWithRoles(int $eventId, string $searchTerm = ''): array
    {
        // Această metodă ar trebui să existe în EventRoleRepository
        // și să join-uiască cu tabelul users pentru a obține numele și prenumele.
        // Dacă nu există, va trebui să o adaugi.
        return $this->eventRoleRepository->findParticipantsByEventId($eventId, $searchTerm);
    }

    /**
     * Obține toate task-urile pentru un anumit eveniment.
     *
     * @param int $eventId ID-ul evenimentului.
     * @return EventTask[]
     */
    public function getEventTasks(int $eventId): array
    {
        // Aici apelăm metoda din EventRepository.
        // Repository-ul returnează deja EventTask objects cu userAssignedName și assignerName.
        return $this->eventRepository->getTasksForEvent($eventId);
    }

    /**
     * Salvează (creează/actualizează) sarcinile pentru un eveniment.
     *
     * @param int $eventId
     * @param int $assignedByUserId ID-ul utilizatorului care atribuie sarcinile (admin/owner)
     * @param array $postData Array-ul $_POST de la formular
     * @throws Exception
     */
    public function saveEventTasks(int $eventId, int $assignedByUserId, array $postData): void
    {
        // Obține toate task-urile curente pentru eveniment pentru a compara și actualiza/șterge
        $currentTasks = $this->eventRepository->getTasksForEvent($eventId);
        $currentTasksByUser = [];
        foreach ($currentTasks as $task) {
            $currentTasksByUser[$task->getUserId()] = $task;
        }

        foreach ($postData as $key => $value) {
            // Căutăm câmpurile care încep cu "task_"
            if (strpos($key, 'task_') === 0) {
                $assignedToUserId = (int) str_replace('task_', '', $key);
                $taskDescription = trim($value);

                // Verificăm dacă utilizatorul este participant la eveniment
                // Aceasta ar trebui să fie o verificare robustă, poate printr-o metodă în EventRoleRepository
                $isParticipant = $this->eventRoleRepository->isUserAssignedToEventRole($assignedToUserId, $eventId);

                if (!$isParticipant) {
                    error_log(
                        "Tentativă de a atribui task unui non-participant: user_id $assignedToUserId, event_id $eventId"
                    );
                    continue; // Sărim peste acest task dacă utilizatorul nu e participant
                }

                if (array_key_exists($assignedToUserId, $currentTasksByUser)) {
                    // Task existent: actualizează sau șterge
                    $existingTask = $currentTasksByUser[$assignedToUserId];
                    if (!empty($taskDescription)) {
                        // Actualizează task-ul existent
                        if ($existingTask->getTaskDescription() !== $taskDescription) {
                            $this->eventRepository->updateEventTask(
                                $existingTask->getTaskId(),
                                $taskDescription,
                                $assignedByUserId
                            );
                        }
                    } else {
                        // Șterge task-ul dacă descrierea este goală
                        $this->eventRepository->deleteEventTask($existingTask->getTaskId());
                    }
                } elseif (!empty($taskDescription)) {
                    // Task nou: creează
                    $newTask = new EventTask($eventId, $assignedToUserId, $taskDescription, $assignedByUserId);
                    $this->eventRepository->saveEventTask($newTask);
                }
            }
        }
    }

    /**
     * Returnează toate evenimentele pentru o organizație.
     *
     * @param int $orgId
     * @return Event[]
     */
    public function getEventsForOrganization(int $orgId): array
    {
        return $this->eventRepository->findByOrgId($orgId);
    }

    /**
     * Obține detaliile unui singur eveniment, incluzând numele organizației și ID-ul proprietarului organizației.
     *
     * @param int $eventId ID-ul evenimentului.
     * @return Event|null Returnează obiectul Event cu proprietăți suplimentare 'orgName' și 'orgOwnerId', sau null.
     */
    public function getEventDetails(int $eventId): ?Event
    {
        $event = $this->eventRepository->find($eventId);

        if ($event) {
            // Obținem detaliile organizației folosind OrganizationService
            // Acum apelăm metoda getOrganizationById() din OrganizationService
            $organization = $this->organizationService->getOrganizationById($event->getOrgId());

            if ($organization) {
                // Adaugă numele organizației și ID-ul proprietarului ca proprietăți dinamice.
                // Acestea pot fi accesate apoi ca $event->orgName și $event->orgOwnerId
                $event->orgName = $organization->getName();
                $event->orgOwnerId = $organization->getOwnerId();
            } else {
                // Dacă organizația nu este găsită (caz excepțional, indică o eroare de date)
                $event->orgName = 'Organizație necunoscută';
                $event->orgOwnerId = null;
            }
        }
        return $event;
    }

    /**
     * Găsește un eveniment după ID. (Metoda originală, redenumită din getEventById pentru a evita confuzia)
     *
     * @param int $eventId
     * @return Event|null
     */
    public function findEventById(int $eventId): ?Event
    {
        return $this->eventRepository->find($eventId);
    }

    /**
     * Actualizează un eveniment existent.
     *
     * @param int $eventId
     * @param int $orgId
     * @param string $name
     * @param string $description
     * @param string $date
     * @param string $availableRolesStr
     * @return bool
     */
    public function updateEvent(
        int $eventId,
        int $orgId,
        string $name,
        string $description,
        string $date,
        string $availableRolesStr
    ): bool {
        $event = $this->eventRepository->find($eventId);
        if (!$event || $event->getOrgId() !== $orgId) {
            return false; // Evenimentul nu există sau nu aparține organizației
        }

        $event->setName($name);
        $event->setDescription($description);
        $event->setDate($date);
        $event->setAvailableRoles($availableRolesStr);

        return $this->eventRepository->update($event);
    }

    /**
     * Șterge un eveniment și toate rolurile/cererile asociate.
     * @param int $eventId
     * @param int $orgId
     * @return bool
     */
    public function deleteEvent(int $eventId, int $orgId): bool
    {
        // Asigură-te că evenimentul aparține organizației
        $event = $this->eventRepository->find($eventId);
        if (!$event || $event->getOrgId() !== $orgId) {
            return false;
        }

        // Șterge rolurile evenimentului (dacă există)
        $this->eventRoleRepository->deleteAllRolesForEvent($eventId);

        // Șterge cererile de invitație la eveniment (unde event_id este setat)
        // Aici este implicit că ON DELETE CASCADE pe fk_request_event va șterge automat cererile
        // dacă evenimentul este șters, dar e bine să știm că se întâmplă.
        // Dacă nu ai CASCADE, ar trebui să faci o ștergere explicită aici.

        // Șterge task-urile evenimentului (dacă există)
        $this->eventRepository->deleteAllTasksForEvent($eventId); // Presupune o metodă nouă în EventRepository

        // Șterge evenimentul în sine
        return $this->eventRepository->delete($eventId, $orgId);
    }

    // pt assign roles
    public function getEventManagementDetails(int $eventId, int $userId): array
    {
        $event = $this->eventRepository->find($eventId);

        if (!$event) {
            return [
                'event' => null,
                'org_name' => null,
                'roles' => [],
                'has_management_permission' => false,
                'permission_error' => 'Evenimentul nu a fost găsit.',
            ];
        }

        // Obține detalii organizație pentru numele și owner_id
        $organization = $this->organizationService->getOrganizationById($event->getOrgId());
        $orgName = $organization ? $organization->getName() : 'Organizație necunoscută';
        $orgOwnerId = $organization ? $organization->getOwnerId() : null;

        // Verifică permisiunile
        $isOrgAdminOrOwner = $this->roleRepository->isUserAdminOrOwner($userId, $event->getOrgId());
        $isEventOwner = $userId === $event->getCreatedBy(); // creatorul evenimentului
        $hasManagementPermission = $isOrgAdminOrOwner || $isEventOwner;

        $permissionError = null;
        if (!$hasManagementPermission) {
            $permissionError = 'Nu aveți permisiunea de a gestiona rolurile pentru acest eveniment.';
        }

        // Parsează rolurile disponibile
        $availableRolesString = $event->getAvailableRoles() ?? '';
        $roles = !empty($availableRolesString) ? array_map('trim', explode(',', $availableRolesString)) : [];
        $roles = array_filter($roles);

        // Adaugă proprietăți dinamice la obiectul Event (pentru a fi accesibile în view)
        $event->orgName = $orgName;
        $event->orgOwnerId = $orgOwnerId; // ID-ul owner-ului organizației

        return [
            'event' => $event,
            'org_name' => $orgName,
            'roles' => $roles, // Rolurile disponibile definite la nivel de eveniment
            'has_management_permission' => $hasManagementPermission,
            'permission_error' => $permissionError,
        ];
    }
    public function getEligibleEventMembers(int $orgId, int $eventId, int $eventCreatorId): array
    {
        // Această metodă ar trebui să existe în EventRoleRepository
        // sau o combinație între RoleRepository și EventRoleRepository.
        // Voi sugera o metodă nouă în EventRoleRepository.
        return $this->eventRoleRepository->findEligibleMembersWithEventRoles($orgId, $eventId, $eventCreatorId);
    }
    public function updateEventRoles(
        int $eventId,
        int $orgId,
        int $actingUserId,
        int $eventCreatorId,
        array $userRoles,
        array $availableRoles
    ): bool {
        $allSuccessful = true;

        // Verifică permisiunile o dată la nivel de serviciu
        $isOrgAdminOrOwner = $this->roleRepository->isUserAdminOrOwner($actingUserId, $orgId);
        $isEventOwner = $actingUserId === $eventCreatorId;
        $hasManagementPermission = $isOrgAdminOrOwner || $isEventOwner;

        if (!$hasManagementPermission) {
            error_log(
                "Tentativă de actualizare roluri eveniment fără permisiuni: User {$actingUserId}, Event {$eventId}"
            );
            return false;
        }

        foreach ($userRoles as $userIdToAssign => $roleAssigned) {
            $userIdToAssign = (int) $userIdToAssign;
            $roleAssigned = trim($roleAssigned);

            // Verifică dacă utilizatorul este membru al organizației sau creatorul evenimentului
            $isMemberOfOrg = $this->roleRepository->isUserMemberOfOrganization($userIdToAssign, $orgId);
            $isEventCreatorUser = $userIdToAssign === $eventCreatorId;

            if (!$isMemberOfOrg && !$isEventCreatorUser) {
                error_log(
                    "Încercare de atribuire rol către non-membru/non-creator: User {$userIdToAssign}, Event {$eventId}, Org {$orgId}"
                );
                $allSuccessful = false;
                continue;
            }

            // Validare rol atribuit
            if (!empty($roleAssigned) && !in_array($roleAssigned, $availableRoles)) {
                error_log(
                    "Încercare de atribuire rol invalid '{$roleAssigned}' pentru Eveniment {$eventId}. Roluri disponibile: " .
                        implode(',', $availableRoles)
                );
                $allSuccessful = false;
                continue;
            }

            if (!empty($roleAssigned)) {
                // Atribuie/actualizează rol
                if (!$this->eventRoleRepository->assignOrUpdateEventRole($eventId, $userIdToAssign, $roleAssigned)) {
                    $allSuccessful = false;
                    error_log("Eroare la salvarea rolului pentru user {$userIdToAssign}, event {$eventId}");
                }
            } else {
                // Șterge rol
                if (!$this->eventRoleRepository->deleteEventRole($eventId, $userIdToAssign)) {
                    $allSuccessful = false;
                    error_log("Eroare la ștergerea rolului pentru user {$userIdToAssign}, event {$eventId}");
                }
            }
        }
        return $allSuccessful;
    }
    public function addNewAvailableRole(int $eventId, string $newRoleName, array $currentRoles): array
    {
        $newRoleName = trim($newRoleName);

        if (empty($newRoleName)) {
            return [
                'success' => false,
                'message' => 'Te rugăm să introduci un nume pentru rol.',
                'newRoles' => $currentRoles,
            ];
        }
        if (in_array($newRoleName, $currentRoles)) {
            return [
                'success' => false,
                'message' => 'Rolul "' . htmlspecialchars($newRoleName) . '" există deja.',
                'newRoles' => $currentRoles,
            ];
        }

        $updatedRoles = $currentRoles;
        $updatedRoles[] = $newRoleName;
        $updatedRoles = array_map('trim', $updatedRoles);
        $updatedRoles = array_filter($updatedRoles);
        $updatedRoles = array_unique($updatedRoles);
        $updatedRolesString = implode(',', $updatedRoles);

        if ($this->eventRepository->updateAvailableRoles($eventId, $updatedRolesString)) {
            return [
                'success' => true,
                'message' => 'Rolul "' . htmlspecialchars($newRoleName) . '" a fost adăugat cu succes!',
                'newRoles' => $updatedRoles,
            ];
        } else {
            return ['success' => false, 'message' => 'Eroare la adăugarea rolului.', 'newRoles' => $currentRoles];
        }
    }
}
