<?php

namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Model;

class EntityData extends Model
{
    /**
     * The table associated with the model will be always Entity.
     *
     * @var string
     */

    public static $contentFields = [];
    public static $dataFields = [];

    /**
     * The primary key
     */
    protected $primaryKey = 'entity_id';
    public $incrementing = false;


    /**
     * Indicates  the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the Entity that owns the data.
     */
    public function entity()
    {
        return $this->belongsTo('Cuatromedios\Kusikusi\Models\Entity');
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

}
