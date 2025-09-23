# Ecommerce Site - Comprehensive Backend System Documentation

## Project Overview
This is a Laravel-based ecommerce platform for selling perfumes, built with:
- **Backend**: Laravel PHP Framework
- **Frontend**: React with Inertia.js
- **Database**: MySQL
- **Search**: Meilisearch via Laravel Scout
- **Containerization**: Laravel Sail (Docker)
- **UI Components**: shadcn/ui with Tailwind CSS

## Backend System Architecture

### Core Components

#### 1. Models
Located in `/app/Models/`

- **Product** - Main product entity with search capabilities
- **ProductVariant** - Product variations (different SKUs, prices)
- **ProductImage** - Product images with hero image support
- **Brand** - Perfume brands
- **Category** - Product categories with hierarchical structure
- **Order** - Customer orders
- **OrderItem** - Individual items in orders
- **Payment** - Payment transactions
- **User** - Customer accounts
- **Cart** - Shopping cart functionality
- **Inventory** - Stock management
- **Coupon** - Discount codes
- **Review** - Product reviews
- **Wishlist** - Customer wishlists

#### 2. Controllers
Located in `/app/Http/Controllers/`

- **ProductController** - Product listing and detail pages
- **CartController** - Shopping cart operations
- **CheckoutController** - Checkout process
- **OrdersController** - Order management
- **WishlistController** - Wishlist functionality
- **Auth Controllers** - User authentication

#### 3. Database Migrations
Located in `/database/migrations/`

Key migrations include:
- Users and authentication tables
- Product catalog tables (brands, categories, products, variants, images)
- Order processing tables (orders, order_items, payments)
- Shopping cart and wishlist tables
- Inventory management tables
- Coupon and discount system
- Review system

#### 4. Seeders & Factories
Located in `/database/seeders/` and `/database/factories/`

- **ProductSeeder** - Creates sample products
- **BrandSeeder** - Creates perfume brands
- **CategorySeeder** - Creates product categories
- **ProductVariantSeeder** - Creates product variants
- **ProductImageSeeder** - Adds product images
- **InventorySeeder** - Sets up stock levels

## Detailed Backend Analysis

### Product System Architecture

#### Models

**Product Model** (`/app/Models/Product.php`)
- Uses Laravel Scout for search integration
- Has relationships with Brand, Category, ProductImage, ProductVariant
- Supports both single category and multiple categories via pivot table
- Has a heroImage relationship for featured images
- Searchable attributes include: id, slug, name, brand, category, description
- Fillable attributes: name, slug, brand_id, category_id, short_desc, long_desc, is_active, featured, attributes_json, meta_json, published_at

**ProductVariant Model** (`/app/Models/ProductVariant.php`)
- Belongs to Product
- Has one Inventory record
- Contains pricing information (price_yen, sale_price_yen)
- Has SKU for unique identification
- Option_json for variant-specific attributes (e.g., volume)

**ProductImage Model** (`/app/Models/ProductImage.php`)
- Belongs to Product
- Has path, alt text, sort order, hero flag, and rank
- Supports multiple images per product with one designated as hero

**Brand Model** (`/app/Models/Brand.php`)
- Simple model with name and slug
- Has many products

**Category Model** (`/app/Models/Category.php`)
- Hierarchical structure with parent_id and depth
- Has many products
- Supports both direct product assignment and many-to-many relationships

#### Database Schema

**Products Table** (`2025_09_05_202303_create_products_table.php`)
```
- id (bigint, unsigned, autoincrement, primary)
- name (varchar 120)
- slug (varchar 140, unique)
- brand_id (bigint, unsigned, foreign key to brands)
- category_id (bigint, unsigned, foreign key to categories)
- short_desc (text, nullable)
- long_desc (mediumtext, nullable)
- is_active (boolean, default true)
- featured (boolean, default false)
- attributes_json (json, nullable) - for fragrance notes, radar, etc.
- meta_json (json, nullable) - for SEO, flags
- published_at (datetime, nullable)
- timestamps
- indexes on brand_id, category_id, is_active/featured
```

**Product Variants Table** (`2025_09_05_202440_create_product_variants_table.php`)
```
- id (bigint, unsigned, autoincrement, primary)
- product_id (bigint, unsigned, foreign key to products)
- sku (varchar 64, unique)
- option_json (json, nullable) - e.g., {"volume":"50ml"}
- price_yen (integer, unsigned)
- sale_price_yen (integer, unsigned, nullable)
- is_active (boolean, default true)
- timestamps
- index on product_id/is_active
```

**Product Images Table** (`2025_09_05_202406_create_product_images_table.php` and `2025_09_06_193744_harden_product_images_hero_and_rank.php`)
```
- id (bigint, unsigned, autoincrement, primary)
- product_id (bigint, unsigned, foreign key to products)
- is_hero (boolean, default false) - only one hero per product
- rank (unsigned integer, default 0)
- path (varchar 255)
- alt (varchar 150, nullable)
- sort (integer, default 0)
- timestamps
- indexes on product_id/sort, product_id/rank
- unique functional index on hero images
```

**Brands Table** (`2025_09_05_202057_create_brands_table.php`)
```
- id (bigint, unsigned, autoincrement, primary)
- name (varchar 80, unique)
- slug (varchar 90, unique)
- timestamps
```

**Categories Table** (`2025_09_05_202231_create_categories_table.php` and `2025_09_06_192205_add_category_fk_and_pivot.php`)
```
- id (bigint, unsigned, autoincrement, primary)
- name (varchar 80)
- slug (varchar 90, unique)
- parent_id (bigint, unsigned, nullable, foreign key to categories)
- depth (unsigned tiny integer, default 0)
- timestamps
- indexes on parent_id, depth
```

**Category-Product Pivot Table** (`2025_09_06_192205_add_category_fk_and_pivot.php`)
```
- category_id (bigint, unsigned, foreign key to categories)
- product_id (bigint, unsigned, foreign key to products)
- unique constraint on category_id/product_id
- foreign keys with cascade delete/update
```

#### Controllers

**ProductController** (`/app/Http/Controllers/ProductController.php`)
- **index()** method handles product listing with:
  - Search functionality using Laravel Scout (Meilisearch)
  - Filtering by brand, category, price range
  - Sorting options (relevance, newest, price ascending/descending)
  - Pagination with configurable items per page
  - Faceted search with brand/category/price buckets
  - Caching for performance optimization
  
- **show()** method handles product detail pages with:
  - Full product information loading
  - Related products based on brand/category
  - Product variants with inventory information
  - Product images with gallery support

Key features:
- Search fallback to database when Meilisearch is unavailable
- Complex faceted filtering with dynamic counts
- Hero image selection for product cards
- Price calculation with sale price support
- Related product recommendations

#### Search System

**Laravel Scout Integration**
- Configured to use Meilisearch as the search driver
- Environment variables:
  - `SCOUT_DRIVER=meilisearch`
  - `MEILISEARCH_HOST=http://meilisearch:7700`
  
**Searchable Attributes** (defined in Product model):
- id
- slug
- name
- brand name
- category name
- combined description (short_desc + long_desc)

#### Environment Configuration

**Key Environment Variables:**
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700

REDIS_HOST=redis
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis

STRIPE_SECRET=...
STRIPE_PUBLISHABLE_KEY=...
```

## Backend Services & Features

### 1. Search System
- **Laravel Scout** integration with **Meilisearch**
- Full-text search on product names, descriptions, brands, categories
- Searchable attributes defined in Product model
- Fallback to database search when Meilisearch is unavailable
- Cached search results for performance

### 2. Authentication & Authorization
- **Laravel Breeze** for authentication
- User registration, login, password reset
- Email verification
- Role-based access control (if implemented)

### 3. Shopping Cart
- Session-based cart storage
- Cart persistence for logged-in users
- Cart item management (add, update, remove)
- Cart validation and stock checking

### 4. Checkout & Payment
- Multi-step checkout process
- Stripe payment integration
- Order creation and management
- Payment status tracking

### 5. Inventory Management
- Stock level tracking
- Safety stock thresholds
- Automatic inventory updates on order completion

### 6. Coupon System
- Discount code management
- Percentage and fixed amount discounts
- Usage limits and expiration dates

## Strict Development Rules

### Backend Development Rules

1. **NO DATABASE SCHEMA CHANGES WITHOUT APPROVAL**
   - All database migrations must be reviewed before implementation
   - Foreign key relationships must be maintained
   - Data integrity is paramount
   - Always create new migrations rather than modifying existing ones

2. **CONTROLLER MODIFICATIONS REQUIRE BACKEND UNDERSTANDING**
   - All controller changes must maintain API compatibility
   - Response formats must remain consistent
   - Error handling must follow existing patterns
   - Caching strategies must be preserved

3. **MODEL CHANGES MUST PRESERVE EXISTING FUNCTIONALITY**
   - Relationships must not be broken
   - Accessors and mutators must be maintained
   - Searchable attributes must be preserved
   - Fillable/guarded attributes must be maintained

4. **SEARCH FUNCTIONALITY DEPENDS ON LARAVEL SCOUT**
   - Searchable arrays must be maintained
   - Meilisearch integration must be preserved
   - Fallback mechanisms must work
   - Search indexing must be maintained

5. **AUTHENTICATION SYSTEM IS CRITICAL**
   - User registration/login must not be broken
   - Password reset functionality must work
   - Email verification must be maintained

### Frontend Development Rules

1. **USE EXISTING COMPONENTS ONLY**
   - Only use components from `/resources/js/components/ui/`
   - Follow shadcn/ui patterns and conventions
   - Do not create duplicate components

2. **MAINTAIN BACKEND COMPATIBILITY**
   - All frontend changes must work with existing API responses
   - Do not modify data structures without backend changes
   - Preserve existing routing and URL patterns

3. **ENHANCE, DON'T REPLACE**
   - Modify existing files rather than creating new ones
   - Extend existing components rather than replacing them
   - Maintain existing import/export structures

4. **PRESERVE USER EXPERIENCE**
   - Do not break existing functionality
   - Maintain consistent styling and UX patterns
   - Ensure mobile responsiveness

### Database Rules

1. **MIGRATIONS ARE IMMUTABLE**
   - Never modify existing migration files
   - Always create new migrations for changes
   - Test migrations thoroughly before deployment

2. **DATA INTEGRITY IS MANDATORY**
   - Foreign key constraints must be maintained
   - Cascade rules must be preserved
   - Validation must be maintained

3. **PERFORMANCE CONSIDERATIONS**
   - Indexes must be maintained for search performance
   - Queries must be optimized
   - N+1 problems must be avoided

### Search System Rules

1. **LARAVEL SCOUT INTEGRATION**
   - Searchable arrays must be maintained
   - Meilisearch configuration must be preserved
   - Fallback to database search must work

2. **SEARCH ATTRIBUTES**
   - Product names must be searchable
   - Brand names must be searchable
   - Category names must be searchable
   - Descriptions must be searchable

### Containerization Rules

1. **LARAVEL SAIL COMPLIANCE**
   - All services must work with Sail configuration
   - Docker Compose files must not be modified without approval
   - Service dependencies must be maintained

2. **ENVIRONMENT VARIABLES**
   - All environment variables must be preserved
   - Configuration must work with existing .env files
   - Database connections must be maintained

## Current System Status

### Working Features
- ✅ Product listing and detail pages
- ✅ Search functionality (Meilisearch)
- ✅ Shopping cart
- ✅ User authentication
- ✅ Order processing
- ✅ Payment integration (Stripe)
- ✅ Inventory management
- ✅ Coupon system
- ✅ Review system
- ✅ Wishlist functionality

### Known Limitations
- ⚠️ Filter sidebar is basic (no collapsible functionality)
- ⚠️ Advanced filtering options are limited
- ⚠️ Sorting options are basic
- ⚠️ Pagination could be enhanced

## Future Enhancement Guidelines

### Before Making Any Changes
1. **ANALYZE EXISTING CODE** - Understand current implementation
2. **IDENTIFY BACKEND DEPENDENCIES** - Know what backend changes are needed
3. **PLAN DATABASE MIGRATIONS** - Design any required schema changes
4. **ENSURE BACKWARD COMPATIBILITY** - Don't break existing functionality
5. **GET APPROVAL** - Confirm approach before implementation

### Enhancement Categories
1. **UI/UX Improvements** - Visual enhancements that don't require backend changes
2. **Frontend Functionality** - Client-side features that enhance user experience
3. **Backend Features** - Server-side functionality that requires database/controller changes
4. **Performance Optimizations** - Improvements to speed and efficiency
5. **Mobile Responsiveness** - Enhancements for mobile devices

## Layout Design Implementation Tasks

Based on the minimalist design reference from `@public/design-comp/card-4-ideal-2.html`, the following tasks need to be implemented:

### 1. Navigation System Enhancement
- Create minimalist header with logo, search bar, and user actions
- Implement secondary navigation with product categories
- Ensure responsive behavior for mobile devices
- Maintain consistent color scheme (#f6f3ef background, #222 text)

#### Detailed Implementation Steps:
- **Header Structure**: 
  - Logo area (200px × 100px) on left side
  - Search bar (934px × 50px) in center
  - User actions (194px × 50px) on right side with favorites, login/user profile buttons
  - Height: 116px, Max width: 1440px
- **Navigation Elements**:
  - Primary navigation menu below header
  - Secondary navigation with product categories
  - Mobile hamburger menu for responsive design
- **User Actions**:
  - Favorites button with heart icon
  - User profile/login button
  - Cart icon with item count badge

#### Technical Implementation Details:
- **CSS Variables**:
  ```css
  :root {
    --bg-color: #f6f3ef;
    --text-color: #222;
    --muted-color: #999;
    --line-color: #ddd;
  }
  ```
- **Header Structure**:
  ```jsx
  <header className="border-b border-sidebar-border/80">
    <div className="mx-auto flex h-[116px] w-full max-w-[1440px] items-center px-4">
      {/* Logo - 200px × 100px */}
      <div className="flex h-[100px] w-[200px] items-center justify-center">
        {/* Logo component */}
      </div>
      
      {/* Search Bar - 934px × 50px */}
      <div className="mx-4 flex h-[50px] flex-1 items-center">
        {/* Search form */}
      </div>
      
      {/* User Actions - 194px × 50px */}
      <div className="flex h-[50px] w-[194px] items-center justify-center gap-3">
        {/* Favorites, User profile buttons */}
      </div>
    </div>
  </header>
  ```
- **Component Dependencies**:
  - Use existing shadcn/ui components: Button, Input, DropdownMenu
  - Import Heart, User, Search icons from lucide-react
  - Maintain existing AppLogo component

### 2. Main Visual Section Implementation
- Add hero banner with promotional content
- Implement recommended products header section
- Ensure proper spacing and typography
- Maintain minimalist aesthetic with clean borders

#### Detailed Implementation Steps:
- **Hero Banner**:
  - Dimensions: 1408px × 300px
  - Background gradient: #f6f3ef to #f8f6f2
  - Promotional text with brand colors
  - Responsive design for all screen sizes
- **Recommended Header**:
  - "recommended" text header as seen in design
  - Border styling matching design reference
  - Proper padding and typography

#### Technical Implementation Details:
- **CSS Styling**:
  ```css
  .hero-banner {
    max-width: 1408px;
    height: 300px;
    background: linear-gradient(to right, #f6f3ef, #f8f6f2);
    border-top: 1px solid var(--line-color);
    border-left: 1px solid var(--line-color);
    border-right: 1px solid var(--line-color);
  }
  
  .recommended-header {
    max-width: 1440px;
    margin: 0 auto 0 auto;
    padding: 1.1rem 1rem 1.1rem 2rem;
    background: #f8f6f2;
    border-top: 1px solid var(--line-color);
    border-left: 1px solid var(--line-color);
    border-right: 1px solid var(--line-color);
    border-bottom: none;
    font-size: 1.08rem;
    font-weight: 600;
    color: var(--text-color);
    letter-spacing: 0.01em;
  }
  ```
- **Component Structure**:
  ```jsx
  {/* Hero Banner - 1408px × 300px */}
  <div className="mb-8 w-full">
    <div className="mx-auto w-full max-w-[1408px]">
      <div className="h-[300px] w-full bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/20 dark:to-orange-900/20 flex items-center justify-center rounded-lg">
        {/* Banner content */}
      </div>
    </div>
  </div>
  
  {/* Recommended Header */}
  <div className="recommended-header">recommended</div>
  ```

### 3. Filter Section Development
- Create collapsible filter sidebar with brand, category, price filters
- Implement filter toggle button with active state indicators
- Add sorting dropdown with multiple options
- Ensure consistent styling with design reference

#### Detailed Implementation Steps:
- **Filter Sidebar**:
  - Width: 300px
  - Collapsible functionality with toggle button
  - Border styling matching design reference
  - Filter sections for:
    - Search filter
    - Gender filter (すべて, メンズ, ウィメンズ, ユニセックス)
    - Brand filter with checkboxes
    - Price filter with input fields
    - Fragrance type filter with checkboxes
- **Filter Controls**:
  - Apply/Reset filter buttons
  - Active filter indicators
  - Clear individual filters functionality

#### Technical Implementation Details:
- **State Management**:
  ```jsx
  const [isFiltersOpen, setIsFiltersOpen] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedGenders, setSelectedGenders] = useState([]);
  const [selectedBrands, setSelectedBrands] = useState([]);
  const [priceRange, setPriceRange] = useState([0, 100000]);
  const [selectedFragranceTypes, setSelectedFragranceTypes] = useState([]);
  const [sortValue, setSortValue] = useState('');
  ```
- **Filter Toggle Button**:
  ```jsx
  <button 
    onClick={() => setIsFiltersOpen(!isFiltersOpen)}
    className="flex items-center gap-2 rounded border px-3 py-2 text-sm hover:bg-neutral-100"
  >
    <FilterIcon className="h-4 w-4" />
    <span>絞り込み</span>
    {activeFiltersCount > 0 && (
      <span className="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">
        {activeFiltersCount}
      </span>
    )}
  </button>
  ```
- **Component Dependencies**:
  - Use existing shadcn/ui components: Input, Checkbox, Select
  - Import Filter, X icons from lucide-react

### 4. Left Side Search Filter Implementation
- Implement advanced search and filtering options
- Add gender filtering (すべて, メンズ, ウィメンズ, ユニセックス)
- Create brand filtering with checkboxes
- Implement price range filtering with input fields
- Add fragrance type filtering with checkboxes
- Include apply/reset filter buttons

#### Detailed Implementation Steps:
- **Search Bar**:
  - Text input with search icon
  - Proper styling matching design
- **Gender Filter**:
  - Button group with 4 options
  - Selected state styling
  - Hover effects
- **Brand Filter**:
  - Scrollable checkbox list
  - Brand names with product counts
  - Check/uncheck functionality
- **Price Filter**:
  - Min/max price input fields
  - ¥ symbol prefix
  - Validation for numeric input
- **Fragrance Type Filter**:
  - Checkbox list with fragrance types
  - Labels with proper spacing

#### Technical Implementation Details:
- **Filter Functions**:
  ```jsx
  const toggleGender = (genderValue) => {
    setSelectedGenders(prev => 
      prev.includes(genderValue) 
        ? prev.filter(value => value !== genderValue) 
        : [...prev, genderValue]
    );
  };
  
  const toggleBrand = (brandSlug) => {
    setSelectedBrands(prev => 
      prev.includes(brandSlug) 
        ? prev.filter(slug => slug !== brandSlug) 
        : [...prev, brandSlug]
    );
  };
  
  const handlePriceChange = (index, value) => {
    const numValue = parseInt(value) || 0;
    const newRange = [...priceRange];
    newRange[index] = numValue;
    setPriceRange(newRange);
  };
  ```
- **Form Submission**:
  ```jsx
  const applyFilters = () => {
    const params = new URLSearchParams();
    
    if (searchQuery) params.set('q', searchQuery);
    if (selectedGenders.length > 0) params.set('gender', selectedGenders.join(','));
    if (selectedBrands.length > 0) params.set('brand', selectedBrands.join(','));
    if (priceRange[0] > 0) params.set('price_min', priceRange[0].toString());
    if (priceRange[1] < 100000) params.set('price_max', priceRange[1].toString());
    if (sortValue) params.set('sort', sortValue);
    
    router.get('/products', Object.fromEntries(params), {
      preserveState: true,
      preserveScroll: true,
    });
  };
  ```

### 5. Right Side Product Grid Enhancement
- Implement minimalist product cards with favorite buttons and add-to-cart
- Create 4-column grid layout (responsive for mobile)
- Add favorite button functionality with visual feedback
- Implement add-to-cart buttons with proper styling
- Ensure consistent spacing and typography
- Maintain product image sizing and alignment

#### Detailed Implementation Steps:
- **Grid Layout**:
  - 4-column grid on desktop (repeat(4, 1fr))
  - 2-column grid on tablet (repeat(2, 1fr))
  - 1-column grid on mobile (1fr)
  - Cell dimensions: min-height 620px, padding 3.2rem 2.2rem
- **Product Cards**:
  - Favorite button (♡/♥) in top-right corner
  - Product image (300px × 300px) centered
  - Product name with min-height 2.5em
  - Product price with color #888
  - Product rating with stars
  - Product description with min-height 2.2em
  - Add to cart button with border styling
- **Styling**:
  - Border styling matching design reference
  - Color palette: #f6f3ef background, #222 text, #888 price, #bbb rating
  - Typography: Helvetica Neue, proper font sizes
  - Spacing: Consistent padding and margins

#### Technical Implementation Details:
- **Grid CSS**:
  ```css
  .grid-container {
    max-width: 1440px;
    margin: auto;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-template-rows: auto;
    border-top: 1px solid var(--line-color);
    border-left: 1px solid var(--line-color);
  }
  
  .product-cell {
    border-right: 1px solid var(--line-color);
    border-bottom: 1px solid var(--line-color);
    padding: 3.2rem 2.2rem 3.2rem 2.2rem;
    text-align: center;
    position: relative;
    min-height: 620px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    height: 100%;
  }
  ```
- **Product Card JSX**:
  ```jsx
  <div className="product-cell">
    <button 
      className={`fav-btn ${isFavorited ? 'liked' : ''}`}
      onClick={toggleFavorite}
    >
      {isFavorited ? '♥' : '♡'}
    </button>
    <img 
      src={product.imageUrl} 
      alt={product.name} 
      className="product-image"
    />
    <div className="product-name">{product.name}</div>
    <div className="product-price">¥{product.price.toLocaleString()}</div>
    <div className="product-rating">
      <span>☆ ☆ ☆ ☆ ☆</span>
    </div>
    <div className="product-desc">{product.description}</div>
    <button 
      className="add-cart-btn"
      onClick={addToCart}
    >
      カートに追加
    </button>
  </div>
  ```
- **Responsive Design**:
  ```css
  @media (max-width: 1024px) {
    .grid-container {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (max-width: 600px) {
    .grid-container {
      grid-template-columns: 1fr;
    }
    .product-cell {
      min-height: 400px;
      padding: 2rem 1rem;
    }
  }
  ```

## Emergency Procedures

### If Something Breaks
1. **STOP ALL CHANGES** - Immediately halt development
2. **ROLLBACK** - Use git to revert to last working state
3. **IDENTIFY ISSUE** - Determine what caused the problem
4. **FIX OR RESTORE** - Either fix the issue or restore from backup
5. **TEST THOROUGHLY** - Ensure system is fully functional before continuing

### Backup Procedures
1. **DATABASE BACKUP** - Regular MySQL dumps
2. **CODE BACKUP** - Git version control
3. **CONFIGURATION BACKUP** - .env file and docker-compose.yml
4. **ASSET BACKUP** - Product images and other media

## Contact Information

For any questions or approvals regarding system changes:
- Project Owner: [Your Name]
- Technical Lead: [Your Name]
- Emergency Contact: [Your Contact Information]

---
*This documentation is maintained by Qwen AI Assistant and must be updated whenever system changes are made.*