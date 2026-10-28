<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use Modules\PartCategory\Repositories\PartCategoryRepository;
use Modules\Part\Repositories\PartRepository;
use Modules\SysLog\Repositories\SysLogRepository;
use App\Helpers\DataHelper;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\DB;
use Validator;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->_partCategoryRepository = new PartCategoryRepository;
        $this->_partRepository = new PartRepository;
        $this->_logRepository = new SysLogRepository;
        $this->_logHelper = new LogHelper;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $partcategories = $this->_partCategoryRepository->getAll();
        $logs = $this->_logRepository->paginate(10);

        $thisMonthStart = Carbon::now()->startOfMonth()->toDateTimeString();
        $thisMonthEnd = Carbon::now()->endOfMonth()->toDateTimeString();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth()->toDateTimeString();

        $stats = DB::table('part')
            ->select(
                'part_category_id',
                DB::raw('COALESCE(SUM(CAST(qty_end AS SIGNED)), 0) as total_qty'),
                DB::raw("COALESCE(SUM(CASE WHEN updated_at BETWEEN '{$thisMonthStart}' AND '{$thisMonthEnd}' THEN CAST(qty_end AS SIGNED) ELSE 0 END), 0) as this_month_qty"),
                DB::raw("COALESCE(SUM(CASE WHEN updated_at BETWEEN '{$thisMonthStart}' AND '{$thisMonthEnd}' THEN CAST(qty_end AS SIGNED) * CAST(price AS DECIMAL(15,2)) ELSE 0 END), 0) as this_month_amount"),
                DB::raw("COALESCE(SUM(CASE WHEN updated_at <= '{$lastMonthEnd}' THEN CAST(qty_end AS SIGNED) ELSE 0 END), 0) as last_month_qty"),
                DB::raw("COALESCE(SUM(CASE WHEN updated_at <= '{$lastMonthEnd}' THEN CAST(qty_end AS SIGNED) * CAST(price AS DECIMAL(15,2)) ELSE 0 END), 0) as last_month_amount")
            )
            ->groupBy('part_category_id')
            ->get()
            ->keyBy('part_category_id');

        $labels = [];
        $qty = [];
        $thsqty = [];
        $lstqty = [];
        $thsamounts = [];
        $lstamounts = [];

        foreach ($partcategories as $partcategory) {
            $catId = $partcategory->part_category_id;
            $stat = $stats->get($catId);

            $labels[$catId] = $partcategory->part_category_name;
            $qty[$catId] = $stat ? floatval($stat->total_qty) : 0;
            $thsqty[$catId] = $stat ? floatval($stat->this_month_qty) : 0;
            $lstqty[$catId] = $stat ? floatval($stat->last_month_qty) : 0;
            $thsamounts[$catId] = $stat ? floatval($stat->this_month_amount) : 0;
            $lstamounts[$catId] = $stat ? floatval($stat->last_month_amount) : 0;
        }

        $allParts = collect([]);

        return view('dashboard::index', compact('partcategories', 'labels', 'thsqty', 'qty', 'lstqty', 'logs', 'allParts', 'thsamounts', 'lstamounts'));
    }

    function array_multisum(array $arr): float
    {
        $sum = array_sum($arr);
        foreach ($arr as $child) {
            $sum += is_array($child) ? $this->array_multisum($child) : 0;
        }
        return $sum;
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function tv()
    {
        $partcategories = $this->_partCategoryRepository->getAll();
        $parts = DB::table('part')
            ->join('part_category', 'part.part_category_id', '=', 'part_category.part_category_id')
            ->select(
                'part.part_id',
                'part.part_no',
                'part.part_name',
                'part_category.part_category_name',
                'part.no_urut',
                'part.qty_end',
                'part.asal',
                'part.wear_and_tear_code',
                'part.invoice',
                'part.po',
                'part.po_date',
                'part.rec_date',
                'part.used_date'
            )
            ->orderBy('part.part_id')
            ->get();
        return view('dashboard::tv', compact('partcategories', 'parts'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('dashboard::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('dashboard::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id) {}
}
