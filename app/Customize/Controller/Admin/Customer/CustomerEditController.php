<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Controller\Admin\Customer;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\CustomerType;
use Eccube\Repository\CustomerRepository;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\OrderItem;
use Customize\Entity\TransferHistory;

class CustomerEditController extends AbstractController
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    public function __construct(
        CustomerRepository $customerRepository,
        EncoderFactoryInterface $encoderFactory
    ) {
        $this->customerRepository = $customerRepository;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @Route("/%eccube_admin_route%/customer/new", name="admin_customer_new", methods={"GET", "POST"})
     * @Route("/%eccube_admin_route%/customer/{id}/edit", requirements={"id" = "\d+"}, name="admin_customer_edit", methods={"GET", "POST"})
     * @Template("@admin/Customer/edit.twig")
     */
    public function index(Request $request, $id = null)
    {
        $this->entityManager->getFilters()->enable('incomplete_order_status_hidden');
        // 編集
        if ($id) {
            $Customer = $this->customerRepository
                ->find($id);

            if (is_null($Customer)) {
                throw new NotFoundHttpException();
            }

            $oldStatusId = $Customer->getStatus()->getId();
            // 編集用にデフォルトパスワードをセット
            $previous_password = $Customer->getPassword();
            $Customer->setPassword($this->eccubeConfig['eccube_default_password']);
        // 新規登録
        } else {
            $Customer = $this->customerRepository->newCustomer();

            $oldStatusId = null;
            $previous_password = null;
        }

        // 売上履歴一覧.
        $OrderItemType = OrderItemType::PRODUCT;

        if ( $request->request->has('transfer-date') ) {
            $date = $request->request->get('transfer-date') . '-01' ;
            $startDate = new \DateTime(date('Y-m-01', strtotime($date)));
            $endDate = new \DateTime(date('Y-m-t', strtotime($date)));
        } else {
            $date = date('Y-m-01');
            $startDate = new \DateTime($date);
            $date = date('Y-m-t');
            $endDate = new \DateTime($date);
        }

        $orderItemRepository = $this->getDoctrine()->getRepository(OrderItem::class);
        $qb = $orderItemRepository->createQueryBuilder('oi')
            ->leftJoin('oi.Product', 'p')
            ->leftJoin('oi.Order', 'o')
            ->where('oi.OrderItemType = :OrderItemType')
            ->andWhere('p.Customer = :Customer')
            ->andWhere('o.order_date >= :start_date')
            ->andWhere('o.order_date <= :end_date')
            ->setParameter('OrderItemType', $OrderItemType)
            ->setParameter('Customer', $Customer)
            ->setParameter('start_date', $startDate)
            ->setParameter('end_date', $endDate);

        $orderItems = $qb->getQuery()->getResult();

        $transferHistoryRepository = $this->getDoctrine()->getRepository(TransferHistory::class);
        $transferHistory = $transferHistoryRepository->findOneBy(['customer_id' => $Customer->getId(), 'transfered_date' => $endDate]);

        $balance = 0;
        if ( $transferHistory )
            $balance = $transferHistory->getBalance();

        // 会員登録フォーム
        $builder = $this->formFactory
            ->createBuilder(CustomerType::class, $Customer);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !$request->request->has('mode')) {
            log_info('会員登録開始', [$Customer->getId()]);

            $encoder = $this->encoderFactory->getEncoder($Customer);

            if ($Customer->getPassword() === $this->eccubeConfig['eccube_default_password']) {
                $Customer->setPassword($previous_password);
            } else {
                if ($Customer->getSalt() === null) {
                    $Customer->setSalt($encoder->createSalt());
                    $Customer->setSecretKey($this->customerRepository->getUniqueSecretKey());
                }
                $Customer->setPassword($encoder->encodePassword($Customer->getPassword(), $Customer->getSalt()));
            }

            // 退会ステータスに更新の場合、ダミーのアドレスに更新
            $newStatusId = $Customer->getStatus()->getId();
            if ($oldStatusId != $newStatusId && $newStatusId == CustomerStatus::WITHDRAWING) {
                $Customer->setEmail(StringUtil::random(60).'@dummy.dummy');
            }

            $this->entityManager->persist($Customer);
            $this->entityManager->flush();

            log_info('会員登録完了', [$Customer->getId()]);

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_COMPLETE, $event);

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_customer_edit', [
                'id' => $Customer->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'Customer' => $Customer,
            'orderItems' => $orderItems,
            'balance' => $balance,
            'selectedMonth' => substr($date, 0, 7),
        ];
    }
}
