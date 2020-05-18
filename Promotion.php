<?php

namespace App\Models;

use App\Http\Resources\PromotionProductsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use App\Models\PortfolioProductVariant;
use Illuminate\Support\Facades\DB;

class Promotion extends Model
{
    protected $fillable = ['name','code','brief','type','details','start_date','end_date','data','portfolio_id','odoo_id'];
    protected  const  TYPE_COMPO = 1;

    public function promotionList(){
        return[
            self::TYPE_COMPO=>"Combo",
        ];
    }

    public function getPromotionType(){
      $promotion=  $this->promotionList();
      return $type=$promotion[$this->type];
    }
    public function getPromotions(){
        $now = Carbon::now();
        $portfolio_id=config('portfolio.id');
       return $promotions =self::where('portfolio_id','=',$portfolio_id)
               ->where('start_date', '<=', $now)
               ->where('end_date', '>=',$now )
            ->orderBy('id','ASC')
            ->get();
    }

    public function getPromotionJson(){
        $json=json_decode($this->data);
        //print_r($products=$json->products[1][1]);
       return $result=[
           'original_price'=>$json->original_price,
           'member_price'=>$json->member_price,
           'points'=>$json->points,
           'banner'=>URL::to('/storage/'. $json->banner),
           'images'=>$json->images,
           'available_quantity'=>$this->checkAvailabeQuantity($products=$json->static_products[1][1], $products=$json->dynamic_products[1][1]),
           'static_products'=>new PromotionProductsCollection($products=$json->static_products[1][1]),
           'dynamic_products'=>new PromotionProductsCollection($products=$json->dynamic_products[1][1]),
           'static_gift'=>new PromotionProductsCollection($products=$json->static_gift[1][1]),
           'dynamic_gift'=>new PromotionProductsCollection($products=$json->dynamic_gift[1][1])
           ] ;

    }
    public function checkAvailabeQuantity($static_products,$dynamic_product){
        $productVariant = new ProductsVariant();
        $availableQuantity=array();
        if(count($static_products)>0){
            foreach ($static_products as $product ){
                $variants= $productVariant->getComboVriant($product->variants);
                foreach ($variants as $variant){
                    $availableQuantity[]=$variant->available_quantity;
                }
            }
        }
        if(count($dynamic_product)>0){
            foreach ($dynamic_product as $product ){
                $variants= $productVariant->getComboVriant($product->variants)->groupby('product_id');
                $dynamic_available=array();
                foreach ($variants as $variant){
                    foreach ($variant as $item)
                    {
                        $dynamic_available[] = $item->available_quantity;
                    }
                 }
                $availableQuantity[]=max($dynamic_available);
            }
        }
        return min($availableQuantity);
    }
    public function returnPromotion($id){
        return $promotion =self::findOrFail($id);
    }
    public function promotionDetails($id){
        $productVariant = new ProductsVariant();
        $promotion  = $this->returnPromotion($id);
        $type = $promotion->type ;
        switch ($type) {
            case 1:
                $data = json_decode($promotion->data);
                $price = $data->member_price ;
                $points = $data->points ;
                $static_products = $data->static_products[1][1];
                $dynamic_products = $data->dynamic_products[1][1];
                $static_gift = $data->static_gift[1][1];
                $dynamic_gift = $data->dynamic_gift[1][1];
                if(count($static_products)>0){
                    foreach ($static_products as $product ){
                        $variants= $productVariant->getComboVriant($product->variants);
                        foreach ($variants as $variant){
                            $ids[]=$variant->id;
                        }
                    }
                }
                if(count($dynamic_products)>0){
                    foreach ($dynamic_products as $product ){
                        $variants= $productVariant->getComboVriant($product->variants);
                        foreach ($variants as $variant){
                            $ids[]=$variant->id;
                        }

                    }
                }
                $gifts_ids = [] ;
                if(count($static_gift)>0){
                    foreach ($static_gift as $product ){
                        $variants= $productVariant->getComboVriant($product->variants);
                        foreach ($variants as $variant){
                            $gifts_ids[]=$variant->id;
                            $ids[]=$variant->id;
                        }

                    }
                }
                if(count($dynamic_gift)>0){
                    foreach ($dynamic_gift as $product ){
                        $variants= $productVariant->getComboVriant($product->variants);
                        foreach ($variants as $variant){
                            $gifts_ids[]=$variant->id;
                            $ids[]=$variant->id;
                        }

                    }
                }

                $promo_line = isset($data->service_variant_id) ? ($data->service_variant_id ) :NULL ;
                $pack_variants = isset($data->pack_variants) ? ($data->pack_variants ) :[] ;
                $action = isset($data->action) ? ($data->action ) :NULL ;

                return [
                        "type"=> $type,
                        "action" => $action ,
                        "odoo_id" => $promotion->odoo_id ,
                        "member_price" => $price ,
                        "original_price" => $data->original_price ,
                        "points" => $points,
                        "promo_line_id"=>$promo_line,
                        "variants" => $ids ,
                        "pack_variants" => $pack_variants,
                        "gift_variants"=> $gifts_ids
                    ];
                break;
            default:
                break ;
        }

    }
    public function single_variant_quantity($promo_id , $variant_id){
        $promotion  = $this->returnPromotion($promo_id);
        $data = json_decode($promotion->data);
        $variants_with_quantity = [];
        $static_products = $data->static_products[1][1];
        $dynamic_products = $data->dynamic_products[1][1];
        $static_gift = $data->static_gift[1][1];
        $dynamic_gift = $data->dynamic_gift[1][1];
        $all_products = array_merge($static_products , $dynamic_products,$static_gift ,$dynamic_gift);
        foreach($all_products as $product){
            $qty = $product->quantity ;
            $variants =$product->variants;
            // return $qty ;
            foreach($variants as $variant){
                 $variants_with_quantity[$variant->id] = $qty ;
            }
        }
        if(isset($variants_with_quantity[$variant_id] )){
            return $variants_with_quantity[$variant_id] ;
        }else{
            return 1 ;
        }

    }
    public function promotionName($id){
        $promotion  = $this->returnPromotion($id);
        $data = json_decode($promotion->data);
        return  $data->promotion_name ;
    }
    public function createPromotion($promotion, $varaiants,$pack,$service_variant_id, $gifts, $dynamicVariants,$orginal_price){
        try{
            $log=new OdooRequestLog;
            $lastCode = DB::table('promotions')
                    ->select(DB::raw('MAX(CAST(code AS UNSIGNED)) as code'))
                    ->first();
            if(!empty($lastCode->code)){
               $code=substr($lastCode->code, 2);
               $code=  (int)$code+1;
               //echo "code:".$code;exit;
            }else{
                $code=0;
            }
//            $code = !empty($lastCode->code) ?(int)substr($lastCode->code, 2)+1  : 0;
//          // echo"<pre>"; print_r($lastCode);exit;
//         //echo "code:".$code;exit;

            $name=$promotion['name'];
            $code='91'.sprintf('%04d',$code );
            $exists = Promotion::where('odoo_id', $promotion['id'])
                    ->select(DB::raw('code,name'))
                    ->first();
                if(isset($exists)){
                    if(!empty($exists->code))
                        $code=$exists->code;
                    $name=$exists->name;

                }
            $data=[
                "banner"=>"combo cards/".$code.'.png',
                "images"=>[URL::to('/storage/combo cards/'. $code.'.png')],
                "action"=>$promotion['action'],
                "promotion_name"=>$promotion['name'],
                "original_price"=>$orginal_price,
                "member_price"=>$promotion['package_value'],
                "points"=>$promotion['promo_points'],
                "static_products"=>[
                    "&",
                    [
                       "|",
                       $varaiants
                    ]
                ],
                "dynamic_products"=>[
                    "&",
                    [
                       "|",
                        $dynamicVariants
                    ]
                ],
                "static_gift"=>[
                    "&",
                    [
                       "|",
                        isset($gifts['static_gifts'])?$gifts['static_gifts']:[]
                    ]
                ],
                "dynamic_gift"=>[
                    "&",
                    [
                       "|",
                        isset($gifts['dynamic_gifts'])?$gifts['dynamic_gifts']:[]
                    ]
                ]

                  ];
            if(count($pack)>0)
                $data['pack_variants']=$pack;

            if($service_variant_id!=null)
                $data['service_variant_id']=$service_variant_id;
                $satrtDate=$this->convertDate($promotion['from_date'],1);
                $newMonth=$satrtDate->month;
                $cruntMonth=$now = Carbon::now()->month;
                $portfolio_id=($newMonth==$cruntMonth)?config('portfolio.id'):null;
                self::updateOrCreate(['odoo_id'=>$promotion['id']],[
                        'odoo_id'=> $promotion['id'],
                        'name'=>$name,
                        'data'=> json_encode($data),
                        'code'=>$code,
                        'start_date'=>$this->convertDate($promotion['from_date'],1),
                        'end_date'=>$this->convertDate($promotion['to_date'],2),
                        'portfolio_id'=>$portfolio_id,
                        'type'=>1

          ]);


//               $promotioDate=json_decode($promotion);
//               $data['banner']="combo cards/".$promotioDate->id;
//               $data['images']=["combo cards/".$promotioDate->id];
//              self::where('id', $promotioDate->id)->update(array('data' => json_encode($data)));
      }catch(\Exception $e){
          $log->create_log(OdooRequestLog::ACTION_PROMOTION_SYNC, "creatPromotion function".$e->getMessage());
      }

    }

    public function convertDate($date,$type){
       $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
       $date= $date->setTimezone('Africa/Cairo');
       return $type==1?$date->addDays(2):$date->subDays(2);
    }
    public function getOriginalPrice($varaiant_id,$quantity){

        try{
            $log= new OdooRequestLog();
            $portfolioVariant=$variants=PortfolioProductVariant::where('products_variant_id',$varaiant_id)
            ->select( DB::raw( 'portfolios_products_variant.member_price' ) )->get()->first();
            $member_price=isset($portfolioVariant)?$portfolioVariant->member_price:0;
            if($member_price >1000000)
                $member_price=substr($member_price,0,-6);
           // echo $member_price."\n";
            return $member_price *$quantity;


        }catch(\Exception $e){
            $log->create_log(OdooRequestLog::ACTION_PROMOTION_SYNC, $e->getMessage());
        }
    }
    public function generatePromotionCode($offset){
        return sprintf('%04d',$offset );

    }

}

