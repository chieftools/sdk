@props([
    'name',
    'type' => 'text',
    'size' => 'regular',
    'class' => 'mb-3 last:mb-0',
    'label' => false,
    'value' => null,
    'options' => [],
    'disabled' => false,
    'required' => false,
    'readonly' => false,
    'copyable' => false,
    'autofocus' => false,
    'autocomplete' => null,
])

@php
    $hasError     = $errors->has($name);
    $errorMessage = $errors->get($name)[0] ?? null;

    $labelClass = $hasError
        ? 'text-red-700'
        : 'text-gray-700';
    $inputClass = $hasError
        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
        : 'border-gray-300 focus:ring-brand-500 focus:border-brand-500';

    if ($disabled) {
        $inputClass .= ' opacity-50 cursor-not-allowed';
    }

    $inputClass .= $copyable
        ? ' rounded-none rounded-l-md'
        : ' rounded-md';

    $sizeClass = [
        'lg'      => 'px-4 py-3',
        'regular' => 'px-3 py-2 sm:text-sm',
    ][$size];
@endphp

<div class="{{ $class }}">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium {{ $labelClass }}">{{ $label }}{{ $required ? '*' : '' }}</label>
    @endif
    <div class="mt-1 flex">
        <div class="relative flex items-stretch flex-grow focus-within:z-10">
            @if($type === 'textarea')
                <textarea id="{{ $name }}"
                          name="{{ $name }}"
                          rows="4"
                          class="appearance-none block w-full border shadow-sm placeholder-gray-400 focus:outline-none {{ $sizeClass }} {{ $inputClass }}"
                          {{ $disabled ? 'disabled' : '' }}
                          {{ $required ? 'required' : '' }}
                          {{ $readonly ? 'readonly' : '' }}
                          {{ $autofocus ? 'autofocus' : '' }}
                          {{ $autocomplete ? "autocomplete='{$autocomplete}'" : '' }}
                >{{ old($name, $value) }}</textarea>
            @elseif($type === 'select')
                <select id="{{ $name }}"
                        name="{{ $name }}"
                        class="mt-1 block w-full bg-white border border-gray-300 shadow-sm py-2 px-3 focus:outline-none {{ $sizeClass }} {{ $inputClass }}"
                        {{ $disabled ? 'disabled' : '' }}
                        {{ $required ? 'required' : '' }}
                        {{ $readonly ? 'readonly' : '' }}
                        {{ $autofocus ? 'autofocus' : '' }}
                        {{ $autocomplete ? "autocomplete='{$autocomplete}'" : '' }}
                >
                    @foreach($options as $optionValue => $option)
                        <option value="{{ $optionValue }}"{{ $optionValue === old($name, $value) ? ' selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            @else
                <input id="{{ $name }}"
                       name="{{ $name }}"
                       type="{{ $type }}"
                       value="{{ old($name, $value) }}"
                       class="appearance-none block w-full border shadow-sm placeholder-gray-400 focus:outline-none {{ $sizeClass }} {{ $inputClass }}"
                       {{ $disabled ? 'disabled' : '' }}
                       {{ $required ? 'required' : '' }}
                       {{ $readonly ? 'readonly' : '' }}
                       {{ $autofocus ? 'autofocus' : '' }}
                       {{ $autocomplete ? "autocomplete='{$autocomplete}'" : '' }}>
            @endif

            @if($hasError)
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fa fa-fw fa-exclamation-circle text-red-500"></i>
                </div>
            @endif
        </div>

        @if($copyable)
            <button data-clipboard="#{{ $name }}" type="button" class="-ml-px relative inline-flex items-center space-x-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500">
                <i class="fal fa-fw fa-copy"></i>
            </button>
        @endif
    </div>

    @if($slot)
        <p class="ml-1 mt-1 text-sm text-gray-500">{{ $slot }}</p>
    @endif

    @if($hasError)
        <p class="mt-2 text-sm text-red-600">{{ $errorMessage }}</p>
    @endif
</div>
