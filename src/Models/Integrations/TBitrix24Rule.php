<?php

namespace HolartWeb\AxoraCMS\Models\Integrations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What to create in Bitrix24 when an event happens on the site.
 *
 * @property array{main?: array, contact?: array, company?: array}|null $mapping
 */
class TBitrix24Rule extends Model
{
    public const ENTITY_LEAD = 'lead';

    public const ENTITY_DEAL = 'deal';

    public const ENTITY_NONE = 'none';

    public const MODE_NONE = 'none';

    public const MODE_CREATE = 'create';

    public const MODE_FIND_OR_CREATE = 'find_or_create';

    public const ENTITIES = [self::ENTITY_LEAD, self::ENTITY_DEAL, self::ENTITY_NONE];

    public const MODES = [self::MODE_NONE, self::MODE_CREATE, self::MODE_FIND_OR_CREATE];

    protected $table = 't_bitrix24_rules';

    protected $fillable = [
        'name',
        'event',
        'form_code',
        'is_active',
        'entity',
        'contact_mode',
        'company_mode',
        'send_products',
        'mapping',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_products' => 'boolean',
        'mapping' => 'array',
        'sort' => 'integer',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(TBitrix24Log::class, 'rule_id');
    }
}
