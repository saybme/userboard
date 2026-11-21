<?php namespace Saybme\Ub\Models;

use Model;
use Input;
use Log;
use Saybme\Ub\Classes\LicensePlate\PlatePhotoService;

class Carnumber extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /** Не пересоздавать фото при служебном save() после генерации */
    public static bool $skipPlatePhotoRegen = false;

    /** @var string|null */
    protected $plateFingerprintBefore;

    protected $fillable = [
        'number',
        'user',
        'type',
        'pay',
        'urgently',        
        'username',
        'contacts',
        'type_price',
        'sum_start',
        'sum_end',
        'props',
        'price',
        'address',
        'is_active',
        'series',
        'region',
        'type_pay',
        'description'
    ];

    protected $jsonable = ['address','props'];
   
    public $table = 'saybme_ub_carnumbers';
    
    public $rules = [
        'user' => 'required',
        'type_pay' => 'required'
    ];

    public $belongsTo = [
        'user' => [
            \Saybme\Ub\Models\User::class,
            'key' => 'user_id',
        ],
    ];

    public $belongsToMany = [
        'managers' => [
            \Saybme\Ub\Models\User::class,
            'table' => 'saybme_ub_carnumber_managers',
            'key' => 'carnumber_id',
            'otherKey' => 'user_id',
        ],
    ];

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    /** Новые объявления сверху: срочно → дата публикации → id */
    public function scopeLatestPublished($query)
    {
        return $query->orderByDesc('urgently')->orderByDesc('created_at')->orderByDesc('id');
    }  

    public function getClientLabelAttribute()
    {
        if (!$this->user) {
            return 'Без клиента';
        }

        return $this->user->display_name;
    }

    public function afterCreate()
    {
        $this->attachManagers();
    }

    public function attachManagers()
    {
        $managerIds = User::active()
            ->where('utype_id', 2)
            ->pluck('id')
            ->all();

        if ($managerIds) {
            $this->managers()->syncWithoutDetaching($managerIds);
        }
    }

    public function beforeCreate()
    {
        // Фронтовые формы: props приходят через Input
        $series = Input::get('props.series');
        if (is_array($series)) {
            $this->series = implode('', $series);
        }

        $number = Input::get('props.number');
        if (is_array($number)) {
            $this->number = implode('', $number);
        }

        $region = Input::get('props.region');
        if ($region !== null && $region !== '') {
            $this->region = $region;
        }
    }

    public function beforeSave()
    {
        $this->syncPlateColumnsFromProps();

        if (static::$skipPlatePhotoRegen) {
            return;
        }

        $this->plateFingerprintBefore = $this->exists
            ? $this->fingerprintFromStoredState()
            : '';
    }

    public function afterSave()
    {
        if (static::$skipPlatePhotoRegen) {
            return;
        }

        $service = new PlatePhotoService();
        $after = $service->fingerprint($this);
        $changed = $after !== '' && $after !== (string) $this->plateFingerprintBefore;

        // В админке и на сайте: номер изменился или фото ещё нет
        if ($changed || !$this->photo) {
            static::$skipPlatePhotoRegen = true;
            try {
                $service->attachToCarnumber($this);
            } finally {
                static::$skipPlatePhotoRegen = false;
            }
        }
    }

    /**
     * Синхронизация series/number/region из props (форма бэкенда).
     */
    public function syncPlateColumnsFromProps(): void
    {
        $props = is_array($this->props) ? $this->props : [];

        if (isset($props['series'])) {
            $this->series = is_array($props['series'])
                ? implode('', $props['series'])
                : (string) $props['series'];
        }
        if (isset($props['number'])) {
            $this->number = is_array($props['number'])
                ? implode('', $props['number'])
                : (string) $props['number'];
        }
        if (array_key_exists('region', $props) && $props['region'] !== null && $props['region'] !== '') {
            $this->region = is_array($props['region'])
                ? implode('', $props['region'])
                : (string) $props['region'];
        }
    }

    private function fingerprintFromStoredState(): string
    {
        $snap = new self();
        $snap->type = $this->getOriginal('type');
        $snap->series = $this->getOriginal('series');
        $snap->number = $this->getOriginal('number');
        $snap->region = $this->getOriginal('region');
        $origProps = $this->getOriginal('props');
        if (is_string($origProps)) {
            $decoded = json_decode($origProps, true);
            $snap->props = is_array($decoded) ? $decoded : [];
        } else {
            $snap->props = is_array($origProps) ? $origProps : [];
        }

        return (new PlatePhotoService())->fingerprint($snap);
    }


    public function getLinkAttribute(){
        $arr[] = 'gossnumbers';
        $arr[] = $this->id;
        $url = implode('/', $arr);
        return url($url);
    }

    public function getCabinetLinkAttribute()
    {
        return url('cabinet/obyavleniya/nomer/' . $this->id);
    }

    public function getSeriesPartsAttribute()
    {
        $parts = [];
        for ($i = 0; $i < 3; $i++) {
            $parts[$i] = $this->propPart('series', $i, $this->series);
        }
        return $parts;
    }

    public function getNumberPartsAttribute()
    {
        $parts = [];
        for ($i = 0; $i < 5; $i++) {
            $parts[$i] = $this->propPart('number', $i, $this->number);
        }
        return $parts;
    }

    public function getRegionValueAttribute()
    {
        $props = is_array($this->props) ? $this->props : [];
        if (isset($props['region']) && $props['region'] !== '' && $props['region'] !== null) {
            return is_array($props['region']) ? implode('', $props['region']) : (string) $props['region'];
        }

        return (string) $this->region;
    }

    private function propPart($key, $index, $fallback = '')
    {
        $props = is_array($this->props) ? $this->props : [];
        if (isset($props[$key]) && is_array($props[$key]) && array_key_exists($index, $props[$key])) {
            return (string) $props[$key][$index];
        }

        return (string) mb_substr((string) $fallback, (int) $index, 1);
    }

    public function getCarNumberAttribute()
    {
        $props = is_array($this->props) ? $this->props : [];
        $series = '';

        if (!empty($props['series'])) {
            $series = is_array($props['series']) ? implode('', $props['series']) : (string) $props['series'];
        } elseif ($this->series) {
            $series = (string) $this->series;
        }

        $number = '';
        if (!empty($props['number'])) {
            $number = is_array($props['number']) ? implode('', $props['number']) : (string) $props['number'];
        } elseif ($this->number) {
            $number = (string) $this->number;
        }

        $region = '';
        if (!empty($props['region'])) {
            $region = is_array($props['region']) ? implode('', $props['region']) : (string) $props['region'];
        } elseif ($this->region) {
            $region = (string) $this->region;
        }

        $type = (int) $this->type;

        if ($type === 2) {
            $parts = array_filter([$number, $series, $region], function ($part) {
                return trim((string) $part) !== '';
            });
        } elseif ($type === 3) {
            $parts = array_filter([$series, $number, $region], function ($part) {
                return trim((string) $part) !== '';
            });
        } else {
            $parts = array_filter([
                mb_substr($series, 0, 1),
                $number,
                mb_substr($series, 1, 2),
                $region,
            ], function ($part) {
                return trim((string) $part) !== '';
            });
        }

        return $parts ? implode(' ', $parts) : ($this->num ?: '');
    }

    public $attachOne = [
        'photo' => \System\Models\File::class
    ];
    
    public function getTypeOptions(){
        $options[1] = 'Авто';
        $options[2] = 'Мото';
        $options[3] = 'Прицеп';
        return $options;
    }

    // Действие
    public function getTypePayOptions(){
        $options[1] = 'Продам';
        $options[2] = 'Куплю';
        return $options;    
    }

    // Фиксированная цена
    public function getFixPriceAttribute(){

        $types['sum_start'] = 'от';
        $types['sum_end'] = 'до';

        $prices['sum_start'] = $this->sum_start;
        $prices['sum_end'] = $this->sum_end;

        $rows = array();
        foreach($prices as $key => $price){
            if(trim($price)){
                $rows[$key] = $types[$key] .' '. $price . 'руб.';
            }    
        }

        return implode(' ', $rows);
    }

    // Адрес собственника
    public function getFullAddressAttribute(){

        $types['index'] = '';
        $types['region'] = '';
        $types['city'] = 'г. ';
        $types['street'] = 'ул. ';
        $types['house'] = 'дом ';
        $types['building'] = 'строение ';
        $types['room'] = 'кв. ';

        $arr = array();
        foreach($this->address as $key => $row){
            if(trim($row)){
                $arr[] = $types[$key] . $row;    
            }
        }

        return collect($arr);
    }

}
