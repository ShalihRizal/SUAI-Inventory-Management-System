<?php

namespace Modules\Forecast\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

use Modules\PartCategory\Repositories\PartCategoryRepository;
use Modules\Part\Repositories\PartRepository;
use Modules\CarlineCategory\Repositories\CarlineCategoryRepository;
use Modules\Forecast\Repositories\ForecastRepository;
use App\Helpers\DataHelper;
use App\Helpers\LogHelper;
// use DB;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;
use App\Services\ForecastService;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Facades\Excel;

class ForecastController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->_forecastRepository = new ForecastRepository;
        $this->_partCategoryRepository = new PartCategoryRepository;
        $this->_partRepository = new PartRepository;
        $this->_CarlineCategoryRepository = new CarlineCategoryRepository;
        $this->_logHelper           = new LogHelper;
        $this->module               = "Forecast";
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

        $forecasts = collect([]);
        $partcategories = $this->_partCategoryRepository->getAll();
        $parts = $this->_partRepository->getAll();
        $carlinecategories = $this->_CarlineCategoryRepository->getAll();

        return view('forecast::index', compact('forecasts', 'partcategories', 'parts', 'carlinecategories'));
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

        return view('forecast::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            // Authorize
            if (Gate::denies(__FUNCTION__, $this->module)) {
                return redirect('unauthorize');
            }
    
            if (!$request->hasFile('file')) {
                return response()->json(['message' => 'No file uploaded'], 400);
            }
    
            // Start a database transaction
            DB::beginTransaction();
    
            $file = $request->file('file');
            $filePath = $file->getRealPath();
            $csvData = array_map('str_getcsv', file($filePath));
            $header = array_shift($csvData);
    
            foreach ($csvData as $row) {
                $data = array_combine($header, $row);
    
                $existingData = DB::table('part')
                    ->where('part_id', $data['part_id'])
                    ->first();
    
                if ($existingData) {
                    // Update the record
                    DB::table('part')
                        ->where('part_id', $data['part_id'])
                        ->update($data);
                } else {
                    // Insert a new record
                    DB::table('part')->insert($data);
                }
            }
    
            // Commit the transaction if all operations succeeded
            DB::commit();
    
            // Log your action or any relevant information
            Log::info('File uploaded successfully');
    
            return redirect('forecast');
        } catch (\Exception $e) {
        // Rollback the transaction if an exception occurs
        DB::rollback();

        // Log the exception with more details for debugging
        Log::error('Failed to upload file: ' . $e->getMessage() . ' at ' . $e->getFile() . ' line ' . $e->getLine());

        return response()->json(['message' => 'Failed to upload file. ' . $e->getMessage()], 500);
    }

    return response()->json(['message' => 'File upload failed'], 400);
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

        return view('forecast::show');
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

        return view('forecast::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        $validator = Validator::make($request->all(), $this->_validationRules($id));

        if ($validator->fails()) {
            return redirect('forecast')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $this->_forecastRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
        $this->_logHelper->store($this->module, $request->forecast_no, 'update');

        DB::commit();

        return redirect('forecast')->with('message', 'Part berhasil diubah');
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
        $detail  = $this->_forecastRepository->getById($id);

        if (!$detail) {
            return redirect('forecast');
        }

        DB::beginTransaction();

        $this->_forecastRepository->delete($id);
        $this->_logHelper->store($this->module, $detail->forecast_no, 'delete');

        DB::commit();

        return redirect('forecast')->with('message', 'Part berhasil dihapus');
    }

    /**
     * Get data the specified resource in storage.
     * @param int $id
     * @return Response
     */
    public function getdata($id)
    {

        $response   = array('status' => 0, 'result' => array());
        $getDetail  = $this->_forecastRepository->getById($id);

        if ($getDetail) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
        }

        return $response;
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
