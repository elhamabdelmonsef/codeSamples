<?php

namespace App\Http\Controllers\Eve;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromotionCollection;
use App\Models\Promotion;
use Illuminate\Http\Request;
use App\Console\Commands\PromotionSync;

class PromotionController extends Controller
{

    public function showPreview(Promotion $promotion){
        $promotion= $promotion->getPromotions();
        if(count($promotion)>0){
            return new PromotionCollection($promotion);
        }
        else{
            return [];
        }

    }
    public function test(PromotionSync $command){
        $command->handle();

    }
}
