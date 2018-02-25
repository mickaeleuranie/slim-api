<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\tests;

use api\Api;
use api\models\AuthAssignment;
use api\models\EmailUpdate;
use api\models\Family;
use api\models\FamilyMember;
use api\models\FamilyMemberAllergy;
use api\models\FamilyMemberGoal;
use api\models\FamilyMemberMedicalCondition;
use api\models\Newsletter;
use api\models\OauthAccessToken;
use api\models\OauthClient;
use api\models\Profile;
use api\models\User;
use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

abstract class ApiTestCase extends TestCase
{
    use TestCaseTrait;

    /** @var Response */
    private $response;

    /** @var App */
    private $app;

    /** @var \PDO */
    private $pdo;

    protected function request($method, $url, array $requestParameters = [], $role = 'user')
    {
        $request = $this->prepareRequest($method, $url, $requestParameters, $role);
        $response = new Response();
        $app = $this->app;
        Api::check($request, $response, $app->getContainer());
        $this->response = $app($request, $response);
    }

    protected function assertThatResponseHasStatus($expectedStatus)
    {
        $this->assertEquals($expectedStatus, $this->response->getStatusCode());
    }

    protected function assertThatResponseHasContentType($expectedContentType)
    {
        $this->assertContains($expectedContentType, $this->response->getHeader('Content-Type')[0]);
    }

    protected function responseData()
    {
        return json_decode((string) $this->response->getBody(), true);
    }

    /** {@inheritdoc} */
    protected function setUp()
    {
        parent::setUp();
        $this->app =  require __DIR__.'/../app_tests.php';
        $this->getPDO()->beginTransaction();

        // remove unwanted auth assignment and tokens
        $this->clean();
    }

    /**
     * Returns the database operation executed in test setup.
     *
     * @return PHPUnit_Extensions_Database_Operation_DatabaseOperation
     */
    protected function getSetUpOperation()
    {
        $cascadeTruncates = true; //if you want cascading truncates, false otherwise
                                  //if unsure choose false

        return new PHPUnit_Extensions_Database_Operation_Composite([
            new PHPUnit_Extensions_Database_Operation_Truncate($cascadeTruncates),
            PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ]);
        // die('hard');
        // return PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT ( true );
    }

    /** {@inheritdoc} */
    protected function tearDown()
    {
        $this->app = null;
        $this->response = null;
        $this->getPDO()->rollBack();
    }

    /** {@inheritdoc} */
    protected function getTearDownOperation()
    {
        return PHPUnit_Extensions_Database_Operation_Factory::TRUNCATE();
    }

    private function prepareRequest($method, $url, array $requestParameters, $role = 'user')
    {
        $env = Environment::mock([
            'SERVER_NAME'       => getenv('TEST_HOST'),
            'HTTP_HOST'         => getenv('TEST_HOST'),
            'SCRIPT_NAME'       => '/index.php',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'REQUEST_URI'       => $url,
            'REQUEST_METHOD'    => $method,
        ]);

        // add API key and token in header according to user's type
        $env['HTTP_' . str_replace('-', '_', getenv('API_REQUEST_HEADERS_PREFIX')) . '_KEY']   = getenv('API_TOKEN_TEST');
        $env['HTTP_' . str_replace('-', '_', getenv('API_REQUEST_HEADERS_PREFIX')) . '_TOKEN'] = 'token' . strtolower($role);

        $parts = explode('?', $url);
        if (isset($parts[1])) {
            $env['QUERY_STRING'] = $parts[1];
        }

        // add request parameters according to method
        $body = new RequestBody();
        switch (strtoupper($method)) {
            // url encode given parameters
            case 'GET':
                if (!empty($requestParameters)) {
                    $url .= '?' . http_build_query($requestParameters);
                }
                $env['REQUEST_URI'] = $url;
                $parts = explode('?', $url);
                if (isset($parts[1])) {
                    $env['QUERY_STRING'] = $parts[1];
                }
                $body->write(json_encode($requestParameters));
                break;

            // add given parameter to body
            case 'POST':
            default:
                $body->write(json_encode($requestParameters));
                break;
        }
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body);
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withMethod($method);
        return $request;
    }

    /**
     * @return \PDO
     */
    public function getPDO()
    {
        if (!$this->pdo) {
            $this->pdo = $this->app->getContainer()->pdo;
        }

        return $this->pdo;
    }

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        // return $this->createDefaultDBConnection($this->pdo, getenv('DB_NAME_TEST'));
        return $this->createDefaultDBConnection($this->pdo, ':memory:');
    }

    /**
     * Clean given tables with TRUNCATE
     * @param array $tables
     */
    public function cleanTables(array $tables)
    {
        $this->getPDO()->query('SET @TEMP_PREV_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;')->execute();
        $this->getPDO()->query('SET FOREIGN_KEY_CHECKS=0;')->execute();
        foreach ($tables as $tableName) {
            $this->getPDO()->query('TRUNCATE TABLE `' . $tableName . '`;')->execute();
        }
        $this->getPDO()->query('SET FOREIGN_KEY_CHECKS = @TEMP_PREV_FOREIGN_KEY_CHECKS;')->execute();
    }

    /**
     * Reset auth tables
     */
    private function clean()
    {
        $this->cleanTables([
            AuthAssignment::tableName(),
            OauthAccessToken::tableName(),
            OauthClient::tableName(),
            User::tableName(),
            EmailUpdate::tableName(),
        ]);
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/_files/basic-seed.xml');
    }
}
