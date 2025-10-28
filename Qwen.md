## Implementation Rules

1. Only modify existing files, do not create new files to avoid confusion
2. Maintain backward compatibility with existing search functionality
3. Follow existing code patterns and architecture
4. Ensure 100% alignment with current system design
5. Write error-free, well-tested code
6. Implement one feature completely before moving to the next
7. Maintain Japanese language support

## Stage 5 - Accounts & User Area Implementation

### Goal
Add a complete user-account system to the EC site so customers can register, log in, view their order history, manage personal info, and interact with products (wishlist / reviews).

### Core Features to Implement
- Authentication (Login / Register / Logout) - Already implemented with Laravel Breeze
- My Account Dashboard - Enhance existing /dashboard to become user account center
- Order History & Details - Create order history section in dashboard
- Addresses & Profile Management - Create address management section; reuse existing profile settings
- Wishlist Integration - Create wishlist management section in dashboard
- Product Reviews - Create reviews management section in dashboard
- Account Security - Password change section; reuse existing password settings

### Backend Implementation
- Create user_addresses table and model
- Update User model with address relationship
- Create Address controller with CRUD operations
- Enhance dashboard controller to fetch all account data

### Frontend Implementation
- Enhance dashboard.tsx to include all account sections
- Create sections for profile, orders, addresses, wishlist, reviews
- Reuse existing settings components where appropriate
- Maintain Japanese UI language

### Files to Modify
- database/migrations/* - Add user_addresses migration
- app/Models/User.php - Add address relationship
- app/Http/Controllers/AddressController.php - New controller for addresses
- app/Http/Controllers/DashboardController.php - Enhance to fetch account data
- resources/js/pages/dashboard.tsx - Enhance to include account sections
- resources/js/pages/settings/profile.tsx - May need integration with dashboard
- resources/js/pages/settings/password.tsx - May need integration with dashboard
- routes/web.php - Add any necessary routes

### Integration Notes
- Reuse existing profile and password settings components
- Ensure all user account functionality is accessible from the dashboard
- Maintain existing settings routes initially for backward compatibility
- Follow existing UI/UX patterns and component architecture
- Maintain type safety with TypeScript
