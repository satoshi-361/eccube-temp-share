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
use Plugin\Recommend4\Repository\RecommendProductRepository as RecommendRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use Plugin\CustomerReview4\Entity\CustomerReviewTotal;

use Customize\Service\BlogHelper;

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

    /**
     * @var RecommendRepository
     */
    protected $recommendRepository;
    
    /**
     * @var BlogHelper
     */
    protected $blogHelper;

    /**
     * @var MobileDetector
     */
    protected $mobileDetector;

    public function __construct(
        RankService $rankService,
        MobileDetector $mobileDetector,
        PickupRepository $pickupRepository,
        RecommendRepository $recommendRepository,
        BlogHelper $blogHelper
    ) {
        $this->rankService = $rankService;
        $this->mobileDetector = $mobileDetector;
        $this->pickupRepository = $pickupRepository;
        $this->recommendRepository = $recommendRepository;
        $this->blogHelper = $blogHelper;
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

        if ( $this->mobileDetector->isMobile() ) {
            return $this->render('index_sp.twig', [
                'Pickups' => $Pickups,
                'page_no' => 0,
                'limit' => $limit,
            ]);
        }

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
            ->orderBy('b.approved_date', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        return [
            'NewBlogs' => $NewBlogs,
            'page_no' => 0,
            'limit' => 4,
        ];
    }

    /**
     * @Route("/top/sp_rank_block", name="top_sp_rank_block", methods={"GET", "POST"})
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSpRanks(Request $request)
    {
        $pageNo = $request->request->get('page_no');
        $limit = $request->request->get('limit');

        $Ranks = $this->rankService->getRanksByParameters( $pageNo * $limit, $limit );

        $response = [
            'Blogs' => $Ranks,
            'page_no' => $pageNo,
            'limit' => $limit,
        ];

        return new JsonResponse($response);
    }

    /**
     * @Route("/top/sp_pickup_block", name="top_sp_pickup_block", methods={"GET", "POST"})
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSpPickups(Request $request)
    {
        $pageNo = $request->request->get('page_no');
        $limit = $request->request->get('limit');

        $Pickups = $this->pickupRepository->getPickupsByParameters( $pageNo * $limit, $limit );

        $response = [
            'Blogs' => $Pickups,
            'page_no' => $pageNo,
            'limit' => $limit,
        ];

        return new JsonResponse($response);
    }

    /**
     * @Route("/top/sp_news_block", name="top_sp_news_block", methods={"GET", "POST"})
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSpNews(Request $request)
    {
        $pageNo = $request->request->get('page_no');
        $limit = $request->request->get('limit');
        
        $blogRepository = $this->getDoctrine()->getRepository(Blog::class);
        
        $qb = $blogRepository->createQueryBuilder('b')
            ->andWhere('b.Status = 1')
            ->andWhere('b.launch_date <= :today')
            ->setParameter('today', new \DateTime())
            ->orderBy('b.approved_date', 'DESC')
            ->setFirstResult($pageNo * $limit)
            ->setMaxResults($limit);
        
        $CustomerReviewTotalRepository = $this->getDoctrine()->getRepository(CustomerReviewTotal::class);
        $NewBlogs = [];

        foreach ($qb->getQuery()->getResult() as $Product) {
            $Categories = [];
            foreach ( $Product->getProductCategories() as $ProductCategory ) {
                $Categories[] = $ProductCategory->getCategory()->getName();
            }

            $reviewList = $CustomerReviewTotalRepository->getRecommend($Product->getId());
            $reviewerTotal = $reviewTotalPoint = 0;
            $count = 5;

            foreach ( $reviewList as $review ) {
                $reviewerTotal += $review;
                $reviewTotalPoint += $review * $count;
                $count -= 1;
            }

            $resultItem = [
                'id' => $Product->getId(),
                // 'image' => $Product->getMainFileName()->getFileName(),
                'image' => $this->blogHelper->getBlogImage($Product, 'middle')->__toString(),
                'name' => $Product->getName(),
                'price' => $Product->getPrice02IncTaxMax(),
                'affiliate' => $Product->getAffiliateReward(),
                'editor_image' => $Product->getCustomer()->getImage(),
                'nick_name' => $Product->getCustomer()->getNickName(),
                'launch_date' => $Product->getLaunchDate()->format('Y年m月d日'),
                'stock' => $Product->getStockMax(),
                'categories' => array_reverse($Categories),
                'review_point' => $reviewerTotal == 0 ? 0 : number_format(( $reviewTotalPoint / $reviewerTotal ), 1, '.', ''),
                'reviewer_total' => $reviewerTotal,
            ];
            $NewBlogs[] = $resultItem;
        }

        $response = [
            'Blogs' => $NewBlogs,
            'page_no' => $pageNo,
            'limit' => $limit,
        ];

        return new JsonResponse($response);
    }

    /**
     * @Route("/top/sp_recommend_block", name="top_sp_recommend_block", methods={"GET", "POST"})
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSpRecommend(Request $request)
    {
        $pageNo = $request->request->get('page_no');
        $limit = $request->request->get('limit');
        
        $Recommends = $this->recommendRepository->getRecommendsByParameters( $pageNo * $limit, $limit );

        $response = [
            'Blogs' => $Recommends,
            'page_no' => $pageNo,
            'limit' => $limit,
        ];

        return new JsonResponse($response);
    }
}
