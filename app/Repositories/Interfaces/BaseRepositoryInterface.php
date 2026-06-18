<?php
// app/Repositories/Interfaces/BaseRepositoryInterface.php
namespace App\Repositories\Interfaces;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*'], array $relations = []);
    public function find(int $id, array $columns = ['*'], array $relations = []);
    public function findBy(string $field, mixed $value);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): bool;
    public function paginate(int $perPage = 15);
}
