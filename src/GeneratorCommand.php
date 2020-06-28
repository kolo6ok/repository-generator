<?php

namespace Kolo6ok\RepositoryGenerator;


use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class GeneratorCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'repository-generator:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate base code for repository service';

    /** @var Repository */
    protected $config;

    /** @var Filesystem */
    protected $files;

    /**
     *
     * @param Repository $config
     * @param Filesystem $files
     */
    public function __construct(
        /*ConfigRepository */ $config,
                              Filesystem $files
        /* Illuminate\View\Factory */
    ) {
        $this->config = $config;
        $this->files = $files;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $types = [
            'uuid',
            'increments',
            'bigIncrements',
            'boolean',
            'tinyInteger',
            'smallInteger',
            'integer',
            'bigInteger',
            'float',
            'double',
            'time',
            'timestamp',
            'timestampTz',
            'string',
            'text',
            'mediumText',
            'longText',
        ];
        $typeAsk = PHP_EOL;
        foreach ($types as $id=>$type) {
            $typeAsk .= $id.' - '.$type.PHP_EOL;
        }
        $typeAsk .= 'Enter field type [0-'.(count($types)-1).']';
        $name = null;
        while (!$name) {
            $name = $this->ask('Enter table name');
            if (!preg_match('/^[a-z0-9_]+$/u', $name)) {
                $this->error(PHP_EOL.PHP_EOL.' Incorrect table name!'.PHP_EOL);
                $name = null;
            }
        }
        $model = null;
        while (!$model) {
            $model = $this->ask('Enter model name');
            if (!preg_match('/^[a-z][a-z0-9]*$/ui', $model)) {
                $this->error(PHP_EOL.PHP_EOL.' Incorrect model name!'.PHP_EOL);
                $model = null;
            }
        }
        $_model = ucfirst($model);
        if ($model !== $_model) {
            $model = $_model;
            $this->warn('Model name changed to '.$model);
        }


        $fields = [];
        $primary = [];
        $isFirst = true;
        do {
            $required = false;
            $unique = false;
            if ($isFirst) {
                $fieldName = $this->ask('Enter name for new field', 'id');
            } else {
                $fieldName = $this->ask('Enter name for new field', '-');
            }
            $isFirst = false;
            if (preg_match('/^[a-z_][a-z0-9_]*$/ui', $fieldName)) {
                $fieldType = $this->ask($typeAsk, 0);
                if (!isset($types[$fieldType])) {
                    $fieldType = 0;
                    $this->info('set default type '.$types[0]);
                }
                if ($fieldType < 14 && $this->confirm('It is primary key?', ($fieldName=='id')?true:false)) {
                    $primary[$fieldName] = $fieldName;
                    $required = true;
                }
                if (!$required && $this->confirm('It is required?', false)) {
                    $required = true;
                }
                if ($required && !isset($primary[$fieldName]) && $this->confirm('It is unique?', false)) {
                    $unique = true;
                }
                $fields[$fieldName] = [
                    'type' => $types[$fieldType],
                    'required' => $required,
                    'unique' => $unique,
                    'primary' => isset($primary[$fieldName])?true:false,
                ];
                $this->warn(' Press Enter for continue');
            } elseif ($fieldName !== '-') {
                $this->error(PHP_EOL.PHP_EOL.' Incorrect field name!'.PHP_EOL);
            }
        } while ($fieldName !== '-');


        $this->generateMigrationContent($name, $fields, $primary);
        $this->generateDTO($model, $fields);
        $this->generateModel($model, $name, $fields, $primary);
        $this->generateRepository($model, $fields, $primary);
        $this->generateService($model, $fields, $primary);
        $this->generateProvider($model);
        return true;
    }

    protected function generateMigrationContent($name, $fields, $primary=[])
    {
        $lower = 'generator_service_'.$name.'_table';
        $fileName = 'database/migrations/'.date('Y_m_d_His').'_'.$lower.'.php';
        $className = 'GeneratorService'
            .preg_replace_callback(
                '/((^|_)[a-z])/u',
                function($e){return mb_strtoupper(mb_substr($e[0],-1));},
                $name
            )
            .'Table'
        ;
        $content = '<?php'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'use Illuminate\\Support\\Facades\\Schema;'.PHP_EOL;
        $content .= 'use Illuminate\\Database\\Schema\\Blueprint;'.PHP_EOL;
        $content .= 'use Illuminate\\Database\\Migrations\\Migration;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'class '.$className.' extends Migration'.PHP_EOL;
        $content .= '{'.PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * Run the migrations.'.PHP_EOL;
        $content .= '     *'.PHP_EOL;
        $content .= '     * @return void'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function up()'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        Schema::create(\''.$name.'\', function (Blueprint $table) {'.PHP_EOL;
        foreach ($fields as $field=>$definion) {
            $content .= '            $table->'.$definion['type'].'(\''.$field.'\')';
            if (!$definion['required']) {
                $content .= '->nullable()';
            }
            if ($definion['unique']) {
                $content .= '->unique()';
            }
            $content .= ';'.PHP_EOL;
        }
        $content .= '            $table->timestamps();'.PHP_EOL;
        if ($primary) {
            $content .= '            $table->primary(';
            $content .= ((count($primary)>1)?'[':'').'\'';
            $content .= implode('\',\'', $primary);
            $content .= '\''.((count($primary)>1)?']':'');
            $content .= ');'.PHP_EOL;
        }
        $content .= '        });'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * Reverse the migrations.'.PHP_EOL;
        $content .= '     *'.PHP_EOL;
        $content .= '     * @return void'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function down()'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        Schema::dropIfExists(\''.$name.'\');'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= '}'.PHP_EOL;
        $this->fileSave($fileName,$content);

    }

    protected function generateDTO($model, $fields) {
        $fileName = 'app/Services/'.$model.'Service/DTO/'.$model.'DTO.php';
        $className = $model.'DTO';
        $content = '<?php'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'namespace App\\Services\\'.$model.'Service\\DTO;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'use Kolo6ok\\RepositoryGenerator\\DTO;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '/**'.PHP_EOL;
        $content .= ' * Class '.$className . ' extends DTO'.PHP_EOL;
        $content .= ' *'.PHP_EOL;
        foreach ($fields as $field=>$definion) {
            $content .= ' * @property '. $this->getPhpType($definion['type']) .' $'.$field . PHP_EOL;
        }
        $content .= ' */'.PHP_EOL;
        $content .= 'class '.$className.PHP_EOL;
        $content .= '{'.PHP_EOL;
        foreach ($fields as $field=>$definion) {
            $content .= PHP_EOL;
            $content .= '    /**' . PHP_EOL;
            $content .= '     * @var '. $this->getPhpType($definion['type']) . PHP_EOL;
            $content .= '     */' . PHP_EOL;
            $content .= '    public $' . $field . ';' . PHP_EOL;
        }
        $content .= '}'.PHP_EOL;
        $this->fileSave($fileName,$content);
    }

    protected function generateModel($model, $table, $fields, $primary) {
        $fileName = 'app/Services/'.$model.'Service/Models/'.$model.'Model.php';
        $className = $model;
        $content = '<?php'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'namespace App\\Services\\'.$model.'Service\\Models;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'use Kolo6ok\\RepositoryGenerator\\Model;'.PHP_EOL;
        $content .= 'use App\\Services\\'.$model.'Service\\DTO\\'.$model.'DTO as DTO;'.PHP_EOL;
        $content .= 'use Carbon\\Carbon;'.PHP_EOL;
        if (count($primary) > 1) {
            $content .= PHP_EOL;
            $content .= 'use Illuminate\Database\Eloquent\Builder;'.PHP_EOL;
        }
        $content .= PHP_EOL;
        $content .= ''.PHP_EOL;
        $content .= '/**'.PHP_EOL;
        $content .= ' * Class '.$className.'Model'.PHP_EOL;
        $content .= ' *'.PHP_EOL;
        foreach ($fields as $field=>$definion) {
            $content .= ' * @property '. $this->getPhpType($definion['type']) .' $'.$field . PHP_EOL;
        }
        $content .= ' * @property string $table'.PHP_EOL;
        $content .= ' * @property Carbon $created_at'.PHP_EOL;
        $content .= ' * @property Carbon $updated_at'.PHP_EOL;
        $content .= ' */'.PHP_EOL;
        $content .= 'class '.$className.'Model extends Model'.PHP_EOL;
        $content .= '{'.PHP_EOL;


        if (count($primary) == 1) {
            $_p = $primary;
            $key = array_pop($_p);
            $keyType = $this->getPhpType($fields[$key]['type']);
            $content .= PHP_EOL;
            $content .= '    /**' . PHP_EOL;
            $content .= '     * @var string' . PHP_EOL;
            $content .= '     */' . PHP_EOL;
            $content .= '    protected $keyType = \''.$keyType.'\';'.PHP_EOL;
            $content .= PHP_EOL;
            $content .= '    /**' . PHP_EOL;
            $content .= '     * @var string' . PHP_EOL;
            $content .= '     */' . PHP_EOL;
            $content .= '    protected $primaryKey = \''.$key.'\';'.PHP_EOL;
        }

        $content .= PHP_EOL;
        $content .= '    /**' . PHP_EOL;
        $content .= '     * @var string' . PHP_EOL;
        $content .= '     */' . PHP_EOL;
        $content .= '    protected $table = \''.$table.'\';' . PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**' . PHP_EOL;
        $content .= '     * @var array' . PHP_EOL;
        $content .= '     */' . PHP_EOL;
        $content .= '    protected $fillable = ['.PHP_EOL;
        $content .= '        \''.implode('\','.PHP_EOL.'        \'', array_keys($fields)).'\''.PHP_EOL;
        $content .= '    ];' . PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @return DTO'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function createDTO(): DTO'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        $dto = new DTO();'.PHP_EOL;
        foreach ($fields as $field=>$definion) {
            $content .= '        $dto->'.$field.' = $this->'.$field.';' . PHP_EOL;
        }
        $content .= '        return $dto;'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param DTO $dto'.PHP_EOL;
        $content .= '     * @return $this'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function loadDTO($dto)'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        foreach ($fields as $field=>$definion) {
            $content .= '        $this->'.$field.' = $dto->'.$field.';' . PHP_EOL;
        }
        $content .= '        return $this;'.PHP_EOL;
        $content .= '    }'.PHP_EOL;

        if (count($primary) > 1) {
            $content .= PHP_EOL;
            $content .= '    /**'.PHP_EOL;
            $content .= '     * Set the keys for a save update query.'.PHP_EOL;
            $content .= '     *'.PHP_EOL;
            $content .= '     * @param  \Illuminate\Database\Eloquent\Builder  $query'.PHP_EOL;
            $content .= '     * @return \Illuminate\Database\Eloquent\Builder'.PHP_EOL;
            $content .= '     */'.PHP_EOL;
            $content .= '    protected function setKeysForSaveQuery(Builder $query)'.PHP_EOL;
            $content .= '    {'.PHP_EOL;
            $content .= '        foreach ($this->primaryKey as $field) {'.PHP_EOL;
            $content .= '            $query->where($field, \'=\', $this->$field);'.PHP_EOL;
            $content .= '        }'.PHP_EOL;
            $content .= '        return $query;'.PHP_EOL;
            $content .= '    }'.PHP_EOL;
            $content .= PHP_EOL;
            $content .= '    /**'.PHP_EOL;
            $content .= '     * Get the primary key value for a save query.'.PHP_EOL;
            $content .= '     *'.PHP_EOL;
            $content .= '     * @return mixed'.PHP_EOL;
            $content .= '     */'.PHP_EOL;
            $content .= '    protected function getKeyForSaveQuery()'.PHP_EOL;
            $content .= '    {'.PHP_EOL;
            $content .= '        $key = [];'.PHP_EOL;
            $content .= '        foreach ($this->primaryKey as $field) {'.PHP_EOL;
            $content .= '            $key[$field] = $this->original[$field];'.PHP_EOL;
            $content .= '        }'.PHP_EOL;
            $content .= '        return $key;'.PHP_EOL;
            $content .= '    }'.PHP_EOL;
        }
        $content .= '}'.PHP_EOL;
        $this->fileSave($fileName,$content);
    }

    protected function generateRepository($model, $fields, $primary)
    {
        if (count($primary) == 1) {
            $_p = $primary;
            $key = array_pop($_p);
            $keyType = $this->getPhpType($fields[$key]['type']);
            $search = '[\''.$key.'\' => $id]';
        } else {
            $keyType = 'array';
            $search = '$id';

        }
        $fileName = 'app/Services/'.$model.'Service/Repositories/'.$model.'Repository.php';
        $content = '<?php'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'namespace App\\Services\\'.$model.'Service\\Repositories;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'use Kolo6ok\\RepositoryGenerator\\ServiceRepository;'.PHP_EOL;
        $content .= 'use App\\Services\\'.$model.'Service\\Models\\'.$model.'Model as Model;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'class '.$model.'Repository extends ServiceRepository'.PHP_EOL;
        $content .= '{'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '    /* @var string'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    protected $model = Model::class;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param array $filter'.PHP_EOL;
        $content .= '     * @param array $sort'.PHP_EOL;
        $content .= '     * @param int $offset'.PHP_EOL;
        $content .= '     * @param int $count'.PHP_EOL;
        $content .= '     * @return Model[]'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function list($filter = [], $sort = [], $offset = 0, $count = 0): array'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        return parent::list($filter, $sort, $offset, $count);'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param '.$keyType.' $id'.PHP_EOL;
        $content .= '     * @return Model|null'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function get($id): ?Model'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        $pk = '.$search.';'.PHP_EOL;
        $content .= '        return parent::get($pk);'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param Model $model'.PHP_EOL;
        $content .= '     * @return Model'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function save($model): Model'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        return parent::save($model);'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= '}'.PHP_EOL;
        $this->fileSave($fileName, $content);
    }

    protected function generateService($model, $fields, $primary)
    {
        if (count($primary) == 1) {
            $_p = $primary;
            $key = array_pop($_p);
            $keyType = $this->getPhpType($fields[$key]['type']);
            $search = '$dto->'.$key;
            $assocKey = '$dto->'.$key;
        } else {
            $keyType = 'array';
            $assocKey = '';
            $search = '[';
            foreach ($primary as $field) {
                $search .= '\''.$field.'\' => $dto->'.$field.',';
                $assocKey .= '$dto->'.$field.'.\'.\'.';
            }
            $search .= ']';
            $assocKey = substr($assocKey,0,-5);
        }
        $fileName = 'app/Services/'.$model.'Service/'.$model.'Service.php';
        $content = '<?php'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'namespace App\\Services\\'.$model.'Service;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'use App\\Services\\'.$model.'Service\\DTO\\'.$model.'DTO as DTO;'.PHP_EOL;
        $content .= 'use App\\Services\\'.$model.'Service\\Models\\'.$model.'Model as Model;'.PHP_EOL;
        $content .= 'use App\\Services\\'.$model.'Service\\Repositories\\'.$model.'Repository as Repository;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '/**'.PHP_EOL;
        $content .= ' * Class '.$model.'Service'.PHP_EOL;
        $content .= ' *'.PHP_EOL;
        $content .= ' * @property Repository $repository'.PHP_EOL;
        $content .= ' */'.PHP_EOL;
        $content .= 'class '.$model.'Service'.PHP_EOL;
        $content .= '{'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @var Repository'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    protected $repository;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param Repository $repository'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function __construct(Repository $repository)'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        $this->repository = $repository;'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param array $filter'.PHP_EOL;
        $content .= '     * @param array $sort'.PHP_EOL;
        $content .= '     * @param int $offset'.PHP_EOL;
        $content .= '     * @param int $count'.PHP_EOL;
        $content .= '     * @param bool $assoc'.PHP_EOL;
        $content .= '     * @return DTO[]'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function list($filter = [], $sort = [], $offset = 0, $count = 0, $assoc = false): array'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        $models = $this->repository->list($filter, $sort, $offset, $count);'.PHP_EOL;
        $content .= '        $result = [];'.PHP_EOL;
        $content .= '        foreach($models as $model) {'.PHP_EOL;
        $content .= '             $dto = $model->createDTO();'.PHP_EOL;
        $content .= '             if ($assoc) {'.PHP_EOL;
        $content .= '                 $key = '.$assocKey.';'.PHP_EOL;
        $content .= '                 $result[$key] = $dto;'.PHP_EOL;
        $content .= '             } else {'.PHP_EOL;
        $content .= '                 array_push($result, $dto);'.PHP_EOL;
        $content .= '             }'.PHP_EOL;
        $content .= '        }'.PHP_EOL;
        $content .= '        return $result;'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param '.$keyType.' $id'.PHP_EOL;
        $content .= '     * @return DTO|null'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function get('.$keyType.' $id): ?DTO'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        $model = $this->repository->get($id);'.PHP_EOL;
        $content .= '        return $model ? $model->createDTO() : null;'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param DTO $dto'.PHP_EOL;
        $content .= '     * @return DTO'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function save(DTO $dto): DTO'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        $model = $this->repository->get('.$search.');'.PHP_EOL;
        $content .= '        if (!$model) {'.PHP_EOL;
        $content .= '            $model = new Model();'.PHP_EOL;
        $content .= '            $model->exists = false;'.PHP_EOL;
        $content .= '        }'.PHP_EOL;
        $content .= '        $model = $model->loadDTO($dto);'.PHP_EOL;
        $content .= '        return $this->repository->save($model)->createDTO();'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param array $filter'.PHP_EOL;
        $content .= '     * @return bool'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function delete(array $filter): bool'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        return $this->repository->delete($filter);'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @param array $filter'.PHP_EOL;
        $content .= '     * @return int'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function count($filter = []): int'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        return $this->repository->count($filter);'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= '}'.PHP_EOL;
        $this->fileSave($fileName, $content);
    }

    protected function generateProvider($model)
    {
        $fileName = ' app/Services/'.$model.'Service/'.$model.'ServiceProvider.php';
        $content = '<?php'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'namespace App\\Services\\'.$model.'Service;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'use App\\Services\\'.$model.'Service\\Repositories\\'.$model.'Repository as Repository;'.PHP_EOL;
        $content .= 'use App\\Services\\'.$model.'Service\\'.$model.'Service as Service;'.PHP_EOL;
        $content .= 'use Illuminate\\Support\\ServiceProvider;'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'class '.$model.'ServiceProvider extends ServiceProvider'.PHP_EOL;
        $content .= '{'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @return void'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function boot(): void'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        //'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    /**'.PHP_EOL;
        $content .= '     * @return void'.PHP_EOL;
        $content .= '     */'.PHP_EOL;
        $content .= '    public function register(): void'.PHP_EOL;
        $content .= '    {'.PHP_EOL;
        $content .= '        $this->app->singleton(Repository::class, function () {'.PHP_EOL;
        $content .= '            return new Repository();'.PHP_EOL;
        $content .= '        });'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= '        $this->app->singleton(Service::class, function () {'.PHP_EOL;
        $content .= '            return new Service($this->app->get(Repository::class));'.PHP_EOL;
        $content .= '        });'.PHP_EOL;
        $content .= '    }'.PHP_EOL;
        $content .= '}'.PHP_EOL;
        $this->fileSave($fileName, $content);
    }

    protected function fileSave($fileName, $content) {
        $fileName = trim($fileName);
        $line = str_pad('', mb_strlen($fileName)+5,'=');
        $ask = PHP_EOL.'File '.$fileName.PHP_EOL;
        $ask .= $line.PHP_EOL;
        $ask .= $content;
        $ask .= $line.PHP_EOL;
        $ask .= 'Write it?';

        if ($this->confirm($ask, true)) {
            $file = base_path($fileName);
            if (!file_exists($file) || $this->confirm('Rewrite file ' . $file . '?', false)) {
                $pathInfo = pathinfo($file);
                $dirName = $pathInfo['dirname'];
                if (!is_dir($dirName)) {
                    mkdir($dirName, 0775, true);
                }
                file_put_contents($file, $content);
            }
        }
    }

    protected function getPhpType($type) {
        $types = [
            'uuid' => 'string',
            'increments' => 'int',
            'bigIncrements' => 'int',
            'boolean' => 'bool',
            'tinyInteger' => 'int',
            'smallInteger' => 'int',
            'integer' => 'int',
            'bigInteger' => 'int',
            'float' => 'float',
            'double' => 'float',
            'string' => 'string',
            'time' => 'string',
            'timestamp' => 'Carbon',
            'timestampTz' => 'Carbon',
            'text' => 'string',
            'mediumText' => 'string',
            'longText' => 'string',
        ];
        return isset($types[$type])?$types[$type]:'string';
    }
}
