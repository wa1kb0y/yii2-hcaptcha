<?php

namespace walkboy\hcaptcha\tests;

use ReflectionException;
use ReflectionMethod;
use walkboy\hcaptcha\HCaptchaValidator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use yii\base\Exception as YiiBaseException;

class HCaptchaValidatorTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $validatorClass;

    /** @var ReflectionMethod */
    private $validatorMethod;

    /**
     * @throws ReflectionException
     */
    public function testValidateValueSuccess()
    {
        $this->validatorClass
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(['success' => true, 'hostname' => 'localhost']);

        $this->assertNull($this->validatorMethod->invoke($this->validatorClass, 'test'));
        $this->assertNull($this->validatorMethod->invoke($this->validatorClass, 'test'));
    }

    public function testValidateValueFailure()
    {
        $this->validatorClass
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(['success' => false, 'hostname' => 'localhost']);

        $this->assertNotNull($this->validatorMethod->invoke($this->validatorClass, 'test'));
        $this->assertNotNull($this->validatorMethod->invoke($this->validatorClass, 'test'));
    }

    /**
     * @throws ReflectionException
     */
    public function testValidateValueException()
    {
        $this->validatorClass
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn([]);

        $this->expectException(YiiBaseException::class);
        $this->validatorMethod->invoke($this->validatorClass, 'test');
    }

    /**
     * @throws ReflectionException
     */
    public function testHostNameValidateFailure()
    {
        $this->validatorClass
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(['success' => false, 'hostname' => 'localhost']);
        $this->validatorClass
            ->expects($this->once())
            ->method('getHostName')
            ->willReturn('test');
        $this->validatorClass->checkHostName = true;

        $this->expectException(YiiBaseException::class);
        $this->validatorMethod->invoke($this->validatorClass, 'test');
    }

    /**
     * @throws ReflectionException
     */
    public function testHostNameValidateSuccess()
    {
        $this->validatorClass
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(['success' => false, 'hostname' => 'localhost']);
        $this->validatorClass
            ->expects($this->once())
            ->method('getHostName')
            ->willReturn('localhost');
        $this->validatorClass->checkHostName = true;

        $this->validatorMethod->invoke($this->validatorClass, 'test');
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->validatorClass = $this->getMockBuilder(HCaptchaValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResponse', 'getHostName'])
            ->getMock();

        $this->validatorMethod = (new ReflectionClass(HCaptchaValidator::class))->getMethod('validateValue');
        $this->validatorMethod->setAccessible(true);
    }
}
