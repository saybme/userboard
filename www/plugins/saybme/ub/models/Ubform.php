<?php namespace Saybme\Ub\Models;

use Saybme\Ub\Models\Forminput;
use Saybme\Ub\Models\Formvalues;
use Log;
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
    
    // Событие после создания
    public function afterCreate(){
        $this->createForm();        
    }

    // Перед удалением
    public function beforeDelete(){
        // Удаляем шаблон
        $filename = base_path($this->getFormPath());
        unlink($filename);    
    }

    // Создаем шаблон формы
    private function createForm(){    

        $text = '';  
        $filename = base_path($this->getFormPath());     
        
        $fh = fopen($filename, 'w');
        fwrite($fh, $text);
        fclose($fh);

    }    
    
    // Путь к форме
    public function getFormPath() {
        return '/plugins/saybme/ub/components/ubforms/forms/form_' . $this->id . '.htm';
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
        'photos' => \System\Models\File::class,
        'pfiles' => \System\Models\File::class
    ];   

    public $hasMany = [
        'inputs' => \Saybme\Ub\Models\Forminput::class,
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
