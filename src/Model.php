<?php

namespace Kolo6ok\RepositoryGenerator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Webpatser\Uuid\Uuid;

/**
 * Class Mode
 * @mixin \Eloquent
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property mixed primaryUuid
 * @property mixed id
 */
abstract class Model extends EloquentModel {

    abstract protected function createDTO();
    abstract protected function loadDTO($dto);

    /**
     * Создать на основе модели DTO с вложенными объектами
     */
    protected function createNestedDTO(){}
    /**
     * Загрузить в модель данные из DTO с вложенными объектами
     * @param $dto
     */
    protected function loadNestedDTO($dto){}

    public function isPrimaryUUID()
    {
        return $this->primaryUuid;
    }

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $dateFormat = 'Y-m-d H:i:s';

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            /**
             * @var self $model
             */
            if ($model->incrementing && !$model->id) {
                $model->id = (string)Uuid::generate(4);
            }
        });
    }

    public function list($filter=[])
    {
        if ($filter) {
            $query = self::query();
            foreach ($filter as $field => $value) {
                $query = $query->where($field, $value);
            }
            $result = $query->get()->all();
        } else {
            $result = self::all()->all();
        }
        return $result;
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        if (is_array($this->primaryKey)) {
            foreach ($this->primaryKey as $field) {
                $query->where($field, '=', $this->$field);
            }
        } else {
            $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());
        }
        return $query;
    }

    /**
     * Get the primary key value for a save query.
     *
     * @return mixed
     */
    protected function getKeyForSaveQuery()
    {
        if (is_array($this->primaryKey)) {
            $key = [];
            foreach ($this->primaryKey as $field) {
                $key[$field] = $this->original[$field];
            }
            return $key;
        } else {
            return $this->original[$this->getKeyName()]
                ?? $this->getKey();
        }
    }
}
