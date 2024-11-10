<?php namespace Saybme\Ub\Models;

use Model;

class Prpage extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\NestedTree;

    protected $slugs = ['slug' => 'name'];
    
    public $table = 'saybme_ub_profile_pages';
    
    public $rules = [
        'name' => 'required',
        'slug' => 'required'
    ];

    public function scopeActive($query) {
        return $query->where('is_active', true);
    } 

    public $belongsTo = [
        'form' => \Saybme\Ub\Models\Ubform::class
    ];

    public function getLinkAttribute(){
        $arr[] = 'cabinet';
        $arr[] = $this->slug;
        $url = implode('/', $arr);
        return url($url);
    }



    public static function getMenuTypeInfo($type) {
        $result = [];
        if ($type == 'ub-page-cabinet') {
            $result = [
                'references'   => self::listSubCategoryOptions(),
                'nesting'      => true,
                'dynamicItems' => true
            ];
        }    
        return $result;
    }

    protected static function listSubCategoryOptions() {
        $category = self::getNested();

        $iterator = function($categories) use (&$iterator) {
            $result = [];

            foreach ($categories as $category) {
                if (!$category->children) {
                    $result[$category->id] = $category->name;
                }
                else {
                    $result[$category->id] = [
                        'title' => $category->name,
                        'items' => $iterator($category->children)
                    ];
                }
            }

            return $result;
        };

        return $iterator($category);
    }

    public static function resolveMenuItem($item, $url, $theme) {
       
        $model = self::find($item->reference);
        if (!$model) return;     
        
        return [
            'title' => $item->name,
            'url' => $model->link,
            'isActive' => true
        ];
        
    }    

}
