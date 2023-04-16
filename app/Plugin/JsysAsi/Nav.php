<?php

namespace Plugin\JsysAsi;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'jsys_asi_admin_menu_tfa' => [
                'name'     => 'jsys_asi.admin.admin_menu.tfa.title',
                'icon'     => 'fa-key',
                'children' => [
                    'jsys_asi_admin_menu_tfa_user' => [
                        'name' => 'jsys_asi.admin.admin_menu.tfa_user.title',
                        'url'  => 'admin_jsys_asi_tfa_user',
                    ],
                ],
            ],
            'jsys_asi_admin_menu_login' => [
                'name'     => 'jsys_asi.admin.admin_menu.login.title',
                'icon'     => 'fa-sign-in-alt',
                'children' => [
                    'jsys_asi_admin_menu_login_history' => [
                        'name' => 'jsys_asi.admin.admin_menu.login_history.title',
                        'url'  => 'admin_jsys_asi_login_history',
                    ],
                    'jsys_asi_admin_menu_locked_list' => [
                        'name' => 'jsys_asi.admin.admin_menu.locked_list.title',
                        'url'  => 'admin_jsys_asi_locked_list',
                    ],
                ],
            ],
        ];
    }
}
