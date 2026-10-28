<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Implementations\QueryBuilderImplementation;

class SysRoleRepository extends QueryBuilderImplementation
{
    public $fillable = ['role_id', 'task_id', 'group_id', 'created_at', 'created_by', 'updated_at', 'updated_by'];

    protected static $groupPermissions = [];

    public function __construct()
    {
        $this->table = 'sys_roles';
        $this->pk = 'role_id';
    }

    public static function flushPermissionCache()
    {
        self::$groupPermissions = [];
    }

    public function getByModuleTask($module, $task, $group)
    {
        try {
            if (!isset(self::$groupPermissions[$group])) {
                $rows = DB::connection($this->db)
                    ->table($this->table)
                    ->join('sys_tasks', 'sys_roles.task_id', '=', 'sys_tasks.task_id')
                    ->join('sys_modules', 'sys_tasks.module_id', '=', 'sys_modules.module_id')
                    ->where('sys_roles.group_id', '=', $group)
                    ->select('sys_modules.module_name', 'sys_tasks.task_name', 'sys_roles.*')
                    ->get();

                $map = [];
                foreach ($rows as $row) {
                    $key = strtolower($row->module_name) . ':' . strtolower($row->task_name);
                    $map[$key] = $row;
                }
                self::$groupPermissions[$group] = $map;
            }

            $lookupKey = strtolower($module) . ':' . strtolower($task);
            return self::$groupPermissions[$group][$lookupKey] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
