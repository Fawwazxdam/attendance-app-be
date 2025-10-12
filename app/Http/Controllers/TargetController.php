<?php

namespace App\Http\Controllers;

use App\Models\Target;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $targets = Target::with('student.user')->get();
        return response()->json($targets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|string|in:active,completed,cancelled',
        ]);

        $target = Target::create($request->all());

        return response()->json($target->load('student.user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Target $target)
    {
        return response()->json($target->load('student.user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Target $target)
    {
        $request->validate([
            'description' => 'sometimes|required|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'status' => 'sometimes|required|string|in:active,completed,cancelled',
        ]);

        $target->update($request->all());

        return response()->json($target->load('student.user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Target $target)
    {
        $target->delete();
        return response()->json(['message' => 'Target deleted successfully']);
    }
}
