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

namespace Customize\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Eccube\Entity\Product as Blog;
use Plugin\TopBanner\Entity\RecommendProduct as TopBanner;
use Plugin\Pickup4\Entity\RecommendProduct as PickupBlog;
use Plugin\OrderBySale4\Service\RankService;
use Plugin\Pickup4\Repository\RecommendProductRepository as PickupRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class TopController extends AbstractController
{
    /**
     * @var RankService
     */
    protected $rankService;

    /**
     * @var PickupRepository
     */
    protected $pickupRepository;

    public function __construct(
        RankService $rankService,
        PickupRepository $pickupRepository
    ) {
        $this->rankService = $rankService;
        $this->pickupRepository = $pickupRepository;
    }

    /**
     * @Route("/", name="homepage", methods={"GET"})
     * @Template("index.twig")
     */
    public function index()
    {
        $limit = 3;

        $Ranks = $this->rankService->getRanks( $limit );
        $Pickups = $this->pickupRepository->getRecommendProduct( $limit );

        return [
            'Ranks' => $Ranks,
            'Pickups' => $Pickups,
            'page_no' => 0,
            'limit' => $limit,
        ];
    }

    /**
     * @Route("/top/blog_blocks", name="top_blog_blocks", methods={"GET", "POST"})
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getBlogBlocks(Request $request)
    {
        $pageNo = $request->request->get('page_no');
        $limit = $request->request->get('limit');

        $Ranks = $this->rankService->getRanksByParameters( $pageNo * $limit, $limit );
        $Pickups = $this->pickupRepository->getPickupsByParameters( $pageNo * $limit, $limit );

        $response = [
            'Ranks' => $Ranks,
            'Pickups' => $Pickups,
            'page_no' => $pageNo,
            'limit' => $limit,
        ];

        return new JsonResponse($response);
    }
    
    /**
     * @Route("/block_top_slider", name="block_top_slider", methods={"GET"})
     * @Template("Block/top_slider.twig")
     */
    public function topSlider(Request $request)
    {
        $topBannerRepository = $this->getDoctrine()->getRepository(TopBanner::class);

        $TopBanners = $topBannerRepository->getRecommendProduct();

        return [
            'TopBanners' => $TopBanners,
        ];
    }
    
    /**
     * @Route("/block_new_blogs", name="block_new_blogs", methods={"GET"})
     * @Template("Block/new_blogs.twig")
     */
    public function newBlogs(Request $request)
    {
        $blogRepository = $this->getDoctrine()->getRepository(Blog::class);
        
        $NewBlogs = $blogRepository->createQueryBuilder('b')
            ->andWhere('b.Status = 1')
            ->andWhere('b.launch_date <= :today')
            ->setParameter('today', new \DateTime())
            ->orderBy('b.update_date', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        return [
            'NewBlogs' => $NewBlogs,
        ];
    }
}
