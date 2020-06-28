<?php

namespace Kolo6ok\RepositoryGenerator;


use Illuminate\Database\Eloquent\Builder;

/**
 * Class ServiceRepository
 *
 * @property string $model
 * @property array $searchFields
 */
abstract class ServiceRepository
{

    /**
     * @var string
     */
    protected $model;

    /**
     * @param array $filter
     * @param array $sort
     * @param int $offset
     * @param int $count
     * @return mixed
     */
    public function list($filter = [], $sort = [], $offset = 0, $count = 0)
    {
        /**
         * @var Builder $query
         */
        $query = call_user_func([$this->model, 'query']);
        if ($filter) {
            foreach ($filter as $f => $value) {
                $arr = explode(' ', $f);
                $field = $arr[0];
                $operator = isset($arr[1]) ? $arr[1] : '=';
                if ($operator == 'like' || $operator == 'ilike') {
                    $value = (array)$value;
                    $query->where(function ($q) use ($field, $value, $operator) {
                        /**
                         * @var Builder $q
                         * @var  $index
                         * @var  $item
                         */
                        foreach ($value as $index => $item) {
                            $q->orWhere($field, $operator, "%$item%");
                        }
                    });
                } else {
                    $query = is_array($value) ? $query->whereIn($field, $value) : $query->where($field, $operator, $value);
                }
            }
        }
        if ($sort) {
            foreach ($sort as $field => $type) {
                switch (strtoupper($type)) {
                    case 'DESC':
                        $query->orderByDesc($field);
                        break;
                    default:
                        $query->orderBy($field);
                        break;
                }
            }
        }
        if ($offset) {
            $query->offset($offset);
        }
        if ($count) {
            $query->limit($count);
        }
        $result = $query->get()->all();
        return $result;
    }

    /**
     * @param array $filter
     * @param array $sort
     * @param int $offset
     * @param int $count
     * @param array $relFilter
     * @param array $relSort
     * @param array $relations
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection|Model[]
     */
    public function listExtended($filter = [], $sort = [], $offset = 0, $count = 0, $relFilter = [], $relSort = [], $relations = [])
    {
        /**
         * @var Builder $query
         */
        $query = call_user_func([$this->model, 'query']);
        if ($filter) {
            foreach ($filter as $f => $value) {
                $arr = explode(' ', $f);
                $field = $arr[0];
                $operator = isset($arr[1]) ? $arr[1] : '=';
                if ($operator == 'like' || $operator == 'ilike') {
                    $value = (array)$value;
                    $query->where(function ($q) use ($field, $value, $operator) {
                        /**
                         * @var Builder $q
                         * @var  $index
                         * @var  $item
                         */
                        foreach ($value as $index => $item) {
                            $q->orWhere($field, $operator, "%$item%");
                        }
                    });
                } else {
                    $query = is_array($value) ? $query->whereIn($field, $value) : $query->where($field, $operator, $value);
                }
            }
        }
        $with = $relations;
        foreach ($relFilter as $rel => $rFilter) {
            foreach ($rFilter as $key => $value) {
                if (!is_array($value)) $value = [$value];
                foreach ($value as $item) {
                    $query->whereHas($rel, function ($q) use ($key, $item) {
                        $arr = explode(' ', $key);
                        $field = $arr[0];
                        $operator = isset($arr[1]) ? $arr[1] : '=';
                        if ('like' == $operator || 'ilike' == $operator) {
                            $item = "%$item%";
                        }
                        /** @var Builder $q */
                        $q->where($field, $operator, $item);
                    });
                }
            }
        }
        if ($relSort) {
            $resultWith = [];
            foreach ($relSort as $rel => $rSort) {
                if (in_array($rel, $with)) {
                    $with = array_flip($with);
                    unset ($with[$rel]);
                    $with = array_flip($with);
                }
                $resultWith[$rel] = function ($q) use ($rSort) {
                    /** @var Builder $q */
                    $orderBy = [];
                    foreach ($rSort as $column => $direction) {
                        $orderBy[] = "$column $direction";
                    }
                    $q->orderByRaw(implode(',', $orderBy));
                };
            }
            $with = array_merge($with, $resultWith);
        }
        $query->with($with);
        if ($sort) {
            foreach ($sort as $column => $direction) {
                switch (strtolower($direction)) {
                    case 'desc':
                        $query->orderByDesc($column);
                        break;
                    default:
                        $query->orderBy($column);
                        break;
                }
            }
        }
        if ($offset) {
            $query->offset($offset);
        }

        if ($count) {
            $query->limit($count);
        }

        return $query->get();
    }

    /**
     * @param array $pk
     * @return mixed
     */
    public function get($pk)
    {
        $model = call_user_func([$this->model, 'firstOrNew'], $pk);
        return ($model->exists) ? $model : null;
    }

    /**
     * @param object $model
     * @return mixed
     */
    public function save($model)
    {
        $model->save();
        return $model;
    }

    /**
     * @param array $filter
     * @return bool
     */
    public function delete($filter)
    {
        /**
         * @var Builder $query
         */
        $query = call_user_func([$this->model, 'query']);
        foreach ($filter as $field => $value) {
            $query = is_array($value) ? $query->whereIn($field, $value) : $query->where($field, '=', $value);
        }
        return $query->delete();
    }

    /**
     * @param array $filter
     * @return int
     */
    public function count($filter = [])
    {
        /**
         * @var Builder $query
         */
        $query = call_user_func([$this->model, 'query']);
        if ($filter) {
            foreach ($filter as $f => $value) {
                $arr = explode(' ', $f);
                $field = $arr[0];
                $operator = isset($arr[1]) ? $arr[1] : '=';
                if ($operator == 'like' || $operator == 'ilike') {
                    $value = '%' . $value . '%';
                }
                $query = is_array($value) ? $query->whereIn($field, $value) : $query->where($field, $operator, $value);
            }
        }
        return $query->get()->count();
    }

}
