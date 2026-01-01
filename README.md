# Velox Logistics

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white" alt="Vue.js">
  <img src="https://img.shields.io/badge/Inertia.js-2.x-9553E9?style=for-the-badge&logo=inertia&logoColor=white" alt="Inertia.js">
  <img src="https://img.shields.io/badge/TailwindCSS-3.x-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="TailwindCSS">
</p>

A modern, full-stack logistics operation management system built with Laravel, Vue.js, and Inertia.js. Features a beautiful dark-themed UI with glassmorphism design, comprehensive project tracking, multi-level approval workflows, and real-time analytics.

## ✨ Features

### 📊 Dashboard & Analytics
- Interactive dashboard with real-time project statistics
- Trend charts with configurable time ranges (3/6/12 months)
- Geographic visualization with Leaflet maps
- Quick stats cards with animated counters

### 📁 Project Management
- Full project lifecycle tracking (Planning → Survey → Construction → Handover)
- BOQ (Bill of Quantities) management with detailed item tracking
- BEP (Break Even Point) projections and financial planning
- Document management with version control
- Project status history timeline

### ✅ Approval Workflows
- Configurable multi-step approval flows
- Visual workflow editor with drag-and-drop nodes
- Role-based approval routing
- SLA tracking for approval steps
- Email notifications for pending approvals

### 👥 User & Role Management
- Comprehensive role-based access control (RBAC)
- Granular permission management
- User activity audit logs
- Multi-database user synchronization

### 🎨 Modern UI/UX
- Dark mode glassmorphism design
- Responsive layout for all screen sizes
- Command palette (Ctrl+K) for quick navigation
- Toast notifications and confirmation dialogs
- Smooth animations and transitions

## 🛠️ Tech Stack

**Backend:**
- PHP 8.2+
- Laravel 12.x
- Laravel Octane (FrankenPHP)
- Spatie Laravel Permission
- RabbitMQ for queue processing

**Frontend:**
- Vue.js 3.x with Composition API
- Inertia.js 2.x
- TailwindCSS 3.x
- Lucide Icons
- Vue Flow (workflow visualization)
- Leaflet (maps)

**Database:**
- MySQL 8.x
- Redis (caching)

## 📦 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.x
- Redis (optional, for caching)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/velox-logistics.git
   cd velox-logistics
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   
   Update `.env` with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=velox_operations
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

7. **Start development server**
   ```bash
   composer dev
   ```
   
   This runs Laravel server, queue worker, log viewer, and Vite concurrently.

## 📸 Screenshots

> Screenshots coming soon

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👤 Author

**Fauzan Al Rafi**
- GitHub: [@fauzanalrafii](https://github.com/fauzanalrafii)

---

<p align="center">Made with ❤️ using Laravel & Vue.js</p>
