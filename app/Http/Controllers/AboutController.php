<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Http\Requests\StoreAboutRequest;
use App\Http\Requests\UpdateAboutRequest;

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
        About::find(1)->update($request->all());
    }
}
