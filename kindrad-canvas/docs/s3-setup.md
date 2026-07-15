# S3-Compatible Object Storage Setup — Dev and Production

This guide explains how to wire object storage (S3-compatible) into Kindred Canvas. The application already uses `Storage::disk(config('generation.disk'))` in several places (generation outputs, project photos, admin thumbnails, etc.). The disk defaults to `s3` — meaning **the app expects an S3-compatible backend to be reachable at runtime**.

Three backends are documented here:

- **Dev (default)** — local **MinIO** container, comes with Sail.
- **Prod option A** — **AWS S3** (the canonical cloud object store).
- **Prod option B** — **MinIO self-hosted** in production (same S3 protocol, your own server).
- **Prod option C** — other S3-compatible providers (Cloudflare R2, DigitalOcean Spaces, Backblaze B2, Wasabi). All work the same way because the Laravel `s3` driver speaks the S3 protocol.

The Laravel `s3` driver (which uses `league/flysystem-aws-s3-v3`) speaks the standard AWS Signature V4 protocol. **Any** S3-compatible backend works without changing application code — only the `.env` values change.

---

## The four scenarios at a glance

| Scenario | `AWS_ENDPOINT` | `AWS_USE_PATH_STYLE_ENDPOINT` | Bucket example | Notes |
|---|---|---|---|---|
| **Dev with Sail MinIO** (default) | `http://minio:9000` (container hostname) | `true` | `kindred-canvas-dev` | Free, hermetic, no cloud account needed |
| **Prod on AWS S3** | _empty_ (default AWS regional endpoint) | `false` | `kindred-canvas-prod` | The canonical production choice |
| **Prod on self-hosted MinIO** | `https://minio.your-domain.com` | `true` | `kindred-canvas-prod` | Free, full data sovereignty, you operate the server |
| **Prod on R2 / DO Spaces / B2 / Wasabi** | provider-specific | `true` (most) | `kindred-canvas-prod` | Each provider has slightly different values; see section 4 |

In every scenario, the **bucket must exist before the app starts writing** (or you must create it on first write via the app's own bootstrap — we don't do that yet, so create it explicitly).

The disk name (`s3`) is the same everywhere. The `GENERATION_DISK` config in `config/generation.php` selects which filesystem disk `config('generation.disk')` resolves to at runtime; `s3` works in all four scenarios.

---

## 1. Dev with MinIO (default — already running with Sail)

This is the path the project ships with. The MinIO container is declared in `compose.yaml` under the service name `minio`, exposed on port 9000 (API) and 8900 (console). It ships with credentials `sail / password`.

### 1.1 One-time bucket creation

```bash
docker exec kindrad-canvas-minio-1 mc alias set local http://localhost:9000 sail password
docker exec kindrad-canvas-minio-1 mc mb local/kindred-canvas-dev
docker exec kindrad-canvas-minio-1 mc anonymous set download local/kindred-canvas-dev
```

Three commands:

1. Register the MinIO server in the `mc` client.
2. Create the bucket.
3. **Mark the bucket as anonymously readable.** Without this, every `Storage::url()` returns a 403 when the browser tries to load the image — uploads succeed but previews never render.

If you want to change the bucket name, edit `AWS_BUCKET` in `.env` AND run `mc mb local/<new-name>` with the same name. The `anonymous set download` permission has to be reapplied if you ever recreate the bucket.

### 1.2 `.env` values

The defaults that ship in `.env.example` work out of the box for the Sail MinIO container:

```dotenv
AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=kindred-canvas-dev
AWS_ENDPOINT=http://minio:9000
AWS_URL=http://localhost:9000/kindred-canvas-dev
AWS_USE_PATH_STYLE_ENDPOINT=true
GENERATION_DISK=s3
```

Why two host values?

- `AWS_ENDPOINT=http://minio:9000` — **container hostname** (`minio`), used by Laravel when running **inside** the Sail container to reach MinIO.
- `AWS_URL=http://localhost:9000/kindred-canvas-dev` — **host address** (`localhost`), used when generating public URLs that the **browser** will load.

If you change `AWS_BUCKET`, also change the path segment of `AWS_URL`.

### 1.3 Console

Open `http://localhost:8900`, login with `sail / password`. You can browse buckets, upload/download files by hand, and inspect object metadata.

### 1.4 Quick sanity check

```bash
docker exec kindrad-canvas-laravel.test-1 php artisan tinker --execute='
  Storage::disk("s3")->put("hello.txt", "world");
  echo Storage::disk("s3")->url("hello.txt");
  Storage::disk("s3")->delete("hello.txt");
'
```

Expected: a URL like `http://localhost:9000/kindred-canvas-dev/hello.txt` printed, no exceptions.

---

## 2. Production on AWS S3

This is the canonical cloud path. You create an AWS account, an IAM user with S3 permissions, an S3 bucket, and point Laravel at it.

### 2.1 Create the S3 bucket

1. Open https://s3.console.aws.amazon.com/s3/buckets.
2. Click **Create bucket**.
3. **Bucket name:** `kindred-canvas-prod` (must be globally unique across AWS — adjust if needed).
4. **Region:** pick the one closest to your servers (e.g., `us-east-1`).
5. **Object Ownership:** ACLs disabled (recommended).
6. **Block Public Access settings for this bucket:** **OFF** for the public-assets bucket, **ON** for private data. For our app's generation outputs and admin thumbnails, the bucket needs to be publicly readable so the browser can load URLs directly. Decide based on what you store there.
7. Click **Create bucket**.

### 2.2 Create an IAM access key

1. Open https://console.aws.amazon.com/iam/home#/users.
2. Create a new user (or use an existing one), attach a policy with at least:
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [{
       "Effect": "Allow",
       "Action": ["s3:PutObject", "s3:GetObject", "s3:DeleteObject", "s3:ListBucket", "s3:HeadObject"],
       "Resource": [
         "arn:aws:s3:::kindred-canvas-prod",
         "arn:aws:s3:::kindred-canvas-prod/*"
       ]
     }]
   }
   ```
   Adjust the bucket ARN if you used a different name or region.
3. On the user's **Security credentials** tab, click **Create access key** → **Application running outside AWS**. Copy the **Access key ID** and **Secret access key**.

### 2.3 `.env` values

```dotenv
AWS_ACCESS_KEY_ID=AKIAxxxxxxxxxxxxxxxx
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=kindred-canvas-prod
AWS_ENDPOINT=
AWS_URL=https://kindred-canvas-prod.s3.us-east-1.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
GENERATION_DISK=s3
```

Note the differences from dev:

- `AWS_ENDPOINT` is **empty** — Laravel uses the regional AWS endpoint (`s3.us-east-1.amazonaws.com`).
- `AWS_USE_PATH_STYLE_ENDPOINT=false` — AWS uses virtual-hosted–style URLs (`bucket.s3.region.amazonaws.com/key`), not path-style.
- `AWS_URL` is the public-facing URL prefix, with no `:9000` and `https://`.

### 2.4 CDN (recommended)

For public assets (generated images), put CloudFront in front of your bucket and point `AWS_URL` at the CloudFront distribution domain instead of the S3 endpoint. This is optional but reduces latency and egress cost.

---

## 3. Production on self-hosted MinIO

You can run MinIO on the same machine as Laravel (or on a separate VPS / cluster). MinIO is one binary; production deployment is documented at https://min.io/docs/minio/linux/index.html.

When you self-host MinIO in production, **the Laravel config is identical to dev** except for the hostnames and credentials.

### 3.1 Set up MinIO on your server

```bash
# Install
wget https://dl.min.io/server/minio/release/linux-amd64/minio
chmod +x minio
sudo mv minio /usr/local/bin/

# Create a data directory
sudo mkdir -p /var/lib/minio
sudo chown -R minio-user:minio-user /var/lib/minio

# Create a system user
sudo useradd -r -s /sbin/nologin minio-user
```

Write a systemd unit `/etc/systemd/system/minio.service`:

```ini
[Unit]
Description=MinIO
After=network.target

[Service]
User=minio-user
Group=minio-user
Environment="MINIO_ROOT_USER=YOUR_STRONG_ROOT_USER"
Environment="MINIO_ROOT_PASSWORD=YOUR_STRONG_ROOT_PASSWORD"
ExecStart=/usr/local/bin/minio server /var/lib/minio --console-address ":9001"
Restart=always
LimitNOFILE=65536

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now minio
```

### 3.2 Put HTTPS in front

MinIO doesn't terminate TLS itself in production. Put **nginx** or **Caddy** in front with a valid Let's Encrypt certificate, and proxy to the local MinIO server. For nginx:

```nginx
server {
    listen 443 ssl;
    server_name minio.your-domain.com;

    ssl_certificate /etc/letsencrypt/live/minio.your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/minio.your-domain.com/privkey.pem;

    client_max_body_size 0;

    location / {
        proxy_pass http://127.0.0.1:9000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### 3.3 Create the production bucket

From your server, or from your dev machine pointed at the production MinIO:

```bash
mc alias set prod https://minio.your-domain.com YOUR_ROOT_USER YOUR_ROOT_PASSWORD
mc mb prod/kindred-canvas-prod
```

### 3.4 `.env` values

```dotenv
AWS_ACCESS_KEY_ID=YOUR_MINIO_ROOT_USER
AWS_SECRET_ACCESS_KEY=YOUR_MINIO_ROOT_PASSWORD
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=kindred-canvas-prod
AWS_ENDPOINT=https://minio.your-domain.com
AWS_URL=https://minio.your-domain.com/kindred-canvas-prod
AWS_USE_PATH_STYLE_ENDPOINT=true
GENERATION_DISK=s3
```

Three differences from dev MinIO:

- `AWS_ENDPOINT` is `https://…` instead of `http://minio:9000` (TLS + production hostname).
- `AWS_URL` is `https://…` (browser-facing).
- Credentials are the production root user (or a dedicated IAM-style access key you created in MinIO's console).

### 3.5 Why you might choose this

- **Cost:** no AWS S3 storage or egress fees; you pay only for the server.
- **Data sovereignty:** objects stay on infrastructure you control.
- **Compliance:** some industries require this.

### 3.6 Why you might not

- **Operations:** you own backups, monitoring, scaling, security patches.
- **Durability:** AWS S3 has 11 nines; MinIO single-node has whatever your disk RAID gives you. Run MinIO in distributed (4+ node) mode to approach AWS durability.
- **Egress:** if your users are far from your server, latency suffers. Pair with a CDN.

---

## 4. Other S3-compatible providers (Cloudflare R2, DigitalOcean Spaces, Backblaze B2, Wasabi)

The Laravel `s3` driver speaks the S3 protocol. Any provider that exposes an S3-compatible API works with **the same `AWS_*` env vars**. You only change the hostnames and credentials.

### 4.1 Cloudflare R2

1. https://dash.cloudflare.com → R2 → Create bucket `kindred-canvas-prod`.
2. R2 → Manage R2 API Tokens → Create API token with **Object Read & Write** scoped to that bucket. Copy the Access Key ID and Secret Access Key.
3. R2 → bucket → Settings → Public access → enable the custom domain or R2.dev subdomain.
4. `.env`:

```dotenv
AWS_ACCESS_KEY_ID=<r2_access_key_id>
AWS_SECRET_ACCESS_KEY=<r2_secret_access_key>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=kindred-canvas-prod
AWS_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
AWS_URL=https://pub-<hash>.r2.dev  (or your custom domain)
AWS_USE_PATH_STYLE_ENDPOINT=true
GENERATION_DISK=s3
```

Notes: R2 has no egress fees, which is its main selling point. `AWS_DEFAULT_REGION=auto` is R2's special value.

### 4.2 DigitalOcean Spaces

1. https://cloud.digitalocean.com/spaces → Create Space `kindred-canvas-prod`, region `nyc3` (or pick one).
2. Spaces → Settings → API → Generate new Spaces access key. Copy key + secret.
3. `.env`:

```dotenv
AWS_ACCESS_KEY_ID=<spaces_key>
AWS_SECRET_ACCESS_KEY=<spaces_secret>
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=kindred-canvas-prod
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_URL=https://kindred-canvas-prod.nyc3.cdn.digitaloceanspaces.com  (if you enabled the CDN)
AWS_USE_PATH_STYLE_ENDPOINT=false
GENERATION_DISK=s3
```

DO Spaces supports path-style but its CDN uses virtual-hosted–style URLs.

### 4.3 Backblaze B2

1. https://www.backblaze.com/b2 → Create Bucket `kindred-canvas-prod`.
2. App Keys → Create key with `listBuckets`, `listFiles`, `readFiles`, `writeFiles`, `deleteFiles` on that bucket.
3. `.env`:

```dotenv
AWS_ACCESS_KEY_ID=<b2_key_id>
AWS_SECRET_ACCESS_KEY=<b2_application_key>
AWS_DEFAULT_REGION=us-west-004   (your bucket region)
AWS_BUCKET=kindred-canvas-prod
AWS_ENDPOINT=https://s3.us-west-004.backblazeb2.com
AWS_URL=https://kindred-canvas-prod.s3.us-west-004.backblazeb2.com
AWS_USE_PATH_STYLE_ENDPOINT=true
GENERATION_DISK=s3
```

B2's S3-compatible endpoint is `s3.<region>.backblazeb2.com`. Egress is cheap but not free.

### 4.4 Wasabi

1. https://console.wasabi.com → Create Bucket `kindred-canvas-prod`, region `us-east-1`.
2. Access Keys → Create new access key. Copy key + secret.
3. `.env`:

```dotenv
AWS_ACCESS_KEY_ID=<wasabi_key>
AWS_SECRET_ACCESS_KEY=<wasabi_secret>
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=kindred-canvas-prod
AWS_ENDPOINT=https://s3.us-east-1.wasabisys.com
AWS_URL=https://s3.us-east-1.wasabisys.com/kindred-canvas-prod
AWS_USE_PATH_STYLE_ENDPOINT=true
GENERATION_DISK=s3
```

Wasabi charges flat per-TB (no egress) — competitive if you read a lot.

---

## 5. Where each variable is read

| Variable | Laravel config path | Used by |
|---|---|---|
| `AWS_ACCESS_KEY_ID` | `filesystems.disks.s3.key` | All `Storage::disk('s3')` operations |
| `AWS_SECRET_ACCESS_KEY` | `filesystems.disks.s3.secret` | All `Storage::disk('s3')` operations |
| `AWS_DEFAULT_REGION` | `filesystems.disks.s3.region` | AWS Signature V4 signing |
| `AWS_BUCKET` | `filesystems.disks.s3.bucket` | Bucket name for all operations |
| `AWS_ENDPOINT` | `filesystems.disks.s3.endpoint` | Override endpoint URL (MinIO, R2, DO, B2, Wasabi); empty = AWS default |
| `AWS_URL` | `filesystems.disks.s3.url` | Public URL prefix when generating downloadable URLs |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `filesystems.disks.s3.use_path_style_endpoint` | `true` = `host/bucket/key` (MinIO, R2); `false` = `bucket.host/key` (AWS) |
| `GENERATION_DISK` | `config('generation.disk')` | Tells the generation pipeline which filesystem disk to use |

## 6. Path-style vs virtual-hosted–style URLs

This is the single most common source of "I see the file in the console but `Storage::url()` returns 404":

- **Path-style:** `http://minio:9000/kindred-canvas-dev/hello.txt` — the bucket is the first path segment. Required by MinIO and most S3-compat providers. Set `AWS_USE_PATH_STYLE_ENDPOINT=true`.
- **Virtual-hosted–style:** `https://kindred-canvas-dev.s3.us-east-1.amazonaws.com/hello.txt` — the bucket is a subdomain. Used by AWS S3, R2, and some DO Spaces configurations. Set `AWS_USE_PATH_STYLE_ENDPOINT=false`.

If you mix them up, signed requests fail with 403/404 because the URL the SDK signs doesn't match the URL the bucket expects.

## 7. CDN recommendations

For all four scenarios, **put a CDN in front of the bucket** if your assets are public:

- AWS S3 → CloudFront
- MinIO → Cloudflare, Fastly, or your own nginx caching layer
- R2 → Cloudflare's built-in CDN (zero config — already in front of every bucket)
- DO Spaces → DO Spaces CDN
- B2 → Cloudflare in front of the S3-compatible endpoint

Update `AWS_URL` to point at the CDN domain so generated URLs hit the edge.

## 8. Quick checklist

Before any deployment:

- [ ] Bucket created (MinIO bucket, S3 bucket, R2 bucket, etc.) — name matches `AWS_BUCKET`.
- [ ] Access credentials have read + write + delete + list + head permissions.
- [ ] If the bucket holds public assets, public reads are enabled (or a CDN is in front).
- [ ] `.env` values match the table in section 1, 2, 3, or 4.
- [ ] `php artisan config:clear` run after changing env.
- [ ] Smoke test from inside the Laravel container: `Storage::disk('s3')->put('hello.txt', 'world')`, then read the URL from your browser.

For dev with the default MinIO:

- [ ] Sail containers up: `sail up -d` (or `./vendor/bin/sail up -d`).
- [ ] Bucket created once: `docker exec kindrad-canvas-minio-1 mc mb local/kindred-canvas-dev`.

## 9. Switching the app from S3 back to local

If you ever need to bypass object storage (e.g., for offline debugging), set `GENERATION_DISK=local` in `.env`. Generation outputs and project photos will then land in `storage/app/` instead of the bucket. Reverting is just flipping the value back to `s3` and running `php artisan config:clear`.