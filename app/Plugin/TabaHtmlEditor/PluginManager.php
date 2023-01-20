<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor;

use Plugin\TabaHtmlEditor\Common\Constants;

use Eccube\Plugin\AbstractPluginManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{

    /**
     * プラグインインストール時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function install(array $meta, ContainerInterface $container) {
    }

    /**
     * プラグイン削除時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function uninstall(array $meta, ContainerInterface $container) {
    }

    /**
     * プラグイン有効時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function enable(array $meta, ContainerInterface $container) {
        // デフォルトの設定ファイルをコピーします
        $eccubeConfig = $container->get('Eccube\Common\EccubeConfig');
        $rootPath = $container->getParameter('kernel.project_dir');
        if (!$rootPath) throw new \Exception('kernel.project_dir が取得が出来ませんでした。');
        $plugin_data_dir = $rootPath . Constants::PLUGIN_DATA_DIR;
        if (!file_exists($plugin_data_dir)) mkdir($plugin_data_dir,0775,true);
        $plugin_dir = $eccubeConfig['plugin_realdir'] . DIRECTORY_SEPARATOR . Constants::PLUGIN_CODE . DIRECTORY_SEPARATOR;
        $defaultConfigFile = $plugin_dir . "Resource" . DIRECTORY_SEPARATOR . "config"  . DIRECTORY_SEPARATOR . "default_" . Constants::USER_CONFIG_FILE;
        $configFile = $plugin_data_dir . DIRECTORY_SEPARATOR . Constants::USER_CONFIG_FILE;
        if (!file_exists($configFile)) {
            copy($defaultConfigFile,$configFile);
        }
    }

    /**
     * プラグイン無効時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function disable(array $meta, ContainerInterface $container) {
    }

    /**
     * プラグイン更新時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function update(array $meta, ContainerInterface $container) {
    }
}
