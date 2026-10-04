<?php

namespace HolartWeb\AxoraCMS\Models\Callback;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A field of a custom form.
 */
class TCustomFormField extends Model
{
    protected $table = 't_custom_form_fields';

    protected $fillable = [
        'form_id',
        'code',
        'name',
        'type',
        'is_required',
        'is_multiple',
        'sort',
        'settings',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_multiple' => 'boolean',
        'sort' => 'integer',
        'settings' => 'array',
    ];

    /**
     * Supported field types => labels.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'string' => 'Строка',
        'text' => 'Текст',
        'email' => 'Email',
        'phone' => 'Телефон',
        'number' => 'Число',
        'date' => 'Дата',
        'bool' => 'Да/Нет',
        'enum' => 'Список',
        'image' => 'Изображение',
        'file' => 'Файл',
        'entity' => 'Привязка к элементу',
        'user' => 'Привязка к пользователю',
    ];

    /**
     * Types that cannot hold several values.
     *
     * @var array<int, string>
     */
    public const SINGLE_ONLY_TYPES = ['bool', 'user'];

    /**
     * Types whose value is an uploaded file stored on the public disk.
     *
     * @var array<int, string>
     */
    public const UPLOAD_TYPES = ['image', 'file'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(TCustomForm::class, 'form_id');
    }

    public function isUpload(): bool
    {
        return in_array($this->type, self::UPLOAD_TYPES, true);
    }
}
