<?php

class RequestService
{
    private RequestRepository $requestRepository;
    private UserRepository $userRepository;
    private OrganizationRepository $organizationRepository;
    private RoleRepository $roleRepository;

    public function __construct(
        RequestRepository $requestRepository,
        UserRepository $userRepository,
        OrganizationRepository $organizationRepository,
        RoleRepository $roleRepository
    ) {
        $this->requestRepository = $requestRepository;
        $this->userRepository = $userRepository;
        $this->organizationRepository = $organizationRepository;
        $this->roleRepository = $roleRepository;
    }

    // --- Adaugă aici metodele specifice serviciului de cereri (requests) ---
    // Exemple:
    // public function createJoinRequest(int $userId, int $organizationId): bool { ... }
    // public function approveJoinRequest(int $requestId, int $approverId): bool { ... }
    // public function denyJoinRequest(int $requestId, int $denyerId): bool { ... }
    // public function getPendingRequestsForOrganization(int $organizationId): array { ... }

    public function inviteUserToOrganization(string $inviteeEmail, int $organizationId, int $inviterUserId): array
    {
        $response = ['error' => false, 'success' => false, 'warning' => false, 'message' => ''];

        $invitee = $this->userRepository->findByEmail($inviteeEmail);

        if (!$invitee) {
            $response['error'] = true;
            $response['message'] = 'Nu există un utilizator cu acest email.';
            return $response;
        }

        if ($this->roleRepository->isUserMemberOfOrganization($invitee->id, $organizationId)) {
            $response['warning'] = true;
            $response['message'] = 'Utilizatorul este deja membru al organizației.';
            return $response;
        }

        if ($this->requestRepository->findPendingInvite($invitee->id, $organizationId)) {
            $response['warning'] = true;
            $response['message'] = 'O invitație a fost deja trimisă acestui utilizator.';
            return $response;
        }

        $request = new Request('organization_invite_manual', $inviterUserId, $invitee->id, $organizationId, 'pending');

        if ($this->requestRepository->create($request)) {
            $response['success'] = true;
            $response['message'] = 'Invitația a fost trimisă cu succes!';
        } else {
            $response['error'] = true;
            $response['message'] = 'Eroare la trimiterea invitației.';
        }

        return $response;
    }
}
