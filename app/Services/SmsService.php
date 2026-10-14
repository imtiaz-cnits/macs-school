<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SmsLog;
use Carbon\Carbon;

class SmsService
{
    protected $username;
    protected $apiKey;
    protected $senderId;
    protected $testMode;
    protected $testRecipient;

    public function __construct()
    {
        $this->username = config('services.mim_sms.username');
        $this->apiKey = config('services.mim_sms.api_key');
        $this->senderId = config('services.mim_sms.sender_id');
        $this->testMode = config('services.mim_sms.test_mode', true);
        $this->testRecipient = config('services.mim_sms.test_recipient');
    }

    /**
     * Send single SMS using MIM SMS V2 API
     *
     * Provider Limitations Note:
     * The MiM SMS API (https://api.mimsms.com/api/V2/SMS) does not support a client-side
     * idempotency-key header or unique request identifier. A 200 HTTP response confirms
     * that the message was accepted by the gateway, but does not provide real-time handset DLR.
     * In the event of an ambiguous transport failure (e.g. HTTP timeout, socket reset),
     * it is impossible to verify programmatically whether the gateway processed the SMS without
     * an external delivery receipt callback. To prevent sending duplicate SMS to guardians,
     * ambiguous events are marked 'Ambiguous' and locked against automatic resending.
     * Exactly-once SMS delivery cannot be guaranteed under network partition without
     * provider-level idempotency support.
     *
     * @param string $mobileNumber
     * @param string $message
     * @param int|null $studentId
     * @param string|null $eventKey
     * @param string|null $eventType
     * @return bool
     */
    public function sendSms(
        string $mobileNumber,
        string $message,
        ?int $studentId = null,
        ?string $eventKey = null,
        ?string $eventType = null
    ): bool
    {
        // 1. Idempotency & Lifecycle Check if eventKey is specified
        $smsLog = null;
        if ($eventKey) {
            $existing = SmsLog::where('event_key', $eventKey)->first();
            if ($existing) {
                // Terminal state: successfully sent
                if ($existing->status === 'Sent') {
                    Log::info("MIM SMS skipped: event_key {$eventKey} was already successfully sent.");
                    return false;
                }

                // Ambiguous outcome: delivery acceptance is uncertain (e.g. timeout / connection drop).
                // Automatic duplicate send is blocked to protect against sending duplicate SMS to guardians.
                if ($existing->status === 'Ambiguous') {
                    Log::warning("MIM SMS skipped: event_key {$eventKey} is in Ambiguous status; automatic duplicate send blocked.");
                    return false;
                }

                // Currently in-flight by a concurrent process (within 60s cooldown)
                if ($existing->status === 'Attempted' && $existing->updated_at && $existing->updated_at->gt(Carbon::now('Asia/Dhaka')->subSeconds(60))) {
                    Log::info("MIM SMS skipped: event_key {$eventKey} is currently in-flight by another process.");
                    return false;
                }

                // Blocked by test mode whitelist
                if ($existing->status === 'Blocked (Test Mode)') {
                    Log::info("MIM SMS skipped: event_key {$eventKey} was blocked by test mode whitelist.");
                    return false;
                }

                // Definite failure (or expired in-flight reservation):
                // Safe to retry because there is reliable evidence that the earlier attempt was not accepted.
                // Concurrency safety: Atomically transition status to 'Attempted' with updated_at.
                $claimed = SmsLog::where('event_key', $eventKey)
                    ->where(function ($q) {
                        $q->where('status', 'Failed')
                          ->orWhere(function ($sub) {
                              $sub->where('status', 'Attempted')
                                  ->where('updated_at', '<=', Carbon::now('Asia/Dhaka')->subSeconds(60));
                          });
                    })
                    ->update([
                        'status'     => 'Attempted',
                        'updated_at' => Carbon::now('Asia/Dhaka'),
                    ]);

                if (!$claimed) {
                    Log::info("MIM SMS retry skipped: concurrent process claimed event_key {$eventKey}.");
                    return false;
                }

                $smsLog = $existing->fresh();
            }
        }

        // Normalize mobile number to Bangladeshi format (starts with 8801)
        $cleanNumber = preg_replace('/[^0-9]/', '', $mobileNumber);
        if (strlen($cleanNumber) == 11 && str_starts_with($cleanNumber, '01')) {
            $cleanNumber = '88' . $cleanNumber;
        }

        if (!preg_match('/^8801[0-9]{9}$/', $cleanNumber)) {
            Log::warning("MIM SMS blocked: recipient number {$mobileNumber} has an invalid format.");
            if ($smsLog) {
                $smsLog->update(['status' => 'Failed']);
            }
            return false;
        }

        if (empty($this->username) || empty($this->apiKey) || empty($this->senderId)) {
            Log::error('MIM SMS is not configured. Set MIM_SMS_USERNAME, MIM_SMS_API_KEY, and MIM_SMS_SENDER_ID.');
            if ($smsLog) {
                $smsLog->update(['status' => 'Failed']);
            }
            return false;
        }

        // 2. Atomic Database Reservation: prevent concurrent race conditions on first attempt
        if ($eventKey && !$smsLog) {
            try {
                $smsLog = SmsLog::create([
                    'student_id'    => $studentId,
                    'event_key'     => $eventKey,
                    'event_type'    => $eventType,
                    'mobile_number' => $mobileNumber,
                    'message'       => $message,
                    'status'        => 'Attempted',
                    'created_at'    => Carbon::now('Asia/Dhaka'),
                    'updated_at'    => Carbon::now('Asia/Dhaka'),
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException | \Illuminate\Database\QueryException $e) {
                Log::warning("MIM SMS atomic reservation skipped: duplicate event_key {$eventKey} collision detected.");
                return false;
            }
        }

        // Whitelist check: Only send to allowed numbers in test mode
        if ($this->testMode) {
            $allowedNumbers = array_filter(array_map(
                fn ($number) => preg_replace('/[^0-9]/', '', trim($number)),
                explode(',', (string) $this->testRecipient)
            ));
            $allowedNumbers = array_map(function ($number) {
                return strlen($number) === 11 && str_starts_with($number, '01')
                    ? '88' . $number
                    : $number;
            }, $allowedNumbers);

            if (!in_array($cleanNumber, $allowedNumbers)) {
                Log::info('MIM SMS blocked: recipient is not the configured test number.');

                if ($smsLog) {
                    $smsLog->update(['status' => 'Blocked (Test Mode)']);
                } else {
                    SmsLog::create([
                        'student_id'    => $studentId,
                        'event_key'     => $eventKey,
                        'event_type'    => $eventType,
                        'mobile_number' => $mobileNumber,
                        'message'       => $message,
                        'status'        => 'Blocked (Test Mode)',
                        'created_at'    => Carbon::now('Asia/Dhaka'),
                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                    ]);
                }

                return false;
            }
        }

        try {
            $url = 'https://api.mimsms.com/api/V2/SMS';

            $response = Http::timeout(5)->post($url, [
                'userName'        => $this->username,
                'apiKey'          => $this->apiKey,
                'mobileNumber'    => $cleanNumber,
                'senderName'      => $this->senderId,
                'transactionType' => 'T',
                'message'         => $message
            ]);

            $isSuccess = $response->successful();

            Log::info("MIM SMS Send status to {$cleanNumber}: " . ($isSuccess ? 'Success' : 'Failed') . " - Response: " . $response->body());

            // A 2xx response is a definite acceptance by the provider gateway
            // A 4xx/5xx response with a completed HTTP roundtrip is a definite rejection
            $finalStatus = $isSuccess ? 'Sent' : 'Failed';

            if ($smsLog) {
                $smsLog->update(['status' => $finalStatus]);
            } else {
                SmsLog::create([
                    'student_id'    => $studentId,
                    'event_key'     => $eventKey,
                    'event_type'    => $eventType,
                    'mobile_number' => $mobileNumber,
                    'message'       => $message,
                    'status'        => $finalStatus,
                    'created_at'    => Carbon::now('Asia/Dhaka'),
                    'updated_at'    => Carbon::now('Asia/Dhaka'),
                ]);
            }

            return $isSuccess;

        } catch (\Exception $e) {
            // Determine whether this was an ambiguous network/transport timeout without response
            // or an explicit client/server exception that contains a response
            $isAmbiguous = true;
            if ($e instanceof \Illuminate\Http\Client\RequestException && $e->hasResponse()) {
                $isAmbiguous = false;
            }

            $failureStatus = $isAmbiguous ? 'Ambiguous' : 'Failed';

            Log::error("MIM SMS sending error for {$mobileNumber} [{$failureStatus}]: " . $e->getMessage());

            if ($smsLog) {
                $smsLog->update(['status' => $failureStatus]);
            } else {
                SmsLog::create([
                    'student_id'    => $studentId,
                    'event_key'     => $eventKey,
                    'event_type'    => $eventType,
                    'mobile_number' => $mobileNumber,
                    'message'       => $message,
                    'status'        => $failureStatus,
                    'created_at'    => Carbon::now('Asia/Dhaka'),
                    'updated_at'    => Carbon::now('Asia/Dhaka'),
                ]);
            }

            return false;
        }
    }

    /**
     * Get real-time SMS balance from MiM SMS API
     *
     * @param bool $forceRefresh
     * @return array
     */
    public function getBalance(bool $forceRefresh = false): array
    {
        if (empty($this->username) || empty($this->apiKey)) {
            return [
                'status'    => 'unconfigured',
                'balance'   => null,
                'message'   => 'MiM SMS credentials are not configured.',
                'synced_at' => Carbon::now('Asia/Dhaka')->format('h:i A'),
            ];
        }

        $cacheKey = 'mim_sms_live_balance';
        if ($forceRefresh) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () {
            try {
                // 1. Try POST request (default recommended method)
                $response = Http::timeout(6)->post('https://api.mimsms.com/api/V2/BalanceCheck', [
                    'userName' => $this->username,
                    'apiKey'   => $this->apiKey,
                ]);

                $json = $response->successful() ? $response->json() : null;
                $balance = $json ? $this->extractBalanceFromResponse($json) : null;

                // 2. If POST didn't yield a balance, try GET request as fallback
                if ($balance === null) {
                    $getResponse = Http::timeout(6)->get('https://api.mimsms.com/api/V2/BalanceCheck', [
                        'userName' => $this->username,
                        'apiKey'   => $this->apiKey,
                    ]);
                    if ($getResponse->successful()) {
                        $getJson = $getResponse->json();
                        $getBalance = $this->extractBalanceFromResponse($getJson);
                        if ($getBalance !== null) {
                            $json = $getJson;
                            $balance = $getBalance;
                            $response = $getResponse;
                        } elseif (!$json && $getJson) {
                            $json = $getJson;
                            $response = $getResponse;
                        }
                    }
                }

                if ($balance !== null) {
                    $currency = $json['data']['currency'] ?? $json['currency'] ?? 'BDT';
                    $ratePerSms = (float) config('services.mim_sms.rate_per_sms', 0.33);
                    if ($ratePerSms <= 0) {
                        $ratePerSms = 0.33;
                    }
                    $remainingSms = (int) floor((float)$balance / $ratePerSms);

                    return [
                        'status'        => 'success',
                        'balance'       => $balance,
                        'currency'      => $currency,
                        'rate_per_sms'  => $ratePerSms,
                        'remaining_sms' => $remainingSms,
                        'raw'           => $json,
                        'synced_at'     => Carbon::now('Asia/Dhaka')->format('h:i A'),
                    ];
                }

                if ($json) {
                    $rawJsonStr = json_encode($json, JSON_UNESCAPED_UNICODE);
                    $msg = $json['message'] ?? $json['responseResult'] ?? $json['status'] ?? 'Unknown response';
                    if (in_array(strtolower($msg), ['success', 'ok', 'successful'])) {
                        $msg = 'Raw: ' . substr($rawJsonStr, 0, 100);
                    }

                    Log::warning("MiM SMS BalanceCheck returned non-balance response: " . $rawJsonStr);
                    return [
                        'status'    => 'error',
                        'balance'   => null,
                        'message'   => $msg,
                        'raw'       => $json,
                        'synced_at' => Carbon::now('Asia/Dhaka')->format('h:i A'),
                    ];
                }

                Log::warning("MiM SMS BalanceCheck HTTP error: " . $response->status() . " Body: " . $response->body());
                return [
                    'status'    => 'error',
                    'balance'   => null,
                    'message'   => 'Gateway returned HTTP ' . $response->status(),
                    'synced_at' => Carbon::now('Asia/Dhaka')->format('h:i A'),
                ];
            } catch (\Exception $e) {
                Log::error("MiM SMS BalanceCheck exception: " . $e->getMessage());
                return [
                    'status'    => 'error',
                    'balance'   => null,
                    'message'   => $e->getMessage(),
                    'synced_at' => Carbon::now('Asia/Dhaka')->format('h:i A'),
                ];
            }
        });
    }

    /**
     * Recursively extract balance from various provider response shapes.
     */
    protected function extractBalanceFromResponse($data): ?string
    {
        if (is_numeric($data)) {
            return (string)$data;
        }

        if (!is_array($data)) {
            if (is_string($data) && preg_match('/^\s*([0-9]+(?:\.[0-9]+)?)\s*$/', $data, $m)) {
                return $m[1];
            }
            return null;
        }

        // Direct candidate keys
        $candidateKeys = [
            'responseResult',
            'balance',
            'Balance',
            'currentBalance',
            'current_balance',
            'CurrentBalance',
            'sms_balance',
            'smsBalance',
            'amount',
            'Amount',
            'credit',
            'Credit',
            'credits',
            'remaining_sms',
            'remainingSms',
            'available_balance',
            'balance_amount',
        ];

        foreach ($candidateKeys as $key) {
            if (isset($data[$key])) {
                $val = $data[$key];
                if (is_numeric($val)) {
                    return (string)$val;
                }
                if (is_string($val) && preg_match('/[0-9]+(?:\.[0-9]+)?/', $val, $m)) {
                    return $m[0];
                }
            }
        }

        // If 'data' key exists, inspect inside
        if (isset($data['data'])) {
            $fromData = $this->extractBalanceFromResponse($data['data']);
            if ($fromData !== null) {
                return $fromData;
            }
        }

        // Search in items (e.g. data: [ { balance: ... } ])
        foreach ($data as $item) {
            if (is_array($item)) {
                $fromItem = $this->extractBalanceFromResponse($item);
                if ($fromItem !== null) {
                    return $fromItem;
                }
            }
        }

        return null;
    }
}

