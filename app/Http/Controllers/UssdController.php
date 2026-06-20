<?php

namespace App\Http\Controllers;

use App\Services\UssdService;
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
        // Log delivery report for future use
        logger()->info('SMS Delivery Report', $request->all());

        return response('OK', 200);
    }
}
