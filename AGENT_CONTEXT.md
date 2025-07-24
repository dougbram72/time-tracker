# Agent OS Context for This Project

This project uses the Agent OS framework for structured AI-assisted development.

## Quick Reference

### Global Context
- **Standards**: ~/.agent-os/standards/ (tech stack, code style, best practices)
- **Instructions**: ~/.agent-os/instructions/ (workflow definitions)

### Project Context  
- **Product Docs**: .agent-os/product/ (overview, roadmap, architecture, decisions)
- **Specifications**: .agent-os/specs/ (feature specs and task breakdowns)

### Available Commands
- `@plan-product [description]` - Initialize or update product planning
- `@create-spec [feature]` - Create detailed feature specification
- `@execute-tasks [optional: task]` - Implement tasks from current spec
- `@analyze-product` - Analyze existing codebase and create Agent OS docs

### Current Status
- Product Phase: **Planning Complete** - Product documentation created
- Active Spec: **Timer Management** - Core timer functionality specification
- Next Milestone: **Phase 1 MVP Development** - Core time tracking functionality
- Laravel Version: **Laravel 12** - Initialized and ready for development
- Database: **SQLite** (development) / **MySQL** (production)

## Technical Stack

### Backend
- **Framework**: Laravel 12.x (PHP 8.2+)
- **Database**: SQLite for development, MySQL for production
- **Authentication**: Laravel Sanctum
- **API**: RESTful endpoints with JSON responses

### Frontend
- **Templates**: Blade (server-side rendering)
- **JavaScript**: Alpine.js for reactivity
- **CSS**: Tailwind CSS (mobile-first)
- **Build Tool**: Vite

### Mobile Optimization
- Touch-friendly interfaces (44px minimum touch targets)
- Progressive Web App capabilities
- Local storage for offline timer persistence
- Mobile-first responsive design

## How to Use

1. **Setup**: ✅ Complete - Laravel 12 application initialized
2. **Planning**: ✅ Complete - Product documentation and Timer Management spec ready
3. **Development**: Use `@execute-tasks` to implement specifications
4. **Existing Code**: Use `@analyze-product` to integrate with existing projects

Always reference the three-layer context: Standards → Product → Specs

## Development Commands

```bash
# Start development server
php artisan serve

# Run database migrations
php artisan migrate

# Build frontend assets
npm run dev

# Run tests
php artisan test
```

## Project Structure

```
├── .agent-os/
│   ├── product/          # Product planning docs
│   └── specs/            # Feature specifications
├── app/                  # Laravel application code
├── database/             # Migrations and seeders
├── resources/            # Views, CSS, JS
├── routes/               # Web and API routes
└── tests/                # Test files
```
