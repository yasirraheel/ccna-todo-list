# Todo Sync App

## Environment setup

1. Copy `.env.example` to `.env`
2. Set values as needed:
   - `PORT`: backend server port
   - `PUBLIC_API_BASE_URL`: optional absolute base URL for API origin, e.g. `https://learn-ccna.shahabtech.com`

If `PUBLIC_API_BASE_URL` is empty, the frontend auto-detects API origin with fallbacks.

## Run

```bash
npm install
npm start
```
