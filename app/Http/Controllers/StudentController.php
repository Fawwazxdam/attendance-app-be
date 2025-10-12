<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::with(['user', 'grade', 'studentPoint'])->get();
        return response()->json($students);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'fullname' => 'required|string|max:255',
            'grade_id' => 'required|exists:grades,id',
            'birth_date' => 'required|date',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
        ]);

        // Ensure the user doesn't already have a student record
        if (Student::where('user_id', $request->user_id)->exists()) {
            return response()->json(['error' => 'User already has a student record'], 400);
        }

        $student = Student::create($request->all());

        return response()->json($student->load(['user', 'grade', 'studentPoint']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        return response()->json($student->load(['user', 'grade', 'studentPoint']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'fullname' => 'sometimes|required|string|max:255',
            'grade_id' => 'sometimes|required|exists:grades,id',
            'birth_date' => 'sometimes|required|date',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
        ]);

        $student->update($request->all());

        return response()->json($student->load(['user', 'grade', 'studentPoint']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(['message' => 'Student deleted successfully']);
    }
}
