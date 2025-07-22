<?php

namespace App\GeneralModule\Controllers;

use App\GeneralModule\Models\Setting;
use App\GeneralModule\Requests\Auth\AuthUpdatePasswordRequest;
use App\GeneralModule\Requests\UserRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\GeneralModule\Requests\Auth\AuthRequest;
use App\GeneralModule\Services\AuthService;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseController
{
    protected AuthService $service;
    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }
    public function store(AuthRequest $request)
    {
        return response()->json($this->service->login($request->validated()));
    }
    public function profile()
    {
        return response()->json($this->service->profileAuth());
    }
    public function profile_update(UserRequest $request)
    {
        $input = $request->validated();
        $file = $request->file('avatar');
        return response()->json($this->service->profileAuthUpdate($input, $file));
    }
    public function refresh_token()
    {
        return response()->json($this->service->refresh_token());
    }
    public function logout()
    {
        return response()->json($this->service->logout());
    }
    public function store_device_token(Request $request)
    {
        $input = $request->validate([
            "device_token" => "required|string|max:255",
        ]);
        $userId = Auth::id();
        return response()->json($this->service->store_device_token($input["device_token"], $userId));
    }
    public function profile_update_password(AuthUpdatePasswordRequest $request)
    {
        $input = $request->validated();
        $userId = Auth::id();
        return response()->json($this->service->update_password($userId, $input));
    }
    public function set_imei(Request $request)
    {
        try {
            if (Auth::user()->device_id === null) {
                Auth::user()->update([
                    'device_id' => $request->input('imei')
                ]);
            }
            return response()->json(Auth::user());
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function setting(Request $request)
    {
        try {
            $detail = Setting::where('company_id', Auth::user()->company_id)->first();
            return response()->json($detail);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function schedule()
    {
        try {
            $userId = Auth::id();
            return response()->json($this->service->schedule($userId));
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function current_attendance()
    {
        try {
            $userId = Auth::id();
            return response()->json($this->service->attendanceToday($userId));
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function activity()
    {
        try {
            return response()->json($this->service->activity());
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function profile_update_avatar(Request $request)
    {
        try {
            $file = $request->file('file');
            return response()->json($this->service->updateProfile($file));
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function summary_absen()
    {
        try {
            return response()->json($this->service->summary_absen());
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function set_token(Request $request)
    {
        $token = $request->only('token');
        try {
            return response()->json($this->service->setup_token($token['token']));
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
