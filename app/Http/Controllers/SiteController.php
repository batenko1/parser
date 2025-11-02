<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class SiteController
{

    public function index()
    {
        $sites = Site::all();

        return view('sites', compact('sites'));
    }

    public function save(Request $request)
    {

        $sites = $request->input('sites');

        foreach ($sites as $siteId => $value) {

            Site::query()
                ->where('id', $siteId)
                ->update([
                    'speed_x' => $value['flame'] ?? 0,
                    'very_fast_value' => $value['rocket'] ?? 0,
                ]);
        }

        return redirect()->back();

    }

}
