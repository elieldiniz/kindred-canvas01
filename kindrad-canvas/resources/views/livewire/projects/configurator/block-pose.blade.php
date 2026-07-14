<x-blocks.card
    icon="directions_walk"
    title="Pose"
    helper="Pick a pose for the subject(s)."
>
    <div class="flex flex-wrap gap-2" data-test="block-pose-grid">
        @foreach ($poses as $pose)
            <x-blocks.selection-card
                wire-click="$dispatch('pose-selected', { poseId: {{ $pose->id }} })"
                :icon="match ($pose->slug) { 'abracados' => 'favorite', 'beijo' => 'favorite_border', 'sentados' => 'weekend', 'caminhando' => 'directions_walk', 'natal' => 'ac_unit', 'praia' => 'beach_access', 'sofa' => 'weekend', 'flores' => 'local_florist', default => 'person' }"
                :name="$pose->name"
                :selected="$pose->id === $poseId"
                compact
                :test-id="'pose-card-'.$pose->slug"
            />
        @endforeach
    </div>
</x-blocks.card>