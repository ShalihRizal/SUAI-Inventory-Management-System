<?php

namespace Modules\SysLog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

use Modules\SysLog\Repositories\SysLogRepository;
use Modules\SysModule\Repositories\SysModuleRepository;
use App\Helpers\DataHelper;
use App\Helpers\LogHelper;
use DB;

class SysLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->_sysLogRepository   = new SysLogRepository;
        $this->_logHelper           = new LogHelper;
        $this->module               = "SysLog";
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

        $logs = $this->_sysLogRepository->paginate(25);

        return view('syslog::index', compact('logs'));
    }
}
