<?php

namespace App\Livewire\Projects\Configurator;

use App\Models\Project;
use App\Models\SourceImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class BlockPhotos extends Component
{
    use WithFileUploads;

    public ?int $projectId = null;

    public ?string $subjectType = null;

    public ?int $slotCount = null;

    public ?string $photo0 = null;

    public ?string $photo1 = null;

    public function mount(?int $projectId = null, ?string $subjectType = null): void
    {
        $this->projectId = $projectId;
        $this->subjectType = $subjectType;
        $this->slotCount = in_array($subjectType, ['casal', 'familia'], true) ? 2 : 1;
        $this->refreshPreviews();
    }

    public function updatedSubjectType(string $value): void
    {
        $this->subjectType = $value;
        $this->slotCount = in_array($value, ['casal', 'familia'], true) ? 2 : 1;
    }

    public function updatedPhoto0(): void
    {
        $this->upload(0);
    }

    public function updatedPhoto1(): void
    {
        $this->upload(1);
    }

    public function upload(int $slot): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $property = $slot === 0 ? 'photo0' : 'photo1';
        $file = $this->{$property};

        if (! $file instanceof TemporaryUploadedFile) {
            return;
        }

        $this->validate([
            $property => ['required', 'file', 'mimes:jpeg,png,webp', 'max:10240'],
        ], [], [$property => __('photo')]);

        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        $disk = config('filesystems.default');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $key = sprintf('source-images/%d/%s.%s', $user->id, (string) Str::uuid(), $extension);
        Storage::disk($disk)->putFileAs(dirname($key), $file->getRealPath(), basename($key));

        $sourceImage = SourceImage::create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $key,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'image/jpeg',
            'size_bytes' => $file->getSize(),
        ]);

        $project->photos()->updateOrCreate(
            ['position' => $slot],
            ['source_image_id' => $sourceImage->id],
        );

        $this->reset($property);
        $this->refreshPreviews();
    }

    public function removePhoto(int $slot): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $photo = $project->photos()->where('position', $slot)->first();

        if ($photo !== null) {
            $photo->delete();
        }

        $this->refreshPreviews();
    }

    public function render()
    {
        return view('livewire.projects.configurator.block-photos');
    }

    private function refreshPreviews(): void
    {
        $this->photo0 = null;
        $this->photo1 = null;

        if ($this->projectId === null) {
            return;
        }

        $project = Project::find($this->projectId);

        if ($project === null) {
            return;
        }

        foreach ($project->photos()->with('sourceImage')->get() as $photo) {
            $previewUrl = Storage::disk($photo->sourceImage->disk)->url($photo->sourceImage->path);
            $property = $photo->position === 0 ? 'photo0' : 'photo1';
            $this->{$property} = $previewUrl;
        }
    }

    private function authorizeUpdateOrAbort(): Project
    {
        if ($this->projectId === null) {
            abort(404);
        }

        $project = Project::find($this->projectId);

        if ($project === null || $project->trashed()) {
            abort(404);
        }

        $this->authorize('update', $project);

        if ($project->first_generated_at !== null) {
            abort(409);
        }

        return $project;
    }
}
