@props([
    'name',
    'label',
    'type' => 'text',
    'size' => 'regular',
    'class' => 'mb-3 last:mb-0',
    'value' => null,
    'options' => [],
    'disabled' => false,
    'required' => false,
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

    $sizeClass = [
        'lg'      => 'px-4 py-3',
        'regular' => 'px-3 py-2 sm:text-sm',
    ][$size];
@endphp

<div class="{{ $class }}">
    <label for="{{ $name }}" class="block text-sm font-medium {{ $labelClass }}">{{ $label }}{{ $required ? '*' : '' }}</label>
    <div class="mt-1 relative">
        @if($type === 'textarea')
            <textarea id="{{ $name }}"
                      name="{{ $name }}"
                      rows="4"
                      class="appearance-none block w-full border rounded-md shadow-sm placeholder-gray-400 focus:outline-none {{ $sizeClass }} {{ $inputClass }}"
                      {{ $disabled ? 'disabled' : '' }}
                      {{ $required ? 'required' : '' }}
                      {{ $autofocus ? 'autofocus' : '' }}
                      {{ $autocomplete ? "autocomplete='{$autocomplete}'" : '' }}
            >{{ old($name, $value) }}</textarea>
        @elseif($type === 'select')
            <select id="country"
                    name="country"
                    autocomplete="country"
                    class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-gray-900 focus:border-gray-900 sm:text-sm"
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
                   class="appearance-none block w-full border rounded-md shadow-sm placeholder-gray-400 focus:outline-none {{ $sizeClass }} {{ $inputClass }}"
                   {{ $disabled ? 'disabled' : '' }}
                   {{ $required ? 'required' : '' }}
                   {{ $autofocus ? 'autofocus' : '' }}
                   {{ $autocomplete ? "autocomplete='{$autocomplete}'" : '' }}>
        @endif

        @if($hasError)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <i class="fa fa-fw fa-exclamation-circle text-red-500"></i>
            </div>
        @endif

        @if($slot)
            <p class="ml-1 mt-1 text-sm text-gray-500">{{ $slot }}</p>
        @endif
    </div>
    @if($hasError)
        <p class="mt-2 text-sm text-red-600">{{ $errorMessage }}</p>
    @endif
</div>
