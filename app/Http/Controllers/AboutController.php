<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Http\Requests\StoreAboutRequest;
use App\Http\Requests\UpdateAboutRequest;
use App\Models\Content;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Str;

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

            $dir = public_path("logo");
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            $image   = Image::read($request->file('logo'));
            $resize = $image->scale(height: 35)->toWebp(100);
            $name = Str::random(40) . ".webp";
            $path = "logo/{$name}";
            Storage::disk("public")->put("{$dir}/{$name}", $resize);
        }
        $about = About::find(1);
        
        $about->name = $request->name;
        if ($path) {
    
            $about->logo = $path;
        }
        $about->save();
        Content::where("id", 1)->update(['data' => $request->data]);
        return response()->json(['success' => true, 'message' => 'Успешно обновлено']);
    }
}
