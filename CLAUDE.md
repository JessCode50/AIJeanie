# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Structure

This is a multi-application repository containing three distinct projects:

1. **Contact Manager (Primary Application)** - Located in `Contact-Manager-openAI copy 2/`
   - Backend: CodeIgniter 4 PHP application (`backend/myApp/`)
   - Frontend: Vue 3 + Vite application (`frontend/my-vue-app/`)

2. **Next.js Application** - Located in `my-app/`
   - React/Next.js application with TypeScript and Tailwind CSS

3. **Standalone Backend** - Located in `backend/myApp/`
   - Appears to be a duplicate or related CodeIgniter 4 application

## Development Commands

### Contact Manager Backend (CodeIgniter 4)
```bash
# Navigate to backend directory
cd "Contact-Manager-openAI copy 2/backend/myApp"

# Install dependencies
composer install

# Start development server (runs on port 8080)
php spark serve --host localhost --port 8080

# Run tests
composer test
# or
phpunit
```

### Contact Manager Frontend (Vue 3 + Vite)
```bash
# Navigate to frontend directory
cd "Contact-Manager-openAI copy 2/frontend/my-vue-app"

# Install dependencies
npm install

# Start development server (runs on port 5173)
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

### Next.js Application
```bash
# Navigate to Next.js app directory
cd my-app

# Install dependencies
npm install

# Start development server with Turbopack (runs on port 3000)
npm run dev

# Build for production
npm run build

# Start production server
npm run start

# Run linting
npm run lint
```

## Application Architecture

### Contact Manager
- **Backend Architecture**: CodeIgniter 4 MVC pattern
  - Controllers: `AiController`, `HealthController`, `ServerMonitorController`
  - Models: `ContactsModel` for database operations
  - Utilities: `ContextManager`, `ValidationHelper`, `ResponseFormatter`, `ErrorHandler`
  - External APIs: Integrates with cPanel API and WHMCS API
  - AI Integration: Uses OpenAI API (configured via environment variables)

- **Frontend Architecture**: Vue 3 with Composition API
  - Single-page application with view-based routing
  - Views: contacts list, contact detail, edit, new contact, AI interface
  - Styling: Tailwind CSS v4 with PostCSS

### Next.js Application
- **Architecture**: Next.js 15 with App Router
- **UI Components**: Built with Radix UI primitives
- **Styling**: Tailwind CSS v4
- **TypeScript**: Full TypeScript support with strict configuration

## Environment Setup

### Contact Manager Backend
The backend requires an OpenAI API key configured as an environment variable:

```bash
# Create .env file in Contact-Manager-openAI copy 2/backend/myApp/
OPENAI_API_KEY=your_actual_api_key_here
CI_ENVIRONMENT=development
```

### Port Configuration
- Backend API: `http://localhost:8080`
- Vue Frontend: `http://localhost:5173`
- Next.js App: `http://localhost:3000`

## Key Integration Points

- The Vue frontend communicates with the CodeIgniter backend via REST API
- Backend includes cPanel and WHMCS API integrations for hosting management
- AI functionality is integrated through OpenAI API in the backend
- Cross-origin requests are configured between frontend (5173) and backend (8080)

## Security Notes

- API keys are stored in environment variables, not hardcoded
- CORS is properly configured for cross-origin requests
- Input validation is handled through ValidationHelper utility
- Error handling is centralized through ErrorHandler utility

## Testing

- Backend: PHPUnit tests located in `tests/` directory
- Use `composer test` or `phpunit` to run backend tests
- Frontend: No specific test configuration found