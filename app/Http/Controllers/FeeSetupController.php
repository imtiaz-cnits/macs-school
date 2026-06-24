<?php

namespace App\Http\Controllers; 

use App\Models\FeeCategory;
use App\Models\FeeSetup;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\SessionYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeSetupController extends Controller
{
    // ==========================================
    // Fee Category Methods
    // ==========================================
    
    public function categoryIndex()
    {
        $categories = FeeCategory::with('user')->latest()->get();
        return view('pages.fees.category_index', compact('categories'));
    }

    public function categoryStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:fee_categories',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        FeeCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'user_id' => Auth::id(), // কে তৈরি করেছে তার আইডি
        ]);

        return back()->with('success', 'Fee Category Created Successfully!');
    }

    public function categoryDestroy($id)
    {
        FeeCategory::findOrFail($id)->delete();
        return back()->with('success', 'Fee Category Deleted!');
    }


    // ==========================================
    // Fee Setup Methods
    // ==========================================
    
    public function setupIndex()
    {
        // ড্রপডাউনের জন্য ডাটা পাঠানো হচ্ছে
        $branches = Branch::get();
        $classes = Classes::get();
        $sessions = SessionYear::latest()->get();
        
        // FeeCategory টেবিলে status কলাম আছে, তাই এটি থাকবে
        $categories = FeeCategory::where('status', 'Active')->get(); 
        
        // N+1 Query Issue এড়াতে branch এবং sessionYear যুক্ত করা হলো
        $setups = FeeSetup::with(['category', 'schoolClass', 'user', 'branch', 'sessionYear'])->latest()->get();

        return view('pages.fees.setup_index', compact('branches', 'classes', 'sessions', 'categories', 'setups'));
    }

    public function setupStore(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'class_id' => 'required|exists:classes,id',
            'session_year_id' => 'required|exists:session_years,id',
            'fee_category_id' => 'required|exists:fee_categories,id',
            'amount' => 'required|numeric|min:0',
            'fee_month' => 'nullable|string', // যদি মাসিক ফি হয় (e.g., January)
        ]);

        // চেক করা হচ্ছে একই ক্লাসের একই ফি আগে থেকে সেট করা আছে কিনা
        $exists = FeeSetup::where([
            'branch_id' => $request->branch_id,
            'class_id' => $request->class_id,
            'session_year_id' => $request->session_year_id,
            'fee_category_id' => $request->fee_category_id,
            'fee_month' => $request->fee_month,
        ])->exists();

        if ($exists) {
            return back()->with('error', 'This fee is already set up for this class & month!');
        }

        FeeSetup::create([
            'branch_id' => $request->branch_id,
            'class_id' => $request->class_id,
            'session_year_id' => $request->session_year_id,
            'fee_category_id' => $request->fee_category_id,
            'amount' => $request->amount,
            'fee_month' => $request->fee_month,
            'status' => 'Active',
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Fee Setup Completed Successfully!');
    }

    // ক্যাটাগরি এডিট পেজ দেখানোর জন্য
    public function categoryEdit($id)
    {
        $category = FeeCategory::findOrFail($id);
        return view('pages.fees.category_edit', compact('category'));
    }

    // এডিট করা ডাটা সেভ বা আপডেট করার জন্য
    public function categoryUpdate(Request $request, $id)
    {
        $category = FeeCategory::findOrFail($id);

        $request->validate([
            // কমা (,) এর পর $id দিয়েছি যাতে নিজের নামটা ইউনিক চেকিং থেকে বাদ থাকে
            'name' => 'required|string|max:255|unique:fee_categories,name,' . $id,
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->route('fees.categories.index')->with('success', 'Fee Category Updated Successfully!');
    }

    public function setupDestroy($id)
    {
        FeeSetup::findOrFail($id)->delete();
        return back()->with('success', 'Fee Setup Deleted!');
    }
}