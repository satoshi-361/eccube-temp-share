<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor\Common;

use Symfony\Component\Yaml\Yaml;

/**
 * ユーザーが定義した設定を取得するクラスです。
 */
class UserConfig
{
    private static $instance;
    
    private $data;
    
    private function __construct() {}
    
    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new UserConfig();
            self::$instance->data = array();
        }
        return self::$instance;
    }

    public function load($file) {
        if (file_exists($file)) {
            try {
                $this->data = Yaml::parse(file_get_contents($file));
                return true;
            } catch (\Exception $e) {
                log_error("[TabaFileManager] Failed to parse the configuration file. " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    public function validate($data) {
        if ($data) {
            try {
                Yaml::parse($data);
                return true;
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
        return true;
    }

    public function get($path,$default = null)
    {
        $keys = explode('.',$path);
        $current = $this->data;
        foreach ($keys as $key) {
            if (!isset($current[$key])) return $default;
            $current = $current[$key];
        }
        return $current;
    }
    
    final function __clone()
    {
        throw new \Exception('Clone is not allowed against ' . get_class($this));
    }
}