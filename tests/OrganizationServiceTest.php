<?php

use PHPUnit\Framework\TestCase;

class OrganizationServiceTest extends TestCase
{
    private OrganizationService $service;
    private $organizationRepositoryMock;
    private $userRepositoryMock;
    private $roleRepositoryMock;
    private $requestRepositoryMock;

    protected function setUp(): void
    {
        $this->organizationRepositoryMock = $this->createMock(OrganizationRepository::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->roleRepositoryMock = $this->createMock(RoleRepository::class);
        $this->requestRepositoryMock = $this->createMock(RequestRepository::class);

        $this->service = new OrganizationService(
            $this->organizationRepositoryMock,
            $this->userRepositoryMock,
            $this->roleRepositoryMock,
            $this->requestRepositoryMock
        );

        // Mock a generic organization for all tests
        $this->organizationRepositoryMock->method('findById')->willReturn($this->createMock(Organization::class));
    }

    public function testGetOrganizationMembers(): void
    {
        // This is an integration test and needs a real service instance.
        $pdo = Database::getConnection();
        $organizationRepository = new OrganizationRepository($pdo);
        $userRepository = new UserRepository($pdo);
        $roleRepository = new RoleRepository($pdo);
        $requestRepository = new RequestRepository($pdo);

        $service = new OrganizationService(
            $organizationRepository,
            $userRepository,
            $roleRepository,
            $requestRepository
        );

        // Organization with ID 7 has seed data in org_plus.sql
        $members = $service->getOrganizationMembers(7);
        $this->assertNotEmpty($members, 'Organization should have at least one member');

        foreach ($members as $member) {
            $this->assertInstanceOf(User::class, $member);
        }
    }

    public function testRemoveMemberSuccess(): void
    {
        $orgId = 1;
        $memberUserId = 11;
        $actingUserId = 10; // Admin or Owner

        // Acting user has permission
        $this->roleRepositoryMock->method('isUserAdminOrOwner')->with($actingUserId, $orgId)->willReturn(true);
        // Member to be removed is not the owner
        $this->roleRepositoryMock->method('isUserOwner')->with($memberUserId, $orgId)->willReturn(false);

        // Expect role and requests to be removed
        $this->roleRepositoryMock->expects($this->once())->method('removeRole')->with($memberUserId, $orgId);
        $this->requestRepositoryMock
            ->expects($this->once())
            ->method('deleteRequestsRelatedToUserAndOrg')
            ->with($orgId, $memberUserId);

        $result = $this->service->removeMember($orgId, $memberUserId, $actingUserId);
        $this->assertTrue($result);
    }

    public function testRemoveMemberNoPermission(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nu ai permisiuni suficiente pentru a șterge membri.');

        // Acting user does not have permission
        $this->roleRepositoryMock->method('isUserAdminOrOwner')->willReturn(false);

        $this->service->removeMember(1, 11, 12);
    }

    public function testRemoveOwnerFails(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nu poți șterge owner-ul organizației.');

        $ownerId = 10;
        $actingUserId = 10;

        $this->roleRepositoryMock->method('isUserAdminOrOwner')->willReturn(true);
        // Attempting to remove the owner
        $this->roleRepositoryMock->method('isUserOwner')->with($ownerId, 1)->willReturn(true);

        $this->service->removeMember(1, $ownerId, $actingUserId);
    }

    public function testRemoveSelfFails(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nu te poți șterge pe tine însuți din organizație.');

        $actingUserId = 10;

        $this->roleRepositoryMock->method('isUserAdminOrOwner')->willReturn(true);
        $this->roleRepositoryMock->method('isUserOwner')->willReturn(false);

        $this->service->removeMember(1, $actingUserId, $actingUserId);
    }

    public function testUpdateMemberRoleSuccess(): void
    {
        $orgId = 1;
        $memberUserId = 11;
        $newRole = 'admin';
        $actingUserId = 10;

        $this->roleRepositoryMock->method('isUserAdminOrOwner')->with($actingUserId, $orgId)->willReturn(true);
        $this->roleRepositoryMock->method('isUserOwner')->with($memberUserId, $orgId)->willReturn(false);
        $this->roleRepositoryMock->method('isUserMemberOfOrganization')->with($memberUserId, $orgId)->willReturn(true);

        $this->roleRepositoryMock
            ->expects($this->once())
            ->method('updateRole')
            ->with($memberUserId, $orgId, $newRole)
            ->willReturn(true);

        $result = $this->service->updateMemberRole($orgId, $memberUserId, $newRole, $actingUserId);
        $this->assertTrue($result);
    }

    public function testUpdateMemberRoleNoPermission(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nu ai permisiuni suficiente pentru a modifica roluri.');

        $this->roleRepositoryMock->method('isUserAdminOrOwner')->willReturn(false);

        $this->service->updateMemberRole(1, 11, 'admin', 12);
    }

    public function testUpdateOwnerRoleFails(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nu poți modifica rolul owner-ului organizației.');

        $this->roleRepositoryMock->method('isUserAdminOrOwner')->willReturn(true);
        $this->roleRepositoryMock->method('isUserOwner')->willReturn(true);

        $this->service->updateMemberRole(1, 11, 'admin', 10);
    }

    public function testUpdateMemberRoleInvalidRole(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Rolul specificat este invalid.');

        $this->roleRepositoryMock->method('isUserAdminOrOwner')->willReturn(true);
        $this->roleRepositoryMock->method('isUserOwner')->willReturn(false);

        $this->service->updateMemberRole(1, 11, 'invalid-role', 10);
    }

    public function testUpdateRoleForNonMemberFails(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Utilizatorul nu este membru al acestei organizații.');

        $this->roleRepositoryMock->method('isUserAdminOrOwner')->willReturn(true);
        $this->roleRepositoryMock->method('isUserOwner')->willReturn(false);
        $this->roleRepositoryMock->method('isUserMemberOfOrganization')->willReturn(false);

        $this->service->updateMemberRole(1, 11, 'admin', 10);
    }
}
