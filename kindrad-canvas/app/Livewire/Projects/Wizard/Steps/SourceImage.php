<?php

namespace App\Livewire\Projects\Wizard\Steps;

use App\Models\Project;
use App\Models\SourceImage as SourceImageModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class SourceImage extends Component
{
    use WithFileUploads;

    public int $projectId;

    public ?int $sourceImageId = null;

    /**
     * @var TemporaryUploadedFile|null
     */
    public $photo = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'mimes:jpeg,png,webp', 'max:10240'],
        ];
    }

    public function mount(int $projectId, ?int $sourceImageId = null): void
    {
        $this->projectId = $projectId;
        $this->sourceImageId = $sourceImageId;
    }

    public function previewUrl(): ?string
    {
        if ($this->sourceImageId === null) {
            return null;
        }

        $image = SourceImageModel::find($this->sourceImageId);

        if ($image === null) {
            return null;
        }

        return Storage::disk($image->disk)->url($image->path);
    }

    public function replace(): void
    {
        $this->reset('photo');
        $this->resetValidation();
    }

    #[On('source-image-removed')]
    public function removeLocalPreview(): void
    {
        $this->reset('photo');
        $this->sourceImageId = null;
        $this->resetValidation();
    }

    public function updatedPhoto(): void
    {
        $this->upload();
    }

    public function upload(): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(401);
        }

        $project = Project::find($this->projectId);

        if ($project === null || $project->trashed()) {
            abort(404);
        }

        $this->authorize('update', $project);

        $this->validate();

        $extension = strtolower((string) ($this->photo->getClientOriginalExtension() ?: 'jpg'));

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }

        $key = sprintf('source-images/%d/%s.%s', $user->id, (string) Str::uuid(), $extension);
        $realPath = $this->photo->getRealPath();

        if ($realPath === false) {
            $this->addError('photo', __('Cannot read uploaded file.'));

            return;
        }

        Storage::disk('s3')->putFileAs(
            dirname($key),
            $realPath,
            basename($key),
        );

        $image = SourceImageModel::create([
            'user_id' => $user->id,
            'disk' => 's3',
            'path' => $key,
            'original_filename' => $this->photo->getClientOriginalName(),
            'mime_type' => $this->photo->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $this->photo->getSize(),
        ]);

        $project->source_image_id = $image->id;
        $project->save();

        $this->reset('photo');
        $this->sourceImageId = $image->id;
        $this->resetValidation();

        $this->dispatch('source-image-uploaded', sourceImageId: $image->id);
    }

    public function remove(): void
    {
        $project = Project::find($this->projectId);

        if ($project !== null) {
            if ($project->trashed()) {
                abort(404);
            }

            $this->authorize('update', $project);
            $project->source_image_id = null;
            $project->save();
        } else {
            abort(404);
        }

        $this->dispatch('source-image-removed');
    }

    public function render()
    {
        return view('livewire.projects.wizard.steps.source-image');
    }
}
