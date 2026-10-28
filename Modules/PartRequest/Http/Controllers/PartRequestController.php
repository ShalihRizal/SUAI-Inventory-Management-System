<?php

namespace Modules\PartRequest\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Auth;

use Modules\PartRequest\Repositories\PartRequestRepository;
use Modules\Carline\Repositories\CarlineRepository;
use Modules\Carname\Repositories\CarnameRepository;
use Modules\Machine\Repositories\MachineRepository;
use Modules\ListOfPartRequest\Repositories\ListOfPartRequestRepository;
use Modules\Users\Repositories\UsersRepository;
use Modules\CarlineCategory\Repositories\CarlineCategoryRepository;
use Modules\Part\Repositories\PartRepository;
use App\Helpers\DataHelper;
use App\Helpers\LogHelper;
use DB;
use Validator;
use Carbon\Carbon;

class PartRequestController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');

        $this->_partRepository = new PartRepository;
        $this->_PartRequestRepository = new PartRequestRepository;
        $this->_CarlineRepository = new CarlineRepository;
        $this->_carnameRepository = new CarnameRepository;
        $this->_userRepository = new UsersRepository;
        $this->_MachineRepository = new MachineRepository;
        $this->_ListOfPartRequestRepository = new ListOfPartRequestRepository;
        $this->_CarlineCategoryRepository = new CarlineCategoryRepository;
        $this->_logHelper = new LogHelper;
        $this->module = "PartRequest";
    }

    public function index(Request $request)
    {
        return $this->spindex($request);
    }

    public function spindex(Request $request)
    {
        $params = [
            'part_category_id' => 2
        ];
        $userparams = [
            'group_id' => 22
        ];

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Panggil repository dengan filter tanggal jika ada
        $partrequests = $this->_PartRequestRepository->getAllByParams($params, $startDate, $endDate);
        $parts = $this->_partRepository->getAllByParams($params);
        $users = $this->_userRepository->getAllByParams($userparams);
        $carlines = $this->_CarlineRepository->getAll();
        $machines = $this->_MachineRepository->getAll();
        $carlinecategories = $this->_CarlineCategoryRepository->getAll();
        $carnames = $this->_carnameRepository->getAll();

        // dd($users);

        return view('partrequest::sp', compact('partrequests', 'parts', 'carlines', 'carlinecategories', 'machines', 'carnames'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function spcreate()
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::spcreate');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function spstore(Request $request)
    {

        date_default_timezone_set('Asia/Jakarta');
        $userGroupId = Auth::user()->group_id;
        $part = $this->_partRepository->getById($request->part_id);
        // $last = $this->_PartRequestRepository->getLast();

        $file_images = [];
        $file_paths = [];
        $image_part_displays = $request->input('image_part_display');

        if ($request->hasFile('image_part')) {
            foreach ($request->file('image_part') as $index => $file) {
                if ($file->isValid()) {
                    $fileName = DataHelper::getFileName($file);
                    $filePath = DataHelper::getFilePath(false, true);
                    $file->storeAs($filePath, $fileName, 'public');
                    $file_images[$index] = $fileName;
                    $file_paths[$index] = $filePath;
                } else {
                    // Jika file tidak valid, gunakan nama dari image_part_display
                    $fileName = DataHelper::getFileName($image_part_displays[$index]);
                    $filePath = DataHelper::getFilePath(false, true);
                    $file_images[$index] = $fileName;
                    $file_paths[$index] = $filePath;
                }
            }
        } else {
            // Jika tidak ada file yang diunggah, gunakan nama dari image_part_display
            foreach ($image_part_displays as $index => $display) {
                $file_images[$index] = $display;
                $file_paths[$index] = DataHelper::getFilePath(false, true);
            }
        }

        $currentMonth = strtoupper(substr(date("F"), 0, 3));
        $currentYear = date('Y');

        $count = $this->_PartRequestRepository->count();

        $padded_part_req_id = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $part_req_number = "$padded_part_req_id/TO/SP/$currentMonth/$currentYear";

        $part_ids = $request->input('part_id');
        $carnames = $request->input('carname');
        $carmodels = $request->input('car_model');
        $shifts = $request->input('shift');
        $serialnos = $request->input('serial_no');
        $applicatornos = $request->input('applicator_no');
        $sidenos = $request->input('side_no');
        $machine_nos = $request->input('machine_no');
        $alasans = $request->input('alasan');
        $orders = $request->input('order');
        $strokes = $request->input('stroke');
        $pics = $request->input('pic');
        $remarkss = $request->input('remarks');
        $part_qtys = $request->input('part_qty');
        $wear_and_tear_statuss = $request->input('wear_and_tear_status');

        $partRequests = [];
        date_default_timezone_set('Asia/Jakarta');
        foreach ($part_ids as $index => $part_id) {
            $partRequests_initial[] = [
                'part_req_pic_filename' => isset($file_images[$index]) ? $file_images[$index] : (isset($image_part_displays[$index]) ? $image_part_displays[$index] : null),
                'part_req_pic_path' => $file_paths[$index] ?? DataHelper::getFilePath(false, true),
                'part_id' => $part_ids[$index],
                'carline' => $carnames[$index],
                'car_model' => $carmodels[$index],
                'shift' => $shifts[$index],
                'serial_no' => $serialnos[$index],
                'applicator_no' => $applicatornos[$index],
                'side_no' => $sidenos[$index],
                'machine_no' => $machine_nos[$index],
                'alasan' => $alasans[$index],
                'order' => $orders[$index],
                'stroke' => $strokes[$index],
                'pic' => $pics[$index],
                'remarks' => $remarkss[$index],
                'part_qty' => $part_qtys[$index],
                'status' => 0,
                'wear_and_tear_status' => $wear_and_tear_statuss[$index],
                'part_req_number' => $part_req_number,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $partImage = $partRequests_initial[0]['part_req_pic_filename'];

        foreach ($part_ids as $index => $part_id) {
            $partRequests[] = [
                'part_req_pic_filename' => $partImage,
                'part_req_pic_path' => $file_paths[$index] ?? DataHelper::getFilePath(false, true),
                'part_id' => $part_ids[$index],
                'carline' => $carnames[$index],
                'car_model' => $carmodels[$index],
                'shift' => $shifts[$index],
                'serial_no' => $serialnos[$index],
                'applicator_no' => $applicatornos[$index],
                'side_no' => $sidenos[$index],
                'machine_no' => $machine_nos[$index],
                'alasan' => $alasans[$index],
                'order' => $orders[$index],
                'stroke' => $strokes[$index],
                'pic' => $pics[$index],
                'remarks' => $remarkss[$index],
                'part_qty' => $part_qtys[$index],
                'status' => 0,
                'wear_and_tear_status' => $wear_and_tear_statuss[$index],
                'part_req_number' => $part_req_number,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        // dd($partRequests);

        DB::beginTransaction();
        try {
            foreach ($partRequests as $partreq) {
                $cek = $this->_PartRequestRepository->insertGetId(DataHelper::_normalizeParams($partreq, true));
                $listofpartreq = [
                    "part_req_id" => $cek,
                    'created_at' => now(),
                ];
                $this->_ListOfPartRequestRepository->insert(DataHelper::_normalizeParams($listofpartreq, true));
                $this->_logHelper->store($this->module, $partreq['part_req_number'], 'create');
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving part requests: ' . $e->getMessage());
            return redirect('partrequest/sp')->with('error', 'Gagal menyimpan part request: ' . $e->getMessage());
        }

        $targetNumbers = [
            '6287836410547',
            '6287824003437',
            '6287824003436',
            '6285351891534'
        ];

        $message = 'Pemberitahuan! Ada Part Request masuk dengan nomor ' . $part_req_number . ', mohon untuk segera periksa. Terimakasih. Akses disini : https://inventory.suaisystem.com';
        $token = 'xT-cKdExR44PbctH-8LY';

        try {
            $target = implode(',', $targetNumbers);
            $whatsappResponse = Http::timeout(5)->get('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'token' => $token,
            ]);

            if ($whatsappResponse->failed()) {
                Log::error('Gagal mengirim pesan WA Fonnte (spstore): ' . $whatsappResponse->body());
            }
        } catch (\Throwable $e) {
            Log::error('Error / Timeout mengirim pesan WA Fonnte (spstore): ' . $e->getMessage());
        }

        return redirect('partrequest/sp')->with('message', 'PartRequest berhasil ditambahkan');
    }



    public function SendWA()
    {
        $userparams = [
            'group_id' => 7
        ];

        $users = $this->_userRepository->getAllByParams($userparams);

        // Extract phone numbers from users
        $phoneNumbers = [];
        foreach ($users as $user) {
            // Assuming phone number is stored in the 'phone' property, modify it accordingly
            if (!empty($user->no_hp)) {
                $phoneNumbers[] = $user->no_hp;
            }
        }

        // Convert the array of phone numbers to a comma-separated string
        $target = implode(',', $phoneNumbers);

        $part_req_number = '...'; // Provide the actual part request number

        try {
            $whatsappResponse = Http::timeout(5)->get('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => 'Pemberitahuan! Ada Part Request masuk dengan nomor ' . $part_req_number . ', mohon untuk segera periksa. Terimakasih. Akses disini: https://inventory.suaisystem.com',
                'token' => 'xT-cKdExR44PbctH-8LY',
            ]);

            if ($whatsappResponse->failed()) {
                Log::error('Gagal mengirim pesan WA Fonnte (SendWA): ' . $whatsappResponse->body());
            }
        } catch (\Throwable $e) {
            Log::error('Error / Timeout mengirim pesan WA Fonnte (SendWA): ' . $e->getMessage());
        }
        return redirect('partrequest/sp')->with('message', 'PartRequest berhasil ditambahkan');
    }


    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function spshow($id)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::spshow');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function spedit($id)
    {

        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::spedit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function spupdate(Request $request, $id)
    {


        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        $validator = Validator::make($request->all(), $this->_validationRules($id));

        if ($validator->fails()) {
            return redirect('partrequest/sp')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $this->_PartRequestRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
        $this->_logHelper->store($this->module, $request->part_req_number, 'update');

        DB::commit();

        return redirect('partrequest/sp')->with('message', 'PartRequest berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function spdestroy($id)
    {
        // Fetch directly from part_request WITHOUT JOIN so it never fails
        // due to a missing/orphaned part_id foreign key
        $detail = DB::table('part_request')->where('part_req_id', $id)->first();

        if (!$detail) {
            return redirect('partrequest/sp');
        }

        DB::beginTransaction();
        try {
            $part_qty = (int) $detail->part_qty;
            $part_id  = $detail->part_id;

            // Update qty_out on the linked part (only if the part still exists)
            $part = $this->_partRepository->getById($part_id);
            if ($part && is_object($part)) {
                $this->_partRepository->update(
                    ['qty_out' => max(0, $part->qty_out - $part_qty)],
                    $part_id
                );
            }

            $this->_PartRequestRepository->delete($id);
            $this->_logHelper->store($this->module, $detail->part_req_number, 'delete');

            DB::commit();
            return redirect('partrequest/sp')->with('message', 'PartRequest berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in spdestroy: ' . $e->getMessage());
            return redirect('partrequest/sp')->with('error', 'Gagal menghapus PartRequest: ' . $e->getMessage());
        }
    }

    /**
     * Get data the specified resource in storage.
     * @param int $id
     * @return Response
     */
    public function spgetdata($id)
    {

        $response = array('status' => 0, 'result' => array());
        $getDetail = $this->_PartRequestRepository->getById($id);

        if ($getDetail) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
        }



        return $response;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function cdindex(Request $request)
    {
        $params = ['part_category_id' => 1];

        // Tangkap input tanggal dari request
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Panggil repository dengan filter tanggal jika ada
        $partrequests = $this->_PartRequestRepository->getAllByParams($params, $startDate, $endDate);

        $parts = $this->_partRepository->getAllByParams($params);
        $carlines = $this->_CarlineRepository->getAll();
        $machines = $this->_MachineRepository->getAll();
        $carlinecategories = $this->_CarlineCategoryRepository->getAll();
        $carnames = $this->_carnameRepository->getAll();

        return view('partrequest::cd', compact('partrequests', 'parts', 'carlines', 'carlinecategories', 'machines', 'carnames'));
    }


    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function cdcreate()
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::cdcreate');
    }
    public function createcd()
    {
        $params       = ['part_category_id' => 1];
        $parts        = $this->_partRepository->getAllByParams($params);
        $carlines     = $this->_CarlineRepository->getAll();
        $machines     = $this->_MachineRepository->getAll();
        $carlinecategories = $this->_CarlineCategoryRepository->getAll();
        $carnames     = $this->_carnameRepository->getAll();
        // partrequests not needed on the create form
        $partrequests = collect();
        return view('partrequest::createcd', compact('partrequests', 'parts', 'carlines', 'machines', 'carlinecategories', 'carnames'));
    }
    public function createsp()
    {
        $params       = ['part_category_id' => 2];
        $parts        = $this->_partRepository->getAllByParams($params);
        $carlines     = $this->_CarlineRepository->getAll();
        $machines     = $this->_MachineRepository->getAll();
        $carlinecategories = $this->_CarlineCategoryRepository->getAll();
        $carnames     = $this->_carnameRepository->getAll();
        $partrequests = collect();
        return view('partrequest::createsp', compact('partrequests', 'parts', 'carlines', 'machines', 'carlinecategories', 'carnames'));
    }
    public function createcf()
    {
        $params       = ['part_category_id' => 4];
        $parts        = $this->_partRepository->getAllByParams($params);
        $carlines     = $this->_CarlineRepository->getAll();
        $machines     = $this->_MachineRepository->getAll();
        $carlinecategories = $this->_CarlineCategoryRepository->getAll();
        $carnames     = $this->_carnameRepository->getAll();
        $partrequests = collect();
        return view('partrequest::createcf', compact('partrequests', 'parts', 'carlines', 'machines', 'carlinecategories', 'carnames'));
    }
    public function createaf()
    {
        $params       = ['part_category_id' => 3];
        $parts        = $this->_partRepository->getAllByParams($params);
        $carlines     = $this->_CarlineRepository->getAll();
        $machines     = $this->_MachineRepository->getAll();
        $carlinecategories = $this->_CarlineCategoryRepository->getAll();
        $carnames     = $this->_carnameRepository->getAll();
        $partrequests = collect();
        return view('partrequest::createaf', compact('partrequests', 'parts', 'carlines', 'machines', 'carlinecategories', 'carnames'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function cdstore(Request $request)
    {

        date_default_timezone_set('Asia/Jakarta');
        $userGroupId = Auth::user()->group_id;
        $part = $this->_partRepository->getById($request->part_id);
        // $last = $this->_PartRequestRepository->getLast();

        $file_images = [];
        $file_paths = [];
        $image_part_displays = $request->input('image_part_display');

        if ($request->hasFile('image_part')) {
            foreach ($request->file('image_part') as $index => $file) {
                if ($file->isValid()) {
                    $fileName = DataHelper::getFileName($file);
                    $filePath = DataHelper::getFilePath(false, true);
                    $file->storeAs($filePath, $fileName, 'public');
                    $file_images[$index] = $fileName;
                    $file_paths[$index] = $filePath;
                } else {
                    // Jika file tidak valid, gunakan nama dari image_part_display
                    $fileName = DataHelper::getFileName($image_part_displays[$index]);
                    $filePath = DataHelper::getFilePath(false, true);
                    $file_images[$index] = $fileName;
                    $file_paths[$index] = $filePath;
                }
            }
        } else {
            // Jika tidak ada file yang diunggah, gunakan nama dari image_part_display
            foreach ($image_part_displays as $index => $display) {
                $file_images[$index] = $display;
                $file_paths[$index] = DataHelper::getFilePath(false, true);
            }
        }

        $currentMonth = strtoupper(substr(date("F"), 0, 3));
        $currentYear = date('Y');

        $count = $this->_PartRequestRepository->count();

        $padded_part_req_id = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $part_req_number = "$padded_part_req_id/TO/CD/$currentMonth/$currentYear";

        $part_ids = $request->input('part_id');
        $carnames = $request->input('carname');
        $carmodels = $request->input('car_model');
        $shifts = $request->input('shift');
        $serialnos = $request->input('serial_no');
        $applicatornos = $request->input('applicator_no');
        $sidenos = $request->input('side_no');
        $machine_nos = $request->input('machine_no');
        $alasans = $request->input('alasan');
        $orders = $request->input('order');
        $strokes = $request->input('stroke');
        $pics = $request->input('pic');
        $remarkss = $request->input('remarks');
        $part_qtys = $request->input('part_qty');
        $wear_and_tear_statuss = $request->input('wear_and_tear_status');

        $partRequests = [];
        date_default_timezone_set('Asia/Jakarta');
        foreach ($part_ids as $index => $part_id) {
            $partRequests_initial[] = [
                'part_req_pic_filename' => isset($file_images[$index]) ? $file_images[$index] : (isset($image_part_displays[$index]) ? $image_part_displays[$index] : null),
                'part_req_pic_path' => $file_paths[$index] ?? DataHelper::getFilePath(false, true),
                'part_id' => $part_ids[$index],
                'carline' => $carnames[$index],
                'car_model' => $carmodels[$index],
                'shift' => $shifts[$index],
                'serial_no' => $serialnos[$index],
                'applicator_no' => $applicatornos[$index],
                'side_no' => $sidenos[$index],
                'machine_no' => $machine_nos[$index],
                'alasan' => $alasans[$index],
                'order' => $orders[$index],
                'stroke' => $strokes[$index],
                'pic' => $pics[$index],
                'remarks' => $remarkss[$index],
                'part_qty' => $part_qtys[$index],
                'status' => 0,
                'wear_and_tear_status' => $wear_and_tear_statuss[$index],
                'part_req_number' => $part_req_number,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $partImage = $partRequests_initial[0]['part_req_pic_filename'];

        foreach ($part_ids as $index => $part_id) {
            $partRequests[] = [
                'part_req_pic_filename' => $partImage,
                'part_req_pic_path' => $file_paths[$index] ?? DataHelper::getFilePath(false, true),
                'part_id' => $part_ids[$index],
                'carline' => $carnames[$index],
                'car_model' => $carmodels[$index],
                'shift' => $shifts[$index],
                'serial_no' => $serialnos[$index],
                'applicator_no' => $applicatornos[$index],
                'side_no' => $sidenos[$index],
                'machine_no' => $machine_nos[$index],
                'alasan' => $alasans[$index],
                'order' => $orders[$index],
                'stroke' => $strokes[$index],
                'pic' => $pics[$index],
                'remarks' => $remarkss[$index],
                'part_qty' => $part_qtys[$index],
                'status' => 0,
                'wear_and_tear_status' => $wear_and_tear_statuss[$index],
                'part_req_number' => $part_req_number,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        // dd($partRequests);

        DB::beginTransaction();
        try {
            foreach ($partRequests as $partreq) {
                $cek = $this->_PartRequestRepository->insertGetId(DataHelper::_normalizeParams($partreq, true));
                $listofpartreq = [
                    "part_req_id" => $cek,
                    'created_at' => now(),
                ];
                $this->_ListOfPartRequestRepository->insert(DataHelper::_normalizeParams($listofpartreq, true));
                $this->_logHelper->store($this->module, $partreq['part_req_number'], 'create');
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving part requests: ' . $e->getMessage());
            return redirect('partrequest/cd')->with('error', 'Gagal menyimpan part request: ' . $e->getMessage());
        }

        $targetNumbers = [
            '6287836410547',
            '6287824003437',
            '6287824003436',
            '6285351891534'
        ];

        $message = 'Pemberitahuan! Ada Part Request masuk dengan nomor ' . $part_req_number . ', mohon untuk segera periksa. Terimakasih. Akses disini : https://inventory.suaisystem.com';
        $token = 'xT-cKdExR44PbctH-8LY';

        try {
            $target = implode(',', $targetNumbers);
            $whatsappResponse = Http::timeout(5)->get('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'token' => $token,
            ]);

            if ($whatsappResponse->failed()) {
                Log::error('Gagal mengirim pesan WA Fonnte (cdstore): ' . $whatsappResponse->body());
            }
        } catch (\Throwable $e) {
            Log::error('Error / Timeout mengirim pesan WA Fonnte (cdstore): ' . $e->getMessage());
        }

        return redirect('partrequest/cd')->with('message', 'PartRequest berhasil ditambahkan');
    }
    public function allstore(Request $request)
    {
        $request->validate([
            'file_upload' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file_upload');
        $dataArray = [];

        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            $header = fgetcsv($handle); // Read header

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($header)) {
                    $record = array_combine($header, $row);

                    // Convert date fields to proper format
                    if (!empty($record['created_at'])) {
                        $parsedCreated = $this->_parseDate($record['created_at']);
                        if ($parsedCreated) {
                            $record['created_at'] = $parsedCreated;
                        }
                    }
                    if (!empty($record['updated_at'])) {
                        $parsedUpdated = $this->_parseDate($record['updated_at']);
                        if ($parsedUpdated) {
                            $record['updated_at'] = $parsedUpdated;
                        }
                    }

                    $dataArray[] = $record;
                }
            }

            fclose($handle);
        }

        // Optional: If you're using a repository function
        $inserted = $this->_PartRequestRepository->insertGetIDs($dataArray);

        for ($i = 0; $i < count($inserted); $i++) {
            $listofpartreq = [
                "part_req_id" => $inserted[$i],
            ];
            $params = DataHelper::_normalizeParams($listofpartreq, true);
            if (!empty($dataArray[$i]['created_at'])) {
                $params['created_at'] = $dataArray[$i]['created_at'];
            }
            if (!empty($dataArray[$i]['updated_at'])) {
                $params['updated_at'] = $dataArray[$i]['updated_at'];
            }
            $cek = $this->_ListOfPartRequestRepository->insert($params);
        }

        // dd($cek);

        return back()->with('success', count($dataArray) . ' records uploaded.');
    }
    /**
     * Show the cdecified resource.
     * @param int $id
     * @return Response
     */
    public function cdshow($id)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::cdshow');
    }

    /**
     * Show the form for editing the cdecified resource.
     * @param int $id
     * @return Response
     */
    public function cdedit($id)
    {

        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::cdedit');
    }

    /**
     * Update the cdecified resource in storage.
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
            return redirect('partrequest/cd')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $this->_PartRequestRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
            $this->_logHelper->store($this->module, $request->part_req_number, 'update');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('partrequest')->with('error', 'Gagal mengubah PartRequest: ' . $e->getMessage());
        }

        return redirect('partrequest')->with('message', 'PartRequest berhasil diubah');
    }
    public function cdupdate(Request $request, $id)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        $validator = Validator::make($request->all(), $this->_validationRules($id));

        if ($validator->fails()) {
            return redirect('partrequest/cd')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $this->_PartRequestRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
            $this->_logHelper->store($this->module, $request->part_req_number, 'update');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('partrequest/cd')->with('error', 'Gagal mengubah PartRequest: ' . $e->getMessage());
        }

        return redirect('partrequest/cd')->with('message', 'PartRequest berhasil diubah');
    }

    /**
     * Remove the cdecified resource from storage.
     * @param int $id
     * @return Response
     */
    public function cddestroy($id)
    {
        // Fetch directly from part_request WITHOUT JOIN so it never fails
        // due to a missing/orphaned part_id foreign key
        $detail = DB::table('part_request')->where('part_req_id', $id)->first();

        if (!$detail) {
            return redirect('partrequest/cd');
        }

        DB::beginTransaction();
        try {
            $part_qty = (int) $detail->part_qty;
            $part_id  = $detail->part_id;

            // Update qty_out on the linked part (only if the part still exists)
            $part = $this->_partRepository->getById($part_id);
            if ($part && is_object($part)) {
                $this->_partRepository->update(
                    ['qty_out' => max(0, $part->qty_out - $part_qty)],
                    $part_id
                );
            }

            $this->_PartRequestRepository->delete($id);
            $this->_logHelper->store($this->module, $detail->part_req_number, 'delete');

            DB::commit();
            return redirect('partrequest/cd')->with('message', 'PartRequest berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in cddestroy: ' . $e->getMessage());
            return redirect('partrequest/cd')->with('error', 'Gagal menghapus PartRequest: ' . $e->getMessage());
        }
    }

    /**
     * Get data the cdecified resource in storage.
     * @param int $id
     * @return Response
     */
    public function cdgetdata($id)
    {

        $response = array('status' => 0, 'result' => array());
        $getDetail = $this->_PartRequestRepository->getById($id);

        if ($getDetail) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
        }

        return $response;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function afindex(Request $request)
    {

        $params = [
            'part_category_id' => 3
        ];

        // Tangkap input tanggal dari request
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Panggil repository dengan filter tanggal jika ada
        $partrequests = $this->_PartRequestRepository->getAllByParams($params, $startDate, $endDate);
        $parts = $this->_partRepository->getAllByParams($params);
        $carlines = $this->_CarlineRepository->getAll();
        $machines = $this->_MachineRepository->getAll();
        $carlinecategories = $this->_CarlineCategoryRepository->getAll();
        $carnames = $this->_carnameRepository->getAll();

        return view('partrequest::af', compact('partrequests', 'parts', 'carlines', 'carlinecategories', 'machines', 'carnames'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function afcreate()
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::afcreate');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function afstore(Request $request)
    {

        date_default_timezone_set('Asia/Jakarta');
        $userGroupId = Auth::user()->group_id;
        $part = $this->_partRepository->getById($request->part_id);
        // $last = $this->_PartRequestRepository->getLast();

        $file_images = [];
        $file_paths = [];
        $image_part_displays = $request->input('image_part_display');

        if ($request->hasFile('image_part')) {
            foreach ($request->file('image_part') as $index => $file) {
                if ($file->isValid()) {
                    $fileName = DataHelper::getFileName($file);
                    $filePath = DataHelper::getFilePath(false, true);
                    $file->storeAs($filePath, $fileName, 'public');
                    $file_images[$index] = $fileName;
                    $file_paths[$index] = $filePath;
                } else {
                    // Jika file tidak valid, gunakan nama dari image_part_display
                    $fileName = DataHelper::getFileName($image_part_displays[$index]);
                    $filePath = DataHelper::getFilePath(false, true);
                    $file_images[$index] = $fileName;
                    $file_paths[$index] = $filePath;
                }
            }
        } else {
            // Jika tidak ada file yang diunggah, gunakan nama dari image_part_display
            foreach ($image_part_displays as $index => $display) {
                $file_images[$index] = $display;
                $file_paths[$index] = DataHelper::getFilePath(false, true);
            }
        }

        $currentMonth = strtoupper(substr(date("F"), 0, 3));
        $currentYear = date('Y');

        $count = $this->_PartRequestRepository->count();

        $padded_part_req_id = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $part_req_number = "$padded_part_req_id/TO/AF/$currentMonth/$currentYear";

        $part_ids = $request->input('part_id');
        $carnames = $request->input('carname');
        $carmodels = $request->input('car_model');
        $shifts = $request->input('shift');
        $serialnos = $request->input('serial_no');
        $applicatornos = $request->input('applicator_no');
        $sidenos = $request->input('side_no');
        $machine_nos = $request->input('machine_no');
        $alasans = $request->input('alasan');
        $orders = $request->input('order');
        $strokes = $request->input('stroke');
        $pics = $request->input('pic');
        $remarkss = $request->input('remarks');
        $part_qtys = $request->input('part_qty');
        $wear_and_tear_statuss = $request->input('wear_and_tear_status');

        $partRequests = [];
        date_default_timezone_set('Asia/Jakarta');
        foreach ($part_ids as $index => $part_id) {
            $partRequests_initial[] = [
                'part_req_pic_filename' => isset($file_images[$index]) ? $file_images[$index] : (isset($image_part_displays[$index]) ? $image_part_displays[$index] : null),
                'part_req_pic_path' => $file_paths[$index] ?? DataHelper::getFilePath(false, true),
                'part_id' => $part_ids[$index],
                'carline' => $carnames[$index],
                'car_model' => $carmodels[$index],
                'shift' => $shifts[$index],
                'serial_no' => $serialnos[$index],
                'applicator_no' => $applicatornos[$index],
                'side_no' => $sidenos[$index],
                'machine_no' => $machine_nos[$index],
                'alasan' => $alasans[$index],
                'order' => $orders[$index],
                'stroke' => $strokes[$index],
                'pic' => $pics[$index],
                'remarks' => $remarkss[$index],
                'part_qty' => $part_qtys[$index],
                'status' => 0,
                'wear_and_tear_status' => $wear_and_tear_statuss[$index],
                'part_req_number' => $part_req_number,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $partImage = $partRequests_initial[0]['part_req_pic_filename'];

        foreach ($part_ids as $index => $part_id) {
            $partRequests[] = [
                'part_req_pic_filename' => $partImage,
                'part_req_pic_path' => $file_paths[$index] ?? DataHelper::getFilePath(false, true),
                'part_id' => $part_ids[$index],
                'carline' => $carnames[$index],
                'car_model' => $carmodels[$index],
                'shift' => $shifts[$index],
                'serial_no' => $serialnos[$index],
                'applicator_no' => $applicatornos[$index],
                'side_no' => $sidenos[$index],
                'machine_no' => $machine_nos[$index],
                'alasan' => $alasans[$index],
                'order' => $orders[$index],
                'stroke' => $strokes[$index],
                'pic' => $pics[$index],
                'remarks' => $remarkss[$index],
                'part_qty' => $part_qtys[$index],
                'status' => 0,
                'wear_and_tear_status' => $wear_and_tear_statuss[$index],
                'part_req_number' => $part_req_number,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        // dd($partRequests);

        foreach ($partRequests as $partreq) {
            DB::beginTransaction();
            try {
                $cek = $this->_PartRequestRepository->insertGetId(DataHelper::_normalizeParams($partreq, true));
                $listofpartreq = [
                    "part_req_id" => $cek,
                    'created_at' => now(),
                ];
                $this->_ListOfPartRequestRepository->insert(DataHelper::_normalizeParams($listofpartreq, true));
                $this->_logHelper->store($this->module, $partreq['part_req_number'], 'create');
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                // Handle exception
            }
        }

        $targetNumbers = [
            '+6281911511380',
            '+6285215337568',
            '+6287785121808',
            '+6285965970004'
        ];

        $message = 'Pemberitahuan! Ada Part Request masuk dengan nomor ' . $part_req_number . ', mohon untuk segera periksa. Terimakasih. Akses disini : https://inventory.suaisystem.com';
        $token = 'xT-cKdExR44PbctH-8LY';

        try {
            $target = implode(',', $targetNumbers);
            $whatsappResponse = Http::timeout(5)->get('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'token' => $token,
            ]);

            if ($whatsappResponse->failed()) {
                Log::error('Gagal mengirim pesan WA Fonnte (afstore): ' . $whatsappResponse->body());
            }
        } catch (\Throwable $e) {
            Log::error('Error / Timeout mengirim pesan WA Fonnte (afstore): ' . $e->getMessage());
        }

        return redirect('partrequest/af')->with('message', 'PartRequest berhasil ditambahkan');
    }


    /**
     * Show the afecified resource.
     * @param int $id
     * @return Response
     */
    public function afshow($id)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::afshow');
    }

    /**
     * Show the form for editing the afecified resource.
     * @param int $id
     * @return Response
     */
    public function afedit($id)
    {

        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::afedit');
    }

    /**
     * Update the afecified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function afupdate(Request $request, $id)
    {


        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        $validator = Validator::make($request->all(), $this->_validationRules($id));

        if ($validator->fails()) {
            return redirect('partrequest/af')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $this->_PartRequestRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
        $this->_logHelper->store($this->module, $request->part_req_number, 'update');

        DB::commit();

        return redirect('partrequest/af')->with('message', 'PartRequest berhasil diubah');
    }

    /**
     * Remove the afecified resource from storage.
     * @param int $id
     * @return Response
     */
    public function afdestroy($id)
    {
         // if (Gate::denies(__FUNCTION__, $this->module)) {
        //     return redirect('unauthorize');
        // }

        $detail = $this->_PartRequestRepository->getById($id);

        if (!$detail) {
            return redirect('partrequest/af');
        }

        DB::beginTransaction();
        try {
            // Ambil part_qty dari part request yang akan dihapus
            $part_qty = $detail->part_qty;
            // dd($part_qty);
            $part_id = $detail->part_id;
            // dd($part_id);

            
            
            // Update qty_out pada part
            $part = $this->_partRepository->getById($part_id);
            // dd($part);
            // if ($part) {
                //     $part->qty_out = $part->qty_out - $part_qty;
                //     $this->_partRepository->update($part_id, $part.toArray());
                // }
                
                $part_qty_out = $part->qty_out - $part_qty;

                $param = [
                    'qty_out' =>$part_qty_out
                ];

                    $cek = $this->_partRepository->update($param, $part_id);
                    // dd($cek);

            // Hapus part request
            $this->_PartRequestRepository->delete($id);
            $this->_logHelper->store($this->module, $detail->part_req_number, 'delete');

            DB::commit();
            return redirect('partrequest/af')->with('message', 'PartRequest berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in afdestroy: ' . $e->getMessage());
            return redirect('partrequest/af')->with('error', 'Gagal menghapus PartRequest: ' . $e->getMessage());
        }
    }

    /**
     * Get data the afecified resource in storage.
     * @param int $id
     * @return Response
     */
    public function afgetdata($id)
    {

        $response = array('status' => 0, 'result' => array());
        $getDetail = $this->_PartRequestRepository->getById($id);

        if ($getDetail) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
        }

        return $response;
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function cfindex(Request $request)
    {
        $params = [
            'part_category_id' => 4
        ];
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Panggil repository dengan filter tanggal jika ada
        $partrequests = $this->_PartRequestRepository->getAllByParams($params, $startDate, $endDate);
        $parts = $this->_partRepository->getAllByParams($params);
        $carlines = $this->_CarlineRepository->getAll();
        // dd($carlines);
        $machines = $this->_MachineRepository->getAll();
        $carlinecategories = $this->_CarlineCategoryRepository->getAll();
        $carnames = $this->_carnameRepository->getAll();

        return view('partrequest::cf', compact('partrequests', 'parts', 'carlines', 'carlinecategories', 'machines', 'carnames'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function cfcreate()
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::cfcreate');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function cfstore(Request $request)
    {

        date_default_timezone_set('Asia/Jakarta');
        $userGroupId = Auth::user()->group_id;
        $part = $this->_partRepository->getById($request->part_id);
        // $last = $this->_PartRequestRepository->getLast();

        $file_images = [];
        $file_paths = [];
        $image_part_displays = $request->input('image_part_display');

        if ($request->hasFile('image_part')) {
            foreach ($request->file('image_part') as $index => $file) {
                if ($file->isValid()) {
                    $fileName = DataHelper::getFileName($file);
                    $filePath = DataHelper::getFilePath(false, true);
                    $file->storeAs($filePath, $fileName, 'public');
                    $file_images[$index] = $fileName;
                    $file_paths[$index] = $filePath;
                } else {
                    // Jika file tidak valid, gunakan nama dari image_part_display
                    $fileName = DataHelper::getFileName($image_part_displays[$index]);
                    $filePath = DataHelper::getFilePath(false, true);
                    $file_images[$index] = $fileName;
                    $file_paths[$index] = $filePath;
                }
            }
        } else {
            // Jika tidak ada file yang diunggah, gunakan nama dari image_part_display
            foreach ($image_part_displays as $index => $display) {
                $file_images[$index] = $display;
                $file_paths[$index] = DataHelper::getFilePath(false, true);
            }
        }

        $currentMonth = strtoupper(substr(date("F"), 0, 3));
        $currentYear = date('Y');

        $count = $this->_PartRequestRepository->count();

        $padded_part_req_id = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $part_req_number = "$padded_part_req_id/TO/CF/$currentMonth/$currentYear";

        $part_ids = $request->input('part_id');
        $carnames = $request->input('carname');
        $carmodels = $request->input('car_model');
        $shifts = $request->input('shift');
        $serialnos = $request->input('serial_no');
        $applicatornos = $request->input('applicator_no');
        $sidenos = $request->input('side_no');
        $machine_nos = $request->input('machine_no');
        $alasans = $request->input('alasan');
        $orders = $request->input('order');
        $strokes = $request->input('stroke');
        $pics = $request->input('pic');
        $remarkss = $request->input('remarks');
        $part_qtys = $request->input('part_qty');
        $wear_and_tear_statuss = $request->input('wear_and_tear_status');

        $partRequests = [];
        date_default_timezone_set('Asia/Jakarta');
        foreach ($part_ids as $index => $part_id) {
            $partRequests_initial[] = [
                'part_req_pic_filename' => isset($file_images[$index]) ? $file_images[$index] : (isset($image_part_displays[$index]) ? $image_part_displays[$index] : null),
                'part_req_pic_path' => $file_paths[$index] ?? DataHelper::getFilePath(false, true),
                'part_id' => $part_ids[$index],
                'carline' => $carnames[$index],
                'car_model' => $carmodels[$index],
                'shift' => $shifts[$index],
                'serial_no' => $serialnos[$index],
                'applicator_no' => $applicatornos[$index],
                'side_no' => $sidenos[$index],
                'machine_no' => $machine_nos[$index],
                'alasan' => $alasans[$index],
                'order' => $orders[$index],
                'stroke' => $strokes[$index],
                'pic' => $pics[$index],
                'remarks' => $remarkss[$index],
                'part_qty' => $part_qtys[$index],
                'status' => 0,
                'wear_and_tear_status' => $wear_and_tear_statuss[$index],
                'part_req_number' => $part_req_number,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $partImage = $partRequests_initial[0]['part_req_pic_filename'];

        foreach ($part_ids as $index => $part_id) {
            $partRequests[] = [
                'part_req_pic_filename' => $partImage,
                'part_req_pic_path' => $file_paths[$index] ?? DataHelper::getFilePath(false, true),
                'part_id' => $part_ids[$index],
                'carline' => $carnames[$index],
                'car_model' => $carmodels[$index],
                'shift' => $shifts[$index],
                'serial_no' => $serialnos[$index],
                'applicator_no' => $applicatornos[$index],
                'side_no' => $sidenos[$index],
                'machine_no' => $machine_nos[$index],
                'alasan' => $alasans[$index],
                'order' => $orders[$index],
                'stroke' => $strokes[$index],
                'pic' => $pics[$index],
                'remarks' => $remarkss[$index],
                'part_qty' => $part_qtys[$index],
                'status' => 0,
                'wear_and_tear_status' => $wear_and_tear_statuss[$index],
                'part_req_number' => $part_req_number,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        // dd($partRequests);

        foreach ($partRequests as $partreq) {
            DB::beginTransaction();
            try {
                $cek = $this->_PartRequestRepository->insertGetId(DataHelper::_normalizeParams($partreq, true));
                $listofpartreq = [
                    "part_req_id" => $cek,
                    'created_at' => now(),
                ];
                $this->_ListOfPartRequestRepository->insert(DataHelper::_normalizeParams($listofpartreq, true));
                $this->_logHelper->store($this->module, $partreq['part_req_number'], 'create');
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                // Handle exception
            }
        }

        $targetNumbers = [
            '+6281911511380',
            '+6285215337568',
            '+6287785121808',
            '+6285965970004'
        ];

        $message = 'Pemberitahuan! Ada Part Request masuk dengan nomor ' . $part_req_number . ', mohon untuk segera periksa. Terimakasih. Akses disini : https://inventory.suaisystem.com';
        $token = 'xT-cKdExR44PbctH-8LY';

        try {
            $target = implode(',', $targetNumbers);
            $whatsappResponse = Http::timeout(5)->get('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'token' => $token,
            ]);

            if ($whatsappResponse->failed()) {
                Log::error('Gagal mengirim pesan WA Fonnte (cfstore): ' . $whatsappResponse->body());
            }
        } catch (\Throwable $e) {
            Log::error('Error / Timeout mengirim pesan WA Fonnte (cfstore): ' . $e->getMessage());
        }

        return redirect('partrequest/cf')->with('message', 'PartRequest berhasil ditambahkan');
    }

    /**
     * Show the cfecified resource.
     * @param int $id
     * @return Response
     */
    public function cfshow($id)
    {
        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::cfshow');
    }

    /**
     * Show the form for editing the cfecified resource.
     * @param int $id
     * @return Response
     */
    public function cfedit($id)
    {

        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        return view('partrequest::cfedit');
    }

    /**
     * Update the cfecified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function cfupdate(Request $request, $id)
    {


        // Authorize
        if (Gate::denies(__FUNCTION__, $this->module)) {
            return redirect('unauthorize');
        }

        $validator = Validator::make($request->all(), $this->_validationRules($id));

        if ($validator->fails()) {
            return redirect('partrequest/cf')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $this->_PartRequestRepository->update(DataHelper::_normalizeParams($request->all(), false, true), $id);
        $this->_logHelper->store($this->module, $request->part_req_number, 'update');

        DB::commit();

        return redirect('partrequest/cf')->with('message', 'PartRequest berhasil diubah');
    }

    /**
     * Remove the cfecified resource from storage.
     * @param int $id
     * @return Response
     */
    public function cfdestroy($id)
    {
         // if (Gate::denies(__FUNCTION__, $this->module)) {
        //     return redirect('unauthorize');
        // }

        $detail = $this->_PartRequestRepository->getById($id);

        if (!$detail) {
            return redirect('partrequest/cf');
        }

        DB::beginTransaction();
        try {
            // Ambil part_qty dari part request yang akan dihapus
            $part_qty = $detail->part_qty;
            // dd($part_qty);
            $part_id = $detail->part_id;
            // dd($part_id);

            
            
            // Update qty_out pada part
            $part = $this->_partRepository->getById($part_id);
            // dd($part);
            // if ($part) {
                //     $part->qty_out = $part->qty_out - $part_qty;
                //     $this->_partRepository->update($part_id, $part.toArray());
                // }
                
                $part_qty_out = $part->qty_out - $part_qty;

                $param = [
                    'qty_out' =>$part_qty_out
                ];

                    $cek = $this->_partRepository->update($param, $part_id);
                    // dd($cek);

            // Hapus part request
            $this->_PartRequestRepository->delete($id);
            $this->_logHelper->store($this->module, $detail->part_req_number, 'delete');

            DB::commit();
            return redirect('partrequest/cf')->with('message', 'PartRequest berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in cfdestroy: ' . $e->getMessage());
            return redirect('partrequest/cf')->with('error', 'Gagal menghapus PartRequest: ' . $e->getMessage());
        }
    }

    /**
     * Get data the cfecified resource in storage.
     * @param int $id
     * @return Response
     */
    public function cfgetdata($id)
    {
        $response  = ['status' => 0, 'result' => []];
        $getDetail = $this->_PartRequestRepository->getById($id);

        // getById() returns a string on error — guard against it
        if ($getDetail && is_object($getDetail)) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
        }

        return response()->json($response);
    }

    public function getdata($id)
    {
        $response  = ['status' => 0, 'result' => []];
        $getDetail = $this->_PartRequestRepository->getById($id);

        // getById() returns a string on error — guard against it
        if ($getDetail && is_object($getDetail)) {
            $response['status'] = 1;
            $response['result'] = $getDetail;
        }

        return response()->json($response);
    }
    public function gambar($id)
    {

        $partrequests = $this->_PartRequestRepository->getById($id);
        // $parts = $this->_partRepository->getAll();
        // $users = $this->_userRepository->getAll();
        // $carlines = $this->_CarlineRepository->getAll();
        // $machines = $this->_MachineRepository->getAll();
        // $carlinecategories = $this->_CarlineCategoryRepository->getAll();
        // $carnames = $this->_carnameRepository->getAll();

        // dd($users);

        return view('partrequest::gambar', compact('partrequests'));
    }



    private function _validationRules($id = '')
    {
        if ($id == '') {
            return [
                'part_id' => 'required',
            ];
        } else {
            return [
                'part_id' => 'required',
            ];
        }
    }

    /**
     * Parse date string into Y-m-d H:i:s supporting multiple formats
     * (Indonesian d/m/Y H:i, standard Y-m-d H:i:s, Excel m/d/Y H:i, etc.)
     *
     * @param string $value
     * @return string|null
     */
    private function _parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);

        // 1. Check ISO / Year-first formats (unambiguous)
        $yearFirstFormats = [
            '!Y-m-d H:i:s',
            '!Y-m-d H:i',
            '!Y-m-d',
            '!Y/m/d H:i:s',
            '!Y/m/d H:i',
            '!Y/m/d',
        ];
        foreach ($yearFirstFormats as $fmt) {
            try {
                $cleanFmt = ltrim($fmt, '!');
                $d = Carbon::createFromFormat($fmt, $value);
                if ($d && $d->format($cleanFmt) === $value) {
                    return $d->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // continue to next format
            }
        }

        // 2. Day/Month/Year or Month/Day/Year formats (e.g. 05/10/2024 or 25/08/2024)
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $value, $m)) {
            $p1 = intval($m[1]);
            $p2 = intval($m[2]);
            $year = intval($m[3]);
            $hour = isset($m[4]) ? intval($m[4]) : 0;
            $min = isset($m[5]) ? intval($m[5]) : 0;
            $sec = isset($m[6]) ? intval($m[6]) : 0;

            if ($p1 > 12) {
                // Day must be p1, Month must be p2 (e.g., 25/08/2024)
                $day = $p1;
                $month = $p2;
            } elseif ($p2 > 12) {
                // Month is p1, Day is p2 (e.g., 08/25/2024)
                $month = $p1;
                $day = $p2;
            } else {
                // Ambiguous (both <= 12, e.g. 05/10/2024). Default to Indonesian convention: Day/Month/Year
                $day = $p1;
                $month = $p2;
            }

            try {
                return Carbon::create($year, $month, $day, $hour, $min, $sec)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // fallback below
            }
        }

        // 3. Fallback to Carbon::parse
        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
