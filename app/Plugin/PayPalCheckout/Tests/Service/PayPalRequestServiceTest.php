<?php

namespace Plugin\PayPalCheckout\Tests\Service;

use Eccube\Tests\Service\AbstractServiceTestCase;
use Plugin\PayPalCheckout\Service\PayPalRequestService;
use Plugin\PayPalCheckout\Service\PayPalService;

/**
 * Class PayPalRequestServiceTest
 * @package Plugin\PayPalCheckout\Tests\Service
 */
class PayPalRequestServiceTest extends AbstractServiceTestCase
{
    /**
     * @var PayPalRequestService
     */
    private $service;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $service = self::$container->get(PayPalService::class);
        $this->service = $service->client;
    }

    /**
     * @test
     */
    public function instance()
    {
        $this->assertInstanceOf(PayPalRequestService::class, $this->service);
    }
}
