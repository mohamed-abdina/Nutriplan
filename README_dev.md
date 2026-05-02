# Development — Docker + BrowserSync

- Copy `.env.example` to `.env` and edit DB credentials as needed.
- Start services:
```bash
cp .env.example .env
./bin/dev
```
- Open http://localhost:3000 to use the proxied dev server with live reload (BrowserSync).
- To stop:
```bash
docker-compose down
```

Notes:
- `bin/dev` will run `docker-compose up --build -d` and then `npm run dev`.
- You should run `composer install` and `npm install` once before the first run, or let `bin/dev` run `npm install` automatically.
