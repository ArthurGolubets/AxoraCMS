<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport\Exporters;

use HolartWeb\AxoraCMS\Models\Callback\TComments;
use HolartWeb\AxoraCMS\Models\Callback\TUserRequests;
use HolartWeb\AxoraCMS\Models\Callback\TUsersEmails;
use HolartWeb\AxoraCMS\Models\TModule;
use InvalidArgumentException;

/**
 * Records of the "Обратная связь" module.
 *
 * Options: kind ("comments" | "user_requests" | "subscriptions"), date_from, date_to.
 */
class CallbackExport extends AbstractExportSource
{
    public const KINDS = [
        'comments' => 'Комментарии',
        'user_requests' => 'Обращения',
        'subscriptions' => 'Подписки',
    ];

    public function key(): string
    {
        return 'callback';
    }

    public function label(): string
    {
        return 'Обратная связь';
    }

    public function available(): bool
    {
        return TModule::isInstalled('callback');
    }

    public function title(array $options): string
    {
        return 'Экспорт: '.self::KINDS[$this->kind($options)];
    }

    public function headers(array $options): array
    {
        return match ($this->kind($options)) {
            'comments' => ['ID', 'Дата', 'Имя', 'Телефон', 'Email', 'Комментарий', 'Оценка', 'ID товара', 'Товар', 'Опубликован'],
            'user_requests' => ['ID', 'Дата', 'Имя', 'Email', 'Телефон', 'Сообщение', 'Просмотрено'],
            'subscriptions' => ['ID', 'Дата', 'Email', 'Статус'],
        };
    }

    public function total(array $options): int
    {
        return $this->query($options)->count();
    }

    public function rows(array $options, int $offset, int $limit): array
    {
        $kind = $this->kind($options);
        $query = $this->query($options)->orderBy('id')->skip($offset)->take($limit);

        if ($kind === 'comments') {
            $query->with('product:id,name');
        }

        return $query->get()->map(fn ($record) => match ($kind) {
            'comments' => [$record->id, $this->date($record->created_at), $record->name, $record->phone, $record->email,
                $record->comment, $record->rating, $record->product_id, $record->product?->name, $this->yesNo($record->is_moderated)],
            'user_requests' => [$record->id, $this->date($record->created_at), $record->name, $record->email, $record->phone,
                $record->comment, $this->yesNo($record->viewed_at)],
            'subscriptions' => [$record->id, $this->date($record->created_at), $record->email, $record->status],
        })->all();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function query(array $options)
    {
        $model = match ($this->kind($options)) {
            'comments' => TComments::query(),
            'user_requests' => TUserRequests::query(),
            'subscriptions' => TUsersEmails::query(),
        };

        return $this->applyDateRange($model, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function kind(array $options): string
    {
        $kind = (string) ($options['kind'] ?? '');
        if (! isset(self::KINDS[$kind])) {
            throw new InvalidArgumentException('Неизвестный тип записей обратной связи');
        }

        return $kind;
    }
}
