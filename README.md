# THE BREW BOX — Source Code Starter Package

This package contains **real PHP source files**, not just empty filenames. The **announcement feature** includes implemented Laravel controller actions, request validation, migrations, Blade UI, REST API and Flutter announcement screens.

## Important scope limitation
This is a **source-code starter**, not a completed production coffee-shop system. Other modules (orders, checkout, inventory, reservations, payments, auth, etc.) include model/controller scaffolding, not completed business workflows. It is not a complete Laravel framework installation: `vendor/`, the Laravel framework's full default config/bootstrap/runtime files, and Flutter-generated platform projects are not bundled.

## Set up Laravel
1. Install PHP 8.2+, Composer, Node/npm and MySQL.
2. Create a fresh **Laravel 12** project (`composer create-project laravel/laravel web "^12.0"`) in a temporary location, then copy the provided `web/app`, `web/database`, `web/resources`, `web/routes`, `web/bootstrap/app.php`, `web/bootstrap/providers.php` and other supplied project files into it. **Preserve the fresh project's default framework files**, especially `config/`, default migrations, and Composer-generated dependencies. Reconcile duplicate `users` migrations before running migrations.
3. Install Sanctum (`php artisan install:api`) if needed; merge generated API configuration with supplied routes.
4. Configure `.env` and MySQL; run `php artisan migrate`, `php artisan db:seed`, `php artisan storage:link`, `npm install`, `npm run dev`, `php artisan serve`.
5. Implement registration/login or install Laravel Breeze before using admin routes. Assign admin role only via trusted provisioning, never from public registration.

## Flutter
1. Run `flutter create mobile` in a temporary directory, then merge the provided `mobile/lib` and `mobile/pubspec.yaml` into the generated Flutter project.
2. `flutter pub get` then `flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api` for an Android emulator.
3. Configure Android cleartext traffic for local HTTP development or use HTTPS in production.

## Included working announcement pieces
- Admin CRUD, publish action, request validation and image uploads.
- Public announcement listing/detail views and public JSON listing/detail endpoints.
- Authenticated read tracking endpoint.
- Flutter announcement listing/detail UI with HTTP loading/error handling.

## Not yet implemented
Full authentication, ecommerce transactions, inventory stock deduction, payment gateway, scheduled publishing automation, push notifications, role permissions beyond admin middleware, and complete Flutter screens for other modules.
