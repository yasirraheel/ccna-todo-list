# Todo Sync App

## Environment setup

1. Copy `.env.example` to `.env`
2. Set values as needed:
   - `PUBLIC_API_BASE_URL`: optional absolute base URL for API origin, e.g. `https://learn-ccna.shahabtech.com`
   - `YOUTUBE_API_KEY`: optional YouTube Data API key for full playlist import pagination
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`: MySQL connection for users and tasks

If `PUBLIC_API_BASE_URL` is empty, the frontend auto-detects API origin.
On hosted domains, localhost fallback is disabled.

You can also set API explicitly in `index.html`:

```html
<meta name="todo-api-base" content="https://learn-ccna.shahabtech.com">
```

## Run

```bash
Use Apache/LiteSpeed with PHP and make sure `mod_rewrite` is enabled.
`/api/*` is served by `api/index.php`.
No Node process is required for runtime.
```

## Auth

- Register or login from the app UI.
- Each account sees only its own tasks.
- API uses bearer token authentication for all task and import routes.
