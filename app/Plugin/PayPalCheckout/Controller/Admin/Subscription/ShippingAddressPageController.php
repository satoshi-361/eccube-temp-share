<?php

namespace Plugin\PayPalCheckout\Controller\Admin\Subscription;

use Eccube\Controller\AbstractController;
use Plugin\PayPalCheckout\Entity\PrimaryShippingAddress;
use Plugin\PayPalCheckout\Entity\SubscribingCustomer;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Form\Type\Admin\Subscription\SubscribingCustomerAddressType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ShippingAddressPageController
 * @package Plugin\PayPalCheckout\Controller\Admin\Subscription
 */
class ShippingAddressPageController extends AbstractController
{
    /**
     * @Method("GET")
     * @Route("/%eccube_admin_route%/paypal/shipping_address/{id}", requirements={"id" = "\d+"}, name="paypal_admin_shipping_address")
     * @Template("@PayPalCheckout/admin/shipping_address.twig")
     *
     * @param Request $request
     *
     * @param SubscribingCustomer $SubscribingCustomer
     * @return array|RedirectResponse
     */
    public function index(Request $request, SubscribingCustomer $SubscribingCustomer)
    {
        /** @var PrimaryShippingAddress $PrimaryShippingAddress */
        $PrimaryShippingAddress = $SubscribingCustomer->getPrimaryShippingAddress();

        /** @var FormBuilder $builder */
        $builder = $this->formFactory->createBuilder(SubscribingCustomerAddressType::class, $PrimaryShippingAddress);

        /* @var Form $form */
        $form = $builder->getForm();

        return [
            'SubscribingCustomer' => $SubscribingCustomer,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Method("POST")
     * @Route("/%eccube_admin_route%/paypal/shipping_address/{id}", requirements={"id" = "\d+"}, name="paypal_admin_shipping_address_submit")
     *
     * @param Request $request
     * @param SubscribingCustomer $SubscribingCustomer
     * @return Response
     * @throws PayPalCheckoutException
     */
    public function post(Request $request, SubscribingCustomer $SubscribingCustomer)
    {
        /* @var Form $form */
        $form = $this->createForm(SubscribingCustomerAddressType::class);
        $form->handleRequest($request);

        switch (true) {
            case $form->isSubmitted() && $form->isValid():
                /** @var PrimaryShippingAddress $PrimaryShippingAddress */
                $PrimaryShippingAddress = $form->getData();
                $SubscribingCustomer->setPrimaryShippingAddress($PrimaryShippingAddress);
                $this->entityManager->persist($SubscribingCustomer);
                $this->entityManager->flush();
                $this->addSuccess('登録しました。', 'admin');
                break;
            case $form->isSubmitted() && !$form->isValid():
                $this->addError('失敗しました。', 'admin');
                return $this->render('@PayPalCheckout/admin/shipping_address.twig', [
                    'SubscribingCustomer' => $SubscribingCustomer,
                    'form' => $form->createView(),
                ]);
            default:
                throw new PayPalCheckoutException();
        }

        return $this->redirectToRoute('paypal_admin_shipping_address', [
            'id' => $SubscribingCustomer->getId()
        ]);
    }
}
