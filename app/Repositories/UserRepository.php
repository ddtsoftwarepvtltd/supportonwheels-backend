<?php
// app/Repositories/UserRepository.php
namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function getByRole(string $role)
    {
        return $this->model->role($role)->get();
    }

    public function activeUsers()
    {
        return $this->model->where('status', 'active')->paginate(15);
    }
}
