<?php

namespace Modules\Part\Repositories;

use App\Implementations\QueryBuilderImplementation;
use Illuminate\Support\Facades\DB;

class PartRepository extends QueryBuilderImplementation
{

    public $fillable = [
        'part_no',
        'no_urut',
        'applicator_no',
        'applicator_type',
        'applicator_qty',
        'kode_tooling_bc',
        'part_name',
        'asal',
        'invoice',
        'po',
        'po_date',
        'rec_date',
        'used_date',
        'rcv_date',
        'loc_ppti',
        'loc_tapc',
        'lokasi_hib',
        'lokasi_replacement',
        'qty_begin',
        'qty_in',
        'qty_out',
        'adjust',
        'kategori_inventory',
        'qty_end',
        'qty_end_inventory',
        'qty_end_replacement',
        'qty_kedatangan_barang',
        'qty_in_order',
        'status',
        'safety_stock',
        'rop',
        'qty_order_forecast',
        'remarks',
        'last_sto',
        'molts_no',
        'has_sto',
        'part_category_id',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];

    public function __construct()
    {
        $this->table = 'part';
        $this->pk = 'part_id';
    }

    // Override
    public function getAll()
    {
        try {
            return DB::connection($this->db)
                ->table($this->table)
                ->join('part_category', 'part.part_category_id', '=', 'part_category.part_category_id')
                ->select("part.*", 'part_category.part_category_name')
                ->orderBy('part_id')
                ->get();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    // Di dalam PartRepository.php, tambahkan method berikut:
    public function getAllByParamsPaginated(array $params, $perPage = 10, $page = null, $startDate = null, $endDate = null)
    {
        try {
            $query = DB::connection($this->db)
                ->table($this->table)
                ->join('part_category', 'part.part_category_id', '=', 'part_category.part_category_id')
                ->leftJoin('transaksi_in', 'part.part_id', '=', 'transaksi_in.part_id')
                ->select("part.*", 'part_category.*', 'transaksi_in.qty')
                ->where($params);

            // Tambahkan filter tanggal jika tersedia
            if ($startDate && $endDate) {
                $query->whereBetween('part.created_at', [$startDate, $endDate]);
            }

            return $query->orderBy('part_id')->paginate($perPage, ['*'], 'page', $page);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function getAllByParamsPaginatedKategori(array $params, $perPage = 10)
    {
        $query = DB::connection($this->db)
            ->table($this->table)
            ->select('part_name', 'part_no', 'qty_end', 'loc_ppti', 'last_sto')
            ->where('has_sto', $params['has_sto']);

        if (isset($params['part_category_id'])) {
            $query->where('part_category_id', $params['part_category_id']);
        }

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $query->where($key, $value[0], $value[1]);
            } elseif ($key === 'part_no') {
                $query->where($key, 'LIKE', '%' . $value . '%');
            } else {
                $query->where($key, $value);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Server-side DataTables handler.
     * Handles search, sort, and pagination at the DB level.
     */
    public function getServerSideData($request, $categoryId = null)
    {
        $draw    = intval($request->input('draw', 1));
        $start   = intval($request->input('start', 0));
        $length  = intval($request->input('length', 25));
        $search  = $request->input('search.value', '');
        $orderCol = intval($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'asc');

        // Define sortable columns mapping
        $columns = [
            'part.part_id',
            'part.part_no',
            'part.part_name',
            'part.loc_ppti',
            'part.lokasi_hib',
            'part.loc_tapc',
            'part.qty_begin',
            'part.qty_in',
            'part.qty_out',
            'part.adjust',
            'part.status',
            'part.kategori_inventory',
            'part.ss',
            'part.rop',
            'part.forecast',
            'part.max',
            'part.used_date',
            'part.rcv_date',
        ];

        $sortColumn = isset($columns[$orderCol]) ? $columns[$orderCol] : 'part.part_id';

        // Base query - use DB::table() directly
        $baseQuery = DB::table($this->table)
            ->leftJoin('part_category', 'part.part_category_id', '=', 'part_category.part_category_id')
            ->select("part.*", 'part_category.part_category_name');

        if ($categoryId !== null) {
            $baseQuery->where('part.part_category_id', $categoryId);
        }

        // Total records (before search filter)
        $recordsTotal = (clone $baseQuery)->count();

        // Apply search
        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('part.part_no', 'LIKE', "%{$search}%")
                  ->orWhere('part.part_name', 'LIKE', "%{$search}%")
                  ->orWhere('part.loc_ppti', 'LIKE', "%{$search}%")
                  ->orWhere('part.lokasi_hib', 'LIKE', "%{$search}%")
                  ->orWhere('part.loc_tapc', 'LIKE', "%{$search}%")
                  ->orWhere('part.status', 'LIKE', "%{$search}%")
                  ->orWhere('part.kategori_inventory', 'LIKE', "%{$search}%");
            });
        }

        // Filtered count
        $recordsFiltered = (clone $baseQuery)->count();

        // Apply sort and pagination
        $data = $baseQuery
            ->orderBy($sortColumn, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        return [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ];
    }
}
