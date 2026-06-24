<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class ClassesController extends Controller
{
    /**
     * সব ক্লাসের তালিকা দেখানো (লগইন করা ইউজার অনুযায়ী)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // ডাটাবেস থেকে সব ইউজারের ক্লাস লেটেস্ট অর্ডারে নিয়ে আসা হচ্ছে
            $classes = Classes::latest()->get();
            
            return response()->json(['status' => 'success', 'classData' => $classes], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch data'], 500);
        }
    }
    /**
     * নতুন ক্লাস তৈরি করা
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'class_name' => 'required|string|max:255',
        ]);

        try {
            Classes::create([
                'class_name' => $request->class_name,
                'user_id' => $request->user()->id,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Class Created Successfully'], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Registration Failed'], 500);
        }
    }

    /**
     * এডিট করার জন্য নির্দিষ্ট একটি ক্লাসের তথ্য দেখানো
     */
    public function show($id): JsonResponse
    {
        try {
            $data = Classes::findOrFail($id);
            return response()->json(['status' => 'success', 'rows' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Class Not Found'], 404);
        }
    }

    /**
     * ক্লাসের তথ্য আপডেট করা
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'class_name' => 'required|string|max:255',
        ]);

        try {
            $class = Classes::findOrFail($id);
            $class->update([
                'class_name' => $request->class_name,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Update Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Update Failed'], 500);
        }
    }

    /**
     * ক্লাস ডিলিট করা
     */
    public function destroy($id): JsonResponse
    {
        try {
            $class = Classes::findOrFail($id);
            $class->delete();
            return response()->json(['status' => 'success', 'message' => 'Delete Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Delete Failed'], 500);
        }
    }
}
