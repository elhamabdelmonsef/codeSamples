<?php

namespace App\Http\Controllers\Eve;

use App\Http\Controllers\Controller;
use App\Models\EveCategory;
use Illuminate\Http\Request;

class EveCategoryController extends Controller
{
    public function show(Request $request){
        $eveCategory=new EveCategory();
        $categories=$eveCategory->getCategories();

        return response()->json([

            'data' =>$categories ,
        ],200);
    }

    public  function showPreview(){
        $eveCategory=new EveCategory();
        $categories=$eveCategory->getCategoriesPreview();

        return response()->json([

            'data' =>$categories ,
        ],200);
    }



}
