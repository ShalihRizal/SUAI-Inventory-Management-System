<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->addIndexIfNotExists('sys_log_activities', 'idx_sla_created_at', ['created_at']);
        $this->addIndexIfNotExists('sys_log_activities', 'idx_sla_created_log', ['created_at', 'log_activity_id']);

        $this->addIndexIfNotExists('log_part_request', 'idx_lpr_part_no', ['part_no']);
        $this->addIndexIfNotExists('log_part_request', 'idx_lpr_created_at', ['created_at']);

        $this->addIndexIfNotExists('transaksi_in', 'idx_ti_part_id', ['part_id']);
        $this->addIndexIfNotExists('transaksi_in', 'idx_ti_created_at', ['created_at']);
        $this->addIndexIfNotExists('transaksi_in', 'idx_ti_part_created', ['part_id', 'created_at']);

        $this->addIndexIfNotExists('transaksi_out', 'idx_to_part_no', ['part_no']);
        $this->addIndexIfNotExists('transaksi_out', 'idx_to_created_at', ['created_at']);

        $this->addIndexIfNotExists('part', 'idx_part_category_id', ['part_category_id']);
        $this->addIndexIfNotExists('part', 'idx_part_created_at', ['created_at']);
        $this->addIndexIfNotExists('part', 'idx_part_updated_at', ['updated_at']);
        $this->addIndexIfNotExists('part', 'idx_part_type', ['type']);
        $this->addIndexIfNotExists('part', 'idx_part_kategori_inv', ['kategori_inventory']);
        $this->addIndexIfNotExists('part', 'idx_part_cat_updated', ['part_category_id', 'updated_at']);

        $this->addIndexIfNotExists('part_request', 'idx_pr_status_created', ['status', 'created_at']);
        $this->addIndexIfNotExists('part_request', 'idx_pr_approved_created', ['approved_by', 'created_at']);
        $this->addIndexIfNotExists('part_request', 'idx_pr_car_model', ['car_model']);
        $this->addIndexIfNotExists('part_request', 'idx_pr_part_status', ['part_id', 'status']);
        $this->addIndexIfNotExists('part_request', 'idx_pr_created_by', ['created_by']);

        $this->addIndexIfNotExists('partlist', 'idx_pl_part_req_id', ['part_req_id']);
        $this->addIndexIfNotExists('sub_rack', 'idx_sr_rack_id', ['rack_id']);
        $this->addIndexIfNotExists('machine', 'idx_mach_carname_id', ['carname_id']);

        $this->addIndexIfNotExists('sys_roles', 'idx_roles_group_task', ['group_id', 'task_id']);
        $this->addIndexIfNotExists('sys_tasks', 'idx_tasks_module_task', ['module_id', 'task_name']);
        $this->addIndexIfNotExists('sys_menus', 'idx_menu_sub_pos', ['menu_is_sub', 'menu_position']);
        $this->addIndexIfNotExists('sys_menus', 'idx_menu_parent_pos', ['menu_parent_id', 'menu_position']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Down migration can drop indexes if needed
    }

    protected function addIndexIfNotExists(string $table, string $indexName, array $columns)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        // Check if all columns exist
        foreach ($columns as $col) {
            if (!Schema::hasColumn($table, $col)) {
                return;
            }
        }

        $existing = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (empty($existing)) {
            Schema::table($table, function (Blueprint $t) use ($indexName, $columns) {
                $t->index($columns, $indexName);
            });
        }
    }
};
