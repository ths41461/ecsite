# AI Agent Implementation Guide - ReAct Fragrance Recommendation System

**Project:** ECSite - AI Fragrance Recommendation Agent  
**Pattern:** ReAct (Reasoning + Acting)  
**Approach:** Strict TDD (Test-First Development)  
**Data:** 100% Real (Production DB, Live AI APIs)

---

## 🎯 YOUR MISSION

Implement a complete AI-powered fragrance recommendation system using the ReAct pattern. The system must:

1. Analyze user quiz responses
2. Query real product database
3. Call live AI APIs (Gemini/Groq) for recommendations
4. Support conversational refinement via chat
5. Achieve 90%+ test coverage with 100% real data

---

## 📚 REQUIRED READING (READ THESE FIRST)

**Read in this exact order:**

1. **THIS FILE** (AGENT.md) - Your instruction manual
2. **PRD** (`/code/ecsite/AI_AGENT_PRD.md`) - What to build
3. **Architecture** (`/code/ecsite/AI_AGENT_ARCHITECTURE.md`) - How to build it
4. **Dev Story** (`/code/ecsite/AI_AGENT_DEV_STORY.md`) - Step-by-step plan

**Study these existing files:**

- `app/Services/CartService.php` - Pattern reference (26KB)
- `app/Http/Controllers/CartController.php` - Controller pattern
- `app/Models/Product.php` - Existing model structure

---

## 🔧 ENVIRONMENT SETUP

### Before You Start:

```bash
# 1. Verify environment
cd /code/ecsite
php artisan --version  # Should be Laravel 12.x
php -v                 # Should be 8.3.6

# 2. Check database
ls -la database/database.sqlite  # Should exist

# 3. Verify tests work
./vendor/bin/pest  # Should run existing tests

# 4. Check installed packages
composer show | grep pest    # Should show pestphp/pest
grep "laravel/dusk" composer.json  # Should be installed

# 5. Study existing patterns
cat app/Services/CartService.php | head -50
```

### Critical: Get API Keys

You MUST obtain these from the user before implementing AI features:

- `GEMINI_API_KEY` - Google Gemini API
- `GROQ_API_KEY` - Groq API

Add to `.env`:

```
GEMINI_API_KEY=actual_key_here
GROQ_API_KEY=actual_key_here
```

---

## 🧪 STRICT TDD WORKFLOW (MANDATORY)

**NEVER write code before tests. NEVER.**

### Per-Task Workflow:

```
1. READ task in Dev Story
   ↓
2. STUDY references in Architecture Doc
   ↓
3. WRITE failing test
   Create test file
   Run: ./vendor/bin/pest path/to/test.php
   MUST FAIL (red) ✓
   ↓
4. WRITE code to make test pass
   Implement minimum to pass
   ↓
5. RUN all tests
   ./vendor/bin/pest
   MUST PASS (green) ✓
   ↓
6. REFACTOR (if needed)
   Keep tests green
   ↓
7. COMMIT
   git add .
   git commit -m "Task X.Y: Description"
   ↓
8. NEXT TASK
```

### Test Types You Must Write:

**For EVERY service class:**

- Unit test: `tests/Unit/Services/AI/[ServiceName]Test.php`

**For EVERY API endpoint:**

- Feature test: `tests/Feature/API/[Endpoint]Test.php`
- Must use real production database

**For AI provider classes:**

- Integration test: `tests/Integration/AIProviders/[Provider]Test.php`
- Must call real API (costs quota)

**For UI features:**

- Dusk test: `tests/Browser/[Feature]Test.php`
- Must use real browser

---

## 📋 IMPLEMENTATION PHASES

### Phase 1: Research & Foundation (Days 1-2)

**Task 1.1: Analyze Existing Patterns**

```bash
# Study these files completely:
cat app/Services/CartService.php          # Save to memory
cat app/Services/OrderService.php         # Save to memory
cat app/Http/Controllers/CartController.php  # Controller pattern

# Document findings:
# - How are methods named?
# - How are dependencies injected?
# - How are errors handled?
# - What return types are used?
# - How is caching used?
```

**Create:** Pattern reference document

---

### Phase 2: Database (Days 3-4)

**Task 2.1: Create Migrations**

**Order matters:** Create in this sequence

1. `database/migrations/2026_02_18_000001_create_ai_chat_sessions_table.php`
2. `database/migrations/2026_02_18_000002_create_ai_messages_table.php`
3. `database/migrations/2026_02_18_000003_create_quiz_results_table.php`
4. `database/migrations/2026_02_18_000004_create_user_scent_profiles_table.php`
5. `database/migrations/2026_02_18_000005_create_ai_recommendation_cache_table.php`

**For EACH migration:**

1. Write test first: `tests/Feature/Database/[TableName]MigrationTest.php`
2. Run test (must fail)
3. Create migration
4. Run migration: `php artisan migrate`
5. Run test (must pass)

**Create Models:**

- `app/Models/AiChatSession.php`
- `app/Models/AiMessage.php`
- `app/Models/QuizResult.php`
- `app/Models/UserScentProfile.php`
- `app/Models/AIRecommendationCache.php`

---

### Phase 3: Backend Services (Days 5-10)

**Critical: Follow CartService pattern EXACTLY**

**Task 3.1: ContextBuilder Service**

```php
// File: app/Services/AI/ContextBuilder.php
// Pattern: Follow CartService exactly
// Return: Arrays (not objects)
// Use: Eloquent ORM, not raw SQL
```

**Test first:** `tests/Unit/Services/AI/ContextBuilderTest.php`

- Must use real products from production DB
- Must verify budget constraints
- Must return correct data structure

---

**Task 3.2: ToolRegistry Service**

```php
// File: app/Services/AI/ToolRegistry.php
// Tools to implement:
// 1. search_products()
// 2. check_inventory()
// 3. get_product_reviews()
```

**Test first:** `tests/Unit/Services/AI/ToolRegistryTest.php`

- Query real database
- Return real product data
- Handle edge cases

---

**Task 3.3: GeminiProvider (CRITICAL - Live API)**

```php
// File: app/Services/AI/Providers/GeminiProvider.php
// Implements: AIProviderInterface
// API Endpoint: https://generativelanguage.googleapis.com/v1beta/models
// Model: gemini-2.0-flash
```

**Test first:** `tests/Integration/AIProviders/GeminiLiveTest.php`

- Calls REAL Gemini API
- Tests cost 1 API request each
- Maximum 1,500 requests/day free
- Must test: chat(), generateWithTools()

**CRITICAL:** Monitor API quota usage

---

**Task 3.4: GroqProvider (Fallback)**

```php
// File: app/Services/AI/Providers/GroqProvider.php
// API Endpoint: https://api.groq.com/openai/v1/chat/completions
// Model: llama-3.3-70b-versatile
```

**Test first:** `tests/Integration/AIProviders/GroqLiveTest.php`

- Calls REAL Groq API
- Tests cost 1 API request each
- Maximum 1,000 requests/day free

---

**Task 3.5: ReActAgentEngine (CORE LOGIC)**

```php
// File: app/Services/AI/ReActAgentEngine.php
// Implements: ReAct pattern (Thought -> Action -> Observation)
// Loop: Maximum 5 iterations
// Returns: Final recommendations
```

**Test first:** `tests/Unit/Services/AI/ReActAgentEngineTest.php`

- Test ReAct loop logic
- Test tool execution
- Test max iteration limits
- Test final response parsing

**Integration test:** `tests/Integration/ReActLoopTest.php`

- Uses real AI provider
- Uses real database
- Full end-to-end test

---

**Task 3.6: AIRecommendationService (Main Service)**

```php
// File: app/Services/AI/AIRecommendationService.php
// Pattern: Follow CartService
// Features: Caching, fallback logic
```

**Tests:**

- Unit: `tests/Unit/Services/AI/AIRecommendationServiceTest.php`
- Feature: `tests/Feature/Services/AIRecommendationServiceTest.php`
- Integration: `tests/Integration/FullRecommendationFlowTest.php`

**Must test:**

- Generate recommendations
- Cache functionality
- Fallback to Groq when Gemini fails
- Error handling

---

### Phase 4: API Controllers (Days 11-13)

**Task 4.1: Form Requests**

```php
// File: app/Http/Requests/SubmitQuizRequest.php
// File: app/Http/Requests/SendChatMessageRequest.php
```

**Test:** `tests/Unit/Requests/[RequestName]Test.php`

- All validation rules
- Error messages in Japanese

---

**Task 4.2: AIRecommendationController**

```php
// File: app/Http/Controllers/API/AIRecommendationController.php
// Endpoints:
// POST /api/ai/quiz
// GET /api/ai/recommendations
```

**Test:** `tests/Feature/API/AIRecommendationControllerTest.php`

- Must use real production database
- Must test with real user authentication
- Must verify JSON response structure

---

**Task 4.3: ChatController**

```php
// File: app/Http/Controllers/API/ChatController.php
// Endpoint: POST /api/ai/chat
```

**Test:** `tests/Feature/API/ChatControllerTest.php`

- Test chat message storage
- Test AI response (real API call)
- Test session validation

---

### Phase 5: Frontend (Days 14-17)

**Task 5.1: Results Page**

```typescript
// File: resources/js/pages/FragranceDiagnosisResults.tsx
// Components needed:
// - ScentProfileCard
// - ProductGrid
// - FilterBar
// - ChatButton
```

**Test:** `tests/Browser/FragranceDiagnosisResultsTest.php`

- Dusk browser test
- Tests real user flow

---

**Task 5.2: Chat Components**

```typescript
// Files: resources/js/components/AIChat/
// - ChatContainer.tsx
// - MessageBubble.tsx
// - ChatInput.tsx
// - QuickReplies.tsx
// - TypingIndicator.tsx
```

**Test:** `tests/Browser/ChatInteractionTest.php`

- Tests chat functionality
- Tests AI responses

---

**Task 5.3: Enhanced Quiz**

```typescript
// Modify: resources/js/pages/FragranceDiagnosis.tsx
// Change: 5 questions → 7 questions
// Add: Visual cards
// Add: API integration
// Remove: Alert popup
```

**Test:** `tests/Browser/FragranceDiagnosisTest.php`

- Full quiz flow test
- Waits for AI response
- Verifies results page

---

### Phase 6: Testing & Verification (Days 18-19)

**Run Complete Test Suite:**

```bash
# 1. All unit tests (fast)
./vendor/bin/pest --group=unit

# 2. All feature tests (production DB)
./vendor/bin/pest --group=feature

# 3. All integration tests (live APIs)
./vendor/bin/pest --group=live-api

# 4. All Dusk tests (browser)
php artisan dusk

# 5. Coverage report (must be 90%+)
./vendor/bin/pest --coverage --min=90

# ALL MUST PASS 100%
```

**Create Tinker Scripts:**

- `tests/Tinker/verify-products.php`
- `tests/Tinker/ai-test.php`

---

### Phase 7: Deployment (Days 20-21)

**Deployment Checklist:**

- [ ] Environment variables set (API keys)
- [ ] Migrations run on production
- [ ] Rate limiting configured
- [ ] Monitoring enabled
- [ ] Smoke tests pass

---

## 🎨 CODING STANDARDS

### Follow These Patterns EXACTLY:

**1. Service Class Structure:**

```php
<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExampleService
{
    protected DependencyType $dependency;

    public function __construct(DependencyType $dependency)
    {
        $this->dependency = $dependency;
    }

    public function doSomething(array $data): array
    {
        try {
            // Implementation
            return ['success' => true, 'data' => $result];
        } catch (\Exception $e) {
            Log::error('Error message', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

**2. Method Naming:**

- Use camelCase: `generateRecommendations()` not `generate_recommendations()`
- Verbs for actions: `searchProducts()`, `checkInventory()`
- Descriptive names: `buildContextForChat()` not `build()`

**3. Return Types:**

- Always type hint: `public function method(): array`
- Return arrays for JSON responses
- Return null if no data found

**4. Error Handling:**

```php
try {
    // Code
} catch (SpecificException $e) {
    Log::error('Context', ['key' => 'value']);
    throw new CustomException('Message', 0, $e);
}
```

**5. Database Queries:**

```php
// Good
Product::with(['variants', 'brand'])
    ->where('is_active', true)
    ->get();

// Bad
DB::select('SELECT * FROM products');
```

**6. Caching:**

```php
return Cache::remember($key, 3600, function () {
    return $this->expensiveOperation();
});
```

---

## ⚠️ CRITICAL RULES (VIOLATE = FAILURE)

### ❌ FORBIDDEN:

1. **NO code before tests** - Write failing test first, always
2. **NO fake data** - Use production database for all feature tests
3. **NO mocks** - Call real AI APIs (Gemini, Groq)
4. **NO skipped tests** - All tests must pass
5. **NO SQLite** for feature tests - Use MySQL production
6. **NO "TODO" comments** - Complete each task 100%
7. **NO copy-paste** from Architecture Doc - Understand and implement
8. **NO shortcuts** - "Good enough" is not acceptable

### ✅ REQUIRED:

1. **Write tests first** - They must fail initially
2. **Run all tests** - Before and after every task
3. **100% pass rate** - No exceptions
4. **90%+ coverage** - Minimum acceptable
5. **Follow patterns** - Copy CartService structure exactly
6. **Real data only** - Production DB, live APIs
7. **Commit often** - After every passing task
8. **Ask questions** - Don't guess if unclear

---

## 🚨 COMMON PITFALLS

### 1. Testing with Fake Data

**WRONG:**

```php
// Using factory fake data
$product = Product::factory()->make();
```

**RIGHT:**

```php
// Using real production data
$product = Product::first();
$this->assertNotNull($product);
```

### 2. Mocking AI APIs

**WRONG:**

```php
Http::fake(['gemini.api/*' => Http::response(['fake' => 'data'])]);
```

**RIGHT:**

```php
// Call real API
$response = $provider->chat('test', []);
$this->assertArrayHasKey('message', $response);
```

### 3. Skipping Research Phase

**WRONG:** Jump to Task 2.1 without studying CartService

**RIGHT:** Complete Task 1.1, document all patterns first

### 4. Writing Code Before Tests

**WRONG:** Create service class, then write tests

**RIGHT:** Write test that fails, then create service

### 5. Not Running Full Test Suite

**WRONG:** Only run new test

**RIGHT:** Run `./vendor/bin/pest` after every change

---

## 🔍 VERIFICATION CHECKLIST

### Before Starting Each Task:

- [ ] Read task in Dev Story
- [ ] Check Architecture Doc for code examples
- [ ] Verify understanding of requirements

### During Implementation:

- [ ] Write failing test
- [ ] Run test (must fail)
- [ ] Write minimum code
- [ ] Run test (must pass)
- [ ] Run full test suite (must all pass)

### Before Committing:

- [ ] All tests pass: `./vendor/bin/pest`
- [ ] Coverage 90%+: `./vendor/bin/pest --coverage`
- [ ] No code style issues: `./vendor/bin/pint`
- [ ] Code follows existing patterns

### After Each Phase:

- [ ] All phase tasks complete
- [ ] All tests passing
- [ ] No skipped tests
- [ ] Documentation updated (if needed)

---

## 📊 PROGRESS TRACKING

### Daily Status Update Format:

```
Day X: Phase Y - Task Z
- Completed: [What was done]
- Tests: [X/Y passing]
- Coverage: [X]%
- Blockers: [Any issues]
- Next: [Next task]
```

### Phase Completion Criteria:

**Phase 1:** Research document created
**Phase 2:** All 5 migrations run, all models created, all tests pass
**Phase 3:** All 6 services implemented, all integration tests pass
**Phase 4:** All 2 controllers created, all API tests pass
**Phase 5:** All 3 frontend components created, all Dusk tests pass
**Phase 6:** 70+ tests passing, 90%+ coverage
**Phase 7:** Deployed to production, smoke tests pass

---

## 🆘 GETTING HELP

### When Stuck:

1. **Re-read this file** - Check workflow section
2. **Check Architecture Doc** - Look for code examples
3. **Study CartService** - Copy the pattern exactly
4. **Read existing tests** - See how tests are structured
5. **Run existing tests** - Verify your environment works
6. **Check official docs** - Laravel, PestPHP, Gemini API

### What NOT to Do:

- Don't guess - Ask for clarification
- Don't skip tests - They're mandatory
- Don't use fake data - Use real database
- Don't skip phases - Follow the order

---

## ✅ DEFINITION OF DONE

### Project Complete When:

1. **All 27 tasks complete**
2. **All 70+ tests passing**
3. **90%+ code coverage**
4. **100% real data** (no mocks, no fakes)
5. **All API endpoints working**
6. **Frontend components functional**
7. **Dusk browser tests passing**
8. **Deployed to production**
9. **No failing tests**
10. **No code review issues**

---

## 🚀 START NOW

### Your First Task:

```
Task 1.1: Validate Existing Codebase Architecture
Read: app/Services/CartService.php
Read: app/Services/OrderService.php
Document: All patterns found
```

**DO NOT write any code yet.**
**Study first. Understand patterns. Then proceed to Task 1.2.**

---

**Good luck! Remember: Tests first, real data only, follow patterns exactly.** 🎯

**END OF AGENT GUIDE**
