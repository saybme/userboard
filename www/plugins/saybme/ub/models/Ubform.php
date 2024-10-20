<?php namespace Saybme\Ub\Models;

use Model;

class Ubform extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SimpleTree;
    
    public $timestamps = false;
   
    public $table = 'saybme_ub_ubforms';
  
    public $rules = [
        'name' => 'required'
    ];

    public function scopeActive($query) {
        return $query->where('is_active', true);
    } 

    public function beforeCreate() {    
        $hash = md5(time());     
        $this->hash = $hash;
        $this->url = 'f/' . $hash;
    }

    public function getLinkAttribute(){
        $arr[] = 'cabinet';
        $arr[] = $this->url;
        $url = implode('/', $arr);
        return url($url);
    }

    public $attachMany = [
        'photos' => \System\Models\File::class
    ];
    

    // public function beforeSave() { 
    //     $this->url = 'f/' . $this->hash;
    // }

    public $hasMany = [
        'rows' => [
            Formrow::class,
            'key' => 'parent_id',
            'delete' => true
        ],
    ];    


    public static function getMenuTypeInfo($type) {
        $result = [];
        if ($type == 'ub-form') {
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
