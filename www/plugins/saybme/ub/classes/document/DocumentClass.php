<?php namespace Saybme\Ub\Classes\Document;

use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Models\Document;
use View;
use Input;
use Log;

require dirname(dirname(__FILE__)) .'/pdf/vendor/autoload.php';
use Dompdf\Dompdf;


class DocumentClass {

    // Создаем
    public function create(){

        // Профиль
        $q = new AuthClass;
        $user = $q->getActiveUser();

        $vars['data'] = Input::get();
        $vars['user'] = $user->id;

        // Создаем документ
        $doc = new Document;
        $doc->fill($vars);
        $doc->save();

        return $doc;
    }

    // PDF файл
    static function pdf(){      

        $html = View::make('saybme.ub::documents.page');

        $dompdf = new Dompdf();
        
        $dompdf->set_option('defaultFont', 'Helvetica');
        $dompdf->set_option('isRemoteEnabled', TRUE);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $dompdf->stream('schet', [
            'Attachment' => false
        ]);
        
    }

}
