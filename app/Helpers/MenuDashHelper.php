<?php

namespace App\Helpers;

use Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\SysMenu\Repositories\SysMenuRepository;

class MenuDashHelper
{
    /**
     * Cache for menu items per request
     */
    protected static $allMenus = null;

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public static function render()
    {
        if (!Auth::check()) {
            return "";
        }

        $user = Auth::user();
        $groupId = $user->group_id;

        $_sysmenuRepository = new SysMenuRepository;

        if (self::$allMenus === null) {
            self::$allMenus = DB::table('sys_menus')
                ->orderBy('menu_position', 'asc')
                ->get();
        }

        $topMenus = self::$allMenus->where('menu_is_sub', 0);
        $subsByParent = self::$allMenus->where('menu_is_sub', '!=', 0)->groupBy('menu_parent_id');

        $currentSegment = Request::segment(1);
        $menus = "";

        foreach ($topMenus as $menu) {
            $subsList = $subsByParent->get($menu->menu_id, collect());

            if ($subsList->isNotEmpty()) {
                $subs = "";
                $subLinks = [];
                $areSubs = false;

                foreach ($subsList as $sub) {
                    $getRole = $_sysmenuRepository->getRole($sub->module_id, $groupId);
                    if (!$getRole) {
                        continue;
                    }

                    $active = ($currentSegment == $sub->menu_url) ? 'active' : '';
                    $subLinks[] = $sub->menu_url;
                    $subs .= "<li class='nav-item {$active}'><a class='nav-link pl-3' href='" . url($sub->menu_url) . "'><span class='ml-1 item-text'>{$sub->menu_name}</span></a></li>";
                    $areSubs = true;
                }

                if (!$areSubs) {
                    continue;
                }

                $active = in_array($currentSegment, $subLinks) ? 'active' : '';
                $show = in_array($currentSegment, $subLinks) ? 'show' : '';

                $id_class_replace = str_replace(" ", "", $menu->menu_name);
                $id_class = substr($id_class_replace, 0, 5);
                $menus .= "<li class='nav-item dropdown {$active}'>
                                <a href='#{$id_class}' data-toggle='collapse' aria-expanded='false' class='dropdown-toggle nav-link'>
                                    <i data-feather='{$menu->menu_icon}'></i> <span class='ml-3 item-text'>{$menu->menu_name}</span>
                                </a>
                                <ul id='{$id_class}' class='collapse list-unstyled pl-4 w-100 {$show}'>
                                    {$subs}
                                </ul>
                            </li>";
            } else {
                $getRole = $_sysmenuRepository->getRole($menu->module_id, $groupId);
                if (!$getRole) {
                    continue;
                }

                $active = ($currentSegment == $menu->menu_url) ? 'active' : '';

                $menus .= "
                    <li class='nav-item w-100 {$active}'>
                        <a class='nav-link' href='" . url($menu->menu_url) . "' aria-expanded='false'>
                        <i data-feather='{$menu->menu_icon}'></i><span class='ml-3 item-text'>{$menu->menu_name}</span>
                        </a>
                    </li>
                ";
            }
        }

        return $menus;
    }
}
