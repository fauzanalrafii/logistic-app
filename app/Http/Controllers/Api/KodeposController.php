<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KodeposController extends Controller
{
    public function search(Request $request)
    {
        $search = trim($request->query('search'));

        if (strlen($search) < 3) {
            return response()->json([]);
        }

        $results = DB::connection('mysql_master')
            ->table('master_kodepos_new')
            ->where(function ($q) use ($search) {
                $q->where('ZipCode', 'like', "%{$search}%")
                  ->orWhere('Sub_District', 'like', "%{$search}%")
                  ->orWhere('City', 'like', "%{$search}%")
                  ->orWhere('District', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        return response()->json(
            $results->map(fn ($row) => [
                'id'    => $row->ID,
                'label' => "{$row->ZipCode} - {$row->Sub_District} - {$row->City}",
            ])
        );
    }
}
