<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\SendOtpRequest;
use Modules\Auth\Services\VerificationCodeService;
use Modules\Auth\Enums\VerificationActionType;

class VerifyOtpController extends Controller {

    public function __construct(
        private VerificationCodeService $verificationCodeService
    ) {}
        
    public function sendOtp( SendOtpRequest $request )
    {
        [$contactType, $contact] = $request->getContactType();
        $action = VerificationActionType::from($request->input('action'));

        $code = $this->verificationCodeService->generateCode($contact, $action, $contactType);

        return response()->json([
            'data' => $code,
            'message' => 'OTP sent successfully to ' . $contactType->value,
            'status' => true,
            'status_code' => 200,
        ], 200);
    }
}
