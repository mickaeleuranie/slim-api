<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\models;

use api\Api;
use api\base\Validator;
use api\components\ErrorComponent;
use api\exceptions\ApiException;
use api\interfaces\DbInterface;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Translation\Translator;

// class Db
class Db extends Model implements DbInterface
{
    // use Eloquence, Validable;

    /**
     * @var \Slim\PDO
     */
    protected $pdo;

    /**
     * @var \Symfony\Component\Cache\Adapter\RedisAdapter
     */
    protected $cache;

    /**
     * Table name
     * @var string
     */
    protected $table;

    /**
     * Table name
     * @var string
     */
    protected static $tableName;

    /**
     * @var array
     */
    protected $availableAttributes = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Model validation error
     * @var array
     */
    protected $errors = [];

    /**
     * Model validation error messages
     * Set validation messages you want to use instead of default ones
     * @var array
     */
    protected static $messages = [];

    /**
     * Rules to auto-validate model
     * Available rules as described in the laravel docs:
     * @link http://laravel.com/docs/5.0/validation#available-validation-rules
     * @var array
     */
    protected static $rules = [];

    public function __construct()
    {
        $this->pdo = Api::$pdo;
        $this->table = static::$tableName;
        $this->cache = Api::$dbcache;
    }

    public static function tableName(): string
    {
        return static::$tableName;
    }

    /**
     * Validate model before saving it
     * Then execute beforeSave(), save() and afterSave() functions
     * @return bool
     */
    public function save(array $options = []): bool
    {
        if (!$this->validate()) {
            return false;
        }

        if (!$this->beforeSave()) {
            $this->errors[] = Api::t('error.before_save');
            return false;
        }

        if (!parent::save($options)) {
            return false;
        }

        if (!$this->afterSave()) {
            $this->errors[] = Api::t('error.after_save');
            return false;
        }

        return true;
    }

    /**
     * Validate model according to custom rules
     * Set errors list if any
     * @return bool
     */
    public function validate(array $data = []): bool
    {
        if (empty($data)) {
            $data = $this->getAttributes();
        }
        $child = get_called_class();

        // get custom messages if defined in model
        // $messages = Validator::getMessages();
        $messages = array_merge(Validator::getMessages(), $child::$messages);

        $validator = Validator::make($data, $child::$rules, $messages);
        $validator->setAttributeNames($this->attributeLabels());

        if ($validator->fails()) {
            $this->errors = $validator->errors()->all();
            return false;
        }

        return true;
    }

    /**
     * Get model errors
     * @param boolean $toString If true, return commat-separated
     * @return mixed array \ string
     * @see validate()
     */
    public function errors($toString = false)
    {
        if ($toString) {
            return implode(', ', $this->errors);
        }

        return $this->errors;
    }

    /**
     * Check if actual object is a new record or not
     * @return bool
     */
    public function isNewRecord(): bool
    {
        return !$this->exists;
    }

    /**
     * Child classes must rewrite this function
     * @throws api\exceptions\ApiException
     */
    public function attributeLabels()
    {
        throw new ApiException(get_called_class() . ' must implements attributeLabels() function');
    }

    /**
     * Execute specific operations on object data before saving them
     * Child classes must rewrite this function to use it
     * @return bool
     */
    public function beforeSave(): bool
    {
        return true;
    }

    /**
     * Execute specific operations on object data before saving them
     * Child classes must rewrite this function to use it
     * @return bool
     */
    public function afterSave(): bool
    {
        return true;
    }

    public static function getResolver()
    {
        return parent::$resolver;
    }
}
