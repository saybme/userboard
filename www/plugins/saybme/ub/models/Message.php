<?php namespace Saybme\Ub\Models;

use Model;

class Message extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $fillable = ['user','suptheme','comment'];
    
    public $table = 'saybme_ub_messages';
   
    public $rules = [
        'user' => 'required',
        'comment' => 'required'
    ];

    public $belongsTo = [
        'user' => \Saybme\Ub\Models\User::class,
        'suptheme' => \Saybme\Ub\Models\Suptheme::class
    ];

    public function getIntrotextAttribute(){
        $comment = $this->comment;
        return mb_strimwidth($comment, 0, 80, "...");;
    }

}
