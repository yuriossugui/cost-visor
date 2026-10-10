# Cost Visor - Docker Files

## Production

```bash
docker-compose up -d --build
```

Services:
- Frontend: http://localhost
- Backend API: http://localhost:9000/api

## Development

```bash
docker-compose -f docker-compose.dev.yml up -d --build
```

Services:
- Frontend (Vite dev server): http://localhost:5173
- Backend (PHP artisan serve): http://localhost:9000/api

## Commands

```bash
# Run migrations
docker-compose exec backend php artisan migrate

# Run tests
docker-compose exec backend php artisan test

# Run scheduler manually
docker-compose exec scheduler php artisan schedule:run

# Clear cache
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan cache:clear

# Generate JWT secret
docker-compose exec backend php artisan jwt:secret --force

# View logs
docker-compose logs -f backend
docker-compose logs -f frontend
```

## Environment Variables

Copy `.env.example` to `.env` in backend and adjust as needed. For Docker, the `.env.docker` is used automatically.

## Volumes

- `sqlite_data`: Persists the SQLite database
- `sqlite_data_dev`: Persists the SQLite database for development