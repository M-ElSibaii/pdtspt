<?php

namespace App\Http\Controllers;

use App\Models\productdatatemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\groupofproperties;
use App\Models\properties;
use App\Models\comments;
use App\Models\Answers;
use App\Models\Likes;
use App\Models\propertiesdatadictionaries;
use App\Models\referencedocuments;

class ProductdatatemplatesController extends Controller
{
    /** Get the latest version of a PDT */
    public function getLatestPDTs()

    {

        $latestPDT = DB::table('productdatatemplates as pdt')
            ->join(
                DB::raw("(SELECT 
                GUID,
                MAX(versionNumber) as max_versionNumber,
                MAX(revisionNumber) as max_revisionNumber
                FROM productdatatemplates 
                GROUP BY GUID) as mx"),
                function ($join) {
                    $join->on('mx.GUID', '=', 'pdt.GUID');
                    $join->on('mx.max_versionNumber', '=', 'pdt.versionNumber');
                    $join->on('mx.max_revisionNumber', '=', 'pdt.revisionNumber');
                }
            )
            ->get();
        return view('dashboard', compact('latestPDT'));
    }





    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $PDTs = productdatatemplates::get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\productdatatemplates  $productdatatemplates
     * @return \Illuminate\Http\Response
     */
    public function show(productdatatemplates $productdatatemplates)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\productdatatemplates  $productdatatemplates
     * @return \Illuminate\Http\Response
     */
    public function edit(productdatatemplates $productdatatemplates)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\productdatatemplates  $productdatatemplates
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, productdatatemplates $productdatatemplates)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\productdatatemplates  $productdatatemplates
     * @return \Illuminate\Http\Response
     */
    public function destroy(productdatatemplates $productdatatemplates)
    {
        //
    }
}
