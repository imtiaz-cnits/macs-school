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

    public function __construct()
    {
        $this->username = config('services.mim_sms.username', 'info@codenextit.com');
        $this->apiKey = config('services.mim_sms.api_key', '2TLHE115406U4ON');
        $this->senderId = config('services.mim_sms.sender_id', '8809601004913');
    }

    /**
     * Send single SMS using MIM SMS V2 API
     *
     * @param string $mobileNumber
     * @param string $message
     * @param int|null $studentId
     * @return bool
     */
    public function sendSms(string $mobileNumber, string $message, ?int $studentId = null): bool
    {
        // Normalize mobile number to Bangladeshi format (starts with 8801)
        $cleanNumber = preg_replace('/[^0-9]/', '', $mobileNumber);
        if (strlen($cleanNumber) == 11 && str_starts_with($cleanNumber, '01')) {
            $cleanNumber = '88' . $cleanNumber;
        }

        // Whitelist check: Only send to your test number (01788428280) for now
        $allowedNumbers = ['8801788428280'];
        if (!in_array($cleanNumber, $allowedNumbers)) {
            Log::info("MIM SMS blocked: Mobile number {$cleanNumber} is not whitelisted for testing.");
            
            // Log to database as Blocked/Test Filter
            SmsLog::create([
                'student_id' => $studentId,
                'mobile_number' => $mobileNumber,
                'message' => $message,
                'status' => 'Blocked (Test Mode)',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            
            return true; 
        }

        try {
            $url = 'https://api.mimsms.com/api/V2/Send';
            
            $response = Http::timeout(10)->get($url, [
                'userName' => $this->username,
                'apiKey' => $this->apiKey,
                'mobileNumber' => $cleanNumber,
                'senderName' => $this->senderId,
                'transactionType' => 'T',
                'message' => $message
            ]);

            $isSuccess = $response->successful();
            
            // Log response for debugging
            Log::info("MIM SMS Send status to {$cleanNumber}: " . ($isSuccess ? 'Success' : 'Failed') . " - Response: " . $response->body());

            // Save log to database
            SmsLog::create([
                'student_id' => $studentId,
                'mobile_number' => $mobileNumber,
                'message' => $message,
                'status' => $isSuccess ? 'Sent' : 'Failed',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return $isSuccess;

        } catch (\Exception $e) {
            Log::error("MIM SMS sending failed for {$mobileNumber}: " . $e->getMessage());
            
            // Log failure to database
            SmsLog::create([
                'student_id' => $studentId,
                'mobile_number' => $mobileNumber,
                'message' => $message,
                'status' => 'Failed',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return false;
        }
    }
}
