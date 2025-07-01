<?php

use PHPUnit\Framework\TestCase;

class RequestServiceTest extends TestCase
{
    private RequestService $service;
    private $requestRepositoryMock;
    private $userRepositoryMock;
    private $organizationRepositoryMock;
    private $roleRepositoryMock;

    protected function setUp(): void
    {
        $this->requestRepositoryMock = $this->createMock(RequestRepository::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->organizationRepositoryMock = $this->createMock(OrganizationRepository::class);
        $this->roleRepositoryMock = $this->createMock(RoleRepository::class);

        $this->service = new RequestService(
            $this->requestRepositoryMock,
            $this->userRepositoryMock,
            $this->organizationRepositoryMock,
            $this->roleRepositoryMock
        );
    }

    public function testInviteUserToOrganizationSuccess(): void
    {
        $inviteeEmail = 'test@example.com';
        $orgId = 1;
        $inviterId = 10;

        $invitee = $this->createMock(User::class);
        $invitee->id = 5;

        $this->userRepositoryMock->method('findByEmail')->with($inviteeEmail)->willReturn($invitee);
        $this->roleRepositoryMock->method('isUserMemberOfOrganization')->with($invitee->id, $orgId)->willReturn(false);
        $this->requestRepositoryMock->method('findPendingInvite')->with($invitee->id, $orgId)->willReturn(false);
        $this->requestRepositoryMock->method('create')->willReturn(true);

        $result = $this->service->inviteUserToOrganization($inviteeEmail, $orgId, $inviterId);

        $this->assertTrue($result['success']);
        $this->assertEquals('Invitația a fost trimisă cu succes!', $result['message']);
    }

    public function testInviteUserNotFound(): void
    {
        $this->userRepositoryMock->method('findByEmail')->willReturn(null);
        $result = $this->service->inviteUserToOrganization('test@example.com', 1, 10);
        $this->assertTrue($result['error']);
        $this->assertEquals('Nu există un utilizator cu acest email.', $result['message']);
    }

    public function testInviteUserAlreadyMember(): void
    {
        $invitee = $this->createMock(User::class);
        $invitee->id = 5;
        $this->userRepositoryMock->method('findByEmail')->willReturn($invitee);
        $this->roleRepositoryMock->method('isUserMemberOfOrganization')->willReturn(true);
        $result = $this->service->inviteUserToOrganization('test@example.com', 1, 10);
        $this->assertTrue($result['warning']);
        $this->assertEquals('Utilizatorul este deja membru al organizației.', $result['message']);
    }

    public function testInviteUserAlreadyPending(): void
    {
        $invitee = $this->createMock(User::class);
        $invitee->id = 5;
        $this->userRepositoryMock->method('findByEmail')->willReturn($invitee);
        $this->roleRepositoryMock->method('isUserMemberOfOrganization')->willReturn(false);
        $this->requestRepositoryMock->method('findPendingInvite')->willReturn(true);
        $result = $this->service->inviteUserToOrganization('test@example.com', 1, 10);
        $this->assertTrue($result['warning']);
        $this->assertEquals('O invitație a fost deja trimisă acestui utilizator.', $result['message']);
    }

    public function testInviteFailsToSave(): void
    {
        $invitee = $this->createMock(User::class);
        $invitee->id = 5;
        $this->userRepositoryMock->method('findByEmail')->willReturn($invitee);
        $this->roleRepositoryMock->method('isUserMemberOfOrganization')->willReturn(false);
        $this->requestRepositoryMock->method('findPendingInvite')->willReturn(false);
        $this->requestRepositoryMock->method('create')->willReturn(false);
        $result = $this->service->inviteUserToOrganization('test@example.com', 1, 10);
        $this->assertTrue($result['error']);
        $this->assertEquals('Eroare la trimiterea invitației.', $result['message']);
    }
}
