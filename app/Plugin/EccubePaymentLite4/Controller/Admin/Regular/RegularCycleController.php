<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Regular;

use Eccube\Controller\AbstractController;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularCycleType;
use Plugin\EccubePaymentLite4\Form\Type\Admin\RegularCycleFormType;
use Plugin\EccubePaymentLite4\Repository\RegularCycleRepository;
use Plugin\EccubePaymentLite4\Repository\RegularCycleTypeRepository;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RegularCycleController extends AbstractController
{
    /**
     * @var RegularCycleRepository
     */
    private $regularCycleRepository;
    /**
     * @var RegularCycleTypeRepository
     */
    private $regularCycleTypeRepository;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;

    public function __construct(
        RegularCycleRepository $regularCycleRepository,
        RegularCycleTypeRepository $regularCycleTypeRepository,
        IsActiveRegularService $isActiveRegularService
    ) {
        $this->regularCycleRepository = $regularCycleRepository;
        $this->regularCycleTypeRepository = $regularCycleTypeRepository;
        $this->isActiveRegularService = $isActiveRegularService;
        if (!$this->isActiveRegularService->isActive()) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/cycle/index",
     *     name="eccube_payment_lite4_admin_regular_cycle"
     * )
     * @Template("@EccubePaymentLite4/admin/Regular/Cycle/index.twig")
     */
    public function index()
    {
        $RegularCycles = $this
            ->regularCycleRepository
            ->findBy([], ['sort_no' => 'DESC']);

        return [
            'RegularCycles' => $RegularCycles,
        ];
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/cycle/create",
     *     name="eccube_payment_lite4_admin_regular_cycle_new"
     * )
     * @Template("@EccubePaymentLite4/admin/Regular/Cycle/edit.twig")
     */
    public function create(Request $request)
    {
        /** @var RegularCycle $RegularCycle */
        $RegularCycle = $this->regularCycleRepository->findOneBy([], [
            'sort_no' => 'DESC',
        ]);
        $sortNo = 1;
        if ($RegularCycle) {
            $sortNo = $RegularCycle->getSortNo() + 1;
        }

        $form = $this->createForm(RegularCycleFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $RegularCycle = new RegularCycle();
            /** @var RegularCycleType $RegularCycleType */
            $RegularCycleType = $this->regularCycleTypeRepository->find($formData['regular_cycle_type']);
            $RegularCycle
                ->setRegularCycleType($RegularCycleType)
                ->setDay($formData['day'])
                ->setWeek($formData['week'])
                ->setSortNo($sortNo);
            $this->entityManager->persist($RegularCycle);
            $this->entityManager->flush();
            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('eccube_payment_lite4_admin_regular_cycle');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/cycle/{id}/edit",
     *     name="eccube_payment_lite4_admin_regular_cycle_edit",
     *     requirements={"id" = "\d+"}
     * )
     * @Template("@EccubePaymentLite4/admin/Regular/Cycle/edit.twig")
     */
    public function edit(Request $request, RegularCycle $RegularCycle)
    {
        $form = $this->createForm(RegularCycleFormType::class, $RegularCycle);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($RegularCycle);
            $this->entityManager->flush();
            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('eccube_payment_lite4_admin_regular_cycle_edit', ['id' => $RegularCycle->getId()]);
        }

        return [
            'form' => $form->createView(),
            'regular_cycle_id' => $RegularCycle->getId(),
        ];
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/cycle/{id}/delete",
     *     requirements={"id" = "\d+"},
     *     name="eccube_payment_lite4_admin_regular_cycle_delete",
     *     methods={"DELETE"}
     * )
     */
    public function delete(Request $request, RegularCycle $RegularCycle)
    {
        $this->isTokenValid();

        try {
            $this->entityManager->remove($RegularCycle);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $RegularCycle->getRegularCycleType()->getName()]);
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('eccube_payment_lite4_admin_regular_cycle');
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/cycle/sort_no/move",
     *     name="eccube_payment_lite4_admin_regular_cycle_sort_no_move",
     *     methods={"POST"}
     * )
     */
    public function moveSortNo(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        if ($this->isTokenValid()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $regularCycleId => $sortNo) {
                $RegularCycle = $this->regularCycleRepository->find($regularCycleId);
                $RegularCycle->setSortNo($sortNo);
                $this->entityManager->persist($RegularCycle);
            }
            $this->entityManager->flush();
        }

        return $this->json('OK', 200);
    }
}
