<?php

namespace App\Livewire\Projects\Configurator;

use App\Models\Project;
use App\Models\SourceImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class BlockPhotos extends Component
{
    use WithFileUploads;

    public ?int $projectId = null;

    public ?string $subjectType = null;

    public ?int $slotCount = null;

    /**
     * Uploaded files held in Livewire's temporary storage until the user
     * clicks "Generate". Once persisted, the matching slot stores the
     * `SourceImage` id instead of a TemporaryUploadedFile.
     *
     * @var array<int, TemporaryUploadedFile|int|null> slot index => temp file or source_image_id
     */
    public array $photoSlots = [];

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

    public function updatedPhotoSlots(): void
    {
        // Triggered whenever any slot receives a file. Walk the slots,
        // validate each, and surface inline errors.
        foreach ($this->photoSlots as $slot => $value) {
            if (! $value instanceof TemporaryUploadedFile) {
                continue;
            }

            $this->resetValidation("photoSlots.{$slot}");

            try {
                $this->validateOnly(
                    "photoSlots.{$slot}",
                    ['photoSlots.'.$slot => ['required', 'file', 'mimes:jpeg,png,webp', 'max:10240']],
                    [],
                    ['photoSlots.'.$slot => __('photo')],
                );
            } catch (ValidationException $e) {
                $this->reset("photoSlots.{$slot}");

                throw $e;
            }
        }
    }

    public function removePhoto(int $slot): void
    {
        // Unset the slot so the dropzone returns to its empty state.
        unset($this->photoSlots[$slot]);

        // If the photo had already been persisted to S3 (e.g. user clicks
        // Generate, then comes back to remove), also delete the SourceImage.
        if ($this->projectId !== null) {
            $project = Project::find($this->projectId);

            if ($project !== null) {
                $photo = $project->photos()->where('position', $slot)->first();

                if ($photo !== null) {
                    $photo->sourceImage?->delete();
                    $photo->delete();
                }
            }
        }

        $this->refreshPreviews();
    }

    /**
     * Persist every pending temp upload into S3 and link it to the project.
     * Called by the parent Configurator right before SubmitGeneration runs.
     */
    public function persistPendingPhotos(): void
    {
        if ($this->projectId === null) {
            return;
        }

        $project = $this->authorizeUpdateOrAbort();

        $user = Auth::user();

        if ($user === null) {
            abort(401);
        }

        $disk = (string) config('generation.disk', config('filesystems.default'));

        foreach ($this->photoSlots as $slot => $value) {
            if (! $value instanceof TemporaryUploadedFile) {
                continue;
            }

            $extension = strtolower($value->getClientOriginalExtension() ?: 'jpg');
            $key = sprintf('source-images/%d/%s.%s', $user->id, (string) Str::uuid(), $extension);

            Storage::disk($disk)->putFileAs(dirname($key), $value->getRealPath(), basename($key));

            $sourceImage = SourceImage::create([
                'user_id' => $user->id,
                'disk' => $disk,
                'path' => $key,
                'original_filename' => $value->getClientOriginalName(),
                'mime_type' => $value->getMimeType() ?? 'image/jpeg',
                'size_bytes' => $value->getSize(),
            ]);

            $project->photos()->updateOrCreate(
                ['position' => $slot],
                ['source_image_id' => $sourceImage->id],
            );

            // Replace the temp file with the persisted id so refreshPreviews
            // can resolve the URL from S3 instead of the temp disk.
            $this->photoSlots[$slot] = $sourceImage->id;
        }

        $this->refreshPreviews();
    }

    public function render()
    {
        return view('livewire.projects.configurator.block-photos');
    }

    /**
     * @return array<int, string|null> slot index => preview URL
     */
    public function getPreviewUrls(): array
    {
        $urls = [];

        if ($this->projectId === null) {
            return $urls;
        }

        $project = Project::find($this->projectId);

        if ($project === null) {
            return $urls;
        }

        $persisted = $project->photos()->with('sourceImage')->get()->keyBy('position');

        foreach (range(0, max(1, $this->slotCount ?? 1) - 1) as $slot) {
            // Prefer a freshly-uploaded temp file for instant preview
            if (isset($this->photoSlots[$slot]) && $this->photoSlots[$slot] instanceof TemporaryUploadedFile) {
                try {
                    $urls[$slot] = $this->photoSlots[$slot]->temporaryUrl();

                    continue;
                } catch (\Throwable $e) {
                    $urls[$slot] = null;

                    continue;
                }
            }

            // Fall back to the persisted S3 URL once the user has clicked Generate
            if (isset($persisted[$slot])) {
                $photo = $persisted[$slot];
                $urls[$slot] = $photo->sourceImage
                    ? Storage::disk($photo->sourceImage->disk)->url($photo->sourceImage->path)
                    : null;

                continue;
            }

            $urls[$slot] = null;
        }

        return $urls;
    }

    public function refreshPreviews(): void
    {
        // Kept for backward compatibility with existing templates — the
        // actual preview rendering now goes through getPreviewUrls().
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
