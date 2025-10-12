<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $grades = Grade::with(['homeroomTeacher.user', 'students.user'])->get();
        return response()->json($grades);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id',
        ]);

        $grade = Grade::create($request->all());

        return response()->json($grade->load(['homeroomTeacher.user', 'students.user']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Grade $grade)
    {
        return response()->json($grade->load(['homeroomTeacher.user', 'students.user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Grade $grade)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'homeroom_teacher_id' => 'sometimes|nullable|exists:teachers,id',
        ]);

        $grade->update($request->all());

        return response()->json($grade->load(['homeroomTeacher.user', 'students.user']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grade $grade)
    {
        $grade->delete();
        return response()->json(['message' => 'Grade deleted successfully']);
    }
}
