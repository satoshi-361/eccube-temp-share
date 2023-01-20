<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor\EventListener;

use Plugin\TabaHtmlEditor\Common\Constants;
use Plugin\TabaHtmlEditor\Common\UserConfig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class InitializeListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * コンストラクタ
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container,\Twig_Environment $twig)
    {
        $this->container = $container;
        $this->twig = $twig;

        // 設定ファイルの読み込み
        UserConfig::getInstance()->load($this->container->getParameter('kernel.project_dir') . Constants::PLUGIN_DATA_DIR . DIRECTORY_SEPARATOR . Constants::USER_CONFIG_FILE);
        $this->twig->addGlobal(Constants::PLUGIN_CODE.'UserConfig', UserConfig::getInstance());

        // Twigグローバル変数セット
        $this->twig->addGlobal(Constants::PLUGIN_CODE.'Constants', new Constants());

        // コンテナにプラグイン間で共有するデータホルダーを登録します。
        if (!$this->container->has(Constants::CONTAINER_KEY_NAME)) {
            $this->container->set(
                Constants::CONTAINER_KEY_NAME,
                new class {
                    private $data;
                    public function set($key, $val)
                    {
                        $this->data[$key] = $val;
                    }
                    public function get($key)
                    {
                        if (isset($this->data[$key])) return $this->data[$key];
                        return null;
                    }
                }
            );
        }
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\HttpKernel\EventListener\RouterListener::onKernelRequest()
     */
    public function onKernelRequest() {
    }
}