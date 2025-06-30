<?php

class OrganizationService
{
    private OrganizationRepository $organizationRepository;
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private RequestRepository $requestRepository;

    public function __construct(
        OrganizationRepository $organizationRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        RequestRepository $requestRepository
    ) {
        $this->organizationRepository = $organizationRepository;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->requestRepository = $requestRepository;
    }

    public function getOrganizationMembers(int $organizationId): array
    {
        $userRoles = $this->roleRepository->findUsersByOrganizationId($organizationId);

        $members = [];
        foreach ($userRoles as $userRole) {
            $user = $this->userRepository->find($userRole['user_id']);
            if ($user) {
                $members[] = $user;
            }
        }
        return $members;
    }

    public function getOrganizationById(int $orgId): ?Organization
    {
        return $this->organizationRepository->findById($orgId);
    }

    public function removeMember(int $orgId, int $memberUserId, int $actingUserId): bool
    {
        $organization = $this->organizationRepository->findById($orgId);
        if (!$organization) {
            throw new Exception('Organizația nu a fost găsită.');
        }

        if (!$this->roleRepository->isUserAdminOrOwner($actingUserId, $orgId)) {
            throw new Exception('Nu ai permisiuni suficiente pentru a șterge membri.');
        }

        if ($this->roleRepository->isUserOwner($memberUserId, $orgId)) {
            throw new Exception('Nu poți șterge owner-ul organizației.');
        }

        if ($actingUserId === $memberUserId) {
            throw new Exception('Nu te poți șterge pe tine însuți din organizație.');
        }

        $this->roleRepository->removeRole($memberUserId, $orgId);

        $this->requestRepository->deleteRequestsRelatedToUserAndOrg($orgId, $memberUserId);

        return true;
    }

    public function updateMemberRole(int $orgId, int $memberUserId, string $newRole, int $actingUserId): bool
    {
        $organization = $this->organizationRepository->findById($orgId);
        if (!$organization) {
            throw new Exception('Organizația nu a fost găsită.');
        }

        if (!$this->roleRepository->isUserAdminOrOwner($actingUserId, $orgId)) {
            throw new Exception('Nu ai permisiuni suficiente pentru a modifica roluri.');
        }

        if ($this->roleRepository->isUserOwner($memberUserId, $orgId)) {
            throw new Exception('Nu poți modifica rolul owner-ului organizației.');
        }

        $validRoles = ['member', 'admin', 'viewer'];
        if (!in_array($newRole, $validRoles)) {
            throw new Exception('Rolul specificat este invalid.');
        }

        if (!$this->roleRepository->isUserMemberOfOrganization($memberUserId, $orgId)) {
            throw new Exception('Utilizatorul nu este membru al acestei organizații.');
        }

        return $this->roleRepository->updateRole($memberUserId, $orgId, $newRole);
    }

    public function inviteMemberByEmail(
        int $orgId,
        int $senderUserId,
        string $receiverEmail,
        string $role = 'member'
    ): bool {
        $organization = $this->organizationRepository->findById($orgId);
        if (!$organization) {
            throw new Exception('Organizația nu a fost găsită.');
        }

        if (!$this->roleRepository->isUserAdminOrOwner($senderUserId, $orgId)) {
            throw new Exception('Nu ai permisiuni suficiente pentru a invita membri.');
        }

        $receiverUser = $this->userRepository->findByEmail($receiverEmail);
        if (!$receiverUser) {
            throw new Exception('Nu există un utilizator înregistrat cu acest email.');
        }

        $receiverUserId = $receiverUser->id;

        if ($this->roleRepository->isUserMemberOfOrganization($receiverUserId, $orgId)) {
            throw new Exception('Utilizatorul este deja membru al organizației.');
        }

        $existingInvite = $this->requestRepository->findExistingRequest(
            'organization_invite_manual',
            $senderUserId,
            $receiverUserId,
            $orgId,
            null,
            ['pending', 'accepted']
        );
        if ($existingInvite) {
            throw new Exception('O invitație pending sau acceptată a fost deja trimisă acestui utilizator.');
        }

        $request = new Request('organization_invite_manual', $senderUserId, $receiverUserId, $orgId, 'pending');

        if (!$this->requestRepository->save($request)) {
            throw new Exception('Eroare la salvarea invitației în baza de date.');
        }

        return true;
    }
}
