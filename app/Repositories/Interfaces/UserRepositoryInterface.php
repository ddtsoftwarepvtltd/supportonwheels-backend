<?php

// app/Repositories/Interfaces/UserRepositoryInterface.php
namespace App\Repositories\Interfaces;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email);
    public function getByRole(string $role);
    public function activeUsers();
}
