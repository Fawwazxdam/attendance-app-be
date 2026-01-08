<?php

namespace App\Http\Controllers;

use App\Models\StimulusControl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StimulusControlController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stimulusControls = StimulusControl::with('student.user')->get();
        return response()->json([
            'success' => true,
            'data' => $stimulusControls
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'value' => 'required|string',
        ]);

        $stimulusControl = StimulusControl::create([
            'uuid' => Str::uuid(),
            'student_id' => $request->student_id,
            'value' => $request->value,
        ]);

        return response()->json([
            'success' => true,
            'data' => $stimulusControl->load('student.user')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(StimulusControl $stimulusControl)
    {
        return response()->json([
            'success' => true,
            'data' => $stimulusControl->load('student.user')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StimulusControl $stimulusControl)
    {
        $request->validate([
            'value' => 'sometimes|required|string',
        ]);

        $stimulusControl->update($request->only(['value']));

        return response()->json([
            'success' => true,
            'data' => $stimulusControl->load('student.user')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StimulusControl $stimulusControl)
    {
        $stimulusControl->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stimulus control deleted successfully'
        ]);
    }
}
