<?php namespace Saybme\Ub\Models;

use Model;
use Str;

/**
 * Model
 */
class Consform extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\Sluggable;    

    public $slugs = ['slug' => 'title'];

    protected $jsonable = ['data'];

    /**
     * @var array dates to cast from the database.
     */
    protected $dates = ['deleted_at'];

    /**
     * @var string table in the database used by the model.
     */
    public $table = 'saybme_ub_consform';

    /**
     * @var array rules for validation.
     */
    public $rules = [
        'title' => 'required'
    ];

    public function beforeSave()
    {
        $this->ensureCodes();
    }

    public function getValueIdOptions()
    {
        return Formvalue::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('parent_id')->orWhere('nest_depth', 0);
            })
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }

    public function getBlocksAttribute()
    {
        return is_array($this->data) ? $this->data : [];
    }

    private function ensureCodes()
    {
        $blocks = is_array($this->data) ? $this->data : [];

        foreach ($blocks as $blockIndex => $block) {
            if (!is_array($block)) {
                continue;
            }

            if (empty($block['code'])) {
                $slug = Str::slug((string) ($block['title'] ?? ''));
                $blocks[$blockIndex]['code'] = $slug !== ''
                    ? 'block_' . $slug
                    : 'block_' . substr(md5(uniqid((string) $blockIndex, true)), 0, 8);
            }

            $fields = $block['fields'] ?? [];
            foreach ($fields as $fieldIndex => $field) {
                if (!is_array($field)) {
                    continue;
                }

                if (empty($field['code'])) {
                    $blocks[$blockIndex]['fields'][$fieldIndex]['code'] = 'ub' . substr(md5(uniqid((string) $fieldIndex, true)), 0, 12);
                }
            }
        }

        $this->data = $blocks;
    }

}
