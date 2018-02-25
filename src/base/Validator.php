<?php
namespace api\base;

use api\Api;
use api\models\Db;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Translation\Translator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Validation\Factory;

class Validator
{
    protected static $factory;
    protected static $validator;

    public static function instance()
    {
        if (!static::$factory) {
            // @see https://stackoverflow.com/a/31367265/5313715
            $translationFileLoader = new FileLoader(new Filesystem, __DIR__ . '/../lang');
            $translator = new Translator($translationFileLoader, Api::$locale);
            $factory = new Factory($translator);

            // handle presence verifier
            $container = Api::$container->db->getContainer();
            $presenceVerifier = new DatabasePresenceVerifier(Db::getResolver());
            $factory->setPresenceVerifier($presenceVerifier);

            static::$factory = $factory;
        }
        return static::$factory;
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::instance();
        switch (count($args)) {
            case 0:
                return $instance->$method();

            case 1:
                return $instance->$method($args[0]);

            case 2:
                return $instance->$method($args[0], $args[1]);

            case 3:
                return $instance->$method($args[0], $args[1], $args[2]);

            case 4:
                return $instance->$method($args[0], $args[1], $args[2], $args[3]);

            default:
                return call_user_func_array(array($instance, $method), $args);
        }
    }

    public static function getMessages()
    {
        return [
            'integer'  => Api::t('validation.integer'),
            'required' => Api::t('validation.required'),
            'min'      => Api::t('validation.min'),
            'max'      => Api::t('validation.max'),
            'between'  => Api::t('validation.between'),
            'email'    => Api::t('validation.email'),
            'string'   => Api::t('validation.string'),
        ];
    }
}
