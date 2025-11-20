<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeveloperController extends Controller
{
    public function updateTimezone()
    {
        DB::table('attendances')->orderBy('id')->chunk(200, function ($records) {
            foreach ($records as $record) {
                DB::table('attendances')
                    ->where('id', $record->id)
                    ->update([
                        'created_at' => Carbon::parse($record->created_at)->addHours(7),
                        'updated_at' => Carbon::parse($record->updated_at)->addHours(7),
                    ]);
            }
        });

        return "Selesai memperbaiki timezone!";
    }
}
