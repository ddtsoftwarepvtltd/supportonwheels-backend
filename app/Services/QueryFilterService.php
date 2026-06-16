<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class QueryFilterService
{
    public function handle(
        Request $request,
        EloquentBuilder|QueryBuilder $query,
        array $options = []
    ): LengthAwarePaginator|CursorPaginator {
        $context = new FilterContext($request, $options);
        $term = $context->getSearchTerm();

        $query = $this->applyEagerLoads($query, $options);

        if ($term !== null) {
            $request->query->remove('page');
            $query = $this->applySearch($query, $context);
        }

        $query = $this->applyFieldFilters($query, $context);
        $query = $this->applySorting($query, $context);

        if (config('app.debug')) {
            $this->validateFilterColumns($query, $context);
        }

        return $context->hasCursor()
            ? $query->cursorPaginate($context->getPerPage())->withQueryString()
            : $query->paginate($context->getPerPage())->withQueryString();
    }

    private function applyEagerLoads(
        EloquentBuilder|QueryBuilder $query,
        array $options
    ): EloquentBuilder|QueryBuilder {
        if (! empty($options['with']) && $query instanceof EloquentBuilder) {
            $query->with($options['with']);
        }

        return $query;
    }

    private function applySearch(
        EloquentBuilder|QueryBuilder $query,
        FilterContext $context
    ): EloquentBuilder|QueryBuilder {
        $term = $context->getSearchTerm();
        $options = $context->getOptions();

        if ($term === null) {
            return $query;
        }

        $columns = $context->getSearchableColumns();
        $joinSearchable = $options['join_searchable'] ?? [];

        if (empty($columns) && empty($joinSearchable)) {
            return $query;
        }

        return $query->where(function ($q) use ($term, $columns, $joinSearchable) {
            foreach ($columns as $column) {
                $this->applyColumnSearch($q, $column, $term);
            }

            foreach ($joinSearchable as $column) {
                $q->orWhere($column, 'like', $term.'%');
            }
        });
    }

    private function applyColumnSearch(
        EloquentBuilder|QueryBuilder $q,
        string $column,
        string $term
    ): void {
        if (! str_contains($column, '.')) {
            $q->orWhere($column, 'like', $term.'%');

            return;
        }

        [$prefix, $field] = explode('.', $column, 2);

        if ($q instanceof EloquentBuilder && $this->isRelation($q, $prefix)) {
            $q->orWhereHas($prefix, fn ($r) => $r->where($field, 'like', $term.'%'));

            return;
        }

        $q->orWhere($column, 'like', $term.'%');
    }

    private function isRelation(EloquentBuilder $query, string $name): bool
    {
        try {
            return method_exists($query->getModel(), $name);
        } catch (\Throwable) {
            return false;
        }
    }

    private function applyFieldFilters(
        EloquentBuilder|QueryBuilder $query,
        FilterContext $context
    ): EloquentBuilder|QueryBuilder {
        $filters = $context->getFieldFilters();
        $exactColumns = $context->getExactMatchColumns();

        foreach ($filters as $rawField => $value) {
            $field = $this->sanitizeColumn($rawField);

            if ($field === '') {
                continue;
            }

            $value = $this->normalizeValue($value);

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if (str_contains($field, '.')) {
                $this->applyRelationFilter($query, $field, $value);

                continue;
            }

            if (str_ends_with($field, '_min')) {
                $query->where(str_replace('_min', '', $field), '>=', $value);

                continue;
            }

            if (str_ends_with($field, '_max')) {
                $query->where(str_replace('_max', '', $field), '<=', $value);

                continue;
            }

            if (str_ends_with($field, '_from')) {
                $query->whereDate(str_replace('_from', '', $field), '>=', $value);

                continue;
            }

            if (str_ends_with($field, '_to')) {
                $query->whereDate(str_replace('_to', '', $field), '<=', $value);

                continue;
            }

            if (in_array($field, $exactColumns, true) || is_array($value)) {
                is_array($value)
                    ? $query->whereIn($field, $value)
                    : $query->where($field, $value);
            } else {
                $query->where($field, 'like', $value.'%');
            }
        }

        return $query;
    }

    private function applyRelationFilter(
        EloquentBuilder|QueryBuilder $query,
        string $field,
        mixed $value
    ): void {
        [$relation, $column] = explode('.', $field, 2);

        if ($query instanceof EloquentBuilder && $this->isRelation($query, $relation)) {
            $query->whereHas(
                $relation,
                is_array($value)
                    ? fn ($q) => $q->whereIn($column, $value)
                    : fn ($q) => $q->where($column, $value)
            );

            return;
        }

        is_array($value)
            ? $query->whereIn($field, $value)
            : $query->where($field, $value);
    }

    private function applySorting(
        EloquentBuilder|QueryBuilder $query,
        FilterContext $context
    ): EloquentBuilder|QueryBuilder {
        $field = $context->getSortField() ?? 'id';
        $direction = $context->getSortDirection();

        $field = $this->sanitizeColumn($field);

        if ($field === '') {
            $field = 'id';
        }

        if (str_contains($field, '.')) {
            return $this->applyRelationSort($query, $field, $direction, $context);
        }

        return $query->orderBy($field, $direction);
    }

    private function applyRelationSort(
        EloquentBuilder|QueryBuilder $query,
        string $field,
        string $direction,
        FilterContext $context
    ): EloquentBuilder|QueryBuilder {
        $parts = explode('.', $field);
        $column = array_pop($parts);
        $relationPath = implode('.', $parts);

        $config = $this->resolveRelationConfig($query, $relationPath, $context);

        if ($config === null) {
            Log::warning("QueryFilterService: relation config missing for [{$relationPath}], fallback to id sort.");

            return $query->orderBy('id', $direction);
        }

        $mainTable = $query instanceof EloquentBuilder
            ? $query->getModel()->getTable()
            : $query->from;

        $joinTable = $config['table'];
        $foreignKey = $config['foreign_key'];
        $ownerKey = $config['owner_key'];

        $alreadyJoined = collect($query->getQuery()->joins ?? [])
            ->pluck('table')
            ->contains($joinTable);

        if (! $alreadyJoined) {
            $query->leftJoin(
                $joinTable,
                "{$mainTable}.{$foreignKey}",
                '=',
                "{$joinTable}.{$ownerKey}"
            );
        }

        if ($query instanceof EloquentBuilder) {
            $query->select("{$mainTable}.*");
        }

        return $query->orderBy("{$joinTable}.{$column}", $direction);
    }

    private function resolveRelationConfig(
        EloquentBuilder|QueryBuilder $query,
        string $relationPath,
        FilterContext $context
    ): ?array {
        $manual = $context->getRelationshipConfig($relationPath);

        if ($manual !== null) {
            return $manual;
        }

        if (! ($query instanceof EloquentBuilder)) {
            return null;
        }

        try {
            $relation = $query->getModel()->{$relationPath}();

            if (method_exists($relation, 'getForeignKeyName')) {
                return [
                    'table' => $relation->getRelated()->getTable(),
                    'foreign_key' => $relation->getForeignKeyName(),
                    'owner_key' => $relation->getOwnerKeyName(),
                ];
            }
        } catch (\Throwable $e) {
            Log::debug("QueryFilterService: auto-detect relation failed [{$relationPath}]: {$e->getMessage()}");
        }

        return null;
    }

    public function getCachedCount(
        EloquentBuilder|QueryBuilder $query,
        int $ttl = 300
    ): int {
        $table = $query instanceof EloquentBuilder
            ? $query->getModel()->getTable()
            : $query->from;

        return (int) Cache::remember(
            "qfs_count_{$table}",
            $ttl,
            fn () => (clone $query)->count()
        );
    }

    public function forgetCountCache(string $table): void
    {
        Cache::forget("qfs_count_{$table}");
    }

    private function validateFilterColumns(
        EloquentBuilder|QueryBuilder $query,
        FilterContext $context
    ): void {
        if (! ($query instanceof EloquentBuilder)) {
            return;
        }

        $table = $query->getModel()->getTable();
        $dbColumns = Schema::getColumnListing($table);
        $filters = array_keys($context->getFieldFilters());

        foreach ($filters as $field) {
            if (str_contains($field, '.')) {
                continue;
            }

            $clean = $this->sanitizeColumn($field);
            $base = preg_replace('/(_min|_max|_from|_to)$/', '', $clean);

            if ($base !== '' && ! in_array($base, $dbColumns, true)) {
                Log::warning("QueryFilterService: unknown filter column [{$base}] on table [{$table}]");
            }
        }
    }

    private function sanitizeColumn(string $column): string
    {
        return preg_replace('/[^a-zA-Z0-9_.]/', '', $column);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_string($value) && str_contains($value, ',')) {
            return array_map('trim', explode(',', $value));
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
    }
}
