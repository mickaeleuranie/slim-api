<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\tests\functional;

use api\components\EmailComponent;
use api\models\User;
use api\exceptions\BadRequestException;
use api\exceptions\ForbiddenException;
use api\exceptions\NotFoundException;
use api\exceptions\UnauthorizedException;
use api\tests\ApiTestCase;
use PHPUnit\DbUnit\DataSet\CompositeDataSet;
use PHPUnit\DbUnit\TestCaseTrait;

class UserTest extends FunctionalTest
{
    use TestCaseTrait;

    /**********
     * SIGNUP *
     **********/

    public function testSignupPasswordTooShort()
    {
        $this->expectException(BadRequestException::class);
        $this->request('POST', '/v1/user/signup', ['email' => 'ipsum@example.com', 'password' => 'ipsum']);
    }

    public function testSignup()
    {
        $this->request('POST', '/v1/user/signup', ['email' => 'ipsum@example.com', 'password' => 'ipsumipsum']);
        $this->assertThatResponseHasStatus(201);
        $this->assertThatResponseHasContentType('application/json');
        $this->commonAssertions();
    }

    public function testMissingParameters()
    {
        $this->expectException(BadRequestException::class);
        $this->request('POST', '/v1/user/signup', ['email' => 'test-user@example.com']);
    }

    public function testExistingEmail()
    {
        $this->expectException(UnauthorizedException::class);
        $this->request('POST', '/v1/user/signup', ['email' => 'lorem@example.com', 'password' => 'loremlorem']);
    }

    /*********
     * LOGIN *
     *********/

    public function testLoginFail()
    {
        $this->expectException(UnauthorizedException::class);
        $this->request('POST', '/v1/user/login', [
           'email'    => 'lorem@example.com',
           'password' => 'loremIpsum',
        ]);
    }

    public function testLoginUnknown()
    {
        $this->expectException(UnauthorizedException::class);
        $this->request('POST', '/v1/user/login', [
           'email'    => 'ipsum@example.com',
           'password' => 'ipsum',
        ]);
    }

    /*******
     * GET *
     *******/
    public function testGetWithoutRights()
    {
        $this->expectException(ForbiddenException::class);
        $this->request('GET', '/v1/user/get/1');
    }

    public function testGetExistingUser()
    {
        $this->request('GET', '/v1/user/get/1', [], 'admin');
        $this->assertThatResponseHasStatus(200);
        $this->assertThatResponseHasContentType('application/json');
        $this->commonAssertions();
    }

    public function testGetEmptyParam()
    {
        $this->expectException(BadRequestException::class);
        $this->request('GET', '/v1/user/get/0', [], 'admin');
    }

    public function testGetUnknownUser()
    {
        $this->expectException(NotFoundException::class);
        $this->request('GET', '/v1/user/get/10', [], 'admin');
    }

    /**********
     * STATUS *
     **********/

    public function testStatusUser()
    {
        $this->request('GET', '/v1/user/status');
        $this->assertThatResponseHasStatus(200);
        $this->assertThatResponseHasContentType('application/json');
        $this->commonAssertions();
    }

    public function testStatusAdmin()
    {
        $this->request('GET', '/v1/user/status', [], 'admin');
        $this->assertThatResponseHasStatus(200);
        $this->assertThatResponseHasContentType('application/json');
        $this->assertArrayHasKey('username', $this->responseData());
    }

    /*************
     * SCENARIOS *
     *************/

    public function testAllMobileAppUserActions()
    {
        // try to signup
        $this->request('POST', '/v1/user/signup', ['email' => 'ipsum@example.com', 'password' => 'ipsumipsum']);
        $this->assertThatResponseHasStatus(201);
        $this->assertThatResponseHasContentType('application/json');
        $this->commonAssertions();

        // try to log in with correct password
        $this->request('POST', '/v1/user/login', [
           'login'    => 'ipsum@example.com',
           'password' => 'ipsumipsum',
        ]);
        $this->assertThatResponseHasStatus(200);
        $this->assertThatResponseHasContentType('application/json');
        $this->commonAssertions();

        // try to log in with wrong password
        $this->expectException(UnauthorizedException::class);
        $this->request('POST', '/v1/user/login', [
           'login'    => 'ipsum@example.com',
           'password' => 'lorem',
        ]);
    }

    protected function commonAssertions()
    {
        $responseData = $this->responseData();
        $this->assertArrayHasKey('username', $responseData);
        $this->assertArrayHasKey('locale', $responseData);
        $this->assertNotEmpty($responseData['locale']);
        $this->assertArrayHasKey('updated_at', $responseData);
        // $this->assertArrayHasKey('autologin_token', $responseData);
        // $this->assertNotEmpty($responseData['autologin_token']);
    }

    /** {@inheritdoc} */
    protected function setUp()
    {
        parent::setUp();
        $this->getConnection()->createDataSet();
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        $ds1 = $this->createFlatXMLDataSet(dirname(__FILE__).'/../fixtures/basic-seed.xml');
        $ds2 = $this->createFlatXMLDataSet(dirname(__FILE__).'/../fixtures/user-seed.xml');

        $compositeDs = new CompositeDataSet;
        $compositeDs->addDataSet($ds1);
        $compositeDs->addDataSet($ds2);

        return $compositeDs;
    }
}
