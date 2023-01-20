<?php

namespace Plugin\CustomerReview4\Controller;

use Plugin\CustomerReview4\Entity\CustomerReviewName;
use Plugin\CustomerReview4\Entity\CustomerReviewList;
use Plugin\CustomerReview4\Entity\CustomerReviewStatus;
use Plugin\CustomerReview4\Repository\CustomerReviewConfigRepository;
use Plugin\CustomerReview4\Repository\CustomerReviewNameRepository;
use Plugin\CustomerReview4\Repository\CustomerReviewListRepository;
use Plugin\CustomerReview4\Repository\CustomerReviewStatusRepository;
use Plugin\CustomerReview4\Form\Type\ReviewPostType;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;


class ReviewPostController extends AbstractController
{
    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CustomerReviewConfigRepository
     */
    protected $customerReviewConfigRepository;

    /**
     * @var CustomerReviewNameRepository
     */
    protected $customerReviewNameRepository;

    /**
     * @var CustomerReviewListRepository
     */
    protected $customerReviewListRepository;

    /**
     * @var CustomerReviewStatusRepository
     */
    protected $customerReviewStatusRepository;

    /**
     * ReviewPostController constructor.
     *
     * @param BaseInfoRepository $baseInfoRepository
     * @param ProductRepository $productRepository
     * @param CustomerReviewConfigRepository $customerReviewConfigRepository
     * @param CustomerReviewNameRepository $customerReviewNameRepository
     * @param CustomerReviewListRepository $customerReviewListRepository
     * @param CustomerReviewStatusRepository $customerReviewStatusRepository
     */
    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        ProductRepository $productRepository,
        CustomerReviewConfigRepository $customerReviewConfigRepository,
        CustomerReviewNameRepository $customerReviewNameRepository,
        CustomerReviewListRepository $customerReviewListRepository,
        CustomerReviewStatusRepository $customerReviewStatusRepository
    ) {
        $this->BaseInfo = $baseInfoRepository->get();
        $this->productRepository = $productRepository;
        $this->customerReviewConfigRepository = $customerReviewConfigRepository;
        $this->customerReviewNameRepository = $customerReviewNameRepository;
        $this->customerReviewListRepository = $customerReviewListRepository;
        $this->customerReviewStatusRepository = $customerReviewStatusRepository;
    }

    /**
     * レビュー投稿画面.
     *
     * @Route("/review/post/{id}", name="review_post", requirements={"id" = "\d+"})
     * @Template("@CustomerReview4/post.twig")
     * @ParamConverter("Product", options={"repository_method" = "findWithSortedClassCategories"})
     *
     * @param Request $request
     * @param Product $Product
     *
     * @return array
     */
    public function post(Request $request, Product $Product)
    {
        $Customer = $this->getUser();
        $config = $this->customerReviewConfigRepository->get();

        if (!$this->checkVisibility($Product)) {
            throw new NotFoundHttpException();
        }

        $review = new CustomerReviewList();
        $review->setRecommendLevel(5);
        if ( empty($request->get('reviewer_name')) )
        {
            if ( $Customer && 
                    $reviewer_data = $this->customerReviewNameRepository->get($Customer->getId()) )
            {
                $reviewer_name = $reviewer_data->getReviewerName();
            } else {
                $reviewer_name = $config->getDefaultReviewerName();
            }
            $review->setReviewerName($reviewer_name);
        }

        $form = $this->createForm(ReviewPostType::class, $review);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            if (!$Customer && $config->isLoginOnly()) {
                return $this->redirectToRoute('homepage');
            }

            switch ($request->get('mode')) {
                case 'confirm':
                    return $this->render('@CustomerReview4/confirm.twig', [
                        'Product' => $Product,
                        'form' => $form->createView(),
                    ]);

                case 'complete':
                    $review->setProduct($Product);
                    if ( $Customer ) {
                        $review->setCustomer($Customer);
                    }
                    $review->setStatus($this->customerReviewStatusRepository->find(CustomerReviewStatus::POST));
                    $review->setPurchase($this->checkDelivered($Customer, $Product));
                    $this->entityManager->persist($review);
                    $this->entityManager->flush($review);

                    if ( $Customer ) {
                        $reviewerName = $this->customerReviewNameRepository->get($Customer->getId());
                        if ( !$reviewerName ) {
                            $reviewerName = new CustomerReviewName();
                        }
                        $reviewerName->setCustomer($Customer);
                        $reviewerName->setReviewerName($review->getReviewerName());
                        $this->entityManager->persist($reviewerName);
                        $this->entityManager->flush($reviewerName);
                    }

                    return $this->redirect($this->generateUrl('review_complete', ['id' => $Product->getId()]));
            }
        }

        // 既に投稿している場合には投稿完了画面へ
        if ( $Customer )
        {
            $searchData = array(
                'product_id' => $Product->getId(),
                'customer_id' => $Customer->getId(),
            );

            $qb = $this->customerReviewListRepository->getQueryBuilderBySearchDataForAdmin($searchData);
            $res = $qb
                ->getQuery()
                ->getResult();

            if ( count($res) ) {
                return $this->redirect($this->generateUrl('review_posted', ['id' => $Product->getId()]));
            }
        }

        return [
            'Product' => $Product,
            'form' => $form->createView(),
            'review_config' => $config,
        ];
    }

    /**
     * @Route("review_confirm", name="review_confirm")
     */
    public function confirm(Request $request)
    {
        /**
         * 管理ページのページ管理で編集させるためのダミー
         * 基本遷移しないので、もし遷移したらTOPページへ
         */
        return $this->redirectToRoute('homepage');
    }

    /**
     * レビュー完了画面.
     *
     * @Route("/review/complete/{id}", name="review_complete", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("@CustomerReview4/complete.twig")
     * @ParamConverter("Product", options={"repository_method" = "findWithSortedClassCategories"})
     *
     * @return array
     */
    public function complete(Product $Product)
    {
        return [
            'Product' => $Product,
        ];
    }

    /**
     * レビュー投稿済み画面.
     *
     * @Route("/review/posted/{id}", name="review_posted", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("@CustomerReview4/posted.twig")
     * @ParamConverter("Product", options={"repository_method" = "findWithSortedClassCategories"})
     *
     * @return array
     */
    public function posted(Product $Product)
    {
        return [
            'Product' => $Product,
        ];
    }

    /**
     * 購入済み商品のチェック
     *
     * @param Customer $Customer
     * @param Product $Product
     *
     * @return boolean 購入済みの場合はtrue
     */
    private function checkDelivered($Customer, $Product)
    {
        if ( !$Customer ) return false;

        $qb = $this->entityManager->createQueryBuilder()
                ->Select('count(o.id)')
                ->from('Eccube\\Entity\\Order', 'o')
                ->from('Eccube\\Entity\\OrderItem', 'oi')
                ->where( 'o.OrderStatus = :staus AND o.Customer = :customer AND o.id = oi.Order AND oi.Product = :product')
                ->setParameter('staus', OrderStatus::DELIVERED)
                ->setParameter('customer', $Customer->getId())
                ->setParameter('product', $Product->getId());
        $count = $qb
            ->getQuery()
            ->getSingleScalarResult();

        return $count ? true : false;
    }

    /**
     * 閲覧可能な商品かどうかを判定
     *
     * @param Product $Product
     *
     * @return boolean 閲覧可能な場合はtrue
     */
    private function checkVisibility(Product $Product)
    {
        $is_admin = $this->session->has('_security_admin');

        // 管理ユーザの場合はステータスやオプションにかかわらず閲覧可能.
        if (!$is_admin) {
            // 在庫なし商品の非表示オプションが有効な場合.
            if ($this->BaseInfo->isOptionNostockHidden()) {
                if (!$Product->getStockFind()) {
                    return false;
                }
            }
            // 公開ステータスでない商品は表示しない.
            if ($Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
                return false;
            }
        }

        return true;
    }

}