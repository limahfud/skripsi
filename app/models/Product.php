<?php

namespace App;

use App\models\Tag;
use App\models\ProductTag;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $table    = 'product';

    public function photos()
    {
        return $this->hasMany('ProductPhoto');
    }

    public function tags()
    {
        return $this->belongsToMany('Tags','product_tag','product_id','tag_id');
    }

    public function addTags($tags = [])
    {
        $rowTagsName    = Tag::all(['name'])->toArray();
        $rowTagsName    = array_flatten($rowTagsName);

        foreach ($tags as $key => $tag)
        {
            if(in_array($tag,$rowTagsName))
            {

            }
            else
            {
                $tag        = new Tag;
                $tag->name  = $tag;
                $tag->save();


            }
        }
    }
}
