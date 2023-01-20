<?php

namespace Plugin\PayPalCheckout\Service;

use Eccube\Entity\Customer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ログイン情報に関するサービス
 *
 * ControllerTrait を参考に実装
 * @see Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait
 *
 * @package Plugin\PayPalCheckout\Service
 */
class LoginService {

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * ログイン済みユーザかを返す
     *
     * @return bool
     */
    public function isLoginUser(): bool
    {
        if (!$this->container->has('security.authorization_checker')) {
            throw new Exception('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        return $this->container->get('security.authorization_checker')->isGranted('ROLE_USER');
    }

    /**
     * ログインしているユーザの情報を返す
     *
     * @return Customer|null
     */
    public function getCustomer()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new Exception('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }
        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return null;
        }
        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }
        return $user;
    }
}
