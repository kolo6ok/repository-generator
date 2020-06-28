<?php

namespace Kolo6ok\RepositoryGenerator;
use Illuminate\Http\Request;

/**
 * Class DTO
 * @package App\Base
 */
abstract class DTO {
    /**
     * Загружен ли данный DTO с вложенными объектами? Если нет - значит он плоский, без вложенности
     * @var bool
     */
    protected $loaded_nested = false;

    /**
     * Массив полей, которые могут быть заполнены функцией fill.
     * Если массив is_null значит заполнять можно ВСЕ поля
     * @var string[]
     */
    protected $_fillable_fields = null;

    /**
     * DTO constructor.
     * @param string[]|Request $fields
     */
    public function __construct($fields)
    {
        $this->fill($fields);
    }

    /**
     * Заполнить набор данных из переданного набора исходный данных.
     * Если поле $_fillable_fields не пустое - будут заполнены только поля из него.
     * Если пустое - то будут заполнены все возможные поля.
     * Поля, начинающиеся с "_" не будут обновлены!
     * @param array|Request $source_data Массив ключ=>значение, либо объект Request
     */
    public function fill($source_data)
    {
        $src = [];
        if (is_array($source_data))
        {
            $src = $source_data;
        }
        else if ($source_data instanceof Request)
        {
            $src = $source_data->json();
        }

        if (is_array($this->_fillable_fields) && count($this->_fillable_fields) > 0)
        {
            $fields = array_combine($this->_fillable_fields, $this->_fillable_fields);
        }
        else
        {
            $fields = get_object_vars($this);
        }

        foreach($src as $key=>$value)
        {
            if (is_string($key) && strlen($key) && substr($key, 0, 1) == "_") continue;
            if (array_key_exists($key, $fields)){
                $this->$key = $value;
            }
        }
    }

    /**
     * Создать новый объект данного класса, заполнив его данными из $source_data
     * @param array|Request $source_data Массив ключ=>значение, либо объект Request
     * @return DTO или его наследник свеже созданный
     */
    public static function createFilled($source_data)
    {
        $dto = new static();
        $dto->fill($source_data);
        return $dto;
    }

    /**
     * @param bool $loaded
     */
    public function setLoadedNested($loaded = true)
    {
        $this->loaded_nested = $loaded;
    }
}
