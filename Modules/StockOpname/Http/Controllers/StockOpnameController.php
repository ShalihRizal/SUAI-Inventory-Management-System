<?php

namespace Modules\StockOpname\Http\Controllers;

use App\Exports\Adjusting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Modules\Part\Repositories\PartRepository;
use Modules\Rack\Repositories\RackRepository;
use Modules\PartCategory\Repositories\PartCategoryRepository;
use Modules\StockOpname\Repositories\StockOpnameRepository;
use Modules\StockOpname\Repositories\LogPartRequestRepository;
use App\Helpers\DataHelper;
use App\Helpers\LogHelper;
use DB;
use Illuminate\Support\Facades\Redirect;
use Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\hasstoexport;
use App\Exports\Nostoexport;

class StockOpnameController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->_rackRepository = new RackRepository;
        $this->_partRepository = new PartRepository;
        $this->_partCategoryRepository = new PartCategoryRepository;
        $this->_stockopnameRepository = new StockOpnameRepository;
        $this->_logPartReqRepository = new LogPartRequestRepository;
        $this->_logHelper = new LogHelper;
        $this->module = "StockOpname";
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        // Use direct aggregate queries instead of loading the entire table
        $yesCount = DB::table('part')->where('has_sto', 'yes')->sum('qty_end');
        $noCount  = DB::table('part')->where('has_sto', 'no')->sum('qty_end');

        $partcategories = $this->_partCategoryRepository->getAll();
        $stockopnames   = collect([]);

        $data   = [];
        $labels = [];
        $qty    = [];

        return view('stockopname::index', compact('stockopnames', 'partcategories', 'data', 'labels', 'qty', 'yesCount', 'noCount'));
    }
    public function afindex()
    {
        $params = [
            'part_category_id' => 3,
            'has_sto'          => 'no'
        ];

        // Use direct aggregate queries instead of loading the entire table
        $yesCount = DB::table('part')->where('has_sto', 'yes')->sum('qty_end');
        $noCount  = DB::table('part')->where('has_sto', 'no')->sum('qty_end');

        $partcategories = $this->_partCategoryRepository->getAll();
        $parts          = $this->_partRepository->getAllByParams($params);
        $racks          = $this->_rackRepository->getAll();
        $stockopnames   = collect([]);
        $crimpingdies   = $this->_partRepository->getAllByParamsPaginatedKategori($params, 10);

        $data   = [];
        $labels = [];
        $qty    = [];

        return view('stockopname::afindex', compact('stockopnames', 'parts', 'partcategories', 'data', 'labels', 'qty', 'yesCount', 'noCount', 'racks', 'crimpingdies'));
    }
    public function cfindex()
    {
        $params = [
            'part_category_id' => 4,
            'has_sto'          => 'no'
        ];

        // Use direct aggregate queries instead of loading the entire table
        $yesCount = DB::table('part')->where('has_sto', 'yes')->sum('qty_end');
        $noCount  = DB::table('part')->where('has_sto', 'no')->sum('qty_end');

        $partcategories = $this->_partCategoryRepository->getAll();
        $parts          = $this->_partRepository->getAllByParams($params);
        $racks          = $this->_rackRepository->getAll();
        $stockopnames   = collect([]);
        $crimpingdies   = $this->_partRepository->getAllByParamsPaginatedKategori($params, 10);

        $data   = [];
        $labels = [];
        $qty    = [];

        return view('stockopname::cfindex', compact('stockopnames', 'parts', 'partcategories', 'data', 'labels', 'qty', 'yesCount', 'noCount', 'racks', 'crimpingdies'));
    }
    public function spindex()
    {
        $params = [
            'part_category_id' => 2,
            'has_sto'          => 'no'
        ];

        // Use direct aggregate queries instead of loading the entire table
        $yesCount = DB::table('part')->where('has_sto', 'yes')->sum('qty_end');
        $noCount  = DB::table('part')->where('has_sto', 'no')->sum('qty_end');

        $partcategories = $this->_partCategoryRepository->getAll();
        $parts          = $this->_partRepository->getAllByParams($params);
        $racks          = $this->_rackRepository->getAll();
        $stockopnames   = collect([]);
        $crimpingdies   = $this->_partRepository->getAllByParamsPaginatedKategori($params, 10);

        $data   = [];
        $labels = [];
        $qty    = [];

        return view('stockopname::spindex', compact('stockopnames', 'parts', 'partcategories', 'data', 'labels', 'qty', 'yesCount', 'noCount', 'crimpingdies', 'racks'));
    }
    public function cdindex()
    {
        $params = [
            'part_category_id' => 1,
            'has_sto'          => 'no'
        ];

        // Use direct aggregate queries instead of loading the entire table
        $yesCount = DB::table('part')->where('has_sto', 'yes')->sum('qty_end');
        $noCount  = DB::table('part')->where('has_sto', 'no')->sum('qty_end');

        $partcategories = $this->_partCategoryRepository->getAll();
        $parts          = $this->_partRepository->getAllByParams($params);
        $racks          = $this->_rackRepository->getAll();
        $stockopnames   = collect([]);
        $crimpingdies   = $this->_partRepository->getAllByParamsPaginatedKategori($params, 10);

        $data   = [];
        $labels = [];
        $qty    = [];

        return view('stockopname::cdindex', compact('stockopnames', 'parts', 'partcategories', 'data', 'labels', 'qty', 'yesCount', 'noCount', 'crimpingdies', 'racks'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('stockopname::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        $validator = Validator::make($request->all(), $this->_validationRules(''));

        if ($validator->fails()) {
            return redirect('stockopname')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        $this->_stockopnameRepository->insert(DataHelper::_normalizeParams($request->all(), true));
        $this->_logHelper->store($this->module, $request->stockopname_no, 'create');
        DB::commit();

        return redirect('stockopname')->with('message', 'StockOpname berhasil ditambahkan');
    }
    public function adjusting()
    {
        return Excel::download(new Adjusting, 'adjusting.xlsx');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('stockopname::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('stockopname::edit');
    }

    public function scan()
    {
        // Authorize
        // if (Gate::denies(__FUNCTION__, $this->module)) {
        //     return redirect('unauthorize');
        // }

        $cdparam = [
            'has_sto' => 'no',
            'part_category_id' => '1'
        ];
        $crimpingdies = $this->_partRepository->getAllByParams($cdparam);

        $afparam = [
            'has_sto' => 'no',
            'part_category_id' => '5'
        ];
        $assemblyfixture = $this->_partRepository->getAllByParams($afparam);

        $cfparam = [
            'has_sto' => 'no',
            'part_category_id' => '6'
        ];
        $checkerfixture = $this->_partRepository->getAllByParams($cfparam);

        $spmparam = [
            'has_sto' => 'no',
            'part_category_id' => '4'
        ];
        $sparepartmachine = $this->_partRepository->getAllByParams($spmparam);

        $parts = $this->_partRepository->getAll();
        $partcategories = $this->_partCategoryRepository->getAll();
        $racks = $this->_rackRepository->getAll();

        // dd($parts);

        return view('stockopname::scan', compact('parts', 'racks', 'partcategories', 'sparepartmachine', 'checkerfixture', 'assemblyfixture', 'crimpingdies'));
    }
    public function hassto()
    {
        $params = [
            'has_sto' => 'yes'
        ];

        // Gunakan paginate untuk parts (misal 10 data per halaman)
        $parts = $this->_partRepository->getAllByParamsPaginated($params, 10);

        // Ambil hanya part_no dari parts yang dipaginasi
        $partNumbers = $parts->pluck('part_no')->toArray();

        // Ambil hanya logs yang relevan dengan parts di halaman ini
        $logs = $this->_logPartReqRepository->getLogsByPartNumbers($partNumbers);

        // Tambahkan qty_actual dari logs ke dalam parts
        foreach ($parts as $part) {
            $log = $logs->where('part_no', $part->part_no)->first();
            $part->qty_description = $log ? $log->description : null;
        }

        return view('stockopname::hassto', compact('parts', 'logs'));
    }
    public function nosto()
    {
        $params = [
            'has_sto' => 'no'
        ];

        $parts = $this->_partRepository->getAllByParams($params);

        return view('stockopname::nosto', compact('parts'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function afupdate(Request $request, $id)
    {
        $qty_end   = $this->getdataByPartNo($request->part_nos)['total'];
        $adjusting = $qty_end - $request->adjust;

        $data = [
            'part_no'    => $request->part_nos,
            'description'=> $request->adjust,
            'qty_end'    => $qty_end,
            'last_sto'   => Carbon::now()->format('Y-m-d'),
            'qty_actual' => $adjusting
        ];

        DB::beginTransaction();
        try {
            $this->_stockopnameRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
            $this->_logPartReqRepository->insert(DataHelper::_normalizeParams($data, true));
            $this->_logHelper->store($this->module, $request->stockopname_no, 'update');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['error' => $e->getMessage()]);
        }

        return Redirect::back();
    }
    public function cfupdate(Request $request, $id)
    {
        $qty_end   = $this->getdataByPartNo($request->part_nos)['total'];
        $adjusting = $qty_end - $request->adjust;

        $data = [
            'part_no'    => $request->part_nos,
            'description'=> $request->adjust,
            'qty_end'    => $qty_end,
            'last_sto'   => Carbon::now()->format('Y-m-d'),
            'qty_actual' => $adjusting
        ];

        DB::beginTransaction();
        try {
            $this->_stockopnameRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
            $this->_logPartReqRepository->insert(DataHelper::_normalizeParams($data, true));
            $this->_logHelper->store($this->module, $request->stockopname_no, 'update');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['error' => $e->getMessage()]);
        }

        return Redirect::back();
    }

    public function cdupdate(Request $request, $id)
    {
        $qty_end   = $this->getdataByPartNo($request->part_nos)['total'];
        $adjusting = $qty_end - $request->adjust;

        $data = [
            'part_no'    => $request->part_nos,
            'description'=> $request->adjust,
            'qty_end'    => $qty_end,
            'last_sto'   => Carbon::now()->format('Y-m-d'),
            'qty_actual' => $adjusting
        ];

        DB::beginTransaction();
        try {
            $this->_stockopnameRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
            $this->_logPartReqRepository->insert(DataHelper::_normalizeParams($data, true));
            $this->_logHelper->store($this->module, $request->stockopname_no, 'update');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['error' => $e->getMessage()]);
        }

        return Redirect::back();
    }
    public function spupdate(Request $request, $id)
    {
        $qty_end   = $this->getdataByPartNo($request->part_nos)['total'];
        $adjusting = $qty_end - $request->adjust;

        $data = [
            'part_no'    => $request->part_nos,
            'description'=> $request->adjust,
            'qty_end'    => $qty_end,
            'last_sto'   => Carbon::now()->format('Y-m-d'),
            'qty_actual' => $adjusting
        ];

        DB::beginTransaction();
        try {
            $this->_stockopnameRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
            $this->_logPartReqRepository->insert(DataHelper::_normalizeParams($data, true));
            $this->_logHelper->store($this->module, $request->stockopname_no, 'update');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['error' => $e->getMessage()]);
        }

        return Redirect::back();
    }

    public function updateall(Request $request)
    {

        DB::beginTransaction();

        $this->_stockopnameRepository->updateHasStoToNo(DataHelper::_normalizeParams($request->all(), false, true));
        $this->_logHelper->store($this->module, $request->stockopname_no, 'update');

        DB::commit();

        return redirect('stockopname')->with('message', 'StockOpname berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }
        // Check detail to db
        $detail = $this->_stockopnameRepository->getById($id);

        if (!$detail) {
            return redirect('stockopname');
        }

        DB::beginTransaction();

        $this->_stockopnameRepository->delete($id);
        $this->_logHelper->store($this->module, $detail->stockopname_no, 'delete');

        DB::commit();

        return redirect('stockopname')->with('message', 'StockOpname berhasil dihapus');
    }

    /**
     * Get data the specified resource in storage.
     * @param int $id
     * @return Response
     */
    public function getdata($id)
    {

        $response = array('status' => 0, 'result' => array());
        $getDetail = $this->_stockopnameRepository->getById($id);

        if ($getDetail) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
        }

        return $response;
    }
    public function getdataByPartNo($part_no)
    {

        $decoded_part_no = urldecode($part_no);
        $search_part_no = str_replace('/', '\/', $decoded_part_no);
        $param = [
            'part_no' => $decoded_part_no
            // 'has_sto' => 'no'
        ];

        $response = array('status' => 0, 'result' => array());
        $getDetail = $this->_partRepository->getAllByParams($param);

        $totalqtyend = 0;

        foreach ($getDetail as $detail) {
            $totalqtyend += $detail->qty_begin + $detail->qty_in - $detail->qty_out;
        }

        if ($getDetail) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
            $response['total'] = $totalqtyend;
        }
        return $response;
    }

    public function getdataByParam($param)
    {

        // dd($param);

        $params = [
            'part_no' => $param
        ];

        $response = array('status' => 0, 'result' => array());
        $getDetail = $this->_partRepository->getAllByParams($params);

        // dd($getDetail);

        if ($getDetail) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
        }

        return $response;
    }

    public function hasstoexport(Request $request)
    {
        $categoryId = $request->input('part_category_id'); // Ambil kategori dari request
        return Excel::download(new HasstoExport($categoryId), 'stockopname.xlsx');
    }

    public function nostoexport()
    {
        return Excel::download(new NostoExport, 'stockopname.xlsx');
    }

    private function _validationRules($id = '')
    {
        if ($id == '') {
            return [
                'part_no' => 'required',
            ];
        } else {
            return [
                'part_no' => 'required',
            ];
        }
    }
}
