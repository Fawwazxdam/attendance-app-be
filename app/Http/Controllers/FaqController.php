<?php

namespace App\Http\Controllers;

use App\Models\faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $faqs = faq::all();
        return response()->json($faqs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = faq::create($request->all());

        return response()->json($faq, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(faq $faq)
    {
        return response()->json($faq);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, faq $faq)
    {
        $request->validate([
            'question' => 'sometimes|required|string',
            'answer' => 'sometimes|required|string',
        ]);

        $faq->update($request->all());

        return response()->json($faq);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(faq $faq)
    {
        $faq->delete();
        return response()->json(['message' => 'FAQ deleted successfully']);
    }
}
