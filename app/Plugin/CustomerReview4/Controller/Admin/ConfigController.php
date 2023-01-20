<?php

namespace Plugin\CustomerReview4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\CustomerReview4\Form\Type\Admin\ConfigType;
use Plugin\CustomerReview4\Repository\CustomerReviewConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(CustomerReviewConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/customer_review/config", name="customer_review4_admin_config")
     * @Template("@CustomerReview4/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('customer_review4_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
