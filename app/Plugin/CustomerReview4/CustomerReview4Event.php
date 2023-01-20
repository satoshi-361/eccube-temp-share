<?php

namespace Plugin\CustomerReview4;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Request\Context;
use Eccube\Event\TemplateEvent;

class CustomerReview4Event implements EventSubscriberInterface
{
    /**
     * @var Twig
     */
    protected $twig;

    /**
     * @var RequestContext
     */
    protected $requestContext;

    /**
     * ConfirmationAge4Event constructor
     *
     * @param Twig_Environment $twig
     * @param Context $requestContext
     */
    public function __construct(\Twig_Environment $twig,
        Context $requestContext )
    {
        $this->twig = $twig;
        $this->requestContext = $requestContext;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Product/list.twig' => 'list',
            'Product/detail.twig' => 'detail',
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function list(TemplateEvent $event)
    {
        $twig = '@CustomerReview4/Block/customer_review4_product_list.twig';
        $event->addSnippet($twig);
    }

    /**
     * @param TemplateEvent $event
     */
    public function detail(TemplateEvent $event)
    {
        $twig = '@CustomerReview4/Block/customer_review4_product_detail.twig';
        $event->addSnippet($twig);

        $twig = '@CustomerReview4/Block/customer_review4_product_detail_review.twig';
        $event->addSnippet($twig);
    }

}
