<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationRequest;
use App\Mail\RegistrationSubmitted;
use App\Services\Registration\RegistrationCard;
use App\Support\ClientConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class RegistrationController extends Controller
{
    public function show(?string $branch = null): View
    {
        return view('registration.form', [
            'clientConfig' => ClientConfig::make($branch),
            'assetBase' => rtrim(asset(''), '/').'/',
        ]);
    }

    public function token(Request $request): JsonResponse
    {
        return response()->json(['token' => $request->session()->token()]);
    }

    public function store(RegistrationRequest $request, RegistrationCard $card): JsonResponse
    {
        $branchName = config('registration.branches.'.$request->input('branch').'.name');

        // Honeypot: bots get a normal-looking answer and nothing is sent.
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true, 'branch' => $branchName]);
        }

        $data = $request->validated();
        $branch = config('registration.branches.'.$data['branch']);

        // Block the same candidate re-sending the same registration in a short window.
        $lockKey = 'registration:'.sha1($data['idNumber'].'|'.$data['branch'].'|'.$data['major']);
        if (! Cache::add($lockKey, true, now()->addMinutes(config('registration.duplicate_window_minutes')))) {
            return response()->json([
                'message' => 'טופס הרישום כבר נשלח. אם צריך לעדכן פרטים, אפשר לפנות לשלוחה.',
            ], 409);
        }

        try {
            $submittedAt = now();
            Mail::to($branch['emails'])->send(new RegistrationSubmitted($data, $card->pdf($data, $submittedAt), $submittedAt));
        } catch (Throwable $e) {
            Cache::forget($lockKey);
            Log::error('Registration email failed', ['branch' => $data['branch'], 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'אירעה תקלה בשליחת הטופס. הפרטים שמורים במכשיר, אפשר לנסות שוב בעוד כמה דקות.',
            ], 503);
        }

        return response()->json(['ok' => true, 'branch' => $branch['name']]);
    }
}
