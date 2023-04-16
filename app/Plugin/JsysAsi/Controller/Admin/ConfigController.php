<?php
namespace Plugin\JsysAsi\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Controller\AbstractController;
use Plugin\JsysAsi\Form\Type\Admin\ConfigType;
use Plugin\JsysAsi\Repository\ConfigRepository;

/**
 * 管理画面セキュリティ向上プラグイン設定Controller
 * @author manabe
 *
 */
class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;


    /**
     * ConfigController constructor.
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\Form\FormView[]
     *
     * @Route("/%eccube_admin_route%/jsys_asi/config", name="jsys_asi_admin_config")
     * @Template("@JsysAsi/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form   = $this->createForm(ConfigType::class, $Config);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $Config->setUpdateDate(new \DateTime());
            $this->entityManager->persist($Config);
            $this->entityManager->flush();
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('jsys_asi_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * マスターキーを生成し、json形式で取得します。
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @Route(
     *   "/%eccube_admin_route%/jsys_asi/config/ajax_create_master_key",
     *   name="jsys_asi_admin_config_ajax_create_master_key"
     * )
     */
    public function ajaxCreateMasterKey(Request $request)
    {
        return $this->json([
            'masterKey' => bin2hex(openssl_random_pseudo_bytes(16)),
        ]);
    }

}
