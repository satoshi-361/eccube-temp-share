<?php

namespace Plugin\PayPalCheckout\Lib\PayPalHttp;

/**
 * Interface Injector
 * @package Plugin\PayPalCheckout\Lib\PayPalHttp
 *
 * Interface that can be implemented to apply injectors to Http client.
 *
 * @see HttpClient
 */
interface Injector
{
    /**
     * @param $httpRequest HttpRequest
     */
    public function inject($httpRequest);
}
