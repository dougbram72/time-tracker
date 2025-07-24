# Laravel Time Tracker

⏱️ A comprehensive time tracking application built with Laravel for developers and professionals who need to track time across multiple projects and customer issues.

## 🚀 Features

### Core Timer Functionality
- **Start/Stop/Pause/Resume** timers with accurate time tracking
- **Single Active Timer** constraint - starting a new timer automatically stops the previous one
- **Real-time Updates** - elapsed time updates every second for running timers
- **Local Storage Persistence** - timer state persists across browser sessions

### Project & Issue Management
- **Hierarchical Organization** - track time on Projects and Issues within projects
- **Visual Indicators** - project colors and issue priorities for quick identification
- **Flexible Tracking** - timer can track either projects directly or specific issues

### Mobile-First Design
- **Responsive UI** - optimized for both mobile and desktop use
- **Touch Optimizations** - 44px minimum touch targets, swipe gestures
- **Progressive Web App** features for native-like mobile experience
- **Haptic Feedback** simulation for better touch interaction

### Advanced Features
- **Real-time Synchronization** - timer state syncs across browser tabs and sessions
- **Offline Capability** - works offline with automatic sync when connection restored
- **Error Recovery** - comprehensive error handling with automatic recovery
- **Timer Drift Detection** - automatically corrects timer drift for accuracy

## 🛠️ Technical Stack

- **Backend**: Laravel 12 with Eloquent ORM
- **Frontend**: Alpine.js for reactive components
- **Styling**: Tailwind CSS for responsive design
- **Database**: SQLite (development) / MySQL/PostgreSQL (production)
- **Authentication**: Laravel Sanctum for API security
- **Testing**: Pest PHP with comprehensive test coverage

## 📱 User Experience

### Mobile Touch Optimizations
- **Swipe Gestures**: 
  - Swipe down to minimize widget
  - Swipe right to show recent entries
  - Swipe left to hide recent entries
  - Swipe down on modal to dismiss
- **Long Press Actions**:
  - Long press timer display for emergency reset
  - Long press start button for quick start with last settings
- **Touch Feedback**: Visual and haptic feedback for all interactions

### Desktop Experience
- **Floating Timer Widget** - unobtrusive timer display
- **Keyboard Shortcuts** - efficient timer control
- **Multi-tab Synchronization** - consistent state across browser tabs

## 🧪 Testing

Comprehensive test suite with **88 tests** and **527 assertions**:

- **Unit Tests** - Model logic and business rules
- **Feature Tests** - API endpoints and controller logic
- **Integration Tests** - Complete workflow testing
- **Browser Tests** - UI component and interaction testing
- **Error Handling Tests** - Recovery and resilience testing

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/dougbram72/time-tracker.git
   cd time-tracker
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start the application**
   ```bash
   php artisan serve
   ```

7. **Access the application**
   - Open http://localhost:8000
   - Click "Demo Login" to try the application
   - Default demo user: test@example.com

## 📖 Usage

### Starting a Timer
1. Click the "Start Timer" button
2. Select either a Project or Issue to track time against
3. Choose from your available projects/issues
4. Click "Start" to begin tracking

### Managing Timers
- **Pause**: Click pause button to temporarily stop the timer
- **Resume**: Click resume to continue a paused timer
- **Stop**: Click stop to end the timer and create a time entry
- **Switch**: Starting a new timer automatically stops the current one

### Mobile Usage
- Use swipe gestures for quick navigation
- Long press for advanced actions
- Widget automatically adapts to mobile screen sizes

## 🏗️ Architecture

### Database Schema
- **Users** - Application users
- **Projects** - Top-level work containers
- **Issues** - Specific tasks within projects
- **Timers** - Active timing sessions
- **TimeEntries** - Completed time records

### API Endpoints
- `GET /api/timers/active` - Get current active timer
- `POST /api/timers/start` - Start new timer
- `POST /api/timers/pause` - Pause active timer
- `POST /api/timers/resume` - Resume paused timer
- `POST /api/timers/stop` - Stop timer and create entry
- `POST /api/timers/sync` - Synchronize timer state

### Frontend Architecture
- **Alpine.js Store** - Centralized state management
- **Blade Components** - Reusable UI components
- **Tailwind CSS** - Utility-first styling
- **Local Storage** - Client-side persistence

## 🧪 Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 🚀 Deployment

The application is production-ready with:
- Environment-based configuration
- Database migrations
- Asset compilation
- Comprehensive error handling
- Security best practices

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Configure database connection
- [ ] Set up proper web server (Apache/Nginx)
- [ ] Configure SSL certificate
- [ ] Set up backup strategy
- [ ] Configure monitoring

## 📋 Product Documentation

Comprehensive product planning documentation available in `.agent-os/`:
- **Product Overview** - Vision, users, and use cases
- **Architecture Decisions** - Technical choices and rationale
- **Feature Specifications** - Detailed requirements and designs
- **Development Roadmap** - Planned features and timeline

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes with tests
4. Submit a pull request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👨‍💻 Author

Built by Doug Brammer for efficient time tracking across development projects and customer support tasks.

---

**Ready to track your time efficiently? Get started with the demo and see how this application can streamline your workflow!**
