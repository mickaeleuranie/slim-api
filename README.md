## About this project

> This documentation is still in progress.
> Sources, tools list, examples and more are coming soon

Slim API skeleton, based on ![Tuupola slim-api-skeleton](https://github.com/tuupola/slim-api-skeleton) and use Eloquent ORM.

It uses lot of tools like :

- tuupola/dbal-psr3-logger
- zircote/swagger-php
- projek-xyz/slim-plates
- vlucas/phpdotenv
- firebase/php-jwt (not activated yet)
- illuminate/validation
- illuminate/translation
- predis/predis

## Installation

### Script

Just run `install.sh` script. You can run it without parameter or you can set them.
Without parameter, script will prompt you for every needed value.

```
./install.sh
```

Paramètres possibles :

`-d` : Database name
`-u` : Database user name
`-p` : Database user password
`-y` : Don't ask user to continue or not

Example :

```
./install.sh --dbname=slim_api --dbuser=root --dbpassword=my-passwOrd -y
```

### Hooks

To install hooks, go to `hooks` folder and run

```
chmod +x add-hooks.sh
./add-hooks.sh
```

## Commandes

### Migrations

Create new migration

```
# local database
composer phinx create MigrationName

# test database
composer phinxtest create MigrationName
```

Run remaining migration

```
# local database
composer phinx migrate

# test database
cmposer phinxtest migrate
```

Cancel last executed migration

```
# local database
composer phinx rollback

# test database
cmposer phinxtest rollback
```

### Test if code is PSR-2 complient

```
composer phpcs
```

### Run tests

All tests :

```
composer phpunit
```

Functional tests only :

```
vendor/bin/phpunit --testsuite functional
```

Unit tests only :

```
vendor/bin/phpunit --testsuite unit
```

Run all tests then generate resume page with passed/failed tests

```
composer runtests
```

### Generate Swagger API documentation

```
composer swagger
```

### Generate Code documentation

```
composer generatedoc
```

## CLI

This project allows you to create CLI tasks as follow.
Documentation about this coming soon.

## CircleCI

CircleCI configuration file is located in .circleci folder

## Documentations

- Slim : http://slimframework.com/
- Phinx : http://docs.phinx.org/en/latest/
- PHPUnit : https://phpunit.de/manual/current/en/