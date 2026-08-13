<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_name',
        'subject_code',
        'subject_type',
        'status',
        'user_id'
    ];

    public function class(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    // আপনার নিজস্ব UTF-8 ক্যারেক্টার ফিক্সিং লজিক (অপরিবর্তিত রাখা হয়েছে)
    public function getSubjectNameAttribute($value)
    {
        if (empty($value)) return $value;
        if (preg_match('/[àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ]/i', $value)) {
            $bytes = '';
            for ($i = 0; $i < mb_strlen($value, 'UTF-8'); $i++) {
                $char = mb_substr($value, $i, 1, 'UTF-8');
                $code = mb_ord($char, 'UTF-8');
                if ($code < 256) {
                    $bytes .= chr($code);
                } else {
                    $map = [
                        8218 => 0x82, 8482 => 0x99, 8250 => 0x9B, 8364 => 0x80,
                        8226 => 0x95, 8211 => 0x96, 8212 => 0x97, 8224 => 0x86,
                        8225 => 0x87, 8240 => 0x89, 8254 => 0x9E,
                        353  => 0x9A, 339  => 0x9C, 382  => 0x9E, 376  => 0x9F
                    ];
                    $bytes .= chr($map[$code] ?? 0x3F);
                }
            }
            if (mb_check_encoding($bytes, 'UTF-8') && preg_match('/[\x{0980}-\x{09FF}]/u', $bytes)) {
                return $bytes;
            }
        }
        return $value;
    }

    // Helper function for Bengali normalization (NFC composition/unification)
    private function normalizeBengali($str)
    {
        if (empty($str)) {
            return $str;
        }

        $map = [
            // Decomposed to Precomposed (Yya, Rra, Rhha)
            "\xe0\xa6\xaf\xe0\xa7\xbc" => "\xe0\xa7\x9f", // য + ় -> য়
            "\xe0\xa6\xa1\xe0\xa7\xbc" => "\xe0\xa7\x9c", // ড + ় -> ড়
            "\xe0\xa6\xa2\xe0\xa7\xbc" => "\xe0\xa7\x9d", // ঢ + ় -> ঢ়
            
            // Decomposed to Precomposed Vowels (O, Au)
            "\xe0\xa7\x87\xe0\xa6\xbe" => "\xe0\xa7\x8b", // ে + া -> ো
            "\xe0\xa7\x87\xe0\xa7\x97" => "\xe0\xa7\x8c", // ে + ৗ -> ৌ
        ];
        return strtr($str, $map);
    }

    // 🚀 Advanced Translation Accessor (Bug Fixed)
    public function getTranslatedNameAttribute()
    {
        $name = trim($this->subject_name);
        if (empty($name)) {
            return $name;
        }

        // Normalize the input subject name to resolve Unicode character code variations
        $name = $this->normalizeBengali($name);

        // একটি সিঙ্গেল মাস্টার ডিকশনারি
        $translations = [
            // ১. Combined/Compound Subjects (এগুলো আগে ম্যাচ করানো সেফ)
            'বাংলাদেশ ও বিশ্বপরিচয় / সাধারণ বিজ্ঞান' => 'Bangladesh & Global Studies / General Science',
            'উচ্চতর গণিত / কৃষি শিক্ষা' => 'Higher Mathematics / Agriculture Studies',
            'জীববিজ্ঞান / ভূগোল' => 'Biology / Geography',
            'রসায়ন / অর্থনীতি' => 'Chemistry / Economics',
            'পদার্থ / ইতিহাস' => 'Physics / History',
            'ইসলাম / হিন্দু শিক্ষা' => 'Islam & Hinduism Education',
            'আরবী / ধর্মশিক্ষা' => 'Arabic & Religion',
            
            // ২. Full Subject Names
            'বাংলাদেশ ও বিশ্বপরিচয়' => 'Bangladesh & Global Studies',
            'বাংলাদেশ ও বিশ্বপরিচয়' => 'Bangladesh & Global Studies',
            'ইসলাম ও নৈতিক শিক্ষা' => 'Islam & Moral Education',
            'তথ্য ও যোগাযোগ প্রযুক্তি' => 'ICT',
            'সাধারণ গণিত' => 'General Mathematics',
            'উচ্চতর গণিত' => 'Higher Mathematics',
            'সাধারণ বিজ্ঞান' => 'General Science',
            'সাধারণ জ্ঞান' => 'General Knowledge',
            'সামাজিক বিজ্ঞান' => 'Social Science',
            'কৃষি শিক্ষা' => 'Agriculture Studies',
            'ইসলাম শিক্ষা' => 'Islamic Studies',
            'শারীরিক শিক্ষা' => 'Physical Education',
            'ধর্মশিক্ষা' => 'Religion',
            'জীববিজ্ঞান' => 'Biology',
            '১ম পত্র' => '1st Paper',
            '২য় পত্র' => '2nd Paper',
            
            // ৩. Root/Short Words
            'বাংলা' => 'Bangla',
            'ইংরেজী' => 'English',
            'ইংরেজি' => 'English',
            'গণিত' => 'Mathematics',
            'বিজ্ঞান' => 'Science',
            'সমাজ' => 'Social Science',
            'আরবী' => 'Arabic',
            'ড্রইং' => 'Drawing',
            'অঙ্কন' => 'Drawing',
            'ভূগোল' => 'Geography',
            'রসায়ন' => 'Chemistry',
            'অর্থনীতি' => 'Economics',
            'পদার্থ' => 'Physics',
            'ইতিহাস' => 'History',
            
            // ৪. Individual Words (Safety Net fallback in case compound matching fails)
            'বাংলাদেশ' => 'Bangladesh',
            'বিশ্বপরিচয়' => 'Global Studies',
            'বিশ্বপরিচয়' => 'Global Studies',
            'সাধারণ' => 'General',
            'উচ্চতর' => 'Higher',
            'কৃষি' => 'Agriculture',
            'শিক্ষা' => 'Education',
            'নৈতিক' => 'Moral',
            'ইসলাম' => 'Islam',
            'হিন্দু' => 'Hindu',
            'যোগাযোগ' => 'Communication',
            'প্রযুক্তি' => 'Technology',
            'ধর্ম' => 'Religion',
            'জীব' => 'Biology',
            
            // ৫. Symbols & Small Parts
            '১ম' => '1st',
            '২য়' => '2nd',
            '/' => ' / ',
            'S.B.A' => 'S.B.A',
            'SBA' => 'S.B.A',
        ];

        // Normalize keys of the translation array
        $normalizedTranslations = [];
        foreach ($translations as $key => $val) {
            $normalizedTranslations[$this->normalizeBengali($key)] = $val;
        }

        // ১. Exact Match Check (সরাসরি মিলে গেলে রিটার্ন)
        if (isset($normalizedTranslations[$name])) {
            return $normalizedTranslations[$name];
        }

        // ২. Advanced Partial Match & Replacement
        $replaced = $name;
        
        // 🚨 ম্যাজিক লজিক: অ্যারেটিকে Length অনুযায়ী Descending অর্ডারে সর্ট করা হচ্ছে। 
        // ফলে 'বাংলা' এর আগে 'বাংলাদেশ' রিপ্লেস হবে এবং স্ট্রিং ভাঙবে না।
        uksort($normalizedTranslations, function($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        // ক্রমানুসারে সেফ রিপ্লেসমেন্ট
        foreach ($normalizedTranslations as $bn => $en) {
            $replaced = str_replace($bn, $en, $replaced);
        }

        // ৩. ডাবল স্পেস রিমুভ করে ক্লিন রিটার্ন
        return trim(preg_replace('/\s+/', ' ', $replaced));
    }
}