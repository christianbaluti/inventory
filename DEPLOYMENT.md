# Deployment

## Frontend on Vercel

Push the repository to GitHub, then import it in Vercel.

Use these Vercel settings:

```text
Framework Preset: Vite
Build Command: npm run build:frontend
Output Directory: dist
Install Command: npm install
```

Add this Vercel environment variable:

```text
VITE_API_BASE_URL=https://raphaelmgawi.ct.ws
```

The frontend reads this value at build time and sends every request to `https://raphaelmgawi.ct.ws/api/...`.

## Backend on InfinityFree

Create a production `.env` on InfinityFree from `.env.infinityfree.example`.

Set these production values:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://raphaelmgawi.ct.ws
FRONTEND_URL=https://your-vercel-app.vercel.app
DB_HOST=sql101.infinityfree.com
DB_DATABASE=if0_41811659_raphaelmgawi
DB_USERNAME=if0_41811659
DB_PASSWORD=your InfinityFree database password
MAIL_PASSWORD=your SMTP password
```

Generate a local app key before upload:

```bash
php artisan key:generate --show
```

Put the generated value into `APP_KEY`. Keep `JWT_SECRET="${APP_KEY}"`.

Install backend dependencies locally before uploading:

```bash
composer install --no-dev --optimize-autoloader
```

Upload the Laravel backend files to InfinityFree `htdocs`. Upload `vendor` too. Do not upload `node_modules` or `dist`.

The root `.htaccess` rewrites requests into Laravel `public`, so `https://raphaelmgawi.ct.ws/api/login` should work without adding `/public`.

## Database

If InfinityFree does not allow SSH or Artisan, open phpMyAdmin and import:

```text
database/schema/infinityfree.sql
```

That creates the tables, indexes, InnoDB engine settings, foreign keys, and migration records.

If you do have command-line access, use this instead:

```bash
php artisan migrate --force
```

## After Vercel Gives You A Domain

Update the backend `.env` on InfinityFree:

```text
FRONTEND_URL=https://your-real-vercel-domain.vercel.app
```

If you later add a custom frontend domain, add it too:

```text
FRONTEND_URL=https://your-real-vercel-domain.vercel.app,https://your-custom-domain.com
```

## Checks

Backend:

```text
https://raphaelmgawi.ct.ws/api/dashboard
```

Without a token, it should return an authentication error. That confirms Laravel routing and CORS/API setup are reachable.

Frontend:

Open the Vercel URL, register, verify the OTP from email, create a company, and add an inventory item.
