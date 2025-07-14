<?php

use PHPUnit\Framework\TestCase;

class WorkedHoursServiceTest extends TestCase
{
    private WorkedHoursService $service;
    private $workedHoursRepositoryMock;
    private $organizationRepositoryMock;
    private $roleRepositoryMock;
    private $pdoMock;
    private $pdoStatementMock;

    protected function setUp(): void
    {
        $this->workedHoursRepositoryMock = $this->createMock(WorkedHoursRepository::class);
        $this->organizationRepositoryMock = $this->createMock(OrganizationRepository::class);
        $this->roleRepositoryMock = $this->createMock(RoleRepository::class);

        $this->pdoMock = $this->createMock(PDO::class);
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);

        $this->workedHoursRepositoryMock->method('getPdo')->willReturn($this->pdoMock);
        $this->pdoMock->method('prepare')->willReturn($this->pdoStatementMock);

        $this->service = new WorkedHoursService(
            $this->workedHoursRepositoryMock,
            $this->organizationRepositoryMock,
            $this->roleRepositoryMock
        );
    }

    public function testAddWorkedHoursSuccess(): void
    {
        $memberId = 1;
        $orgId = 10;
        $hours = 8;
        $date = '2025-01-01';
        $recordedById = 2;

        $this->roleRepositoryMock->method('isUserMemberOfOrganization')->with($memberId, $orgId)->willReturn(true);
        $this->workedHoursRepositoryMock->method('save')->willReturn(true);
        $this->pdoStatementMock->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');
        $this->pdoMock->expects($this->never())->method('rollBack');

        $result = $this->service->addWorkedHours($memberId, $orgId, $hours, $date, $recordedById);

        $this->assertTrue($result['success']);
        $this->assertEquals('Ore adăugate cu succes!', $result['message']);
    }

    public function testAddWorkedHoursInvalidHours(): void
    {
        $result = $this->service->addWorkedHours(1, 10, 25, '2025-01-01', 2);
        $this->assertFalse($result['success']);
        $this->assertEquals('Ore invalide.', $result['message']);
    }

    public function testAddWorkedHoursUserNotMember(): void
    {
        $this->roleRepositoryMock->method('isUserMemberOfOrganization')->willReturn(false);
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->service->addWorkedHours(1, 10, 8, '2025-01-01', 2);

        $this->assertFalse($result['success']);
        $this->assertEquals(
            'Nu există acest membru și organizație. Adăugați mai întâi membrul în organizație.',
            $result['message']
        );
    }

    public function testAddWorkedHoursSaveFails(): void
    {
        $this->roleRepositoryMock->method('isUserMemberOfOrganization')->willReturn(true);
        $this->workedHoursRepositoryMock->method('save')->willReturn(false);
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->service->addWorkedHours(1, 10, 8, '2025-01-01', 2);

        $this->assertFalse($result['success']);
    }
}
