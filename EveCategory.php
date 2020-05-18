<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EveCategory extends Model
{
    protected $table =  'eve_categories';

    public function parentId()
    {
        return $this->belongsTo(self::class);
    }

    public function parentIdList()
    {
        return self::where('parent_id', null)->get();
    }
    public function children()
    {
        return $this->hasMany(self::class,'parent_id')->orderBy('order');
    }

    public function getCategories($id = null)
    {
        $eveCategories = self::where('parent_id', $id)->orderBy('order', 'ASC')->get();
        return $this->returnCategoryArray($eveCategories);

    }

    public function getCategoriesPreview($id = null)
    {
        $eveCategories = self::where('parent_id', $id)->take(8)->orderBy('order', 'ASC')->get();
        return $this->returnCategoryArray($eveCategories);

    }

    public function returnCategoryArray($categories)
    {
        $categoriesArray=[] ;
        if(count($categories)>0) {
            foreach ($categories as $category) {
                $categoriesArray[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'image' => URL::to('/storage/'. $category->image) ,
                    'colorCode' => $category->color_code,
                    'order' => $category->order,
                    'children' => $this->returnCategoryArray($category->children)
                ];
            }
        }
        return $categoriesArray ;
    }
}
