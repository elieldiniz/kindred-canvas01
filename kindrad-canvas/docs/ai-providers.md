# AI Image Generation Providers — Dev and Production

Kindred Canvas can generate artwork using one of three providers. The provider is selected by `config('generation.provider')` (which reads `GENERATION_PROVIDER` from `.env`), and the lookup table `generation_providers` lets an admin disable a provider at runtime without code changes.

## Current implementation status

| Provider | Code status | Lookup table status | Config block in `config/generation.php` |
|---|---|---|---|
| **OpenAI** (DALL-E 3, GPT Image 1) | ✅ Fully implemented | ✅ Row seeded, `is_active=true` by default | ✅ Full config (api_key, model, size, endpoint, timeout) |
| **Google Gemini** (Imagen) | ❌ Stub — throws `LogicException` | ✅ Row seeded, `is_active=false` | ❌ Not configured |
| **Replicate** (open-source models) | ❌ Stub — throws `LogicException` | ✅ Row seeded, `is_active=false` | ❌ Not configured |

When `OPENAI_API_KEY` (or whichever provider's key) is **empty**, the provider throws a `RuntimeException` at generation time. The `GenerateArtworkJob` catches this and writes a refund ledger row, so a failed generation still returns the credit to the user — it just doesn't produce an image.

---

## The 3 providers at a glance

| Provider | Default model | API base | Image format | Min credit cost | Free tier |
|---|---|---|---|---|---|
| **OpenAI** | `dall-e-3` (or `gpt-image-1`) | `https://api.openai.com/v1/images/generations` | PNG via `b64_json` | Pre-paid credits | None |
| **Google Gemini** (Imagen) | `imagen-3.0-generate-002` | Vertex AI or AI Studio | PNG via `b64_json` | Per-image pricing | AI Studio has a free tier |
| **Replicate** (open-source) | Any community model (FLUX, SDXL, etc.) | `https://api.replicate.com/v1/predictions` | URL to PNG (then downloaded) | Per-second GPU pricing | Limited free credits on signup |

---

## 1. OpenAI (the only one that works today)

The `OpenAIProvider` class is already wired: it POSTs to `/v1/images/generations`, accepts `response_format: b64_json`, stores the binary to S3/MinIO via `Storage::disk(config('generation.disk'))`, and returns the metadata to the job.

### 1.1 Get the API key

1. Open https://platform.openai.com/api-keys.
2. Click **Create new secret key** → name it `kindred-canvas` → copy the `sk-…` value.
3. OpenAI accounts start with $5 in free credit for the first 3 months. After that, image generation costs ~$0.04/image for DALL-E 3 standard, ~$0.08 for HD.

### 1.2 `.env` values

```dotenv
GENERATION_PROVIDER=openai
OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxxxxx
OPENAI_IMAGE_MODEL=dall-e-3
OPENAI_IMAGE_SIZE=1024x1024
OPENAI_IMAGE_ENDPOINT=https://api.openai.com/v1/images/generations
OPENAI_IMAGE_TIMEOUT=120
```

Field reference:

- `GENERATION_PROVIDER` — which provider's class to instantiate (`openai`, `gemini`, or `replicate`).
- `OPENAI_API_KEY` — your `sk-…` key. Without it, `OpenAIProvider::generate()` throws at runtime.
- `OPENAI_IMAGE_MODEL` — `dall-e-3` (default), `dall-e-2`, or `gpt-image-1` (newer multimodal model). Both `dall-e-3` and `gpt-image-1` accept `b64_json`.
- `OPENAI_IMAGE_SIZE` — `1024x1024`, `1024x1792`, or `1792x1024` for DALL-E 3; `gpt-image-1` also accepts `1536x1024` and `1024x1536`.
- `OPENAI_IMAGE_ENDPOINT` — leave default unless you proxy through another service.
- `OPENAI_IMAGE_TIMEOUT` — seconds; DALL-E 3 can take up to 60s.

### 1.3 Switching to gpt-image-1 (recommended for higher quality)

GPT Image 1 returns more realistic outputs and supports larger sizes. To switch:

```dotenv
OPENAI_IMAGE_MODEL=gpt-image-1
OPENAI_IMAGE_SIZE=1536x1024
```

Pricing is higher (~$0.04–$0.25 per image depending on size/quality). See https://openai.com/pricing.

### 1.4 Verify

```bash
docker exec kindrad-canvas-laravel.test-1 php artisan tinker --execute='
  app(App\Services\Generation\ProviderRegistry::class)
      ->resolveActive()
      ->generate("a small watercolor heart, soft palette", ["print_width_mm" => 100, "print_height_mm" => 100, "dpi" => 300]);
'
```

A successful run stores a binary at `generations/<uuid>.png` in your MinIO bucket and returns a `GenerationResult` with `path`, `mime`, `width`, `height`.

---

## 2. Google Gemini (Imagen) — stub today, would work tomorrow

The `GeminiProvider` class is a 30-line stub. To enable it, two things must happen:

1. **Implement the provider.** Replace the `generate()` body with a call to the Imagen API. Two API surfaces are available:
   - **Vertex AI** (`https://<region>-aiplatform.googleapis.com/v1/projects/<project>/locations/<region>/publishers/google/models/<model>:predict`) — production-grade, requires a GCP project + service account JSON key.
   - **AI Studio** (`https://generativelanguage.googleapis.com/v1beta/models/<model>:generateContent`) — easier setup, has a free tier, but the Imagen image endpoint is on Vertex AI specifically.
2. **Add config keys.** Extend `config/generation.php` with a `gemini` block (mirror of `openai`), then add `GEMINI_API_KEY` / `GEMINI_PROJECT_ID` / `GEMINI_LOCATION` to `.env`.

Then `GENERATION_PROVIDER=gemini` and `generation_providers` row marked `is_active=true` would make Imagen work.

### 2.1 Get credentials

For Vertex AI:

1. Create a GCP project: https://console.cloud.google.com.
2. Enable the **Vertex AI API**.
3. Create a service account with role **Vertex AI User**.
4. Download the JSON key file. Place it outside the repo (e.g., `/home/credentials/gcp.json`).
5. Set `GOOGLE_APPLICATION_CREDENTIALS=/home/credentials/gcp.json` in `.env` (used by the GCP client library automatically).

For AI Studio (text only — Imagen lives on Vertex AI, but you can swap in `gemini-2.5-flash-image` via the multimodal generation endpoint for free-tier prototyping):

1. https://aistudio.google.com/apikey → Create API key.

### 2.2 What the implementation would look like

```php
// app/Services/Generation/GeminiProvider.php — sketch (not implemented yet)
public function generate(string $prompt, array $constraints, ?SourceImage $sourceImage = null): GenerationResult
{
    $endpoint = "https://us-central1-aiplatform.googleapis.com/v1/projects/{config('generation.gemini.project_id')}/locations/us-central1/publishers/google/models/imagen-3.0-generate-002:predict";

    $response = Http::withToken($this->fetchAccessToken())
        ->timeout(120)
        ->acceptJson()
        ->asJson()
        ->post($endpoint, [
            'instances' => [['prompt' => $prompt]],
            'parameters' => ['sampleCount' => 1, 'aspectRatio' => '1:1'],
        ]);

    $binary = base64_decode($response->json('predictions.0.bytesBase64Encoded'));
    $path = config('generation.key_prefix').Str::uuid().'.png';
    Storage::disk(config('generation.disk'))->put($path, $binary);

    return new GenerationResult(path: $path, mime: 'image/png', width: 1024, height: 1024, binary: $binary);
}
```

The fetch-access-token call uses Google's auth library to exchange the service-account JSON for a short-lived bearer token. That's the piece that requires the most plumbing.

---

## 3. Replicate (open-source models) — stub today

Replicate runs community models (FLUX, Stable Diffusion XL, etc.) on GPU. You pay per second of GPU time; many models are very cheap (< $0.01 per image).

### 3.1 Get the API key

1. Open https://replicate.com/account/api-tokens.
2. Copy the `r8_…` token.

### 3.2 `.env` values (once implemented)

```dotenv
GENERATION_PROVIDER=replicate
REPLICATE_API_TOKEN=r8_xxxxxxxxxxxxxxxxxxxxxxxx
REPLICATE_MODEL_VERSION=owner/model-name:hash  # e.g. black-forest-labs/flux-schnell:5599ed30703defd1d160a3a633ff1930666232069bf9cdef04c7b9600e7e5b31
REPLICATE_POLL_INTERVAL=2   # seconds between status polls
REPLICATE_POLL_TIMEOUT=120  # max seconds before failing
```

### 3.3 What the implementation would look like

Replicate uses **async predictions**: you POST to `/v1/predictions`, get back a `prediction.id`, then poll `/v1/predictions/{id}` until `status` is `succeeded` or `failed`. The output is a URL (not a base64 blob) that you download.

```php
// app/Services/Generation/ReplicateProvider.php — sketch (not implemented yet)
public function generate(string $prompt, array $constraints, ?SourceImage $sourceImage = null): GenerationResult
{
    $create = Http::withToken(config('generation.replicate.api_token'))
        ->asJson()->post('https://api.replicate.com/v1/predictions', [
            'version' => config('generation.replicate.model_version'),
            'input' => ['prompt' => $prompt, 'image' => $sourceImage?->path],
        ])->throw();

    $id = $create->json('id');

    // Poll until done
    for ($i = 0; $i < config('generation.replicate.poll_timeout') / config('generation.replicate.poll_interval'); $i++) {
        sleep(config('generation.replicate.poll_interval'));
        $poll = Http::withToken(config('generation.replicate.api_token'))->get("https://api.replicate.com/v1/predictions/{$id}");
        if ($poll->json('status') === 'succeeded') {
            $url = $poll->json('output.0');
            $binary = Http::get($url)->body();
            $path = config('generation.key_prefix').Str::uuid().'.png';
            Storage::disk(config('generation.disk'))->put($path, $binary);
            return new GenerationResult(path: $path, mime: 'image/png', width: 0, height: 0, binary: $binary);
        }
        if ($poll->json('status') === 'failed') {
            throw new \RuntimeException('Replicate prediction failed: '.$poll->json('error'));
        }
    }
    throw new \RuntimeException('Replicate prediction timed out.');
}
```

The job runner (already async via `GenerateArtworkJob`) handles the wait gracefully.

---

## 4. Switching the active provider

Three independent layers must agree on the active provider. Any of them can be the deciding factor:

1. **`GENERATION_PROVIDER` in `.env`** — the primary config. Read by `ProviderRegistry::resolveActive()`.
2. **`generation_providers` lookup table** — the row with `slug = GENERATION_PROVIDER` must have `is_active=true`. If not, the registry falls back to the first active row, then to `openai`.
3. **`config/generation.php` `provider` key** — mirrors `GENERATION_PROVIDER`. Don't edit this directly; change `.env`.

### Switching from OpenAI to (a future) Gemini implementation

```bash
# 1. Edit .env
sed -i 's/^GENERATION_PROVIDER=.*/GENERATION_PROVIDER=gemini/' .env

# 2. Enable in the lookup table
docker exec kindrad-canvas-laravel.test-1 php artisan tinker --execute='
  App\Models\GenerationProvider::where("slug", "gemini")->update(["is_active" => true]);
'

# 3. Reload config
docker exec kindrad-canvas-laravel.test-1 php artisan config:clear
```

The next `GenerateArtworkJob` will route through `GeminiProvider::generate()`.

---

## 5. Where each variable is read

| Variable | Laravel config path | Used by |
|---|---|---|
| `GENERATION_PROVIDER` | `config('generation.provider')` | `ProviderRegistry::resolveActive()` |
| `GENERATION_DISK` | `config('generation.disk')` | Where generation binaries are stored |
| `OPENAI_API_KEY` | `config('generation.openai.api_key')` | `OpenAIProvider::generate()` |
| `OPENAI_IMAGE_MODEL` | `config('generation.openai.model')` | `OpenAIProvider::generate()` |
| `OPENAI_IMAGE_SIZE` | `config('generation.openai.size')` | `OpenAIProvider::generate()` |
| `OPENAI_IMAGE_ENDPOINT` | `config('generation.openai.endpoint')` | `OpenAIProvider::generate()` |
| `OPENAI_IMAGE_TIMEOUT` | `config('generation.openai.timeout')` | `OpenAIProvider::generate()` |

(Gemini and Replicate vars will go in `config('generation.gemini.*')` and `config('generation.replicate.*')` once those providers are implemented.)

## 6. CI / tests

Pest tests stub the provider entirely via `$this->mock(OpenAIProvider::class)` or by injecting a fake provider into the container. No real OpenAI key is required for CI to pass — the existing test suite uses `assertSuccessful()` patterns that work with any provider that returns a `GenerationResult`.

## 7. Quick checklist

To enable image generation in any environment:

- [ ] `OPENAI_API_KEY` set in `.env`.
- [ ] `php artisan config:clear` run after the change.
- [ ] `GENERATION_PROVIDER=openai` (or empty, defaults to openai).
- [ ] `generation_providers` row for `openai` has `is_active=true` (seeded by `CatalogSeeder`).
- [ ] `GENERATION_DISK=s3` (or `local` for offline).
- [ ] S3/MinIO bucket reachable (see [s3-setup.md](s3-setup.md)).

Optional upgrades:

- [ ] Switch `OPENAI_IMAGE_MODEL=gpt-image-1` for higher-quality output.
- [ ] Implement `GeminiProvider` and `ReplicateProvider` for cheaper / open-source alternatives.