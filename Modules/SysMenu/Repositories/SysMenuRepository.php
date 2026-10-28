<?php

namespace Modules\SysMenu\Repositories;

use App\Implementations\QueryBuilderImplementation;
use Illuminate\Support\Facades\DB;

class SysMenuRepository extends QueryBuilderImplementation
{
    public $fillable = ['module_id', 'menu_name', 'menu_url', 'menu_icon', 'menu_is_sub', 'menu_parent_id', 'menu_position', 'created_at', 'created_by', 'updated_at', 'updated_by'];

    protected static $groupModuleRoles = [];

    public function __construct()
    {
        $this->table = 'sys_menus';
        $this->pk = 'menu_id';
    }

    public static function flushRoleCache()
    {
        self::$groupModuleRoles = [];
    }

    public function getAll()
    {
        try {
            return DB::connection($this->db)
                ->table($this->table)
                ->select('sys_menus.*', 'sys_modules.module_name', 'parent.menu_name as menu_name_parent')
                ->leftJoin('sys_modules', 'sys_menus.module_id', '=', 'sys_modules.module_id')
                ->leftJoin('sys_menus as parent', 'parent.menu_id', '=', 'sys_menus.menu_parent_id')
                ->latest('sys_menus.created_at')
                ->get();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllOrderByParams($params)
    {
        try {
            return DB::connection($this->db)
                ->table($this->table)
                ->orderBy('menu_position', 'asc')
                ->where($params)
                ->get();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getRole($module, $group)
    {
        try {
            if (!isset(self::$groupModuleRoles[$group])) {
                $roles = DB::connection($this->db)
                    ->table('sys_roles')
                    ->join('sys_tasks', 'sys_roles.task_id', '=', 'sys_tasks.task_id')
                    ->join('sys_modules', 'sys_tasks.module_id', '=', 'sys_modules.module_id')
                    ->where('sys_roles.group_id', '=', $group)
                    ->select('sys_roles.*', 'sys_modules.module_id')
                    ->get()
                    ->keyBy('module_id');
                self::$groupModuleRoles[$group] = $roles;
            }

            return self::$groupModuleRoles[$group]->get($module);
        } catch (\Exception $e) {
            return null;
        }
    }
}
