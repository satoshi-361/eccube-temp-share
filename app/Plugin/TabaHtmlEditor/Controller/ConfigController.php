<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor\Controller;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Plugin\TabaHtmlEditor\Common\Constants;
use Plugin\TabaHtmlEditor\Common\UserConfig;
use Plugin\TabaHtmlEditor\Form\Type\UserConfigType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;



/**
 * プラグイン設定用コントローラー
 */
class ConfigController extends AbstractController
{
    /**
     * @var 設定ファイル
     */
    private $userConfigFile;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     *  default constructor.
     *
     * @param CsrfTokenManagerInterface $csrfTokenManager
     */
    public function __construct(CsrfTokenManagerInterface $csrfTokenManager){
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @Route("/%eccube_admin_route%/tabahtmleditor/config", name="taba_html_editor_admin_config")
     * @Template("@TabaHtmlEditor/admin/config.twig")
     * @param Request $request
     */
    public function index(Request $request)
    {
        // 設定ファイル
        $this->userConfigFile = $this->container->getParameter('kernel.project_dir') . Constants::PLUGIN_DATA_DIR . DIRECTORY_SEPARATOR . Constants::USER_CONFIG_FILE;

        // フォーム
        $builder = $this->formFactory->createBuilder(UserConfigType::class);
        $form = $builder->getForm();

        // 設定ファイルをフォームにセットします。
        if (file_exists($this->userConfigFile)) {
            if (($data = file_get_contents($this->userConfigFile)) === false) {
                $this->addError(Constants::PLUGIN_CODE_LC . '.admin.error_message.user_config.read_fail','admin');
            } else {
                $form->get('user_config')->setData($data);
            }
        }

        return [
            'csrf_token' => $this->csrfTokenManager->getToken(Constant::TOKEN_NAME)->getValue(), // CSRFトークン文字列
            'form' => $form->createView(),
        ];
    }

    /**
     *  保存
     * 
     * @Route("/%eccube_admin_route%/tabahtmleditor/config_save", name="taba_html_editor_admin_config_save")
     * @param Request $request
     */
    public function save(Request $request)
    {
        // 設定ファイル
        $this->userConfigFile = $this->container->getParameter('kernel.project_dir') . Constants::PLUGIN_DATA_DIR . DIRECTORY_SEPARATOR . Constants::USER_CONFIG_FILE;

        // フォーム
        $builder = $this->formFactory->createBuilder(UserConfigType::class);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if (($message = UserConfig::getInstance()->validate($form->get('user_config')->getData())) !== true) {
            $this->addError('設定ファイルの記述が正しいかご確認ください。[ERROR] ' . $message,'admin');
        } else if ($form->isSubmitted() && $form->isValid()) {
            if (@file_put_contents($this->userConfigFile,$form->get('user_config')->getData()) === false) {
                $this->addError(Constants::PLUGIN_CODE_LC . '.admin.error_message.user_config.write_fail','admin');
            } else {
                $this->addSuccess('admin.common.save_complete', 'admin');
                return $this->redirectToRoute('taba_html_editor_admin_config');
            }
        } else {
            $this->addError('admin.common.save_error', 'admin');
        }

        return $this->render('@TabaHtmlEditor/admin/config.twig', [
            'csrf_token' => $this->csrfTokenManager->getToken(Constant::TOKEN_NAME)->getValue(), // CSRFトークン文字列
            'form' => $form->createView(),
        ]);
    }
}
