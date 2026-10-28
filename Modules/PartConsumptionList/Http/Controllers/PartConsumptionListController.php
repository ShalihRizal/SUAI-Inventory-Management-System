<?php

namespace Modules\PartConsumptionList\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

use Modules\Part\Repositories\PartRepository;
use Modules\PartConsumptionList\Repositories\PartConsumptionListRepository;
use Modules\PartConsumptionList\Repositories\PartConsumptionListDetailRepository;
use Modules\Carline\Repositories\CarlineRepository;
use Modules\Machine\Repositories\MachineRepository;
use Modules\Carname\Repositories\CarnameRepository;
use Modules\CarlineCategory\Repositories\CarlineCategoryRepository;
use App\Helpers\LogHelper;
use App\Helpers\DataHelper;

use DB;
use Validator;

class PartConsumptionListController extends Controller
{

    protected $partRepository;
    protected $partConsumptionListRepository;
    protected $partConsumptionListDetailRepository;
    protected $carlineRepository;
    protected $machineRepository;
    protected $carnameRepository;
    protected $carlineCategoryRepository;
    protected $logHelper;
    protected $module;

    public function __construct(
        PartRepository $partRepository,
        PartConsumptionListRepository $partConsumptionListRepository,
        PartConsumptionListDetailRepository $partConsumptionListDetailRepository,
        CarlineRepository $carlineRepository,
        MachineRepository $machineRepository,
        CarnameRepository $carnameRepository,
        CarlineCategoryRepository $carlineCategoryRepository,
        LogHelper $logHelper
    ) {
        $this->middleware('auth');

        $this->partRepository = $partRepository;
        $this->partConsumptionListRepository = $partConsumptionListRepository;
        $this->partConsumptionListDetailRepository = $partConsumptionListDetailRepository;
        $this->carlineRepository = $carlineRepository;
        $this->machineRepository = $machineRepository;
        $this->carnameRepository = $carnameRepository;
        $this->carlineCategoryRepository = $carlineCategoryRepository;
        $this->logHelper = $logHelper;
        $this->module = "PartConsumptionList";
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

        $partconsumptionlists = $this->partConsumptionListRepository->getAll();
        $parts = collect([]);
        $carlines = $this->carlineRepository->getAll();
        $machines = $this->machineRepository->getAll();
        $carnames = $this->carnameRepository->getAll();
        $carlinecategories = $this->carlineCategoryRepository->getAll();

        // dd()

        return view('partconsumptionlist::index', compact('partconsumptionlists', 'parts', 'carlines', 'carlinecategories', 'carnames', 'machines'));
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

        return view('partconsumptionlist::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {

        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }
        $currentDate = Carbon::now();
        $dataNaon = [
            'pcl_id' => $request->pcl_id,
            'part_id' => $request->part_id,
            'pcl_category' => $request->pcl_category,
            'family' => $request->family,
            'pattern' => $request->pattern,
            'pic_prepared' => $request->pic_prepared,
            'reason' => $request->reason,
            'pic_req' => $request->pic_req,
            'carline' => $request->carline,
            'carname' => $request->carname,
            'status' => $request->status,
            'fase' => $request->fase,
            'created_at' => $currentDate,
            'created_by',
            'updated_at',
            'updated_by'
        ];

        // dd($request);

        DB::beginTransaction();
        $this->partConsumptionListRepository->insert(DataHelper::_normalizeParams($request->all()));
        // $check = $this->partConsumptionListRepository->insert(DataHelper::_normalizeParams($partreq, true));
        $this->logHelper->store($this->module, $request->pcl_id, 'create');
        DB::commit();
        // dd($check);

        return redirect('partconsumptionlist')->with('message', 'PartConsumptionList berhasil ditambahkan');
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

        return view('partconsumptionlist::show');
    }
    public function detail($id)
    {

        $params = [
            'part_consumption_list_id' => $id
        ];

        $partconsumptionlists = $this->partConsumptionListRepository->getById($id);
        $partconsumptionlistsdetails = $this->partConsumptionListDetailRepository->getAllByParams($params);

        // dd($partconsumptionlistsdetails);

        return view('partconsumptionlist::detail', compact('partconsumptionlists', 'partconsumptionlistsdetails'));
    }

    public function sendWA()
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        $token = 'w#xDUKWBboS97ME_gR8p';
        $target = '62895620310202';

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'target' => $target,
                    'message' => 'apal, ngetes weh',

                ),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: $token"
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);

        return view('partconsumptionlist::show');
    }

    public function importCSV(Request $request, $id)
    {

        // dd($id);
        $request->validate([
            'csv' => 'required|mimes:csv,txt',
        ]);

        $file = $request->file('csv');
        $csvData = array_map('str_getcsv', file($file));

        DB::beginTransaction();
        foreach ($csvData as $row) {
            $check = $this->partConsumptionListDetailRepository->insert([

                'part_consumption_list_id' => $id,
                'part_id' => $row[0],
                'end_drawing' => $row[1],
                'no_accessories' => $row[2],
                'type' => $row[3],
                'tiang' => $row[4],
                'qty_per_jb' => $row[5],
                'qty_total' => $row[6],
                'created_at' => $row[7],
                'created_by' => $row[8],
                'updated_at' => $row[9],
                'updated_by' => $row[10]
            ]);
        }

        // dd($request);
        DB::commit();

        return redirect('partconsumptionlist')->with('message', 'CSV file has been imported successfully.');
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

        return view('partconsumptionlist::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {

        // dd($request->all());
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        // $validator = Validator::make($request->all(), $this->_validationRules($id));

        // if ($validator->fails()) {
        //     return redirect('partconsumptionlist')
        //         ->withErrors($validator)
        //         ->withInput();
        // }

        // dd($request);
        DB::beginTransaction();

        // $check = $this->partConsumptionListRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
        $this->partConsumptionListRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
        $this->logHelper->store($this->module, $request->part_req_number, 'update');

        DB::commit();
        // dd($, $request);

        return redirect('partconsumptionlist')->with('message', 'PartConsumptionList berhasil diubah');
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
        $detail = $this->partConsumptionListRepository->getById($id);

        if (!$detail) {
            return redirect('partconsumptionlist');
        }

        DB::beginTransaction();

        $this->partConsumptionListRepository->delete($id);
        $this->logHelper->store($this->module, $detail->part_req_number, 'delete');

        DB::commit();

        return redirect('partconsumptionlist')->with('message', 'PartConsumptionList berhasil dihapus');
    }

    /**
     * Get data the specified resource in storage.
     * @param int $id
     * @return Response
     */
    public function getdata($id)
    {

        $response = array('status' => 0, 'result' => array());
        $getDetail = $this->partConsumptionListRepository->getById($id);

        if ($getDetail) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
        }

        // dd($getDetail);

        return $response;
    }

    private function _validationRules($id = '')
    {
        if ($id == '') {
            return [
                'pcl_id' => 'required',
            ];
        } else {
            return [
                'pcl_id' => 'required',
            ];
        }
    }
}
