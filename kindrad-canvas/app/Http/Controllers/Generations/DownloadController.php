<?php

namespace App\Http\Controllers\Generations;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function download(Request $request, Generation $generation): StreamedResponse|Response
    {
        Gate::authorize('download', $generation);

        abort_unless($generation->status()->where('slug', 'completed')->exists(), 404);
        abort_if($generation->result_path === null, 404);

        $disk = Storage::disk(config('generation.disk'));

        if (! $disk->exists($generation->result_path)) {
            return response()->view('errors.generation-file-missing', status: 404);
        }

        $extension = pathinfo($generation->result_path, PATHINFO_EXTENSION) ?: 'bin';
        $timestamp = ($generation->completed_at ?? $generation->created_at ?? now())->format('Ymd-His');
        $filename = "kindred-canvas-{$generation->project_id}-{$timestamp}.{$extension}";

        return $disk->download($generation->result_path, $filename, [
            'Content-Type' => $generation->result_mime_type ?? 'application/octet-stream',
        ]);
    }
}
