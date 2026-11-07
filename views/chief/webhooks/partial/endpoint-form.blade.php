{{-- The fields of an endpoint, shared by adding and editing one. Needs $eventGroups and the component's url, description, eventMode and events. --}}
<div class="space-y-4">
    <flux:field>
        <flux:label>Endpoint URL</flux:label>
        <flux:input wire:model="url" type="url" placeholder="https://example.com/webhooks/chief" class="font-mono" autocomplete="off" />
        <flux:description>Must use HTTPS and be reachable from the internet. Deliveries are POST requests with a JSON body.</flux:description>
        <flux:error name="url" />
    </flux:field>

    <flux:field>
        <flux:label>Description <flux:badge size="sm" class="ml-1">Optional</flux:badge></flux:label>
        <flux:input wire:model="description" placeholder="What receives these events" />
        <flux:error name="description" />
    </flux:field>

    <flux:field>
        <flux:label>Events</flux:label>
        <flux:radio.group wire:model.live="eventMode" variant="segmented">
            <flux:radio value="all" label="All events" />
            <flux:radio value="selected" label="Selected events" />
        </flux:radio.group>
        @if($eventMode === 'all')
            <flux:description>Every event, including ones added later.</flux:description>
        @endif
        <flux:error name="events" />
    </flux:field>

    @if($eventMode === 'selected')
        {{-- One group for every event, so choosing in one heading keeps the choices under the others --}}
        <flux:checkbox.group wire:model="events" class="max-h-80 space-y-4 overflow-y-auto rounded-lg border border-line p-4">
            @foreach($eventGroups as $group => $types)
                <div class="space-y-2" wire:key="event-group-{{ \Illuminate\Support\Str::slug($group) }}">
                    <flux:heading size="sm">{{ $group }}</flux:heading>
                    @foreach($types as $type)
                        <flux:checkbox :value="$type->value" :label="$type->value" :description="$type->description()" wire:key="event-{{ $type->value }}" />
                    @endforeach
                </div>
            @endforeach
        </flux:checkbox.group>
    @endif
</div>
