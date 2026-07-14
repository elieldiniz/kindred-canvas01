<x-blocks.card
    icon="group"
    title="Subject type"
    helper="Who is in the photo?"
>
    <div class="flex flex-wrap gap-2" data-test="block-subject-type-grid">
        @foreach ($types as $type)
            <x-blocks.selection-card
                wire-click="$dispatch('subject-type-selected', { type: '{{ $type['value'] }}' })"
                :icon="$type['icon']"
                :name="$type['name']"
                :selected="$type['value'] === $subjectType"
                compact
                :test-id="'subject-type-card-'.$type['value']"
            />
        @endforeach
    </div>
</x-blocks.card>