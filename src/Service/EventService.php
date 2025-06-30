<?php

class EventService
{
    private EventRepository $eventRepository;
    private EventRoleRepository $eventRoleRepository;
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private RequestRepository $requestRepository;
    private OrganizationService $organizationService;

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

    public function createEvent(
        string $name,
        string $description,
        string $date,
        int $orgId,
        int $createdByUserId,
        string $availableRolesStr
    ): array {
        $event = new Event($name, $description, $date, $orgId, $createdByUserId, $availableRolesStr);

        $eventId = $this->eventRepository->save($event);
        if (!$eventId) {
            return ['error' => true, 'message' => 'Eroare la salvarea evenimentului în baza de date.'];
        }

        $organizerRole = new EventRole($createdByUserId, $eventId, 'Organizator');
        if (!$this->eventRoleRepository->save($organizerRole)) {
            error_log(
                'Eroare la adăugarea creatorului (' .
                    $createdByUserId .
                    ') ca Organizator pentru evenimentul ' .
                    $eventId
            );
        }

        $members = $this->organizationService->getOrganizationMembers($orgId);
        foreach ($members as $member) {
            if ($member->id !== $createdByUserId) {
                $existingRequest = $this->requestRepository->findExistingRequest(
                    'event_invitation',
                    $createdByUserId,
                    $member->id,
                    $orgId,
                    $eventId,
                    ['pending', 'accepted']
                );

                if (!$existingRequest) {
                    $eventInvitationRequest = new Request(
                        'event_invitation',
                        $createdByUserId,
                        $member->id,
                        $orgId,
                        'pending',
                        null,
                        null,
                        null,
                        $eventId
                    );
                    if (!$this->requestRepository->save($eventInvitationRequest)) {
                        error_log(
                            'Eroare la trimiterea invitației evenimentului ' .
                                $eventId .
                                ' către utilizatorul ' .
                                $member->id
                        );
                    }
                } else {
                    error_log(
                        'Invitație la eveniment ' .
                            $eventId .
                            ' pentru utilizatorul ' .
                            $member->id .
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

    public function getEventParticipantsWithRoles(int $eventId, string $searchTerm = ''): array
    {
        return $this->eventRoleRepository->findParticipantsByEventId($eventId, $searchTerm);
    }

    public function getEventTasks(int $eventId): array
    {
        return $this->eventRepository->getTasksForEvent($eventId);
    }

    public function saveEventTasks(int $eventId, int $assignedByUserId, array $postData): void
    {
        $currentTasks = $this->eventRepository->getTasksForEvent($eventId);
        $currentTasksByUser = [];
        foreach ($currentTasks as $task) {
            $currentTasksByUser[$task->userId] = $task;
        }

        foreach ($postData as $key => $value) {
            if (strpos($key, 'task_') === 0) {
                $assignedToUserId = (int) str_replace('task_', '', $key);
                $taskDescription = trim($value);

                $isParticipant = $this->eventRoleRepository->isUserAssignedToEventRole($assignedToUserId, $eventId);
                if (!$isParticipant) {
                    error_log(
                        "Tentativă de a atribui task unui non-participant: user_id $assignedToUserId, event_id $eventId"
                    );
                    continue;
                }
                if (array_key_exists($assignedToUserId, $currentTasksByUser)) {
                    $existingTask = $currentTasksByUser[$assignedToUserId];
                    if (!empty($taskDescription)) {
                        if ($existingTask->taskDescription !== $taskDescription) {
                            $this->eventRepository->updateEventTask(
                                $existingTask->taskId,
                                $taskDescription,
                                $assignedByUserId
                            );
                        }
                    } else {
                        $this->eventRepository->deleteEventTask($existingTask->taskId);
                    }
                } elseif (!empty($taskDescription)) {
                    $newTask = new EventTask($eventId, $assignedToUserId, $taskDescription, $assignedByUserId);
                    $this->eventRepository->saveEventTask($newTask);
                }
            }
        }
    }

    public function getEventsForOrganization(int $orgId): array
    {
        return $this->eventRepository->findByOrgId($orgId);
    }

    public function getEventDetails(int $eventId): ?Event
    {
        $event = $this->eventRepository->find($eventId);

        if ($event) {
            $organization = $this->organizationService->getOrganizationById($event->orgId);
            if ($organization) {
                $event->orgName = $organization->name;
                $event->orgOwnerId = $organization->ownerId;
            } else {
                $event->orgName = 'Organizație necunoscută';
                $event->orgOwnerId = null;
            }
        }
        return $event;
    }

    public function findEventById(int $eventId): ?Event
    {
        return $this->eventRepository->find($eventId);
    }

    public function updateEvent(
        int $eventId,
        int $orgId,
        string $name,
        string $description,
        string $date,
        string $availableRolesStr
    ): bool {
        $event = $this->eventRepository->find($eventId);
        if (!$event || $event->orgId !== $orgId) {
            return false;
        }
        $event->name = $name;
        $event->description = $description;
        $event->date = $date;
        $event->availableRoles = $availableRolesStr;

        return $this->eventRepository->update($event);
    }

    public function deleteEvent(int $eventId, int $orgId): bool
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event || $event->orgId !== $orgId) {
            return false;
        }

        $this->eventRoleRepository->deleteAllRolesForEvent($eventId);

        $this->eventRepository->deleteAllTasksForEvent($eventId);

        return $this->eventRepository->delete($eventId, $orgId);
    }

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

        $organization = $this->organizationService->getOrganizationById($event->orgId);
        $orgName = $organization ? $organization->name : 'Organizație necunoscută';
        $orgOwnerId = $organization ? $organization->ownerId : null;

        $isOrgAdminOrOwner = $this->roleRepository->isUserAdminOrOwner($userId, $event->orgId);
        $isEventOwner = $userId === $event->createdBy; // creatorul evenimentului
        $hasManagementPermission = $isOrgAdminOrOwner || $isEventOwner;

        $permissionError = null;
        if (!$hasManagementPermission) {
            $permissionError = 'Nu aveți permisiunea de a gestiona rolurile pentru acest eveniment.';
        }

        $availableRolesString = $event->availableRoles ?? '';
        $roles = !empty($availableRolesString) ? array_map('trim', explode(',', $availableRolesString)) : [];
        $roles = array_filter($roles);

        $event->orgName = $orgName;
        $event->orgOwnerId = $orgOwnerId;

        return [
            'event' => $event,
            'org_name' => $orgName,
            'roles' => $roles,
            'has_management_permission' => $hasManagementPermission,
            'permission_error' => $permissionError,
        ];
    }
    public function getEligibleEventMembers(int $orgId, int $eventId, int $eventCreatorId): array
    {
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

            $isMemberOfOrg = $this->roleRepository->isUserMemberOfOrganization($userIdToAssign, $orgId);
            $isEventCreatorUser = $userIdToAssign === $eventCreatorId;

            if (!$isMemberOfOrg && !$isEventCreatorUser) {
                error_log(
                    "Încercare de atribuire rol către non-membru/non-creator: User {$userIdToAssign}, Event {$eventId}, Org {$orgId}"
                );
                $allSuccessful = false;
                continue;
            }

            if (!empty($roleAssigned) && !in_array($roleAssigned, $availableRoles)) {
                error_log(
                    "Încercare de atribuire rol invalid '{$roleAssigned}' pentru Eveniment {$eventId}. Roluri disponibile: " .
                        implode(',', $availableRoles)
                );
                $allSuccessful = false;
                continue;
            }

            if (!empty($roleAssigned)) {
                if (!$this->eventRoleRepository->assignOrUpdateEventRole($eventId, $userIdToAssign, $roleAssigned)) {
                    $allSuccessful = false;
                    error_log("Eroare la salvarea rolului pentru user {$userIdToAssign}, event {$eventId}");
                }
            } else {
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
