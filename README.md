# Todo Sync App

## Environment setup

1. Copy `.env.example` to `.env`
2. Set values as needed:
   - `PORT`: backend server port
   - `PUBLIC_API_BASE_URL`: optional absolute base URL for API origin, e.g. `https://learn-ccna.shahabtech.com`

If `PUBLIC_API_BASE_URL` is empty, the frontend auto-detects API origin.
On hosted domains, localhost fallback is disabled.

You can also set API explicitly in `index.html`:

```html
<meta name="todo-api-base" content="https://learn-ccna.shahabtech.com">
```

## Run

```bash
npm install
npm start
```
