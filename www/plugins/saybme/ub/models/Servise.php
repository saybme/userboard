<?php namespace Saybme\Ub\Models;

use Model;

class Servise extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\NestedTree; 
    use \October\Rain\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    
    public $table = 'saybme_ub_servises';
   
    public $rules = [
        'name' => 'required',
        'slug' => 'required'
    ];

    public $attachOne = [
        'photo' => \System\Models\File::class
    ];    

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }    

    public function beforeCreate() {
        $this->url = $this->getUrl();
    }

    public function beforeSave(){
        $this->url = $this->getUrl();
    }

    // Формируем URL
    private function getUrl(){
        if($this->parent) $arr[] = $this->parent->url;
        $arr[] = $this->slug;
        return implode('/', $arr);
    }

    // Ссылка на ресурс
    public function getLinkAttribute(){
        $arr[] = 'servises';      
        $arr[] = $this->getUrl();
        $url = implode('/', $arr);
        return url($url);
    }

    // Ссылка на ресурс
    public function getLinkOrderAttribute(){
        $arr[] = 'cabinet';      
        $arr[] = $this->getUrl();
        $url = implode('/', $arr);
        return url($url);
    }

    public static function getMenuTypeInfo(){

        $records = Form::active()->get();

        $iterator = function($records) use (&$iterator) {
            $result = [];
            foreach ($records as $record) {
                if (!$record->children) {
                    $result[$record->id] = $record->name;
                }
                else {
                    $result[$record->id] = [
                        'title' => $record->name,
                        'items' => $iterator($record->children)
                    ];
                }
            }
            return $result;
        };
        
        return ['references' => $iterator($records)]; 
       
    }

    public static function resolveMenuItem($item, $url, $theme) {

        // Форма 
        $model = Form::find($item->reference);
        if (!$model) return;

        $arr[] = 'cabinet';
        $arr[] = 'forms';
        $arr[] = $model->hash;

        $url = implode('/', $arr);
        
        return [
            'title' => $item->name,
            'url' => url($url),
            'isActive' => true
        ];
        
    }


}
