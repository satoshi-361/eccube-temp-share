<?php
/*
 * Copyright (C) 2018 SPREAD WORKS Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor;

// use Plugin\TabaHtmlEditor\Common\Constants;
use Plugin\TabaHtmlEditor\Common\UserConfig;

use Eccube\Event\TemplateEvent;
use Eccube\Request\Context;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Doctrine\ORM\EntityManagerInterface;

class TabaHtmlEditorEvent implements EventSubscriberInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     *
     * @var Context
     */
    private $requestContext;

    /**
     * @var array
     */
    private $eccubeConfig;

    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        Context $requestContext)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->requestContext = $requestContext;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => [
                [
                    'onKernelController',
                    100000000
                ],
            ]
        ];
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        //
        // 管理画面イベント
        //
        // if ($this->requestContext->isAdmin()) {
            //
            // テンプレートイベント
            //
            if ($event->getRequest()->attributes->has('_template') && ($template = $event->getRequest()->attributes->get('_template'))) {
                if ($template->getTemplate()) {
                    $templateName = str_replace('@','',$template->getTemplate());
                    $userConfig = UserConfig::getInstance();
                    if (($pages = $userConfig->get('page'))) {
                        foreach ($pages as $page) {
                            if (
                                $page
                                && $page['template']
                                && $templateName == str_replace('@','',$page['template'])
                                && isset($page['selector'])
                            ) {
                                $this->eventDispatcher->addListener($template->getTemplate(), function (TemplateEvent $templateEvent) use ($page) {
                                    if (!is_array($page['selector'])) $page['selector'] = [$page['selector']];
                                    $templateEvent->setParameter("selectors",$page['selector']);
                                    $templateEvent->addSnippet('@TabaHtmlEditor/admin/snippet/editor.twig');
                                    $templateEvent->addAsset('@TabaHtmlEditor/admin/snippet/asset.twig');
                                });
                                break;
                            }
                        }
                    }
                }
            }
        //}
    }
}
