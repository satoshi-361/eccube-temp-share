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

namespace Plugin\TopBanner\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Form\Type\Admin\SearchProductType;
use Plugin\TopBanner\Entity\RecommendProduct;
use Plugin\TopBanner\Form\Type\RecommendProductType;
use Plugin\TopBanner\Repository\RecommendProductRepository;
use Plugin\TopBanner\Service\RecommendService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RecommendController.
 */
class RecommendController extends AbstractController
{
    /**
     * @var RecommendProductRepository
     */
    private $recommendProductRepository;

    /**
     * @var RecommendService
     */
    private $recommendService;

    /**
     * RecommendController constructor.
     *
     * @param RecommendProductRepository $recommendProductRepository
     * @param RecommendService $recommendService
     */
    public function __construct(RecommendProductRepository $recommendProductRepository, RecommendService $recommendService)
    {
        $this->recommendProductRepository = $recommendProductRepository;
        $this->recommendService = $recommendService;
    }

    /**
     * TOPバナー商品一覧.
     *
     * @param Request     $request
     *
     * @return array
     * @Route("/%eccube_admin_route%/plugin/top_banner", name="plugin_top_banner_list")
     * @Template("@TopBanner/admin/index.twig")
     */
    public function index(Request $request)
    {
        $pagination = $this->recommendProductRepository->getRecommendList();

        return [
            'pagination' => $pagination,
            'total_item_count' => count($pagination),
        ];
    }

    /**
     * Create & Edit.
     *
     * @param Request     $request
     * @param int         $id
     *
     * @throws \Exception
     *
     * @return array|RedirectResponse
     * @Route("/%eccube_admin_route%/plugin/top_banner/new", name="plugin_top_banner_new")
     * @Route("/%eccube_admin_route%/plugin/top_banner/{id}/edit", name="plugin_top_banner_edit", requirements={"id" = "\d+"})
     * @Template("@TopBanner/admin/regist.twig")
     */
    public function edit(Request $request, $id = null)
    {
        /* @var RecommendProduct $Recommend */
        $Recommend = null;
        $Product = null;
        if (!is_null($id)) {
            // IDからTOPバナー商品情報を取得する
            $Recommend = $this->recommendProductRepository->find($id);

            if (!$Recommend) {
                $this->addError('plugin_top_banner.admin.not_found', 'admin');
                log_info('The recommend product is not found.', ['Recommend id' => $id]);

                return $this->redirectToRoute('plugin_top_banner_list');
            }

            $Product = $Recommend->getProduct();
        }

        // formの作成
        /* @var Form $form */
        $form = $this->formFactory
            ->createBuilder(RecommendProductType::class, $Recommend)
            ->getForm();

        $form->handleRequest($request);
        $data = $form->getData();
        if ($form->isSubmitted() && $form->isValid()) {
            $service = $this->recommendService;
            if (is_null($data['id'])) {
                if ($status = $service->createRecommend($data)) {
                    $this->addSuccess('plugin_top_banner.admin.register.success', 'admin');
                    log_info('Add the new recommend product success.', ['Product id' => $data['Product']->getId()]);
                }
            } else {
                if ($status = $service->updateRecommend($data)) {
                    $this->addSuccess('plugin_top_banner.admin.update.success', 'admin');
                    log_info('Update the recommend product success.', ['Recommend id' => $Recommend->getId(), 'Product id' => $data['Product']->getId()]);
                }
            }

            if (!$status) {
                $this->addError('plugin_top_banner.admin.not_found', 'admin');
                log_info('Failed the recommend product updating.', ['Product id' => $data['Product']->getId()]);
            }

            return $this->redirectToRoute('plugin_top_banner_list');
        }

        if (!empty($data['Product'])) {
            $Product = $data['Product'];
        }

        $arrProductIdByRecommend = $this->recommendProductRepository->getRecommendProductIdAll();

        return $this->registerView(
            [
                'form' => $form->createView(),
                'recommend_products' => json_encode($arrProductIdByRecommend),
                'Product' => $Product,
            ]
        );
    }

    /**
     * TOPバナー商品の削除.
     *
     * @param Request     $request
     * @param RecommendProduct $RecommendProduct
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/%eccube_admin_route%/plugin/top_banner/{id}/delete", name="plugin_top_banner_delete", requirements={"id" = "\d+"}, methods={"DELETE"})
     */
    public function delete(Request $request, RecommendProduct $RecommendProduct)
    {
        // Valid token
        $this->isTokenValid();
        // TOPバナー商品情報を削除する
        if ($this->recommendProductRepository->deleteRecommend($RecommendProduct)) {
            log_info('The recommend product delete success!', ['Recommend id' => $RecommendProduct->getId()]);
            $this->addSuccess('plugin_top_banner.admin.delete.success', 'admin');
        } else {
            $this->addError('plugin_top_banner.admin.not_found', 'admin');
            log_info('The recommend product is not found.', ['Recommend id' => $RecommendProduct->getId()]);
        }

        return $this->redirectToRoute('plugin_top_banner_list');
    }

    /**
     * Move rank with ajax.
     *
     * @param Request     $request
     *
     * @throws \Exception
     *
     * @return Response
     *
     * @Route("/%eccube_admin_route%/plugin/top_banner/sort_no/move", name="plugin_top_banner_rank_move")
     */
    public function moveRank(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $arrRank = $request->request->all();
            $arrRankMoved = $this->recommendProductRepository->moveRecommendRank($arrRank);
            log_info('Recommend move rank', $arrRankMoved);
        }

        return new Response('OK');
    }

    /**
     * 編集画面用のrender.
     *
     * @param array       $parameters
     *
     * @return array
     */
    protected function registerView($parameters = [])
    {
        // 商品検索フォーム
        $searchProductModalForm = $this->formFactory->createBuilder(SearchProductType::class)->getForm();
        $viewParameters = [
            'searchProductModalForm' => $searchProductModalForm->createView(),
        ];
        $viewParameters += $parameters;

        return $viewParameters;
    }
}
