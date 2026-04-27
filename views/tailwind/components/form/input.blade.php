@props([
    'name',
    'type' => 'text',
    'size' => 'regular',
    'rows' => 4,
    'mono' => false,
    'label' => false,
    'value' => null,
    'style' => null,
    'options' => [],
    'fromOld' => true,
    'checked' => false,
    'disabled' => false,
    'required' => false,
    'readonly' => false,
    'copyable' => false,
    'autofocus' => false,
    'maxlength' => null,
    'placeholder' => null,
    'autocomplete' => null,
    'togglePassword' => false,
    'withoutUnchecked' => false,
    'noPasswordManager' => false,
])

@php
    $hasError     = isset($errors) && $errors->has($name);
    $errorMessage = isset($errors) ? ($errors->get($name)[0] ?? null) : null;

    $labelClass = $hasError
        ? 'text-red'
        : 'text-fg-muted';
    $inputClass = $hasError
        ? 'bg-surface border-red text-fg placeholder-red/60 focus:ring-red focus:border-red'
        : 'bg-surface text-fg border-line-strong placeholder-fg-faint focus:ring-brand-500 focus:border-brand-500';

    if ($disabled) {
        $inputClass .= ' opacity-50 cursor-not-allowed';
    }

    if ($mono) {
        $inputClass .= ' font-mono';
    }

    $inputClass .= $copyable
        ? ' rounded-none rounded-l-md'
        : ' rounded-md';

    $sizeClass = [
        'lg'      => 'px-4 py-3',
        'regular' => 'px-3 py-2 sm:text-sm',
    ][$size];

    $value = $fromOld ? old($name, $value) : $value;
@endphp

@if($type === 'hidden')
    <input id="{{ $name }}"
           name="{{ $name }}"
           type="{{ $type }}"
           value="{{ $value }}"
           {{ $disabled ? 'disabled' : '' }}
           {{ $required ? 'required' : '' }}
           {{ $readonly ? 'readonly' : '' }}
    >
@else
    <div {{ $attributes->merge(['class' => 'mb-3 last:mb-0']) }} @if($maxlength || $togglePassword) x-data="{val: '', passwordReadable: false}" @endif>
        @if($label && $type !== 'checkbox')
            <label for="{{ $name }}" class="flex block text-sm font-medium {{ $labelClass }}">
                {{ $label }}{{ $required ? '*' : '' }}

                @if($maxlength)
                    <span class="ml-auto text-muted text-xs mt-1">
                        <span x-text="val ? val.length : 0"></span>/{{ $maxlength }}
                    </span>
                @endif
            </label>
        @endif
        <div class="mt-1 flex">
            <div class="relative flex items-stretch flex-grow focus-within:z-10">
                @if($type === 'checkbox')
                    <div class="flex items-start">
                        <div class="h-5 flex items-center">
                            @unless($withoutUnchecked)
                                <input type="checkbox"
                                       name="{{ $name }}"
                                       value="0"
                                       class="hidden"
                                       checked>
                            @endunless
                            <input id="{{ $name }}"
                                   type="checkbox"
                                   name="{{ $name }}"
                                   value="{{ $value ?? '1' }}"
                                   class="h-4 w-4 text-brand-600 bg-surface border-line-strong focus:ring-brand-500 rounded {{ $inputClass }}"
                                   {{ $style ? new Illuminate\Support\HtmlString("style='{$style}'") : '' }}
                                   {{ $checked ? 'checked' : '' }}
                                   {{ $disabled ? 'disabled' : '' }}
                                   {{ $required ? 'required' : '' }}
                                   {{ $readonly ? 'readonly' : '' }}
                                   {{ $autofocus ? 'autofocus' : '' }}
                                   {{ $autocomplete ? new Illuminate\Support\HtmlString("autocomplete='{$autocomplete}'") : '' }}
                                   {{ $noPasswordManager ? 'data-1p-ignore' : '' }}
                            >
                        </div>
                        @if($label)
                            <div class="ml-3 text-sm">
                                <label for="{{ $name }}" class="text-fg-muted">{{ $label }}</label>
                            </div>
                        @endif
                    </div>
                @elseif($type === 'textarea')
                    <textarea id="{{ $name }}"
                              name="{{ $name }}"
                              rows="{{ $rows }}"
                              class="appearance-none block w-full border shadow-xs focus:outline-hidden {{ $sizeClass }} {{ $inputClass }}"
                              {{ $style ? new Illuminate\Support\HtmlString("style='{$style}'") : '' }}
                              {{ $disabled ? 'disabled' : '' }}
                              {{ $required ? 'required' : '' }}
                              {{ $readonly ? 'readonly' : '' }}
                              {{ $autofocus ? 'autofocus' : '' }}
                              {!! $maxlength ? "maxlength='{$maxlength}' x-model.fill='val'" : '' !!}
                              {{ $placeholder ? new Illuminate\Support\HtmlString("placeholder='{$placeholder}'") : '' }}
                              {{ $autocomplete ? new Illuminate\Support\HtmlString("autocomplete='{$autocomplete}'") : '' }}
                              {{ $noPasswordManager ? 'data-1p-ignore' : '' }}
                    >{{ $value }}</textarea>
                @elseif($type === 'select')
                    <select id="{{ $name }}"
                            name="{{ $name }}"
                            class="block w-full border shadow-xs py-2 px-3 focus:outline-hidden {{ $sizeClass }} {{ $inputClass }}"
                            {{ $style ? new Illuminate\Support\HtmlString("style='{$style}'") : '' }}
                            {{ $disabled ? 'disabled' : '' }}
                            {{ $required ? 'required' : '' }}
                            {{ $readonly ? 'readonly' : '' }}
                            {{ $autofocus ? 'autofocus' : '' }}
                            {{ $placeholder ? new Illuminate\Support\HtmlString("placeholder='{$placeholder}'") : '' }}
                            {{ $autocomplete ? new Illuminate\Support\HtmlString("autocomplete='{$autocomplete}'") : '' }}
                            {{ $noPasswordManager ? 'data-1p-ignore' : '' }}
                    >
                        @foreach($options as $optionValue => $option)
                            <option value="{{ $optionValue }}"{{ $optionValue === $value ? ' selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="{{ $name }}"
                           name="{{ $name }}"
                           type="{{ $type }}"
                           {!! $type === 'password' && $togglePassword ? ':type="passwordReadable ? \'text\' : \'password\'"' : '' !!}
                           value="{{ $value }}"
                           class="appearance-none block w-full border shadow-xs focus:outline-hidden {{ $sizeClass }} {{ $inputClass }}"
                           {{ $style ? new Illuminate\Support\HtmlString("style='{$style}'") : '' }}
                           {{ $disabled ? 'disabled' : '' }}
                           {{ $required ? 'required' : '' }}
                           {{ $readonly ? 'readonly' : '' }}
                           {{ $autofocus ? 'autofocus' : '' }}
                           {!! $maxlength ? "maxlength='{$maxlength}' x-model.fill='val'" : '' !!}
                           {{ $placeholder ? new Illuminate\Support\HtmlString("placeholder='{$placeholder}'") : '' }}
                           {{ $autocomplete ? new Illuminate\Support\HtmlString("autocomplete='{$autocomplete}'") : '' }}
                           {{ $noPasswordManager ? 'data-1p-ignore' : '' }}
                    >

                    @if($type === 'password' && $togglePassword)
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="passwordReadable = !passwordReadable">
                            <i class="fa fa-fw fa-eye text-fg-subtle" :class="{'block': !passwordReadable, 'hidden': passwordReadable}"></i>
                            <i class="fa fa-fw fa-eye-slash text-fg-subtle" :class="{'block': passwordReadable, 'hidden': !passwordReadable}"></i>
                        </div>
                    @endif
                @endif

                @if($hasError)
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fa fa-fw fa-exclamation-circle text-red"></i>
                    </div>
                @endif
            </div>

            @if($copyable)
                <button data-clipboard="#{{ $name }}" type="button" class="-ml-px relative inline-flex items-center space-x-2 px-4 py-2 border border-line-strong text-sm font-medium rounded-r-md text-fg-muted bg-surface-2 hover:bg-surface-3 focus:outline-hidden focus:ring-1 focus:ring-brand-500 focus:border-brand-500">
                    <i class="fal fa-fw fa-copy"></i>
                </button>
            @endif
        </div>

        @if(isset($slot) && ($slot instanceof Illuminate\Support\HtmlString || $slot instanceof Illuminate\View\ComponentSlot) && $slot->isNotEmpty())
            <p class="mt-1 text-sm text-fg-subtle">{{ $slot }}</p>
        @endif

        @if($hasError)
            <p class="mt-2 text-sm text-red">{{ $errorMessage }}</p>
        @endif
    </div>
@endif
