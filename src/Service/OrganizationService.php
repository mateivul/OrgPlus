<?php

// namespace App\Service;

// use App\Repository\OrganizationRepository;
// use App\Repository\UserRepository;
// use App\Repository\RoleRepository;
// use App\Repository\RequestRepository;
// use App\Entity\Role;
// use App\Entity\Request;
// use App\Model\Organization; // Aici am schimbat de la App\Entity\Organization la App\Model\Organization
// use Exception;

class OrganizationService
{
    private OrganizationRepository $organizationRepository;
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private RequestRepository $requestRepository; // Injectăm RequestRepository

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

    /**
     * Returnează toți membrii unei organizații sub formă de obiecte User.
     *
     * @param int $organizationId
     * @return User[]
     */
    public function getOrganizationMembers(int $organizationId): array
    {
        // Această metodă ar trebui să utilizeze RoleRepository și UserRepository
        // pentru a aduce toți utilizatorii care au un rol în organizație.
        $userRoles = $this->roleRepository->findUsersByOrganizationId($organizationId); // Presupune că RoleRepository are această metodă

        $members = [];
        foreach ($userRoles as $userRole) {
            $user = $this->userRepository->find($userRole['user_id']); // Presupune că userRepository are metoda find
            if ($user) {
                $members[] = $user;
            }
        }
        return $members;
    }

    /**
     * Obține o organizație după ID.
     * Aceasta este metoda pe care EventService o va apela.
     *
     * @param int $orgId ID-ul organizației.
     * @return Organization|null Returnează obiectul Organization sau null dacă nu este găsită.
     */
    public function getOrganizationById(int $orgId): ?Organization
    {
        return $this->organizationRepository->findById($orgId);
    }

    /**
     * Elimină un membru dintr-o organizație.
     *
     * @param int $orgId ID-ul organizației.
     * @param int $memberUserId ID-ul utilizatorului de eliminat.
     * @param int $actingUserId ID-ul utilizatorului care efectuează acțiunea (trebuie să fie admin/owner).
     * @throws Exception Dacă utilizatorul nu are permisiuni, organizația nu există, membrul nu e găsit sau e owner.
     * @return bool True la succes.
     */
    public function removeMember(int $orgId, int $memberUserId, int $actingUserId): bool
    {
        // 1. Verifică dacă organizația există
        $organization = $this->organizationRepository->findById($orgId);
        if (!$organization) {
            throw new Exception('Organizația nu a fost găsită.');
        }

        // 2. Verifică dacă utilizatorul care face acțiunea este admin sau owner
        if (!$this->roleRepository->isUserAdminOrOwner($actingUserId, $orgId)) {
            throw new Exception('Nu ai permisiuni suficiente pentru a șterge membri.');
        }

        // 3. Verifică dacă membrul de șters este owner-ul organizației
        if ($this->roleRepository->isUserOwner($memberUserId, $orgId)) {
            throw new Exception('Nu poți șterge owner-ul organizației.');
        }

        // 4. Verifică dacă utilizatorul care face acțiunea încearcă să se ștergă pe el însuși
        if ($actingUserId === $memberUserId) {
            throw new Exception('Nu te poți șterge pe tine însuți din organizație.');
        }

        // 5. Elimină rolul membrului
        $this->roleRepository->removeRole($memberUserId, $orgId);

        // 6. Șterge cererile asociate cu utilizatorul și organizația respectivă
        $this->requestRepository->deleteRequestsRelatedToUserAndOrg($orgId, $memberUserId);

        return true;
    }

    /**
     * Actualizează rolul unui membru într-o organizație.
     *
     * @param int $orgId ID-ul organizației.
     * @param int $memberUserId ID-ul utilizatorului al cărui rol va fi actualizat.
     * @param string $newRole Noul rol ('member', 'admin', 'viewer').
     * @param int $actingUserId ID-ul utilizatorului care efectuează acțiunea.
     * @throws Exception Dacă utilizatorul nu are permisiuni, rolul nu este valid, etc.
     * @return bool True la succes.
     */
    public function updateMemberRole(int $orgId, int $memberUserId, string $newRole, int $actingUserId): bool
    {
        // 1. Verifică dacă organizația există
        $organization = $this->organizationRepository->findById($orgId);
        if (!$organization) {
            throw new Exception('Organizația nu a fost găsită.');
        }

        // 2. Verifică dacă utilizatorul care face acțiunea este admin sau owner
        if (!$this->roleRepository->isUserAdminOrOwner($actingUserId, $orgId)) {
            throw new Exception('Nu ai permisiuni suficiente pentru a modifica roluri.');
        }

        // 3. Verifică dacă membrul de editat este owner-ul organizației
        if ($this->roleRepository->isUserOwner($memberUserId, $orgId)) {
            throw new Exception('Nu poți modifica rolul owner-ului organizației.');
        }

        // 4. Verifică dacă noul rol este valid
        $validRoles = ['member', 'admin', 'viewer']; // Adaugă 'owner' dacă ar trebui să fie schimbabil (dar în general nu e)
        if (!in_array($newRole, $validRoles)) {
            throw new Exception('Rolul specificat este invalid.');
        }

        // 5. Verifică dacă utilizatorul a cărui rol e modificat e de fapt membru
        if (!$this->roleRepository->isUserMemberOfOrganization($memberUserId, $orgId)) {
            throw new Exception('Utilizatorul nu este membru al acestei organizații.');
        }

        // 6. Actualizează rolul
        return $this->roleRepository->updateRole($memberUserId, $orgId, $newRole);
    }

    /**
     * Trimite o invitație unui utilizator să se alăture unei organizații.
     *
     * @param int $orgId ID-ul organizației.
     * @param int $senderUserId ID-ul utilizatorului care trimite invitația (admin/owner).
     * @param string $receiverEmail Email-ul utilizatorului invitat.
     * @param string $role Rolul propus ('member', 'admin', 'viewer').
     * @throws Exception Dacă utilizatorul nu are permisiuni, emailul nu există, e deja membru, sau deja invitat.
     * @return bool True la succes.
     */
    public function inviteMemberByEmail(
        int $orgId,
        int $senderUserId,
        string $receiverEmail,
        string $role = 'member'
    ): bool {
        // 1. Verifică dacă organizația există
        $organization = $this->organizationRepository->findById($orgId);
        if (!$organization) {
            throw new Exception('Organizația nu a fost găsită.');
        }

        // 2. Verifică dacă utilizatorul care trimite invitația este admin sau owner
        if (!$this->roleRepository->isUserAdminOrOwner($senderUserId, $orgId)) {
            throw new Exception('Nu ai permisiuni suficiente pentru a invita membri.');
        }

        // 3. Caută utilizatorul după email
        $receiverUser = $this->userRepository->findByEmail($receiverEmail);
        if (!$receiverUser) {
            throw new Exception('Nu există un utilizator înregistrat cu acest email.');
        }

        $receiverUserId = $receiverUser->id;

        // 4. Verifică dacă utilizatorul este deja membru al organizației
        if ($this->roleRepository->isUserMemberOfOrganization($receiverUserId, $orgId)) {
            throw new Exception('Utilizatorul este deja membru al organizației.');
        }

        // 5. Verifică dacă există deja o invitație pending sau acceptată
        $existingInvite = $this->requestRepository->findExistingRequest(
            'organization_invite_manual',
            $senderUserId,
            $receiverUserId,
            $orgId,
            null, // event_id is null for organization invites
            ['pending', 'accepted']
        );
        if ($existingInvite) {
            throw new Exception('O invitație pending sau acceptată a fost deja trimisă acestui utilizator.');
        }

        // 6. Creează și salvează cererea de invitație
        $request = new Request('organization_invite_manual', $senderUserId, $receiverUserId, $orgId, 'pending');

        if (!$this->requestRepository->save($request)) {
            throw new Exception('Eroare la salvarea invitației în baza de date.');
        }

        return true;
    }
}
