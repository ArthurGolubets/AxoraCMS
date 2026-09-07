<?php

namespace HolartWeb\AxoraCMS\Helpers;

use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock as TInfoBlockModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Helper class for Bitrix-like syntax: TInfoBlock('banners')->getList()
 */
class TInfoBlock
{
    protected $infoBlock;

    public function __construct(string $code)
    {
        $this->infoBlock = TInfoBlockModel::getByCode($code);

        if (! $this->infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }
    }

    /**
     * Get list of elements
     *
     * @param  array  $filter  Filters to apply
     * @param  array  $order  Ordering ['field' => 'asc|desc']
     * @param  int|null  $limit  Limit results
     * @param  int|null  $offset  Offset results
     * @param  int|null  $perPage  Items per page for pagination (if set, returns LengthAwarePaginator)
     * @param  int  $page  Current page for pagination (default: 1)
     * @return Collection|LengthAwarePaginator
     */
    public function getList(array $filter = [], array $order = ['sort' => 'asc'], ?int $limit = null, ?int $offset = null, ?int $perPage = null, int $page = 1)
    {
        $query = $this->infoBlock->getElements();

        // Apply filters
        foreach ($filter as $key => $value) {
            if ($key === 'id') {
                $query->where('id', $value);
            } elseif ($key === 'code') {
                $query->where('code', $value);
            } elseif ($key === 'name') {
                $query->where('name', 'like', "%{$value}%");
            } else {
                // Filter by property
                $query->whereRaw("JSON_EXTRACT(properties, '$.{$key}') = ?", [$value]);
            }
        }

        // Apply ordering
        foreach ($order as $field => $direction) {
            if (in_array($field, ['id', 'name', 'code', 'sort', 'created_at', 'updated_at'])) {
                $query->orderBy($field, $direction);
            } else {
                // Order by property
                $query->orderByRaw("JSON_EXTRACT(properties, '$.{$field}') {$direction}");
            }
        }

        // If pagination requested, return paginated results
        if ($perPage !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        // Apply limit and offset
        if ($limit) {
            $query->limit($limit);
        }

        if ($offset) {
            $query->offset($offset);
        }

        return $query->get();
    }

    /**
     * Get element by ID
     */
    public function getById(int $id)
    {
        return $this->infoBlock->getElement($id);
    }

    /**
     * Get element by code
     */
    public function getByCode(string $code)
    {
        return $this->infoBlock->getElementByCode($code);
    }

    /**
     * Create new element
     */
    public function add(array $data)
    {
        return $this->infoBlock->createElement($data);
    }

    /**
     * Update element
     */
    public function update(int $id, array $data)
    {
        return $this->infoBlock->updateElement($id, $data);
    }

    /**
     * Delete element
     */
    public function delete(int $id)
    {
        return $this->infoBlock->deleteElement($id);
    }

    /**
     * Get info block instance
     */
    public function getInfoBlock()
    {
        return $this->infoBlock;
    }

    /**
     * Get fields
     */
    public function getFields()
    {
        return $this->infoBlock->fields;
    }

    /**
     * Count elements
     */
    public function count(array $filter = []): int
    {
        $query = $this->infoBlock->getElements();

        // Apply filters
        foreach ($filter as $key => $value) {
            if ($key === 'id') {
                $query->where('id', $value);
            } elseif ($key === 'code') {
                $query->where('code', $value);
            } elseif ($key === 'name') {
                $query->where('name', 'like', "%{$value}%");
            } else {
                // Filter by property
                $query->whereRaw("JSON_EXTRACT(properties, '$.{$key}') = ?", [$value]);
            }
        }

        return $query->count();
    }
}
