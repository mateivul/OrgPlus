<?php

use PHPUnit\Framework\TestCase;

class EventServiceTest extends TestCase
{
    private EventService $service;
    private $eventRepositoryMock;
    private $eventRoleRepositoryMock;
    private $userRepositoryMock;
    private $roleRepositoryMock;
    private $requestRepositoryMock;
    private $organizationServiceMock;

    protected function setUp(): void
    {
        $this->eventRepositoryMock = $this->createMock(EventRepository::class);
        $this->eventRoleRepositoryMock = $this->createMock(EventRoleRepository::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->roleRepositoryMock = $this->createMock(RoleRepository::class);
        $this->requestRepositoryMock = $this->createMock(RequestRepository::class);
        $this->organizationServiceMock = $this->createMock(OrganizationService::class);

        $this->service = new EventService(
            $this->eventRepositoryMock,
            $this->eventRoleRepositoryMock,
            $this->userRepositoryMock,
            $this->roleRepositoryMock,
            $this->requestRepositoryMock,
            $this->organizationServiceMock
        );
    }

    public function testCreateEventSuccess(): void
    {
        $name = 'Test Event';
        $description = 'Event Description';
        $date = '2025-12-31 23:59:59';
        $orgId = 1;
        $createdByUserId = 10;
        $availableRolesStr = 'Volunteer,Photographer';
        $newEventId = 100;

        $this->eventRepositoryMock->method('save')->willReturn($newEventId);
        $this->eventRoleRepositoryMock->method('save')->willReturn(true);

        $member1 = $this->createMock(User::class);
        $member1->id = 11;
        $member2 = $this->createMock(User::class);
        $member2->id = 12;
        $creatorUser = $this->createMock(User::class);
        $creatorUser->id = $createdByUserId;

        $this->organizationServiceMock
            ->method('getOrganizationMembers')
            ->with($orgId)
            ->willReturn([$member1, $member2, $creatorUser]);

        $this->requestRepositoryMock->method('findExistingRequest')->willReturn(null);
        $this->requestRepositoryMock->method('save')->willReturn(true);

        $this->requestRepositoryMock->expects($this->exactly(2))->method('save');

        $result = $this->service->createEvent($name, $description, $date, $orgId, $createdByUserId, $availableRolesStr);

        $this->assertTrue($result['success']);
        $this->assertEquals($newEventId, $result['event_id']);
    }

    public function testDeleteEventSuccess(): void
    {
        $eventId = 100;
        $orgId = 1;

        $event = $this->createMock(Event::class);
        $event->orgId = $orgId;
        $this->eventRepositoryMock->method('find')->with($eventId)->willReturn($event);

        $this->requestRepositoryMock->expects($this->once())->method('deleteRequestsByEventId')->with($eventId);
        $this->eventRoleRepositoryMock->expects($this->once())->method('deleteAllRolesForEvent')->with($eventId);
        $this->eventRepositoryMock->expects($this->once())->method('deleteAllTasksForEvent')->with($eventId);
        $this->eventRepositoryMock->expects($this->once())->method('delete')->with($eventId, $orgId)->willReturn(true);

        $result = $this->service->deleteEvent($eventId, $orgId);

        $this->assertTrue($result);
    }

    public function testDeleteEventNotFound(): void
    {
        $eventId = 100;
        $orgId = 1;

        $this->eventRepositoryMock->method('find')->with($eventId)->willReturn(null);

        $this->eventRoleRepositoryMock->expects($this->never())->method('deleteAllRolesForEvent');
        $this->eventRepositoryMock->expects($this->never())->method('deleteAllTasksForEvent');
        $this->eventRepositoryMock->expects($this->never())->method('delete');

        $result = $this->service->deleteEvent($eventId, $orgId);

        $this->assertFalse($result);
    }

    public function testUpdateEventSuccess(): void
    {
        $eventId = 100;
        $orgId = 1;
        $name = 'Updated Name';
        $description = 'Updated Desc';
        $date = '2026-01-01 12:00:00';
        $roles = 'Manager,Team Lead';

        $event = $this->createMock(Event::class);
        $event->orgId = $orgId;

        $this->eventRepositoryMock->method('find')->with($eventId)->willReturn($event);
        $this->eventRepositoryMock->expects($this->once())->method('update')->with($event)->willReturn(true);

        $result = $this->service->updateEvent($eventId, $orgId, $name, $description, $date, $roles);

        $this->assertTrue($result);
        $this->assertEquals($name, $event->name);
        $this->assertEquals($description, $event->description);
        $this->assertEquals($date, $event->date);
        $this->assertEquals($roles, $event->availableRoles);
    }

    public function testUpdateEventNotFound(): void
    {
        $this->eventRepositoryMock->method('find')->willReturn(null);
        $this->eventRepositoryMock->expects($this->never())->method('update');

        $result = $this->service->updateEvent(999, 1, 'N', 'D', 'D', 'R');

        $this->assertFalse($result);
    }

    public function testSaveEventTasks(): void
    {
        $eventId = 100;
        $actingUserId = 10;

        $existingTaskUser11 = new EventTask($eventId, 11, 'Old Description', $actingUserId, 501);
        $existingTaskUser13 = new EventTask($eventId, 13, 'This will be deleted', $actingUserId, 502);

        $this->eventRepositoryMock
            ->method('getTasksForEvent')
            ->with($eventId)
            ->willReturn([$existingTaskUser11, $existingTaskUser13]);

        $this->eventRoleRepositoryMock->method('isUserAssignedToEventRole')->willReturn(true);

        $postData = [
            'task_11' => 'Updated Description',
            'task_12' => 'New Task Description',
            'task_13' => '',
        ];

        $this->eventRepositoryMock
            ->expects($this->once())
            ->method('updateEventTask')
            ->with(501, 'Updated Description', $actingUserId);

        $this->eventRepositoryMock
            ->expects($this->once())
            ->method('saveEventTask')
            ->with(
                $this->callback(function ($task) {
                    return $task instanceof EventTask && $task->userId === 12;
                })
            );

        $this->eventRepositoryMock->expects($this->once())->method('deleteEventTask')->with(502);

        $this->service->saveEventTasks($eventId, $actingUserId, $postData);
    }
}
