<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor\Controller;

use Eccube\Controller\AbstractController;
use Plugin\TabaHtmlEditor\Common\Constants;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * 管理画面用コントローラー
 *
 * @Route(Plugin\TabaHtmlEditor\Common\Constants::ADMIN_URI_PREFIX,name=Plugin\TabaHtmlEditor\Common\Constants::ADMIN_BIND_PREFIX)
 */
class AdminController extends AbstractController
{

    /**
     * コンストラクタ
     */
    public function __construct(){
     }

    /**
     * 各種ファイルを出力します。
     *
     * @param Request $request
     * @param string $file
     * @throws NotFoundHttpException
     * @return BinaryFileResponse
     *
     * @Route("/assets/{file}",name="_assets",requirements={"file"="[a-zA-Z0-9-_/\s.]+"})
     */
    public function assets(Request $request,$file) {
        if ($this->container->has('profiler')) $this->container->get('profiler')->disable();

        if (strpos($file,'..')) {
            log_fatal("ディレクトリトラバーサル攻撃の可能性があります。 [FILE] " . $file);
            throw new NotFoundHttpException();
        }

        $file = Constants::TEMPLATE_PATH . DIRECTORY_SEPARATOR .  "admin" . DIRECTORY_SEPARATOR . "assets" .  DIRECTORY_SEPARATOR . $file;
        if (file_exists($this->eccubeConfig['plugin_realdir'] . DIRECTORY_SEPARATOR . $file)) {
            log_debug("[ASSETS] [FILE] " . $file);

            // 拡張子によりMIMEを設定します。
            $suffixes = explode(".",$file);
            $suffix = end($suffixes);
            $suffix_def = array(
                "jpeg" => "image/jpg",
                "jpg" => "image/jpg",
                "gif" => "image/gif",
                "png" => "image/png",
                "svg" => "image/svg+xml",
                "js" => "application/x-javascript",
                "css" => "text/css",
                "html" => "text/html",
                "map" => "application/json",
            );
            if (in_array($suffix,array_keys($suffix_def))) {
                $fileObject = new \SplFileInfo($this->eccubeConfig['plugin_realdir'] . DIRECTORY_SEPARATOR . $file);
                $response = new BinaryFileResponse($fileObject);
                $response->headers->set('Content-Type',$suffix_def[$suffix]);
                // キャッシュするヘッダーを出力する設定をします
                if ($this->container->has(Constants::CONTAINER_KEY_NAME)) {
                    $this->container->get(Constants::CONTAINER_KEY_NAME)->set(Constants::HTTP_CACHE_STATUS,true);
                }
                return $response;
            } else {
                throw new NotFoundHttpException();
            }
        } else {
            throw new NotFoundHttpException();
        }
    }

}
