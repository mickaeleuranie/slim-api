<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\tests\unit;

use api\models\EmailUpdate;
use api\models\User;
use api\components\AccountComponent;
use api\components\UserComponent;
use api\exceptions\BadRequestException;
use api\exceptions\ForbiddenException;
use api\exceptions\UnauthorizedException;
use PHPUnit\DbUnit\DataSet\CompositeDataSet;
use PHPUnit\DbUnit\TestCaseTrait;

class UserTest extends UnitTest
{
    use TestCaseTrait;

    public function testGenerateAutologinToken()
    {
        $user = new User;
        $this->assertEmpty($user->autologin_token);
        $user->generateAutologinToken();
        $this->assertNotEmpty($user->autologin_token);
    }

    public function testPassword()
    {
        $user = new User;
        $this->assertEmpty($user->password);
        $user->setPassword('loremIpsum');
        $this->assertNotEmpty($user->password_hash);
        $this->assertTrue($user->validatePassword('loremIpsum'));
        $this->assertFalse($user->validatePassword('loremipsum'));
        $this->assertFalse($user->validatePassword('dolorSit'));
    }

    public function testLogin()
    {
        $result = UserComponent::login('lorem@example.com', 'lorem');
        $this->assertNotempty($result['user']);
        $this->assertNotempty($result['access_token']);
    }

    public function testLoginWrongEmail()
    {
        $this->expectException(UnauthorizedException::class);
        $result = UserComponent::login('wrong@example.com', 'lorem');
    }

    public function testLoginWrongPassword()
    {
        $this->expectException(UnauthorizedException::class);
        $result = UserComponent::login('lorem@example.com', 'password');
    }

    public function testAutologin()
    {
        $user = User::find(1);
        $result = UserComponent::autologin('XXXXXXXXXXXXXXXX');
        $this->assertNotempty($result['user']);
        $this->assertNotempty($result['access_token']);
    }

    public function testAutologinWrongToken()
    {
        $this->expectException(ForbiddenException::class);
        $result = UserComponent::autologin('ZZZZZZZ');
    }

    public function testSocialSignup()
    {
        $user = UserComponent::socialSignup('facebook', [
            'social_identifier' => '1234567890',
            'social_email'      => 'lorem@example.com',
        ]);
        $this->assertNotempty($user['email']);
        $this->assertNotempty($user['auth_key']);
        $this->assertNotempty($user['password_hash']);
        $this->assertNotempty($user['autologin_token']);
        $this->assertEquals('facebook', $user['social_provider']);
    }

    /**
     * Test user creation email already exists
     */
    public function testCreateAlreadyExists()
    {
        $this->expectException(UnauthorizedException::class);
        $userCrash = UserComponent::create(
            [
                'email'     => 'lorem@example.com',
                'password'  => 'ipsumipsum',
                'origin'    => 'mobile-application',
            ],
            false
        );
    }

    /**
     * Test user creation password too short
     */
    public function testCreatePasswordTooShort()
    {
        $this->expectException(BadRequestException::class);
        $userCrash = UserComponent::create(
            [
                'email'     => 'ipsum@example.com',
                'password'  => 'ipsum',
                'origin'    => 'mobile-application',
            ],
            false
        );
        return $user;
    }

    /**
     * Test user creation
     */
    public function testCreate()
    {
        $user = UserComponent::create(
            [
                'email'     => 'ipsum@example.com',
                'password'  => 'ipsumipsum',
            ],
            false
        );

        $this->assertNotEmpty($user);
        $this->assertNotEmpty($user->token);
        $this->assertTrue($user->checkAccess('user'));
        return $user;
    }

    /**
     * Test profile edition
     * Reproduce every steps possible from mobile application
     * @depends testCreate
     */
    public function testProfileEdit($user)
    {
        $user = $this->getUser();

        // set name
        $user = UserComponent::edit($user, [
            'username' => 'Lorem',
        ]);
        $this->assertEquals('Lorem', $user->username);
    }

    /**
     * Test account edition
     * Reproduce every steps possible from mobile application
     * @depends testCreate
     */
    public function testAccountEdit($user)
    {
        $user = $this->getUser();

        // email
        $user = AccountComponent::edit($user, [
            'email'    => 'loremM@example.com',
            'password' => 'lorem',
        ]);
        // check that email update hash has been created
        $emailUpdate = EmailUpdate::where(['user_id' => $user->id])->first();
        $this->assertNotEmpty($emailUpdate);
        $this->assertEquals('loremM@example.com', $emailUpdate->email);

        // password
        $this->assertTrue($user->validatePassword('lorem'));
        $this->assertFalse($user->validatePassword('loremlorem'));
        $user = AccountComponent::edit($user, [
            'password'     => 'lorem',
            'password_new' => 'loremlorem',
        ]);
        $this->assertFalse($user->validatePassword('lorem'));
        $this->assertTrue($user->validatePassword('loremlorem'));
    }

    /**
     * Create user to run tests on it
     * @return api\models\User
     */
    private function getUser()
    {

        return User::where(['id' => 3])->first();
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
