<?php

namespace Plugin\PayPalCheckout\Controller\Admin\Subscription;

use DateTime;
use Eccube\Controller\AbstractController;
use Plugin\PayPalCheckout\Entity\ConfigSubscription;
use Plugin\PayPalCheckout\Entity\SubscribingCustomer;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Form\Type\Admin\Subscription\ConfigSubscriptionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ConfigurationPageController
 * @package Plugin\PayPalCheckout\Controller\Admin\Subscription
 */
class ConfigurationPageController extends AbstractController
{
    /**
     * @Method("GET")
     * @Route("/%eccube_admin_route%/paypal/subscription/configure/{id}", requirements={"id" = "\d+"}, name="paypal_admin_subscription_configure")
     * @Template("@PayPalCheckout/admin/subscription_configure.twig")
     *
     * @param Request $request
     * @param SubscribingCustomer $SubscribingCustomer
     * @return array
     */
    public function index(Request $request, SubscribingCustomer $SubscribingCustomer)
    {
        /** @var FormBuilder $builder */
        $builder = $this->formFactory->createBuilder(ConfigSubscriptionType::class, $SubscribingCustomer->createConfigSubscription());

        /* @var Form $form */
        $form = $builder->getForm();
        return [
            'SubscribingCustomer' => $SubscribingCustomer,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Method("POST")
     * @Route("/%eccube_admin_route%/paypal/subscription/configure/{id}", requirements={"id" = "\d+"}, name="paypal_admin_subscription_configure_submit")
     *
     * @param Request $request
     * @param SubscribingCustomer $SubscribingCustomer
     * @return RedirectResponse|Response
     * @throws PayPalCheckoutException
     */
    public function post(Request $request, SubscribingCustomer $SubscribingCustomer)
    {
        /* @var Form $form */
        $form = $this->createForm(ConfigSubscriptionType::class);
        $form->handleRequest($request);
        switch (true) {
            case $form->isSubmitted() && $form->isValid():
                /** @var ConfigSubscription $configSubscription */
                $configSubscription = $form->getData();
                $SubscribingCustomer->setNextPaymentDate($configSubscription->getNextPaymentDate());
                if ($configSubscription->getIsDeleted()) {
                    $SubscribingCustomer->setWithdrawalAt(new DateTime());
                } else {
                    $SubscribingCustomer->setWithdrawalAt(null);
                }
                $this->entityManager->persist($SubscribingCustomer);
                $this->entityManager->flush();
                $this->addSuccess('登録しました。', 'admin');
                break;
            case $form->isSubmitted() && !$form->isValid():
                $this->addError('失敗しました。', 'admin');
                return $this->render('@PayPalCheckout/admin/subscription_configure.twig', [
                    'SubscribingCustomer' => $SubscribingCustomer,
                    'form' => $form->createView(),
                ]);
            default:
                throw new PayPalCheckoutException();
        }

        return $this->redirectToRoute('paypal_admin_subscription_configure', [
            'id' => $SubscribingCustomer->getId()
        ]);
    }
}
