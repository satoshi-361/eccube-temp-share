<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Mypage\Change;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateRegularOrder implements EventSubscriberInterface
{
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        RegularOrderRepository $regularOrderRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->regularOrderRepository = $regularOrderRepository;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::FRONT_MYPAGE_CHANGE_INDEX_COMPLETE => 'index',
        ];
    }

    public function index(EventArgs $eventArgs)
    {
        /** @var Customer $Customer */
        $Customer = $eventArgs->getArgument('Customer');
        /** @var RegularOrder[] $RegularOrders */
        $RegularOrders = $this->regularOrderRepository->findBy(['Customer' => $Customer]);
        foreach ($RegularOrders as $RegularOrder) {
            $RegularOrder
                ->setSex($Customer->getSex())
                ->setPref($Customer->getPref())
                ->setJob($Customer->getJob())
                ->setName01($Customer->getName01())
                ->setName02($Customer->getName02())
                ->setKana01($Customer->getKana01())
                ->setKana02($Customer->getKana02())
                ->setCompanyName($Customer->getCompanyName())
                ->setEmail($Customer->getEmail())
                ->setPhoneNumber($Customer->getPhoneNumber())
                ->setPostalCode($Customer->getPostalCode())
                ->setAddr01($Customer->getAddr01())
                ->setAddr02($Customer->getAddr02())
                ->setUpdateDate(new \DateTime())
            ;
            $this->entityManager->persist($RegularOrder);
        }
        $this->entityManager->flush();
    }
}
