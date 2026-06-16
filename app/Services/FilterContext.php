<?php

namespace App\Services;

use Illuminate\Http\Request;

class FilterContext
{
    private const SYSTEM_FIELDS = [
        'search', 'sort_field', 'sort_direction',
        'page', 'per_page', 'all', 'cursor',
        'type', 'tab', 'tableName',
        'site_id', 'site_ids',
        'date', 'label', 'prevLabel',
        'financial_year', 'selected_month', 'drill_month',
        'customer_ids', 'wage_50', 'isLive',
    ];

    private const DEFAULT_EXACT_COLUMNS = [
        'id', 'status', 'type', 'role',
        'user_id', 'customer_id', 'company_id',
        'zone_id', 'is_active', 'active',
    ];

    public function __construct(
        private readonly Request $request,
        private readonly array $options = []
    ) {}

    public function getSearchTerm(): ?string
    {
        $term = trim((string) $this->request->input('search', ''));

        return $term !== '' ? $term : null;
    }

    public function getSearchableColumns(): array
    {
        return $this->options['searchable'] ?? [];
    }

    public function getFieldFilters(): array
    {
        $filters = collect($this->request->except(self::SYSTEM_FIELDS))
            ->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])
            ->all();

        $allowed = $this->options['filterable'] ?? null;

        if ($allowed === null) {
            return $filters;
        }

        $expanded = collect($allowed)
            ->flatMap(fn ($column) => [
                $column,
                "{$column}_min",
                "{$column}_max",
                "{$column}_from",
                "{$column}_to",
            ])
            ->all();

        return collect($filters)->only($expanded)->all();
    }

    public function getExactMatchColumns(): array
    {
        return array_merge(
            self::DEFAULT_EXACT_COLUMNS,
            $this->options['exact_columns'] ?? []
        );
    }

    public function getSortField(): ?string
    {
        $field = $this->request->input('sort_field');

        return ($field !== null && $field !== '') ? $field : null;
    }

    public function getSortDirection(): string
    {
        $dir = strtolower((string) $this->request->input('sort_direction', 'desc'));

        return in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';
    }

    public function getPerPage(): int
    {
        return max(1, min((int) $this->request->input('per_page', 10), 500));
    }

    public function getPage(): int
    {
        return max(1, (int) $this->request->input('page', 1));
    }

    public function getRelationshipConfig(string $relation): ?array
    {
        return ($this->options['relationship_config'] ?? [])[$relation] ?? null;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function wantsAllRecords(): bool
    {
        return $this->request->boolean('all');
    }

    public function hasCursor(): bool
    {
        return $this->request->has('cursor');
    }
}
