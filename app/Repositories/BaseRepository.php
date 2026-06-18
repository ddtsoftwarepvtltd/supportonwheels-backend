<?php
// app/Repositories/BaseRepository.php
namespace App\Repositories;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function all(array $columns = ['*'], array $relations = [])
    {
        return $this->model->with($relations)->get($columns);
    }

    public function find(int $id, array $columns = ['*'], array $relations = [])
    {
        return $this->model->with($relations)->findOrFail($id, $columns);
    }

    public function findBy(string $field, mixed $value)
    {
        return $this->model->where($field, $value)->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->find($id)->delete();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->paginate($perPage);
    }
}
