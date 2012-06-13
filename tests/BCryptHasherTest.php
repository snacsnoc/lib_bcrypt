<?php

require_once dirname(__FILE__) . '/../lib_bcrypt.php';

/**
 * Test class for BCryptHasher.
 */
class BCryptHasherTest extends PHPUnit_Framework_TestCase {

    /**
     * @var BCryptHasher
     */
    protected $BCryptHasher;

    /**
     * @var Test password 
     */
    protected $test_password;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->BCryptHasher = new BCryptHasher;

        $this->test_password = 'mytestpassword12345!@#$';
    }

    /**
     * Test hashing a password
     * @covers BCryptHasher::HashPassword
     */
    public function testHashPassword() {


        $hash = $this->BCryptHasher->HashPassword($this->test_password, 8);

        $this->assertStringStartsWith('$2a$08$', $hash);
    }

    /**
     * Test checking a hashed password
     * @covers BCryptHasher::CheckPassword
     */
    public function testCheckPassword() {

        $hash = $this->BCryptHasher->HashPassword($this->test_password, 8);

        $this->assertTrue($this->BCryptHasher->CheckPassword($this->test_password, $hash));
    }

}

?>
