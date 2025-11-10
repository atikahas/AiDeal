# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AiDeal is a Laravel-based web application that provides AI-powered image generation and manipulation capabilities. It leverages Google's Gemini API for AI services and uses Livewire for reactive UI components.

## Tech Stack

- **Backend**: Laravel 12.x with PHP 8.2+
- **Frontend**: Livewire/Volt with Flux UI components, Tailwind CSS v4
- **Database**: SQLite (default), configurable for other databases
- **Build Tools**: Vite for asset compilation
- **Testing**: Pest PHP for testing
- **Code Style**: Laravel Pint for PHP code formatting
- **Queue**: Database queue driver
- **Authentication**: Laravel Fortify with email-based authentication

## Development Commands

```bash
# Initial setup (runs composer install, migrations, npm install, and build)
composer run setup

# Run development server with queue worker, logs, and vite dev server concurrently
composer run dev

# Run tests
composer run test
# or
php artisan test

# Run specific test
php artisan test --filter TestClassName
php artisan test tests/Feature/SpecificTest.php

# Code formatting
./vendor/bin/pint

# Build frontend assets
npm run build

# Development mode for frontend
npm run dev

# Database migrations
php artisan migrate
php artisan migrate:fresh  # Drop all tables and re-migrate

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Generate application key
php artisan key:generate

# Queue worker (for development)
php artisan queue:work

# View logs in real-time
php artisan pail
```

## High-Level Architecture

### Directory Structure

- **app/**: Core application code
  - **Livewire/**: Livewire components for reactive UI
    - **AiImageIdeaSuite/**: AI image generation components
    - **UserActivity/**: Activity tracking components
  - **Services/**: Business logic services
    - **Ai/**: AI-related services (ImagenClient for Google Imagen)
    - **GeminiService.php**: Core Gemini API integration
    - **AiActivityLogger.php**: Tracks AI API usage
  - **Models/**: Eloquent models
    - **User.php**: User model with API key relationships
    - **ApiKey.php**: Stores user API keys
    - **AiActivityLog.php**: Logs AI service usage
    - **ImageJob.php**: Tracks image generation jobs
  - **Providers/**: Service providers
    - **GeminiServiceProvider.php**: Registers AI services

### Key Features

1. **AI Image Generation**: Uses Google's Imagen API for text-to-image and image-to-image generation
2. **User Authentication**: Email-based magic link authentication
3. **API Key Management**: Users can manage their own API keys for AI services
4. **Activity Tracking**: Logs all AI API usage for monitoring and billing
5. **Queue Jobs**: Background processing for image generation tasks

### Environment Configuration

Key environment variables (set in `.env`):

```
# Gemini/Imagen API Configuration
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_MODEL=gemini-2.5-flash
GEMINI_IMAGEN_MODEL=gemini-2.5-flash-image
GEMINI_IMAGE_TIMEOUT=120

# Database (SQLite by default)
DB_CONNECTION=sqlite

# Queue and Cache
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### Frontend Architecture

- Uses Livewire for reactive components without writing JavaScript
- Flux UI components for consistent design
- Tailwind CSS v4 for styling
- Vite for fast development and optimized builds

### Testing

- Uses Pest PHP testing framework
- Tests organized in `tests/Feature/` and `tests/Unit/`
- Database migrations run in memory for testing
- Run with `composer run test` or `php artisan test`

## Important Notes

1. The application uses database-driven queues - ensure queue worker is running for background jobs
2. Image files are stored in `storage/app/public/` - ensure storage is linked (`php artisan storage:link`)
3. API keys are encrypted in the database and tied to individual users
4. All AI API calls are logged for usage tracking and debugging
5. The application supports multiple AI models through the Gemini API integration