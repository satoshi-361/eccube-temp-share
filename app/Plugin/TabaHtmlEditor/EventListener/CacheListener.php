<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor\EventListener;

use Plugin\TabaHtmlEditor\Common\Constants;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CacheListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * コンストラクタ
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 
     * @param FilterResponseEvent $event
     * @param string $eventName
     */
    public function onKernelResponse(FilterResponseEvent $event,$eventName) {
        if ($this->container->has(Constants::CONTAINER_KEY_NAME)) {
            if (!$this->container->get(Constants::CONTAINER_KEY_NAME)->get(Constants::HTTP_CACHE_STATUS)) {
                return;
            }
        } else {
            return;
        }

        $response = $event->getResponse();
        $response->setMaxAge(60 * 60 * 24);
        $response->setSharedMaxAge(60 * 60 * 24);
        //$response->setPublic();
        $response->setLastModified(null);
        $response->setExpires((new \DateTime())->modify('+1 day'));
    }
}