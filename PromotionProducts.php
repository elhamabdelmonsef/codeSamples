<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Product;
use App\Models\ProductsVariant;

class PromotionProducts extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {  //echo "<pre>";       print_r($this->variants);exit;
        $product_id=ProductsVariant::findOrFail($this->variants[0]->id)->product_id;
        $product=new Product();
        $productVariant=new \App\Models\ProductsVariant();
        $product= $product->findOrFail($product_id);
      // echo "<pre>";       print_r($this->variants);exit;
        return [
            'id'=>$product_id,
            'name'=>$product->name,
            'brief'=>$product->brief,
            'quantity'=>$this->quantity,
            'attributes'=>$product->getAttributesofCombo($this->product_id),
            'variants'=>new ProductsVariantCollection($productVariant->getComboVriant($this->variants))
        ];
    }
}
