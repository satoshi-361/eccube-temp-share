<?php

namespace Plugin\PayPalCheckout\Lib\PayPalCheckoutSdk\Core;


use Plugin\PayPalCheckout\Lib\PayPalHttp\Injector;

class GzipInjector implements Injector
{
    public function inject($httpRequest)
    {
        $httpRequest->headers["Accept-Encoding"] = "gzip";
    }
}
