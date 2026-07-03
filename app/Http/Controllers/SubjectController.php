<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    // Fetch and display subject list with search functionality
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Subject::with('class')->latest();

            // Handle Text Search (Subject Name or Code)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('subject_name', 'LIKE', "%{$search}%")
                      ->orWhere('subject_code', 'LIKE', "%{$search}%");
                });
            }

            $subjects = $query->get();

            return response()->json(['status' => 'success', 'subjectData' => $subjects], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch data', 'error' => $e->getMessage()], 500);
        }
    }

   public function store(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_name' => 'required|string|max:255',
            'subject_code' => 'nullable|string|max:50|unique:subjects,subject_code,NULL,id,class_id,' . $request->class_id,
            'subject_type' => 'required|in:Theory,Practical,Objective,Both',
        ]);

        try {
            $data = $request->all();
            $data['user_id'] = Auth::id(); 

            Subject::create($data);
            return response()->json(['status' => 'success', 'message' => 'Subject added successfully!'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Edit: Fetch data for a specific subject
    public function edit($id): JsonResponse
    {
        try {
            $subject = Subject::findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $subject], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Subject not found'], 404);
        }
    }

    // Update subject data
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_name' => 'required|string|max:255',
            'subject_code' => 'nullable|string|max:50|unique:subjects,subject_code,' . $id . ',id,class_id,' . $request->class_id,
            'subject_type' => 'required|in:Theory,Practical,Objective,Both',
        ]);

        try {
            $subject = Subject::findOrFail($id);
            
            $data = $request->all();
            $data['user_id'] = Auth::id();
            
            $subject->update($data);
            return response()->json(['status' => 'success', 'message' => 'Subject updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Update failed', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a subject
    public function destroy($id): JsonResponse
    {
        try {
            $subject = Subject::findOrFail($id);
            $subject->delete();
            return response()->json(['status' => 'success', 'message' => 'Subject deleted successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to delete subject', 'error' => $e->getMessage()], 500);
        }
    }
}