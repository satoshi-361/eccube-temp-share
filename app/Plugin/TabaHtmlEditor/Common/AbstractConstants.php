<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor\Common;

abstract class AbstractConstants
{

    /**
     * @var string プラグインコード
     */
    const PLUGIN_CODE = "TabaHtmlEditor";

    /**
     * @var string プラグインコード(小文字)
     */
    const PLUGIN_CODE_LC = "tabahtmleditor";

    /**
     * @var string コンテナに登録するキー値
     */
    const CONTAINER_KEY_NAME = "spreadworks.taba";

    /**
     * @var string プラグインカテゴリーID
     */
    const PLUGIN_CATEGORY_ID = "taba-app";

    /**
     * @var string プラグインカテゴリー名
     */
    const PLUGIN_CATEGORY_NAME = "taba&trade; app";

    /**
     *
     * @var string 管理画面用ルーティング接頭詞
     */
    const ADMIN_BIND_PREFIX = 'admin_plugin_' . self::PLUGIN_CODE_LC;

    /**
     *
     * @var string 管理画面用URI接頭詞
     */
    const ADMIN_URI_PREFIX = '/%eccube_admin_route%/plugin/taba-app/' . self::PLUGIN_CODE_LC;

    /**
     *
     * @var string 管理画面用コントローラー
     */
    const ADMIN_CONTROLLER = "Plugin\\TabaHtmlEditor\\Controller\\AdminController";

    /**
     *
     * @var string フロント用ルーティング接頭詞
     */
    const FRONT_BIND_PREFIX = 'plugin_' . self::PLUGIN_CODE_LC;

    /**
     *
     * @var string フロント用URI接頭詞
     */
    const FRONT_URI_PREFIX = '/plugin/' . self::PLUGIN_CODE_LC;

    /**
     *
     * @var string フロント用コントローラー
     */
    const FRONT_CONTROLLER = "Plugin\\TabaHtmlEditor\\Controller\\FrontController";

    /**
     *
     * @var string テンプレート設置パス
     */
    const TEMPLATE_PATH = self::PLUGIN_CODE . '/Resource/template';

    /**
     *  キャッシュヘッダーを出力有無設定をコンテナに保存する値のキーです。
     * 
     * @var string
     */
    const HTTP_CACHE_STATUS = self::PLUGIN_CODE .  "_HTTP_CACHE_STATUS";

    /**
     * @var string 設定ファイル
     */
    const USER_CONFIG_FILE = "user_config.yml";

    /**
     * @var string プラグインで使用するデータを保管するディレクトリ
     */
    const PLUGIN_DATA_DIR = DIRECTORY_SEPARATOR . 'app' .  DIRECTORY_SEPARATOR . 'PluginData' . DIRECTORY_SEPARATOR . Constants::PLUGIN_CODE;

    /**
     *
     * @var string 管理画面で使用するページタイトル名
     */
    const PAGE_TITLE = 'taba&trade; app HTMLエディタ';
}
