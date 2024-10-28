<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Http\Requests\StoreAboutRequest;
use App\Http\Requests\UpdateAboutRequest;
use Illuminate\Support\Facades\Storage;

class AboutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return About::get();
    }


    public function store(StoreAboutRequest $request)
    {
        // Storage::disk("public")->put($path, $resize);
        $path = "";
        if ($request->file('logo')) {
            $request->validate([
                'logo' => 'image|max:512',
            ]);

            $path = Storage::disk("public")->putFile("logo", $request->file("logo"));
        }
        $about = About::find(1);
        $about->name = $request->name;
        if ($path) {
            $about->logo = $path;
        }
        $about->save();
        return response()->json(['success' => 1, 'message' => 'Успешно обновлено']);
    }
}
