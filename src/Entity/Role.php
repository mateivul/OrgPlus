<?php

class Role
{
    public int $userId;
    public int $orgId;
    public string $role;
    public string $joinDate;

    public function __construct(int $userId, int $orgId, string $role, string $joinDate)
    {
        $this->userId = $userId;
        $this->orgId = $orgId;
        $this->role = $role;
        $this->joinDate = $joinDate;
    }
}
