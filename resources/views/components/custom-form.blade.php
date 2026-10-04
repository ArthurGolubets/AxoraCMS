{{--
    Ready-made markup for a custom form ("Своя форма"):

        <x-axora-cms::custom-form code="feedback" />
        <x-axora-cms::custom-form code="feedback" submit-text="Отправить заявку" class="my-form" />

    Publish the views (php artisan vendor:publish --tag=axora-cms-views) to restyle it.
--}}
@props(['code', 'submitText' => 'Отправить'])

@php
    $form = app(\HolartWeb\AxoraCMS\Services\CustomFormService::class)->findForm($code);
    $success = session('axora_form_success');
@endphp

@if ($form)
    <form method="POST" action="{{ route('axora-cms.forms.submit', $form->code) }}" enctype="multipart/form-data" {{ $attributes->merge(['class' => 'axora-custom-form']) }}>
        @csrf

        @if (($success['code'] ?? null) === $form->code)
            <div class="axora-custom-form__success">{{ $success['message'] }}</div>
        @endif

        {{-- Honeypot: hidden from people, filled by bots --}}
        <div style="position:absolute;left:-10000px" aria-hidden="true">
            <input type="text" name="{{ \HolartWeb\AxoraCMS\Services\CustomFormService::HONEYPOT_FIELD }}" tabindex="-1" autocomplete="off">
        </div>

        @foreach ($form->fields as $field)
            @php
                $name = $field->code . ($field->is_multiple ? '[]' : '');
                $id = 'axora-' . $form->code . '-' . $field->code;
                $inputType = ['email' => 'email', 'phone' => 'tel', 'number' => 'number', 'date' => 'date'][$field->type] ?? 'text';
                $oldValue = old($field->is_multiple ? $field->code.'.0' : $field->code);
            @endphp

            @continue(in_array($field->type, ['entity', 'user'], true))

            <div class="axora-custom-form__field">
                @if ($field->type === 'bool')
                    <label>
                        <input type="hidden" name="{{ $field->code }}" value="0">
                        <input type="checkbox" name="{{ $field->code }}" value="1" @checked(old($field->code)) @required($field->is_required)>
                        {{ $field->name }}
                    </label>
                @else
                    <label for="{{ $id }}">{{ $field->name }}@if ($field->is_required) *@endif</label>

                    @if ($field->type === 'text')
                        <textarea id="{{ $id }}" name="{{ $name }}" @required($field->is_required)>{{ $oldValue }}</textarea>
                    @elseif ($field->type === 'enum')
                        <select id="{{ $id }}" name="{{ $name }}" @if ($field->is_multiple) multiple @endif @required($field->is_required)>
                            @unless ($field->is_multiple)
                                <option value="">—</option>
                            @endunless
                            @foreach ($field->settings['options'] ?? [] as $option)
                                <option value="{{ $option['code'] }}" @selected(in_array($option['code'], (array) old($field->code), true))>{{ $option['title'] }}</option>
                            @endforeach
                        </select>
                    @elseif (in_array($field->type, ['image', 'file'], true))
                        <input id="{{ $id }}" type="file" name="{{ $name }}" @if ($field->type === 'image') accept="image/*" @endif @if ($field->is_multiple) multiple @endif @required($field->is_required)>
                    @else
                        <input id="{{ $id }}" type="{{ $inputType }}" name="{{ $name }}" value="{{ $oldValue }}" @required($field->is_required)>
                    @endif
                @endif

                @error($field->code)
                    <div class="axora-custom-form__error">{{ $message }}</div>
                @enderror
            </div>
        @endforeach

        <button type="submit">{{ $submitText }}</button>
    </form>
@endif
