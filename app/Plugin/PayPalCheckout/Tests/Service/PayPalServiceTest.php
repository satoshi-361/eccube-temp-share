<?php

namespace Plugin\PayPalCheckout\Tests\Service;

use Eccube\Tests\Service\AbstractServiceTestCase;
use Plugin\PayPalCheckout\Service\PayPalService;

/**
 * Class PayPalServiceTest
 * @package Plugin\PayPalCheckout\Tests\Service
 */
class PayPalServiceTest extends AbstractServiceTestCase
{
    /**
     * @var PayPalService
     */
    private $service;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->service = self::$container->get(PayPalService::class);
    }

    /**
     * @test
     */
    public function instance()
    {
        $this->assertInstanceOf(PayPalService::class, $this->service);
    }
}
