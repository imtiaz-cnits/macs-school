<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class SectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $sections = Section::latest()->get();
            return response()->json(['status' => 'success', 'sectionData' => $sections], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch data'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['section_name' => 'required|string|max:255']);
        try {
            Section::create([
                'section_name' => $request->section_name,
                'user_id' => $request->user()->id,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Section Created'], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Creation Failed'], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $data = Section::findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Not Found'], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate(['section_name' => 'required|string|max:255']);
        try {
            $section = Section::findOrFail($id);
            $section->update(['section_name' => $request->section_name]);
            return response()->json(['status' => 'success', 'message' => 'Update Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Update Failed'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $section = Section::findOrFail($id);
            $section->delete();
            return response()->json(['status' => 'success', 'message' => 'Delete Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Delete Failed'], 500);
        }
    }
}