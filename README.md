## About this project

> This documentation is still in progress.
> Sources, tools list, examples and more are coming soon

Slim API skeleton, based on [Tuupola slim-api-skeleton](https://github.com/tuupola/slim-api-skeleton) and using Eloquent ORM.

It uses lot of tools like :

- tuupola/dbal-psr3-logger
- zircote/swagger-php
- projek-xyz/slim-plates
- vlucas/phpdotenv
- firebase/php-jwt (not activated yet)
- illuminate/validation
- illuminate/translation
- predis/predis
- robmorgan/phinx

## Installation

### Script

Just run `install.sh` script. You can run it without parameter or you can set them.
Without parameter, script will prompt you for every needed value.

```
./install.sh
```

Paramètres possibles :

`-d` or `--dbname` : Database name
`-u` or `--dbuser` : Database user name
`-p` or `--dbpassword` : Database user password
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

## API calls

### API keys

To add a new API key and link him to a user, you need to add an entry in `api_key` table first, then in `user_api_key` with last created `api_key` id.

### Tokens

New access token in created when user logs in. You can define a token as permanent by adding `permanent` value in `oauth_access_tokens.type` field.

If a token isn't permanent, its expire date will be updated to 24h after last call. If a token isn't valid anymore, user will need to log in again.

### How to pass API key and token ?

#### V1

You can pass `apikey` and `access_token` parameters in query params or in request HEADERS. The later is the one you should use.
Passing them in query params functionnality will probably be removed in future versions.

##### From query params

```
http(s)://your-domain.com[/your-path]/controller/action?apikey=API_KEY&access_token=TOKEN
```

##### From request HEADERS

HEADERS parameters names are formatted like this : [API_REQUEST_HEADERS_PREFIX]_KEY and [API_REQUEST_HEADERS_PREFIX]_TOKEN. `[API_REQUEST_HEADERS_PREFIX]` value is defined in `.env` file.

Example :

```
X-SLIM-API-KEY: API_KEY_VALUE
X-SLIM-API-TOKEN: TOKEN_VALUE
```

## CircleCI

CircleCI configuration file is located in .circleci folder

## Documentations

- Slim : http://slimframework.com/
- Phinx : http://docs.phinx.org/en/latest/
- PHPUnit : https://phpunit.de/manual/current/en/