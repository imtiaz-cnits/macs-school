<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class TeacherController extends Controller
{
    // শিক্ষক লিস্ট দেখার জন্য (ভবিষ্যতের জন্য)
   public function index(Request $request): JsonResponse
{
    try {
        $query = Teacher::with(['user'])->latest();

        // সার্চ লজিক (আপনার আগের কোডটিই থাকছে)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_id', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $teachers = $query->get();

        // ডাটা ম্যাপিং: সরাসরি নাম এবং ইমেইল পাঠিয়ে দেওয়া হচ্ছে
        $formattedData = $teachers->map(function($t) {
            return [
                'id'          => $t->id,
                'name'        => $t->user->name ?? 'Unknown User', // সরাসরি নাম
                'email'       => $t->user->email ?? 'No Email',    // সরাসরি ইমেইল
                'phone'       => $t->phone,
                'employee_id' => $t->employee_id,
                'designation' => $t->designation,
                'department'  => $t->department,
                'photo'       => $t->photo,
            ];
        });

        return response()->json(['status' => 'success', 'teacherData' => $formattedData], 200);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Failed to fetch data'], 500);
    }
}

    // নতুন শিক্ষক যুক্ত করা
    public function store(Request $request): JsonResponse
    {
        // ১. ভ্যালিডেশন (email টি users টেবিলে ইউনিক হতে হবে)
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:6',
            'employee_id'  => 'required|string|unique:teachers,employee_id',
            'biometric_id' => 'nullable|string|unique:teachers,biometric_id',
            'designation'  => 'required|string',
            'phone'        => 'required|string|max:20',
            'address'      => 'required|string',
            'gender'       => 'required|string',
            'joining_date' => 'required|date',
            'photo'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1024',
        ]);

        // ২. ডাটাবেস ট্রানজেকশন শুরু (যাতে ২টা টেবিলেই নির্ভুলভাবে ডাটা যায়)
        DB::beginTransaction();

        try {
            // ৩. প্রথমে User একাউন্ট তৈরি (লগইনের জন্য)
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // ৪. ছবি আপলোড হ্যান্ডেলিং
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('teacher_photos', 'public');
            }

            // ৫. এরপর শিক্ষকের প্রোফাইল তৈরি (User ID এর সাথে লিঙ্ক করে)
            Teacher::create([
                'user_id'      => $user->id, // রিলেশন
                'employee_id'  => $request->employee_id,
                'biometric_id' => $request->biometric_id,
                'designation'  => $request->designation,
                'department'   => $request->department,
                'phone'        => $request->phone,
                'address'      => $request->address,
                'gender'       => $request->gender,
                'blood_group'  => $request->blood_group,
                'joining_date' => $request->joining_date,
                'photo'        => $photoPath,
                'created_by'   => $request->user()->id, // যে এডমিন তৈরি করছে
            ]);

            // সব ঠিক থাকলে ট্রানজেকশন সেভ হবে
            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Teacher added and user account created successfully!'], 201);
            
        } catch (Exception $e) {
            DB::rollBack(); // কোনো এরর হলে কোনো ডাটাই সেভ হবে না
            return response()->json([
                'status'  => 'error', 
                'message' => 'Failed to add teacher!', 
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    /**
     * শিক্ষকের প্রোফাইল দেখা (View)
     */
    public function show($id): JsonResponse
    {
        try {
            $teacher = Teacher::with(['user', 'creator'])->findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $teacher], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Teacher not found'], 404);
        }
    }

    /**
     * শিক্ষকের ডাটা আপডেট করা (Edit)
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $teacher = Teacher::findOrFail($id);
            $user_id = $teacher->user_id;

            // ভ্যালিডেশনে নিজের ইমেইল ও আইডিকে ইগনোর করা হয়েছে
            $request->validate([
                'name'         => 'required|string|max:255',
                'email'        => 'required|email|unique:users,email,' . $user_id,
                'password'     => 'nullable|string|min:6', // পাসওয়ার্ড না দিলেও চলবে
                'employee_id'  => 'required|string|unique:teachers,employee_id,' . $id,
                'biometric_id' => 'nullable|string|unique:teachers,biometric_id,' . $id,
                'designation'  => 'required|string',
                'phone'        => 'required|string|max:20',
                'address'      => 'required|string',
                'joining_date' => 'required|date',
                'photo'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1024',
            ]);

            DB::beginTransaction();

            // ১. User টেবিল আপডেট
            $userData = [
                'name'  => $request->name,
                'email' => $request->email,
            ];
            // যদি নতুন পাসওয়ার্ড দেয়, তবেই আপডেট হবে
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            User::where('id', $user_id)->update($userData);

            // ২. ছবি আপডেট
            $photoPath = $teacher->photo;
            if ($request->hasFile('photo')) {
                if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }
                $photoPath = $request->file('photo')->store('teacher_photos', 'public');
            }

            // ৩. Teacher প্রোফাইল আপডেট
            $teacher->update([
                'employee_id'  => $request->employee_id,
                'biometric_id' => $request->biometric_id,
                'designation'  => $request->designation,
                'department'   => $request->department,
                'phone'        => $request->phone,
                'address'      => $request->address,
                'gender'       => $request->gender,
                'blood_group'  => $request->blood_group,
                'joining_date' => $request->joining_date,
                'photo'        => $photoPath,
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Teacher updated successfully!'], 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Update Failed!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * শিক্ষক ডিলিট করা
     */
    public function destroy($id): JsonResponse
    {
        try {
            $teacher = Teacher::findOrFail($id);
            $user = User::findOrFail($teacher->user_id);
            
            // ছবি ডিলিট
            if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                Storage::disk('public')->delete($teacher->photo);
            }
            
            // User ডিলিট করলে Teacher ডাটাও ডিলিট হয়ে যাবে (cascadeOnDelete এর কারণে)
            $user->delete(); 
            
            return response()->json(['status' => 'success', 'message' => 'Teacher deleted successfully!'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Delete Failed!'], 500);
        }
    }
}