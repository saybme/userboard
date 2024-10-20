<?php namespace Saybme\Ub\Models;

use Model;
use Tailor\Models\EntryRecord;
use Log;

class Form extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\NestedTree;

    protected $jsonable = ['tabs','props','inputs'];
    
    public $table = 'saybme_ub_forms';
    
    public $rules = [
        'name' => 'required'
    ];

    public $attachMany = [
        'files' => \System\Models\File::class
    ];
    

    public function beforeCreate() {
        $hash = md5(time());
        $this->hash = $hash;
        $this->url = 'forms/' . $hash;
    }

    public function scopeActive($query) {
        return $query->where('is_active', true);
    } 

    public function getLinkAttribute(){
        $arr[] = 'cabinet';
        $arr[] = $this->url;
        $url = implode('/', $arr);
        return url($url);
    }

    public function getInputValidationsAttribute(){

        $output = array();

        //Log::error($this->inputs);

        if($this->inputs) {
            foreach($this->inputs as $item){  
                if($item['req_input']){
                    $output[$item['code']] = $item['req_input'];
                }                          
            }
        }        

        return $output;
    }

    public function getInsuranceCompaniesAttribute(){
        $records = EntryRecord::inSectionUuid('e26ccf6c-4dbc-4957-b52c-faefe81b758d')->get();
        return $records;
    }


}
