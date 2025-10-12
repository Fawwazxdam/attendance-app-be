<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teachers = Teacher::with('user')->get();
        return response()->json($teachers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'fullname' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'hire_date' => 'required|date',
        ]);

        // Ensure the user doesn't already have a teacher record
        if (Teacher::where('user_id', $request->user_id)->exists()) {
            return response()->json(['error' => 'User already has a teacher record'], 400);
        }

        $teacher = Teacher::create($request->all());

        return response()->json($teacher->load('user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Teacher $teacher)
    {
        return response()->json($teacher->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'fullname' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string|max:255',
            'subject' => 'sometimes|required|string|max:255',
            'hire_date' => 'sometimes|required|date',
        ]);

        $teacher->update($request->all());

        return response()->json($teacher->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher)
    {
        $teacher->delete();
        return response()->json(['message' => 'Teacher deleted successfully']);
    }
}
