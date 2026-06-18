<?php
// app/Services/UserService.php
namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepo
    ) {}

    public function getAllUsers()
    {
        return $this->userRepo->paginate(15);
    }

    public function createUser(array $data)
    {
        $data['password'] = bcrypt($data['password']);
        return $this->userRepo->create($data);
    }

    public function updateUser(int $id, array $data): mixed
    {
        return $this->userRepo->update($id, $data);
    }

    public function deleteUser(int $id): bool
    {
        return $this->userRepo->delete($id);
    }
}
