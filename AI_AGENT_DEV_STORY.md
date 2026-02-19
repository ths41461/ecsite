# AI Fragrance Recommendation Agent - Development Story

**Version:** 1.3  
**Date:** February 18, 2026  
**Status:** Ready for Implementation  
**Approach:** Strict TDD (Test-First)  
**Methodology:** Research → Write Tests → Write Code → Run All Tests → PASS → Next Task

---

## 📋 EXECUTIVE SUMMARY

This Dev Story provides a **step-by-step implementation guide** for building the AI Fragrance Recommendation Agent following **strict Test-Driven Development (TDD)** principles.

### **Core Principles:**

1. **Research First** - Deep dive into existing codebase before writing any code
2. **Test First** - Write failing tests before implementation
3. **Real Data Only** - No mocks, no stubs, use production database
4. **100% Pass Rate** - All tests must pass before proceeding to next task
5. **Validation** - Cross-reference with Architecture Doc and existing patterns

### **Success Criteria:**

- Every task has accompanying tests (unit + feature + integration where applicable)
- All tests use **real production database** (MySQL, not SQLite for feature tests)
- All AI-related tests call **real APIs** (Gemini)
- **Zero skipped tests** unless physically impossible
- **90%+ code coverage** minimum
- **All code review issues** (HIGH, MEDIUM, LOW) auto-fixed

---

## 🎯 SPRINT STRUCTURE

### **Sprint Duration:** 3 Weeks (21 Days)

### **Total Story Points:** 54 points

### **Daily Standup Focus:** Tests written yesterday, tests passing today, blockers

---

## 🔴 PHASE 0: PREREQUISITES (MUST COMPLETE FIRST)

### **Goal:** Ensure all dependencies are ready before development

---

### **TASK 0.1: Seed the Database**

**Status:** 🔴 REQUIRED  
**Story Points:** 1  
**Time Estimate:** 5 minutes

#### **What to Do:**

```bash
# Run database seeders
php artisan db:seed

# Verify products exist
php artisan tinker --execute="echo 'Products: ' . App\Models\Product::count() . PHP_EOL;"
php artisan tinker --execute="echo 'Brands: ' . App\Models\Brand::count() . PHP_EOL;"
php artisan tinker --execute="echo 'Categories: ' . App\Models\Category::count() . PHP_EOL;"
```

**Expected Output:**

- Products: 120+
- Brands: 20+
- Categories: 10+

#### **Acceptance Criteria:**

- [ ] `php artisan db:seed` runs without errors
- [ ] Products table has > 100 records
- [ ] Each product has `attributes_json` with `notes` and `gender`

---

### **TASK 0.2: Get Gemini API Key**

**Status:** 🔴 REQUIRED  
**Story Points:** 1  
**Time Estimate:** 10 minutes

#### **What to Do:**

1. Go to https://ai.google.dev
2. Sign in with Google account
3. Create a new API key
4. Add to `.env`:
    ```
    GEMINI_API_KEY=AIza_your_key_here
    ```

#### **Verification:**

```bash
curl -s "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent" \
  -H "Content-Type: application/json" \
  -H "X-goog-api-key: $GEMINI_API_KEY" \
  -d '{"contents": [{"parts": [{"text": "Say hello"}]}]}'
```

**Expected:** JSON response with AI message

---

### **TASK 0.3: Verify Redis is Working**

**Status:** ✅ VERIFIED  
**Story Points:** 0  
**Time Estimate:** 1 minute

```bash
php artisan tinker --execute="use Illuminate\Support\Facades\Redis; echo Redis::ping();"
```

**Expected:** `PONG` or `+PONG`

---

## 📊 PHASE 0 SUMMARY

| Task | Status      | Description                 |
| ---- | ----------- | --------------------------- |
| 0.1  | 🔴 Required | Seed database with products |
| 0.2  | 🔴 Required | Get Gemini API key          |
| 0.3  | ✅ Verified | Redis working in Sail       |

**Cannot proceed to Phase 1 without completing Tasks 0.1-0.2!**

---

## 📚 PHASE 1: RESEARCH & FOUNDATION (Days 1-2)

### **Goal:** Understand existing codebase 100% before writing any code

---

### **TASK 1.1: Validate Existing Codebase Architecture**

**Status:** 🔍 RESEARCH  
**Story Points:** 3  
**Time Estimate:** 4 hours

#### **What to Research:**

**1. Existing Service Pattern Analysis**

- **File:** `app/Services/CartService.php` (26KB - primary reference)
- **File:** `app/Services/OrderService.php` (16KB)
- **File:** `app/Services/InventoryService.php` (4KB)

**Validation Checklist:**

- [x] How does CartService handle database transactions?
- [x] What is the method naming convention? (camelCase vs snake_case)
- [x] How are exceptions handled? (try-catch patterns)
- [x] What design patterns are used? (Repository, Factory, etc.)
- [x] How is caching implemented? (Cache facade usage)
- [x] What is the return type pattern? (arrays, objects, DTOs?)
- [x] How are dependencies injected? (Constructor injection pattern)

**Document Findings:**

```
Pattern Analysis Results:

Transaction Pattern:
- Uses DB::transaction(function () { ... }) for atomic operations
- Example: OrderService::createFromCart() wraps order + payment creation
- InventoryService::decrementForOrder() uses transactions for stock updates

Naming Convention:
- camelCase for all method names
- Examples: add(), update(), remove(), computeCartDigest(), findReusablePendingOrder()
- Private helper methods also camelCase: loadRaw(), saveRaw(), lineId(), stockBadge()

Error Handling:
- Log then throw pattern: \Log::error('Context', ['key' => 'value']) then throw
- ValidationException for input errors: ValidationException::withMessages(['field' => 'msg'])
- RuntimeException for business logic errors
- Example from CartService: Log variant check before throwing ValidationException

Design Patterns:
- Service Layer Pattern (NOT Repository pattern)
- No use of Repository, Factory, or Strategy patterns
- Eloquent ORM used directly in services
- Single Responsibility: Each service handles one domain (Cart, Order, Inventory)

Caching:
- Redis via Redis facade (NOT Cache facade)
- Pattern: Redis::setex($key, $ttl, json_encode($data))
- Pattern: Redis::get($key) then json_decode
- TTL loaded from config: config('cart.ttl_seconds', 14 * 24 * 60 * 60)
- Keys use naming convention: "cart:{$sessionId}" or "cart:user:{$userId}"

Return Types:
- Arrays for JSON responses: public function add(...): array
- Complex array shapes documented with PHPDoc @return annotations
- Nullable objects: ?Order
- Void for actions that don't return data: public function clear(...): void
- Always type-hinted, never mixed
- Example from CartService::get(): returns array with lines, subtotal_cents, total_cents, currency

Dependency Injection:
- Constructor injection with PHP 8+ property promotion
- Pattern: public function __construct(private DependencyType $dependency) {}
- Example: CartService::__construct(private CouponEligibilityService $couponEligibility)
- Example: OrderService::__construct(private CartService $cart)
- No setter injection or method injection used
- Dependencies are injected once and stored as private properties

Additional Patterns Found:
- Config values loaded in constructor with defaults
- Logging extensively: \Log::info() and \Log::error() throughout
- Raw SQL via DB::table() only when necessary (complex joins)
- Eloquent preferred: Model::with(['relations'])->where()->get()
- Model scopes used: scopeBySlug($query, string $slug)
- Casts defined in models: protected $casts = ['json' => 'array']
```

**2. Existing Controller Pattern Analysis**

- **File:** `app/Http/Controllers/CartController.php`
- **File:** `app/Http/Controllers/ProductController.php`
- **File:** `app/Http/Controllers/API/ShipmentTrackingController.php` (API reference)

**Validation Checklist:**

- [x] How are requests validated? (FormRequest vs inline validation)
- [x] What is the response format? (JSON structure pattern)
- [x] How is authentication checked? (Auth facade usage)
- [x] How are services called from controllers?
- [x] What HTTP status codes are used for different scenarios?
- [x] How are errors formatted in responses?

**Controller Pattern Findings:**

```
Request Validation:
- FormRequest classes for reusable validation: StoreCartRequest, UpdateCartRequest
- Inline validation using $request->validate([...]) in controller methods
- Type casting with $request->integer(), $request->string()
- Examples:
  * CartController uses StoreCartRequest and UpdateCartRequest
  * ShipmentTrackingController uses inline: $request->validate(['tracking_number' => 'required|string'])
  * ProductController uses direct parameter access with type casting

Response Format Pattern:
- API Controllers: Always return JsonResponse with consistent structure
- Standard structure: ['success' => true|false, 'data' => [...], 'message' => '...']
- Inertia Controllers: return Inertia::render('Component/Name', ['prop' => $data])
- JSON responses use response()->json($data) or response()->json($data, $status)

Authentication Check:
- Auth via $request->user()?->id (nullable safe)
- Session ID via $request->session()->getId()
- Example: $userId = $request->user()?->id;
- No explicit Auth::check() calls found, uses nullable operator instead

Service Calling Pattern:
- Constructor injection of services
- Direct method calls: $this->cart->add($sessionId, $variantId, $qty, $userId)
- Service handles all business logic, controller just orchestrates
- Example: CartController calls $this->cart->get($sessionId, $userId)

HTTP Status Codes:
- 200 OK - Standard success responses
- 201 Created - Resource created (not seen in examples, but standard)
- 404 Not Found - Resource not found (ShipmentTrackingController)
- 422 Unprocessable Entity - Validation errors (CartController applyCoupon)
- 500 Internal Server Error - Exception handling (ShipmentTrackingController)

Error Response Format:
- API errors: ['success' => false, 'message' => 'Error description']
- Validation errors: return response()->json(['message' => $ve->getMessage(), 'errors' => $ve->errors()], 422)
- Try-catch blocks wrap service calls in API controllers
- Logging before returning error: \Log::warning() then return error response
```

**3. Database Schema Verification**

- **Files:** All files in `database/migrations/`

**Validation Checklist:**

- [x] Verify all migrations run successfully
- [x] Confirm `products` table has all required columns
- [x] Confirm `inventories` table structure
- [x] Confirm `reviews` table structure
- [x] Check existing indexes on tables
- [x] Verify foreign key relationships

**Database Schema Findings:**

```
Migration Status:
- Total migration files: 60 (includes AI migrations added Feb 18)
- All migrations follow Laravel naming convention: YYYY_MM_DD_HHMMSS_description
- Migration files use anonymous class syntax: return new class extends Migration

Products Table Structure:
- id (bigint, PK)
- name (string, 120 chars)
- slug (string, 140 chars, unique)
- brand_id (FK to brands, cascade on delete)
- category_id (FK to categories, cascade on delete)
- short_desc (text, nullable)
- long_desc (mediumText, nullable)
- is_active (boolean, default true)
- featured (boolean, default false)
- attributes_json (json, nullable) - stores fragrance notes, radar, etc.
- meta_json (json, nullable) - stores seo, flags
- published_at (datetime, nullable)
- timestamps()
- Indexes: brand_id, category_id, [is_active, featured]

Inventories Table Structure:
- id (bigint, PK)
- product_variant_id (FK to product_variants, cascade on delete, unique)
- stock (integer, default 0)
- safety_stock (integer, default 0)
- managed (boolean, default true)
- updated_at (timestamp)
- Unique constraint on product_variant_id

Reviews Table Structure:
- id (bigint, PK)
- product_id (FK to products, cascade on delete)
- user_id (FK to users, nullable, null on delete)
- rating (tinyInteger, 1-5 scale)
- body (text)
- approved (boolean, default false)
- timestamps()
- Index: [product_id, approved]

Product Variants Table Structure:
- id (bigint, PK)
- product_id (FK to products, cascade on delete)
- sku (string, 64 chars, unique)
- option_json (json, nullable) - stores volume/size options
- price_yen (integer, unsigned)
- sale_price_yen (integer, unsigned, nullable)
- is_active (boolean, default true)
- timestamps()
- Index: [product_id, is_active]

Orders Table Structure:
- id (bigint, PK)
- order_number (string, 24 chars, unique)
- user_id (FK to users, nullable, null on delete)
- email (string, 120 chars)
- name (string, 120 chars)
- phone (string, 30 chars, nullable)
- address_line1 (string, 160 chars)
- address_line2 (string, 160 chars, nullable)
- city (string, 100 chars, nullable)
- state (string, 100 chars, nullable)
- zip (string, 20 chars, nullable)
- subtotal_yen (integer)
- tax_yen (integer, default 0)
- shipping_yen (integer, default 0)
- discount_yen (integer, default 0)
- total_yen (integer)
- payment_mode (enum: mock, stripe, default mock)
- status (enum: ordered, processing, shipped, delivered, canceled, refunded, default ordered)
- ordered_at (datetime)
- shipped_at (datetime, nullable)
- delivered_at (datetime, nullable)
- canceled_at (datetime, nullable)
- timestamps()
- Index: [status, ordered_at]

Foreign Key Patterns:
- Cascade on delete for product-related tables (products, product_variants, inventories)
- Null on delete for user-related tables (orders, reviews) to preserve historical data
- Restrict on delete for lookup tables (order_statuses)

Index Patterns:
- Foreign keys automatically indexed by Laravel
- Compound indexes for common query patterns: [is_active, featured], [product_id, approved]
- Unique constraints on business keys: slug, sku, order_number

JSON Columns Usage:
- attributes_json: stores flexible product attributes (fragrance notes, radar data)
- meta_json: stores SEO metadata and feature flags
- option_json: stores variant-specific options (volume, size_ml, gender)

Soft Deletes Pattern:
- Some tables have soft deletes added via later migrations (brands, categories)
- Not universally applied - check specific table migrations
```

**4. Frontend Pattern Analysis**

- **File:** `resources/js/pages/FragranceDiagnosis.tsx` (existing quiz)
- **File:** `resources/js/Components/ui/` (UI components)

**Validation Checklist:**

- [x] What component structure is used? (functional vs class)
- [x] How is state managed? (useState, useReducer?)
- [x] How are API calls made? (fetch, axios, Inertia?)
- [x] What UI component library? (shadcn/ui, Radix)
- [x] How is routing handled? (Inertia.js patterns)
- [x] What TypeScript patterns are used?

**Frontend Pattern Findings:**

```
Component Structure:
- Functional components only (no class components)
- Default export pattern: export default function ComponentName()
- Props defined with TypeScript interfaces/types
- Example from ProductCard.tsx:
  type ProductCardProps = { product: ProductCardData; disableCartDrawer?: boolean };
  export default function ProductCard({ product, disableCartDrawer = false }: ProductCardProps)

State Management:
- useState for local component state
- useRef for mutable references (timeouts, caches)
- useEffect for side effects (API calls, event listeners)
- No useReducer seen, useState is preferred
- Example from FragranceDiagnosis.tsx:
  const [step, setStep] = useState(1);
  const [answers, setAnswers] = useState<Record<string, string>>({});

API Calls:
- Standard fetch() API for AJAX calls (not axios)
- Inertia.js router for page navigation: router.get(), router.post(), router.visit()
- Credentials: 'same-origin' for authenticated requests
- Example from homeNavigation.tsx:
  const response = await fetch('/cart', { headers: {...}, credentials: 'same-origin' });
  const cartData: Cart = await response.json();

UI Component Library:
- shadcn/ui components (Button, Card, Dialog, etc.)
- Radix UI primitives (@radix-ui/react-slot, etc.)
- Tailwind CSS for styling
- class-variance-authority (cva) for variant management
- Example from button.tsx: uses cva() for variant definitions

Routing:
- Inertia.js for SPA-like navigation without full page reloads
- Link component from @inertiajs/react for navigation links
- router.get(), router.post(), router.visit() for programmatic navigation
- Example from ProductCard.tsx: <Link href={`/products/${product.slug}`} />

TypeScript Patterns:
- Strict typing with interfaces and type aliases
- Generic types used where appropriate
- Nullable types: type CartLine = { ..., available_qty: number | null; }
- Function return types implied, not explicitly declared
- Type imports: import type { VariantProps } from "class-variance-authority"
- Example from ProductCard.tsx:
  export type ProductCardData = { id: number; slug: string; ... }

Design Tokens (from FragranceDiagnosis.tsx):
- Background: bg-white
- Container: max-w-4xl mx-auto px-4 py-8
- Headings: text-[#0D0D0D] (black)
- Body text: text-[#444444] (dark gray)
- Borders: border-[#888888] (gray)
- Accent: bg-[#0D0D0D] text-white (black/white buttons)
- Font: Default Tailwind sans-serif

Event Handling:
- Inline arrow functions in JSX: onClick={() => handleAnswer(id, option)}
- Event type annotations: (e: React.ChangeEvent<HTMLInputElement>)
- Keyboard events: onKeyDown={(e: React.KeyboardEvent<HTMLInputElement>) => {...}}

Custom Hooks Pattern:
- Not extensively used, inline useEffect preferred
- Utility functions extracted to lib/ directory (getFreshReviewDataForProduct)

Styling Patterns:
- Tailwind CSS utility classes
- Conditional classes with template literals: className={`... ${condition ? 'active' : ''}`}
- cn() utility from @/lib/utils for class merging
- Arbitrary values: text-[#0D0D0D], w-[288px], h-[392px]
```

#### **Acceptance Criteria:**

- [x] Document all patterns found in existing code
- [x] Create pattern reference guide for new code
- [x] Verify Architecture Doc aligns with actual codebase
- [x] Note any discrepancies between Architecture Doc and reality
- [x] Get 100% clarity on naming conventions and structure

#### **Definition of Done:**

- [x] Pattern analysis document created (documented in Dev Story sections 1-4)
- [x] All existing service/controller patterns documented
- [x] Database schema verified (60 migrations, key tables documented)
- [x] Frontend patterns analyzed (React, TypeScript, Inertia.js)
- [x] No code written yet (research phase only) - VERIFIED
- [x] Architecture Doc aligns with actual codebase

**Discrepancies Found:**

1. PHP Version: Document says 8.3.6, actual is 8.4.12 (this is GOOD - Laravel AI SDK compatible)
2. Caching: Uses Redis facade, not Cache facade (both work, Redis is more specific)
3. No major discrepancies in core patterns - all align with Architecture Doc

---

### **TASK 1.2: Set Up Testing Infrastructure**

**Status:** ✅ COMPLETE  
**Story Points:** 2  
**Time Estimate:** 3 hours  
**Dependencies:** Task 1.1 complete

#### **What to Implement:**

**1. Verify Testing Tools** ✅

```bash
# Check installed packages - COMPLETED
composer show | grep pest        # pestphp/pest 4.0.4 ✓
composer show | grep mockery     # mockery/mockery 1.6.12 ✓
composer show | grep dusk        # laravel/dusk 8.3.6 ✓

# All testing tools verified and installed
```

**2. Install Laravel Dusk** ✅

```bash
composer require --dev laravel/dusk    # COMPLETED
php artisan dusk:install               # ChromeDriver installed ✓
```

**Status:**

- ✅ Laravel Dusk v8.3.6 installed
- ✅ ChromeDriver v145.0.7632.76 installed
- ✅ Dusk scaffolding created

**3. Configure Test Environment** ✅

**File:** `phpunit.xml` (modify existing)

**What to Configure:**

- ✅ Set DB_CONNECTION to mysql (for production DB testing)
- ✅ Set DB_DATABASE to 'laravel' (production)
- ✅ Configure transaction rollback
- ✅ Set up test groups (Unit, Feature, Integration, Browser)

**Configuration Applied:**

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="laravel"/>
<env name="DB_TRANSACTION_ROLLBACK" value="true"/>
```

**4. Create Test Directory Structure** ✅

```
tests/
├── Unit/Services/AI/           ✅ CREATED
├── Feature/AI/                 ✅ CREATED
├── Integration/AIProviders/    ✅ CREATED
├── Browser/                    ✅ CREATED
└── Tinker/                     ✅ CREATED
```

**5. Create Test Helper Script** ✅

**Created:** `run-tests.sh` - Sail-compatible test runner

Usage:

```bash
./run-tests.sh              # Run all tests
./run-tests.sh --unit       # Run unit tests only
./run-tests.sh --feature    # Run feature tests only
./run-tests.sh --coverage   # Run with coverage (min 90%)
```

**Validation:**

- ✅ `php artisan test` runs without errors
- ✅ Test database connection works (MySQL via Sail)
- ✅ All test suites are discoverable
- ✅ Infrastructure test created and passing

#### **Acceptance Criteria:**

- [x] Dusk installed and configured
- [x] Test directory structure created
- [x] Infrastructure test created
- [x] `phpunit.xml` configured for production DB testing
- [x] `./vendor/bin/sail pest` runs successfully

#### **Tests Required:**

- **Infrastructure Test:** `tests/Unit/Infrastructure/SetupTest.php` ✅ CREATED & PASSING
    - ✅ Verify PestPHP is working
    - ✅ Verify database connection (MySQL via Sail)
    - ✅ Verify products table exists
    - ✅ Verify products table has correct columns
    - ✅ Verify test environment configuration

**Test Results (All Passing):**

```
✓ pestphp is working correctly
✓ database connection is working
✓ products table exists
✓ products table has correct columns
✓ test environment is properly configured

Tests:    5 passed (13 assertions)
Duration: 6.61s
```

✓ pestphp is working correctly
✓ database connection is working
✓ test environment is properly configured

````

#### **Definition of Done:**

- ✅ Run `./vendor/bin/sail pest` → Infrastructure tests passing
- ✅ No errors in test infrastructure
- ✅ MySQL connection verified through Sail
- ✅ Ready to write real tests
- ✅ Test runner script created (`run-tests.sh`)

---

## 🗄️ PHASE 2: DATABASE & MODELS (Days 3-4)

### **Goal:** Create database schema and models with 100% test coverage

---

### **TASK 2.1: Create AI Chat Sessions Migration & Model**

**Status:** ✅ COMPLETE
**Story Points:** 2
**Time Estimate:** 2 hours
**Actual Time:** ~30 minutes

#### **TDD Cycle Executed:**

**Step 1: Write Failing Test** ✅
- **File:** `tests/Feature/Database/AiChatSessionMigrationTest.php`
- **Tests Written:** 4 tests
- **Initial Result:** ❌ FAIL (Class "App\Models\AiChatSession" not found)

**Step 2: Write Migration Code** ✅
- **File:** `database/migrations/2026_02_18_000001_create_ai_chat_sessions_table.php`
- **Note:** Migration was pre-existing but not yet run

**Step 3: Run Migration** ✅
- Migration already ran in database

**Step 4: Create Model** ✅
- **File:** `app/Models/AiChatSession.php`
- Created with:
  - `$fillable`: user_id, session_token, quiz_result_id, context_json
  - `$casts`: context_json => 'array'
  - Relationships: user(), messages(), quizResult()

**Step 5: Run Tests** ✅
- **Result:** 4 passed (18 assertions)

#### **Test Results:**
```
✓ ai_chat_sessions table has correct columns
✓ ai_chat_session model can create record
✓ ai_chat_session model has correct fillable attributes
✓ ai_chat_session model casts context_json as array

Tests:    4 passed (18 assertions)
```

#### **Acceptance Criteria:**

- [x] Migration creates table successfully
- [x] Model can create/read/update/delete records
- [x] All columns have correct types
- [x] Foreign keys work correctly (quiz_result_id FK added in Task 2.3)
- [x] Indexes are created (session_token unique)
- [x] **Tests pass 100%**

#### **Definition of Done:**

- [x] Migration file created and run
- [x] Model created with relationships
- [x] Database tests passing
- [x] Can insert test record via Tinker

---

### **TASK 2.2: Create AI Messages Migration & Model**

**Status:** ✅ COMPLETE
**Story Points:** 2
**Time Estimate:** 2 hours
**Actual Time:** ~20 minutes
**Pattern:** Same as Task 2.1

#### **TDD Cycle Executed:**

**Step 1: Write Failing Test** ✅
- **File:** `tests/Feature/Database/AiMessageMigrationTest.php`
- **Tests Written:** 6 tests
- **Initial Result:** ❌ FAIL (Table 'ai_messages' doesn't exist)

**Step 2: Write Migration Code** ✅
- **File:** `database/migrations/2026_02_18_000002_create_ai_messages_table.php`
- Columns: id, session_id (FK cascade), role (enum), content (text), metadata_json, timestamps
- Indexes: [session_id, created_at]

**Step 3: Run Migration** ✅
```bash
./vendor/bin/sail php artisan migrate --path=database/migrations/2026_02_18_000002_create_ai_messages_table.php
```

**Step 4: Create Model** ✅
- **File:** `app/Models/AiMessage.php`
- Created with:
  - `$fillable`: session_id, role, content, metadata_json
  - `$casts`: metadata_json => 'array'
  - Relationships: session() belongsTo AiChatSession

**Step 5: Run Tests** ✅
- **Result:** 6 passed (25 assertions)

#### **Test Results:**
```
✓ ai_messages table has correct columns
✓ ai_message model can create record
✓ ai_message model has correct fillable attributes
✓ ai_message model casts metadata_json as array
✓ ai_message belongs to ai_chat_session
✓ ai_message role must be valid enum value

Tests:    6 passed (25 assertions)
```

#### **Acceptance Criteria:**

- [x] Table created with all columns
- [x] Foreign key to ai_chat_sessions (cascade on delete)
- [x] Model with casts for metadata_json
- [x] **Tests pass 100%**

---

### **TASK 2.3: Create Quiz Results Migration & Model**

**Status:** ✅ COMPLETE
**Story Points:** 2
**Time Estimate:** 2 hours
**Actual Time:** ~20 minutes

#### **TDD Cycle Executed:**

**Step 1: Write Failing Test** ✅
- **File:** `tests/Feature/Database/QuizResultMigrationTest.php`
- **Tests Written:** 6 tests
- **Initial Result:** ❌ FAIL (Table 'quiz_results' doesn't exist)

**Step 2: Write Migration Code** ✅
- **File:** `database/migrations/2026_02_18_000003_create_quiz_results_table.php`
- Columns: id, user_id (FK nullable), session_token, answers_json, profile_type, profile_data_json, recommended_product_ids, timestamps
- Indexes: session_token, user_id, profile_type

**Step 3: Run Migration** ✅
```bash
./vendor/bin/sail php artisan migrate --path=database/migrations/2026_02_18_000003_create_quiz_results_table.php
```

**Step 4: Create Model** ✅
- **File:** `app/Models/QuizResult.php`
- Created with:
  - `$fillable`: user_id, session_token, answers_json, profile_type, profile_data_json, recommended_product_ids
  - `$casts`: answers_json, profile_data_json, recommended_product_ids => 'array'
  - Relationships: user() belongsTo User

**Step 5: Add Foreign Key to ai_chat_sessions** ✅
- **File:** `database/migrations/2026_02_18_000004_add_quiz_result_foreign_to_ai_chat_sessions.php`
- Added FK: quiz_result_id -> quiz_results.id (nullOnDelete)

**Step 6: Run Tests** ✅
- **Result:** 6 passed (34 assertions)

#### **Test Results:**
```
✓ quiz_results table has correct columns
✓ quiz_result model can create record
✓ quiz_result model has correct fillable attributes
✓ quiz_result model casts json fields as array
✓ quiz_result belongs to user
✓ quiz_result can be created without user (anonymous)

Tests:    6 passed (34 assertions)
```

#### **Acceptance Criteria:**

- [x] Table stores quiz answers as JSON
- [x] Profile type stored correctly
- [x] Recommended product IDs stored as JSON array
- [x] **Tests pass 100%**

---

### **TASK 2.4: Create User Scent Profiles Migration & Model**

**Status:** ✅ COMPLETE
**Story Points:** 2
**Time Estimate:** 2 hours
**Actual Time:** ~15 minutes

#### **TDD Cycle Executed:**

**Step 1: Write Failing Test** ✅
- **File:** `tests/Feature/Database/UserScentProfileMigrationTest.php`
- **Tests Written:** 6 tests
- **Initial Result:** ❌ FAIL (Table 'user_scent_profiles' doesn't exist)

**Step 2: Write Migration Code** ✅
- **File:** `database/migrations/2026_02_18_000005_create_user_scent_profiles_table.php`
- Columns: id, user_id (FK unique), profile_type, profile_data_json, preferences_json, timestamps
- Indexes: user_id (unique), profile_type

**Step 3: Run Migration** ✅
```bash
./vendor/bin/sail php artisan migrate --path=database/migrations/2026_02_18_000005_create_user_scent_profiles_table.php
```

**Step 4: Create Model** ✅
- **File:** `app/Models/UserScentProfile.php`
- Created with:
  - `$fillable`: user_id, profile_type, profile_data_json, preferences_json
  - `$casts`: profile_data_json, preferences_json => 'array'
  - Relationships: user() belongsTo User

**Step 5: Run Tests** ✅
- **Result:** 6 passed (23 assertions)

#### **Test Results:**
```
✓ user_scent_profiles table has correct columns
✓ user_scent_profile model can create record
✓ user_scent_profile model has correct fillable attributes
✓ user_scent_profile model casts json fields as array
✓ user_scent_profile belongs to user
✓ user_scent_profile has unique constraint on user_id

Tests:    6 passed (23 assertions)
```

#### **Acceptance Criteria:**

- [x] Unique constraint on user_id
- [x] Profile data stored as JSON
- [x] **Tests pass 100%**

---

### **TASK 2.5: Create AI Recommendation Cache Migration & Model**

**Status:** ✅ COMPLETE
**Story Points:** 2
**Time Estimate:** 2 hours
**Actual Time:** ~20 minutes

#### **TDD Cycle Executed:**

**Step 1: Write Failing Test** ✅
- **File:** `tests/Feature/Database/AIRecommendationCacheMigrationTest.php`
- **Tests Written:** 7 tests
- **Initial Result:** ❌ FAIL (Table 'ai_recommendation_cache' doesn't exist)

**Step 2: Write Migration Code** ✅
- **File:** `database/migrations/2026_02_18_000006_create_ai_recommendation_cache_table.php`
- Columns: id, cache_key (unique), context_hash, product_ids_json, explanation (nullable), expires_at, timestamps
- Indexes: cache_key (unique), expires_at, context_hash

**Step 3: Run Migration** ✅
```bash
./vendor/bin/sail php artisan migrate --path=database/migrations/2026_02_18_000006_create_ai_recommendation_cache_table.php
```

**Step 4: Create Model** ✅
- **File:** `app/Models/AIRecommendationCache.php`
- Created with:
  - `$table`: 'ai_recommendation_cache' (explicit, due to Laravel pluralization issue)
  - `$fillable`: cache_key, context_hash, product_ids_json, explanation, expires_at
  - `$casts`: product_ids_json => 'array', expires_at => 'datetime'

**Step 5: Run Tests** ✅
- **Result:** 7 passed (26 assertions)

#### **Test Results:**
```
✓ ai_recommendation_cache table has correct columns
✓ ai_recommendation_cache model can create record
✓ ai_recommendation_cache model has correct fillable attributes
✓ ai_recommendation_cache model casts product_ids_json as array
✓ ai_recommendation_cache model casts expires_at as datetime
✓ ai_recommendation_cache cache_key must be unique
✓ ai_recommendation_cache can check if expired

Tests:    7 passed (26 assertions)
```

#### **Acceptance Criteria:**

- [x] Cache key is unique
- [x] Expiration timestamp works
- [x] **Tests pass 100%**

---

## 📊 PHASE 2 SUMMARY

### **Status:** ✅ COMPLETE

### **Total Tests:** 29 passed (126 assertions)

### **Files Created:**

**Migrations (6 files):**
1. `database/migrations/2026_02_18_000001_create_ai_chat_sessions_table.php`
2. `database/migrations/2026_02_18_000002_create_ai_messages_table.php`
3. `database/migrations/2026_02_18_000003_create_quiz_results_table.php`
4. `database/migrations/2026_02_18_000004_add_quiz_result_foreign_to_ai_chat_sessions.php`
5. `database/migrations/2026_02_18_000005_create_user_scent_profiles_table.php`
6. `database/migrations/2026_02_18_000006_create_ai_recommendation_cache_table.php`

**Models (5 files):**
1. `app/Models/AiChatSession.php`
2. `app/Models/AiMessage.php`
3. `app/Models/QuizResult.php`
4. `app/Models/UserScentProfile.php`
5. `app/Models/AIRecommendationCache.php`

**Tests (5 files):**
1. `tests/Feature/Database/AiChatSessionMigrationTest.php` (4 tests)
2. `tests/Feature/Database/AiMessageMigrationTest.php` (6 tests)
3. `tests/Feature/Database/QuizResultMigrationTest.php` (6 tests)
4. `tests/Feature/Database/UserScentProfileMigrationTest.php` (6 tests)
5. `tests/Feature/Database/AIRecommendationCacheMigrationTest.php` (7 tests)

### **Test Summary:**
```
✓ AiChatSessionMigrationTest:    4 passed (18 assertions)
✓ AiMessageMigrationTest:        6 passed (25 assertions)
✓ QuizResultMigrationTest:      6 passed (34 assertions)
✓ UserScentProfileMigrationTest: 6 passed (23 assertions)
✓ AIRecommendationCacheTest:     7 passed (26 assertions)

TOTAL: 29 passed (126 assertions)
```

### **Definition of Done:**

- [x] All migrations run successfully
- [x] All models created with correct relationships
- [x] All tests passing (100%)
- [x] Follows existing codebase patterns
- [x] TDD approach followed (tests written first)

---

## ⚙️ PHASE 3: BACKEND SERVICES (Days 5-10)

### **Goal:** Build all AI services following existing CartService pattern

---

### **TASK 3.1: Create ContextBuilder Service**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 3
**Time Estimate:** 4 hours

#### **Prerequisites:**

- Task 1.1 complete (understand CartService pattern)
- All migrations from Phase 2 complete

#### **TDD Cycle:**

**Step 1: Write Failing Tests**

**File:** `tests/Unit/Services/AI/ContextBuilderTest.php`

**Tests to Write:**

1. `test_build_returns_user_profile_structure()`
2. `test_build_returns_available_products()`
3. `test_build_respects_budget_constraint()`
4. `test_build_returns_trending_products()`
5. `test_build_returns_top_rated_products()`
6. `test_build_for_chat_returns_correct_context()`

**Important:** Tests must use **real products** from production DB

```php
test('build returns available products under budget', function () {
    $builder = new ContextBuilder();
    $quizData = ['budget' => 3000, 'personality' => 'romantic'];

    $context = $builder->build($quizData);

    // Verify real products returned
    expect($context['available_products'])->toBeArray();

    if (count($context['available_products']) > 0) {
        $product = $context['available_products'][0];
        expect($product['min_price'])->toBeLessThanOrEqual(3000);
    }
});
```

**Run:**

```bash
./vendor/bin/pest tests/Unit/Services/AI/ContextBuilderTest.php
```

**Expected:** ❌ FAIL (service doesn't exist)

---

**Step 2: Write Service Code**

**File:** `app/Services/AI/ContextBuilder.php`

**Implementation Requirements:**

- Follow CartService pattern exactly
- Use Eloquent ORM (not raw SQL)
- Return arrays, not objects
- Handle null cases gracefully

**Reference:** Architecture Doc Section 5.3

---

**Step 3: Run Unit Tests**

```bash
./vendor/bin/pest tests/Unit/Services/AI/ContextBuilderTest.php
```

**Expected:** ✅ PASS

---

**Step 4: Write Feature Tests**

**File:** `tests/Feature/Services/ContextBuilderIntegrationTest.php`

**Test with real database:**

```php
test('context builder uses real production products', function () {
    // This test runs against production DB
    $productCount = Product::count();
    expect($productCount)->toBeGreaterThan(0);

    $builder = new ContextBuilder();
    $context = $builder->build(['budget' => 5000]);

    // Should return real products
    expect(count($context['available_products']))->toBeGreaterThan(0);
});
```

---

**Step 5: Run All Tests**

```bash
./vendor/bin/pest tests/Unit/Services/AI/ContextBuilderTest.php
./vendor/bin/pest tests/Feature/Services/ContextBuilderIntegrationTest.php
```

**Expected:** ✅ All PASS

#### **Acceptance Criteria:**

- [ ] Service returns correct context structure
- [ ] Uses real products from production DB
- [ ] Respects budget constraints
- [ ] Handles edge cases (no products, etc.)
- [ ] **Unit tests pass**
- [ ] **Feature tests pass**

---

### **TASK 3.2: Create ToolRegistry Service**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 3
**Time Estimate:** 4 hours

**Pattern:** Same TDD approach as Task 3.1

**Test File:** `tests/Unit/Services/AI/ToolRegistryTest.php`

**Service File:** `app/Services/AI/ToolRegistry.php`

**Tools to Implement:**

1. `search_products()` - Query product catalog
2. `check_inventory()` - Check stock levels
3. `get_product_reviews()` - Get ratings

**Tests Must Verify:**

- Returns real product data from DB
- Inventory checks return real stock levels
- Review aggregation works correctly

---

### **TASK 3.3: Create ResponseParser Service**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 2
**Time Estimate:** 3 hours

**Test File:** `tests/Unit/Services/AI/ResponseParserTest.php`

**Service File:** `app/Services/AI/ResponseParser.php`

**What to Parse:**

- Gemini API responses
- JSON extraction from text
- Tool call parsing
- Error handling

---

### **TASK 3.4: Create GeminiProvider (Primary AI Provider)**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 5
**Time Estimate:** 6 hours

**CRITICAL:** This task calls **REAL Gemini API** (get key from ai.google.dev)

#### **Prerequisites:**

- Have Gemini API key ready (Task 0.2)
- Understand rate limits (Primary: 15 RPM, 1,000/day; Fallback: 10 RPM, 250/day)

#### **TDD Cycle:**

**Step 1: Write Failing Tests**

**File:** `tests/Integration/AIProviders/GeminiLiveTest.php`

```php
// Mark as live API test
test('gemini provider returns real response', function () {
    $provider = new GeminiProvider();

    $response = $provider->chat(
        'Hello, this is a test',
        ['budget' => 5000]
    );

    expect($response)->toHaveKey('message');
    expect($response['message'])->toBeString();
})->group('live-api', 'gemini');
```

**Step 2: Create Provider Class**

**File:** `app/Services/AI/Providers/GeminiProvider.php`

**Step 3: Configure API Key**

Add to `.env`:

```
GEMINI_API_KEY=AIza_your_key_here
```

**Step 4: Run Tests (Costs 1 API request)**

```bash
./vendor/bin/pest tests/Integration/AIProviders/GeminiLiveTest.php --group=live-api
```

**Expected:** ✅ PASS (if API key is valid)

**Step 5: Write More Tests**

- Tool calling test
- Error handling test
- Japanese language test
- Fallback model test

**Step 6: Run All Gemini Tests**

```bash
./vendor/bin/pest tests/Integration/AIProviders/GeminiLiveTest.php
```

**Monitor API Usage:**

- Check Google AI Studio for quota usage
- Should use ~3-5 requests for all tests

#### **Acceptance Criteria:**

- [ ] Successfully calls real Gemini API
- [ ] Handles tool calling correctly
- [ ] Parses responses properly
- [ ] Error handling works
- [ ] Fallback model works when primary fails
- [ ] **All live API tests pass**

---

### **TASK 3.5: Create Model Fallback Logic**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 2
**Time Estimate:** 2 hours

**Pattern:** Same as Task 3.4

**Test File:** `tests/Integration/AIProviders/ModelFallbackTest.php`

**What to Test:**

- Primary model (gemini-2.5-flash-lite) works
- Fallback to gemini-2.5-flash when primary fails
- Rate limit handling
- Graceful error when both models fail

**API Models:**
- Primary: `gemini-2.5-flash-lite` (15 RPM, 1,000 RPD)
- Fallback: `gemini-2.5-flash` (10 RPM, 250 RPD)

---

### **TASK 3.6: Create ReActAgentEngine**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 5
**Time Estimate:** 6 hours

**This is the CORE AI agent logic**

**Test File:** `tests/Unit/Services/AI/ReActAgentEngineTest.php`

**Service File:** `app/Services/AI/ReActAgentEngine.php`

**What to Test:**

1. ReAct loop executes correctly
2. Tool calling works end-to-end
3. Max iteration limits enforced
4. Final response parsing works

**Integration Test:** `tests/Integration/ReActLoopTest.php`

This test uses **real AI provider** + **real database** together

---

### **TASK 3.7: Create AIRecommendationService (Main Service)**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 5
**Time Estimate:** 6 hours

**This is the main service that ties everything together**

**Test File:** `tests/Unit/Services/AI/AIRecommendationServiceTest.php`

**Service File:** `app/Services/AI/AIRecommendationService.php`

**Feature Test:** `tests/Feature/Services/AIRecommendationServiceTest.php`

**Integration Test:** `tests/Integration/FullRecommendationFlowTest.php`

**What to Test:**

- End-to-end recommendation flow
- Caching works correctly
- Fallback to secondary model when primary fails
- Error handling

**This test runs the FULL flow:**

1. Real quiz data
2. Real AI API call (Gemini)
3. Real database queries
4. Real product recommendations returned

---

## 🌐 PHASE 4: API CONTROLLERS (Days 11-13)

### **Goal:** Create REST API endpoints with 100% test coverage

---

### **TASK 4.1: Create Form Request Validations**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 2
**Time Estimate:** 3 hours

**Files:**

- `app/Http/Requests/SubmitQuizRequest.php`
- `app/Http/Requests/SendChatMessageRequest.php`

**Test Files:**

- `tests/Unit/Requests/SubmitQuizRequestTest.php`
- `tests/Unit/Requests/SendChatMessageRequestTest.php`

**What to Test:**

- All validation rules work
- Error messages are in Japanese
- All required fields validated
- Data types validated

---

### **TASK 4.2: Create AIRecommendationController**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 4
**Time Estimate:** 5 hours

**File:** `app/Http/Controllers/API/AIRecommendationController.php`

**Test File:** `tests/Feature/API/AIRecommendationControllerTest.php`

**Endpoints to Implement:**

1. `POST /api/ai/quiz` - Submit quiz
2. `GET /api/ai/recommendations` - Get filtered recommendations

**What to Test:**

- Endpoint returns correct HTTP status
- Response has correct JSON structure
- Uses real production database
- Rate limiting works
- Authentication works (if user logged in)

**Example Test:**

```php
test('quiz endpoint returns real recommendations', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/ai/quiz', [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'profile',
            'recommendations',
            'session_id',
        ]);

    // Verify real products returned
    $recommendations = $response->json('recommendations');
    expect(count($recommendations))->toBeGreaterThanOrEqual(5);
});
```

---

### **TASK 4.3: Create ChatController**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 3
**Time Estimate:** 4 hours

**File:** `app/Http/Controllers/API/ChatController.php`

**Test File:** `tests/Feature/API/ChatControllerTest.php`

**Endpoint:** `POST /api/ai/chat`

**Tests Must Verify:**

- Chat message saved to database
- AI response received (real API call)
- Response includes message and products
- Session validation works

---

### **TASK 4.4: Configure API Routes**

**Status:** 🔧 CONFIGURATION
**Story Points:** 1
**Time Estimate:** 1 hour

**File:** `routes/api.php`

**What to Add:**

- Route definitions
- Rate limiting middleware
- Authentication middleware

**Test File:** `tests/Feature/API/RoutesTest.php`

**Tests:**

- All routes respond correctly
- Rate limiting blocks excessive requests
- Authentication required where needed

---

## 🎨 PHASE 5: FRONTEND COMPONENTS (Days 14-17)

### **Goal:** Build React components with E2E browser testing

---

### **TASK 5.1: Create FragranceDiagnosisResults Page**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 4
**Time Estimate:** 5 hours

**File:** `resources/js/pages/FragranceDiagnosisResults.tsx`

**Test File:** `tests/Browser/FragranceDiagnosisResultsTest.php` (Dusk)

**What to Build:**

- Results page layout
- Scent profile card display
- Product grid with filters
- Chat button

**Dusk Test:**

```php
test('results page displays scent profile', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/fragrance-diagnosis/results')
            ->waitFor('@results-container')
            ->assertSee('@scent-profile-card')
            ->assertSee('@product-recommendations');
    });
});
```

---

### **TASK 5.2: Create Chat Components**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 5
**Time Estimate:** 6 hours

**Files:**

- `resources/js/components/AIChat/ChatContainer.tsx`
- `resources/js/components/AIChat/MessageBubble.tsx`
- `resources/js/components/AIChat/ChatInput.tsx`

**Test File:** `tests/Browser/ChatInteractionTest.php`

**What to Test:**

- User can type message
- AI responds (real API call)
- Product cards appear in chat
- Chat history persists

---

### **TASK 5.3: Enhance FragranceDiagnosis Quiz**

**Status:** 🧪 TDD - Write Test First
**Story Points:** 4
**Time Estimate:** 5 hours

**File:** `resources/js/pages/FragranceDiagnosis.tsx` (modify existing)

**Test File:** `tests/Browser/FragranceDiagnosisTest.php`

**What to Modify:**

- Change from 5 to 7 questions
- Add visual cards (not just buttons)
- Add API integration
- Remove alert popup

**Dusk Test (Full Flow):**

```php
test('user can complete full quiz and get recommendations', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/fragrance-diagnosis')
            // Answer all 7 questions
            ->click('@option-romantic')
            ->click('@option-floral')
            ->click('@option-daily')
            ->click('@option-feminine')
            ->type('@budget-input', '5000')
            ->click('@option-beginner')
            ->click('@option-spring')
            // Submit
            ->click('@submit-button')
            // Wait for AI response
            ->waitFor('@results-container', 10)
            // Verify results
            ->assertSee('あなたにおすすめの香水');
    });
});
```

---

## 🧪 PHASE 6: COMPREHENSIVE TESTING (Days 18-19)

### **Goal:** Achieve 90%+ coverage, all tests pass

---

### **TASK 6.1: Run Full Test Suite**

**Status:** 🧪 TESTING
**Story Points:** 3
**Time Estimate:** 4 hours

**Commands:**

```bash
# Unit tests only (fast)
./vendor/bin/pest --group=unit

# Feature tests (production DB)
./vendor/bin/pest --group=feature

# Integration tests (live APIs)
./vendor/bin/pest --group=live-api

# Dusk tests (browser)
php artisan dusk

# All tests with coverage
./vendor/bin/pest --coverage --min=90
```

**Acceptance Criteria:**

- [ ] 90%+ code coverage
- [ ] 100% of tests pass
- [ ] No skipped tests
- [ ] All feature tests use production DB
- [ ] All integration tests call real APIs

---

### **TASK 6.2: Create Tinker Scripts**

**Status:** 🔧 UTILITIES
**Story Points:** 2
**Time Estimate:** 2 hours

**Files:**

- `tests/Tinker/verify-products.php`
- `tests/Tinker/ai-test.php`
- `tests/Tinker/quiz-debug.php`

**These are manual testing tools**

---

### **TASK 6.3: Load Testing**

**Status:** 🧪 PERFORMANCE
**Story Points:** 2
**Time Estimate:** 2 hours

**What to Test:**

- API response times under load
- Database query performance
- Cache hit rates

**Acceptance Criteria:**

- Quiz API < 2s response time
- Chat API < 3s response time
- 100 concurrent users supported

---

## 🚀 PHASE 7: DEPLOYMENT (Days 20-21)

### **Goal:** Deploy to production safely

---

### **TASK 7.1: Production Deployment Checklist**

**Status:** 🚀 DEPLOYMENT
**Story Points:** 3
**Time Estimate:** 4 hours

**Checklist:**

- [ ] Set production environment variables (API keys)
- [ ] Run migrations on production DB
- [ ] Configure rate limiting for production
- [ ] Set up monitoring and logging
- [ ] Configure caching (Redis recommended)
- [ ] Test in staging environment first
- [ ] Deploy to production
- [ ] Run smoke tests on production
- [ ] Monitor error rates

---

### **TASK 7.2: Post-Deployment Verification**

**Status:** ✅ VERIFICATION
**Story Points:** 2
**Time Estimate:** 2 hours

**What to Verify:**

- Quiz works end-to-end on production
- Chat responds correctly
- Real products displayed
- AI APIs responding
- No errors in logs
- Performance acceptable

---

## 📊 TEST SUMMARY BY PHASE

| Phase       | Unit Tests | Feature Tests | Integration Tests | Dusk Tests | Total Tests |
| ----------- | ---------- | ------------- | ----------------- | ---------- | ----------- |
| **Phase 0** | 0          | 0             | 0                 | 0          | 0 (prerequisites) |
| **Phase 1** | 5          | 0             | 0                 | 0          | 5           |
| **Phase 2** | 0          | 29            | 0                 | 0          | 29          |
| **Phase 3** | 15         | 5             | 10                | 0          | 30          |
| **Phase 4** | 2          | 10            | 0                 | 0          | 12          |
| **Phase 5** | 0          | 0             | 0                 | 5          | 5           |
| **Phase 6** | 5          | 5             | 5                 | 3          | 18          |
| **TOTAL**   | **27**     | **49**        | **15**            | **8**      | **99**      |

### **Phase 2 Actual Results:**

| Task | Tests | Assertions | Status |
|------|-------|------------|--------|
| Task 2.1 (AiChatSession) | 4 | 18 | ✅ PASS |
| Task 2.2 (AiMessage) | 6 | 25 | ✅ PASS |
| Task 2.3 (QuizResult) | 6 | 34 | ✅ PASS |
| Task 2.4 (UserScentProfile) | 6 | 23 | ✅ PASS |
| Task 2.5 (AIRecommendationCache) | 7 | 26 | ✅ PASS |
| **TOTAL** | **29** | **126** | ✅ |

---

## 🎯 DEFINITION OF DONE (PER TASK)

For **every task** in this Dev Story:

### **Required:**

1. ✅ **Research complete** - Existing code patterns understood
2. ✅ **Tests written first** - Failing tests exist before code
3. ✅ **Code implemented** - Follows existing patterns
4. ✅ **Unit tests pass** - `./vendor/bin/pest --group=unit`
5. ✅ **Feature tests pass** - `./vendor/bin/pest --group=feature`
6. ✅ **Integration tests pass** (if applicable) - `./vendor/bin/pest --group=live-api`
7. ✅ **Dusk tests pass** (if UI) - `php artisan dusk`
8. ✅ **No code review issues** - HIGH, MEDIUM, LOW all fixed
9. ✅ **Tests use real data** - No mocks, no stubs
10. ✅ **Code committed** - Git commit with descriptive message

### **Blocked if:**

- ❌ Any test fails
- ❌ Code coverage drops below 90%
- ❌ Tests use fake data/mocks
- ❌ Doesn't follow existing patterns

---

## 🚫 CRITICAL RULES

### **ABSOLUTELY FORBIDDEN:**

- ❌ Writing code before tests
- ❌ Using fake data or mocks
- ❌ Skipping tests
- ❌ Commenting "TODO" or "FIXME"
- ❌ "Good enough for now" implementations

### **REQUIRED:**

- ✅ Write tests FIRST (they must fail initially)
- ✅ Use real production database
- ✅ Call real AI APIs (Gemini)
- ✅ All tests MUST PASS before next task
- ✅ Auto-fix ALL code review issues
- ✅ Verify tests actually run and pass

---

## 📚 REFERENCE DOCUMENTS

Throughout implementation, refer to:

1. **PRD** (`AI_AGENT_PRD.md`) - Requirements and user stories
2. **Architecture Doc** (`AI_AGENT_ARCHITECTURE.md`) - Technical specifications
3. **Existing Code** - CartService, ProductController patterns
4. **Official Docs:**
    - Laravel Testing: https://laravel.com/docs/12.x/testing
    - PestPHP: https://pestphp.com/docs/installation
    - Google Gemini: https://ai.google.dev/gemini-api/docs

---

## 🎬 GETTING STARTED

### **Day 0 Checklist (MUST COMPLETE FIRST):**

- [ ] Run `php artisan db:seed` to populate database
- [ ] Verify products exist: `php artisan tinker --execute="echo App\Models\Product::count();"`
- [ ] Get Gemini API key from https://ai.google.dev
- [ ] Add key to `.env`
- [ ] Verify Redis: `php artisan tinker --execute="echo Redis::ping();"`

### **Day 1 Checklist:**

- [ ] Read Architecture Doc completely
- [ ] Open existing CartService.php and study patterns
- [ ] Run existing tests: `./vendor/bin/sail pest` (should pass)
- [ ] Start Task 1.1: Validate Existing Codebase Architecture

### **Before Writing Any Code:**

1. Complete Phase 0 (Prerequisites)
2. Complete Task 1.1 (Research)
3. Complete Task 1.2 (Setup)
4. Write failing tests
5. Run tests to confirm they fail
6. Then write code

---

## 📝 PRODUCT DATA STRUCTURE

### **`products.attributes_json` Structure:**

```json
{
  "notes": {
    "top": "ベルガモット、グリーン",
    "middle": "ホワイトリリー、ジャスミン",
    "base": "ムスク、シダーウッド"
  },
  "gender": "women" | "men" | "unisex"
}
```

### **Accessing in Code:**

```php
$product = Product::first();
$notes = $product->attributes_json['notes'] ?? [];
$gender = $product->attributes_json['gender'] ?? 'unisex';
$topNotes = $notes['top'] ?? '';
```

---

## 🤖 AI PROVIDER STRATEGY

### **Provider Order:**

1. **Gemini 2.5 Flash-Lite (Primary)** - 15 RPM, 1,000/day
2. **Gemini 2.5 Flash (Fallback)** - 10 RPM, 250/day
3. **Cached Response** - If both fail

### **Configuration:**

```php
// config/services.php
'gemini' => [
    'api_key' => env('GEMINI_API_KEY'),
    'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
    'primary_model' => 'gemini-2.5-flash-lite',
    'fallback_model' => 'gemini-2.5-flash',
],
```

---

## 🎨 VISUAL ASSETS

### **Free Sources for Quiz Images:**

| Type | Source | License |
|------|--------|---------|
| Perfume bottles | https://unsplash.com/s/photos/perfume | Free commercial |
| Floral backgrounds | https://pexels.com/search/flower | Free commercial |
| Illustrations | https://undraw.co | MIT (no attribution) |

### **Social Card Generation:**

```bash
composer require intervention/image
```

---

**Ready to begin?** Start with **Phase 0 (Prerequisites)** then **Task 1.1** - Research the existing codebase! 📚✅

**END OF DEV STORY**

**Document History:**
- v1.0 (2026-02-18): Initial draft
- v1.1 (2026-02-18): Added Phase 2 completed results
- v1.2 (2026-02-18):
  - Added Phase 0 (Prerequisites)
  - Added `attributes_json` structure documentation
  - Added visual asset sources
  - Added social card generation (Intervention Image)
  - Added API key setup instructions
  - Confirmed: Anonymous users NO chat history saved
- v1.2.1 (2026-02-18): **CRITICAL FIXES**
  - Fixed Inventory FK: `product_id` → `product_variant_id`
  - Fixed Product notes: Use `$p->attributes_json['notes']` (not `$p->top_notes`)
  - Fixed Reviews column: `approved` (not `is_approved`)
  - Added database structure validation notes
- v1.3 (2026-02-18): **PROVIDER UPDATE**
  - Removed Groq provider (not working)
  - Updated to Gemini-only strategy
  - Primary: gemini-2.5-flash-lite (15 RPM, 1,000 RPD)
  - Fallback: gemini-2.5-flash (10 RPM, 250 RPD)
````
