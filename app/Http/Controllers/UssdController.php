<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Services\UssdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UssdController extends Controller
{
    public function __construct(protected UssdService $ussdService) {}

    /**
     * Handle incoming USSD callback from Africa's Talking.
     */
    public function callback(Request $request): Response
    {
        $response = $this->ussdService->handleRequest($request->all());

        return response($response, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Handle incoming SMS delivery callback (optional — for delivery reports).
     */
    public function smsCallback(Request $request): Response
    {
        logger()->info('SMS Delivery Report', $request->all());

        return response('OK', 200);
    }

    /**
     * Handle airtime validation callback from AT.
     * AT asks "should we send this airtime?" — we say yes (Validated).
     */
    public function airtimeValidation(Request $request): JsonResponse
    {
        logger()->info('Airtime Validation', $request->all());

        // Always validate — rewards are pre-approved by the admin verify action
        return response()->json(['status' => 'Validated']);
    }

    /**
     * Handle airtime status notification from AT.
     * AT tells us whether the airtime was delivered or failed.
     */
    public function airtimeStatus(Request $request): JsonResponse
    {
        logger()->info('Airtime Status', $request->all());

        $requestId = $request->input('requestId');
        $status = $request->input('status'); // "Success" or "Failed"

        // Update the reward record with final delivery status
        if ($requestId) {
            Reward::where('transaction_id', $requestId)
                ->update(['status' => $status === 'Success' ? 'sent' : 'failed']);
        }

        return response()->json(['status' => 'Received']);
    }
}
