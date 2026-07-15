<?php

namespace App\Livewire\Admin\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait StoresThumbnail
{
    abstract protected function disk(): string;

    abstract protected function thumbnailFolder(): string;

    protected function storeThumbnail(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension()) ?: 'jpg';

        return $file->storeAs(
            $this->thumbnailFolder(),
            Str::uuid()->toString().'.'.$extension,
            $this->disk(),
        );
    }

    protected function deleteThumbnail(?string $path): void
    {
        if ($path !== null && $path !== '' && Storage::disk($this->disk())->exists($path)) {
            Storage::disk($this->disk())->delete($path);
        }
    }
}
