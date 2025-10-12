<?php

namespace App\Http\Controllers;

use App\Models\RewardPunishmentRule;
use Illuminate\Http\Request;

class RewardPunishmentRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rules = RewardPunishmentRule::all();
        return response()->json($rules);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:reward,punishment',
            'name' => 'required|string|max:255',
            'points' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        $rule = RewardPunishmentRule::create($request->all());

        return response()->json($rule, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(RewardPunishmentRule $rewardPunishmentRule)
    {
        return response()->json($rewardPunishmentRule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RewardPunishmentRule $rewardPunishmentRule)
    {
        $request->validate([
            'type' => 'sometimes|required|in:reward,punishment',
            'name' => 'sometimes|required|string|max:255',
            'points' => 'sometimes|required|integer',
            'description' => 'nullable|string',
        ]);

        $rewardPunishmentRule->update($request->all());

        return response()->json($rewardPunishmentRule);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RewardPunishmentRule $rewardPunishmentRule)
    {
        $rewardPunishmentRule->delete();
        return response()->json(['message' => 'Reward punishment rule deleted successfully']);
    }
}
