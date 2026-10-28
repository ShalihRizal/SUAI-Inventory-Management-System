<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

use Modules\Notification\Repositories\NotificationRepository;
use Modules\Users\Repositories\UsersRepository;
use Modules\Part\Repositories\PartRepository;
use App\Helpers\DataHelper;
use App\Helpers\LogHelper;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DB;
use Validator;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->_partRepository = new PartRepository;
        $this->_notificationRepository = new NotificationRepository;
        $this->_userRepository = new UsersRepository;
        $this->_logHelper = new LogHelper;
        $this->module = "Notification";
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

        $params = [
            'part_request.status' => 0
        ];

        $notifications = $this->_notificationRepository->getAllByParams($params);
        $users = $this->_userRepository->getAll();

        // dd($notifications);

        return view('notification::index', compact('notifications', 'users'));
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

        return view('notification::create');
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
            return redirect('notification')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        $this->_notificationRepository->insert(DataHelper::_normalizeParams($request->all(), true));
        $this->_logHelper->store($this->module, $request->notification_id, 'create');
        DB::commit();



        return redirect('notification')->with('message', 'notification berhasil ditambahkan');
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

        return view('notification::show');
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

        return view('notification::edit');
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

        $detail = $this->_notificationRepository->getById($id);
        $part = $this->_partRepository->getById($detail->part_id);
        $part_req_number = $detail->part_req_number;
        // dd($part, $detail);
        if ($part) {
            $updateStatus = [
                'status' => 1,
                'wear_and_tear_status' => "On Progress",
                'approved_by' => $request->approved_by
            ];
            // dd($updateStatus);
            DB::beginTransaction();
            // $this->_partRepository->update(DataHelper::_normalizeParams($updatePart, false, true), $detail->part_id);
            $this->_notificationRepository->update(DataHelper::_normalizeParams($updateStatus, false, true), $id);
            $this->_logHelper->store($this->module, $request->notification_id, 'update');
            // dd($request, $check);
            DB::commit();
            // dd($check);

            $targetNumbers = [
                '6287836410547',
                '6287824003437',
                '6287824003436',
                '6285351891534'
            ];

            $message = 'Pemberitahuan! Part Request dengan nomor ' . $part_req_number . ', telah di approve, mohon untuk segera periksa. Terimakasih. Akses disini : https://inventory.suaisystem.com';
            $token = 'xT-cKdExR44PbctH-8LY';

            try {
                $target = implode(',', $targetNumbers);
                $whatsappResponse = Http::timeout(5)->get('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                    'token' => $token,
                ]);

                if ($whatsappResponse->failed()) {
                    Log::error('Gagal mengirim pesan WA Fonnte: ' . $whatsappResponse->body());
                }
            } catch (\Throwable $e) {
                Log::error('Error / Timeout mengirim pesan WA Fonnte: ' . $e->getMessage());
            }
        }


        return redirect('notification')->with('message', 'notification berhasil diubah');
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
        $detail = $this->_notificationRepository->getById($id);

        if (!$detail) {
            return redirect('notification');
        }

        DB::beginTransaction();

        $this->_notificationRepository->delete($id);
        $this->_logHelper->store($this->module, $detail->notification_id, 'delete');

        DB::commit();

        return redirect('notification')->with('message', 'notification berhasil dihapus');
    }

    /**
     * Get data the specified resource in storage.
     * @param int $id
     * @return Response
     */
    public function getdata($id)
    {

        $response = array('status' => 0, 'result' => array());
        $getDetail = $this->_notificationRepository->getById($id);

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
                'notification_id' => 'required',
            ];
        } else {
            return [
                'notification_id' => 'required',
            ];
        }
    }
}
