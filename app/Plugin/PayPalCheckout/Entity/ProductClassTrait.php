<?php

namespace Plugin\PayPalCheckout\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Annotation\FormAppend;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait ProductClassTrait
 * @package Plugin\PayPalCheckout\Entity
 *
 * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\PayPalCheckout\Entity\SubscribingCustomer", mappedBy="ProductClass", cascade={"persist", "remove"})
     */
    private $SubscribingCustomers;

    /**
     * @ORM\Column(name="subscription_pricing", type="integer", nullable=true, options={"unsigned": true})
     * @Assert\GreaterThanOrEqual(1)
     */
    private $subscription_pricing;

    // プラグインの初期リリース(v1.0.0)には、定期決済は含めない
    // 以下を上記Docに追加したらフォームに出る
    //* @FormAppend(
    //*  auto_render=true,
    //*  options={
    //*     "label": "paypal.admin.product.product_class.subscription_pricing"
    //*  })

    /**
     * @ORM\Column(name="use_subscription", type="boolean", nullable=true, options={"default": false})
     */
    private $use_subscription;

    // プラグインの初期リリース(v1.0.0)には、定期決済は含めない
    // 以下を上記Docに追加したらフォームに出る
    //* @FormAppend(
    //*  auto_render=true,
    //*  options={
    //*     "label": "paypal.admin.product.product_class.use_subscription"
    //*  })

    /**
     * @return int|null
     */
    public function getSubscriptionPricing()
    {
        return $this->subscription_pricing;
    }

    /**
     * @param $subscription_pricing
     * @return $this
     */
    public function setSubscriptionPricing($subscription_pricing)
    {
        $this->subscription_pricing = $subscription_pricing;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseSubscription()
    {
        return $this->use_subscription ?? false;
    }

    /**
     * @param $use_subscription
     * @return $this
     */
    public function setUseSubscription($use_subscription)
    {
        $this->use_subscription = $use_subscription;
        return $this;
    }
}
