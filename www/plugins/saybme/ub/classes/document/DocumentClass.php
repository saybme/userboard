<?php namespace Saybme\Ub\Classes\Document;

use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Models\Document;
use View;
use Input;
use Log;

require dirname(dirname(__FILE__)) .'/pdf/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;


class DocumentClass {

    // Создаем
    public function create(){

        // Профиль
        $q = new AuthClass;
        $user = $q->getActiveUser();

        $vars['form'] = Input::get('form');
        $vars['data'] = Input::get();
        $vars['user'] = $user->id;

        // Создаем документ
        $doc = new Document;
        $doc->fill($vars);
        $doc->save();

        return $doc;
    }

    // PDF файл
    static public function pdf($document = null){      
        if(!$document) return;   

        $tpl = $document->form->pdf_tpl; // Шаблон
        if(!trim($tpl)) return;
       
     
        $arr['document'] = $document;
        $html = View::make($tpl, $arr);      

        $options = new Options();
        $options->set('defaultFont', 'dejavu sans');  
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);        
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $dompdf->stream('schet', [
            'Attachment' => false
        ]);
        
    }   

}
