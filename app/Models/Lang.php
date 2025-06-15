<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lang extends Model
{

    use HasFactory;

    protected $table = "langs";
    protected $primaryKey = 'id';

    protected $fillable = [
        'uid',
        'title',
        'iso_code',
        'lenguage_code',
        'locate',
        'date_format_full',
        'date_format_lite',
        'available',
        'created_at',
        'updated_at'
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
{
        return $query->where('uid', $uid)->first();
}

    public function scopeIso($query, $iso)
    {
        return $query->where('iso_code', $iso)->first();
    }

    public function scopeLocate($query, $iso)
    {
        return $query->where('locate', $iso)->first();
    }


    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public static function getSelectOptions()
    {
        $options = self::available()->get()->map(function ($item) {
            return ['value' => $item->id, 'text' => $item->name];
        });

        // japan only en and ja
        if (config('custom.japan')) {
            $options = self::active()->get()->filter(function ($item) {
                return in_array($item->code, ['en','ja']);
            })->map(function ($item) {
                return ['value' => $item->id, 'text' => $item->name];
            });
        }

        return $options;
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public function scopeSearch($query, $keyword)
    {
        // Keyword
        if (!empty(trim($keyword))) {
            $keyword = trim($keyword);
            foreach (explode(' ', $keyword) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('languages.name', 'like', '%'.$keyword.'%')
                        ->orwhere('languages.code', 'like', '%'.$keyword.'%')
                        ->orwhere('languages.region_code', 'like', '%'.$keyword.'%');
                });
            }
        }
    }

    public function getBuilderLang()
    {
        return include $this->languageDir() . DIRECTORY_SEPARATOR . 'builder.php';
    }

    public function languageDir()
    {
        return resource_path(join_paths('lang', $this->iso_code));
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Categorie', 'lang_categorie', 'lang_id', 'categorie_id');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Country', 'countries_lang', 'lang_id', 'country_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Product', 'product_lang', 'lang_id', 'product_id');
    }

}
