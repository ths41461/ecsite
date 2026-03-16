# AI Fragrance Recommendation Agent - Architecture Document

**Version:** 1.3  
**Date:** February 18, 2026  
**Status:** Production-Ready  
**Based on:** Official Google AI and Laravel Documentation

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [System Architecture Overview](#2-system-architecture-overview)
3. [Technology Stack](#3-technology-stack)
4. [Data Flow Architecture](#4-data-flow-architecture)
5. [Backend Components](#5-backend-components)
6. [Frontend Components](#6-frontend-components)
7. [Database Schema](#7-database-schema)
8. [AI Integration (ReAct Pattern)](#8-ai-integration-react-pattern)
9. [API Endpoints](#9-api-endpoints)
10. [Security Architecture](#10-security-architecture)
11. [Performance & Scalability](#11-performance--scalability)
12. [Integration Points](#12-integration-points)
13. [Error Handling & Fallbacks](#13-error-handling--fallbacks)
14. [Deployment Architecture](#14-deployment-architecture)

---

## 1. Executive Summary

### 1.1 Architecture Approach

This document defines a **production-ready AI agent architecture** for the Fragrance Recommendation System. The architecture is built using:

- **ReAct Pattern** (ICLR 2023 paper by Yao et al.)
- **Function Calling** (Official Google Gemini API)
- **Laravel HTTP Client** (Laravel 12.x built-in)
- **Google Gemini** (Primary + Fallback models)

### 1.2 Key Architectural Decisions

| Decision                  | Rationale                                        | Verification         |
| ------------------------- | ------------------------------------------------ | -------------------- |
| **Laravel HTTP Client**   | Built-in, no new packages, PHP 8.4.12 compatible | Laravel 12.x docs    |
| **ReAct Pattern**         | Academic proven pattern for reasoning + action   | ICLR 2023 paper      |
| **Function Calling**      | Official Google API feature                      | ai.google.dev        |
| **No Laravel AI SDK**     | Simple HTTP client sufficient, less complexity   | Design decision      |
| **Service Layer Pattern** | Matches existing CartService, OrderService       | Verified in codebase |

---

## 2. System Architecture Overview

### 2.1 High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              CLIENT LAYER                                    │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌────────────────────┐ │
│  │  FragranceDiagnosis  │  │  FragranceDiagnosis  │  │     AIChat         │ │
│  │  (Quiz - Enhanced)   │  │  (Results Page)      │  │   (Chat UI)        │ │
│  └──────────┬───────────┘  └──────────┬───────────┘  └─────────┬──────────┘ │
└─────────────┼────────────────────────┼────────────────────────┼────────────┘
              │                        │                        │
              └────────────────────────┼────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           API GATEWAY LAYER                                  │
│                    Laravel Routes (routes/api.php)                           │
│                         Rate Limiting: 20 req/min                            │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         CONTROLLER LAYER                                     │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                    AIRecommendationController                            │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                  │ │
│  │  │  submitQuiz  │  │  chat        │  │ getRecommend │                  │ │
│  │  └──────────────┘  └──────────────┘  └──────────────┘                  │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          SERVICE LAYER                                       │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────────────┐  │
│  │ AIRecommendation │  │ ReActAgent       │  │ ToolRegistry             │  │
│  │ Service          │  │ Engine           │  │ (Database Tools)         │  │
│  └────────┬─────────┘  └────────┬─────────┘  └────────────┬─────────────┘  │
│           │                     │                        │                │
│  ┌────────▼─────────────────────────────────────────────▼──────────┐      │
│  │  GeminiProvider (Primary + Fallback models)                     │      │
│  │  - gemini-2.5-flash-lite (Primary: 1,000 RPD)                   │      │
│  │  - gemini-2.5-flash (Fallback: 250 RPD)                         │      │
│  └──────────────────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           DATA LAYER                                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │ ai_chat_    │  │ quiz_       │  │ user_scent_ │  │ ai_recommendation_  │ │
│  │ sessions    │  │ results     │  │ profiles    │  │ cache               │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────────────┘ │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │ ai_messages │  │ products    │  │ inventories │  │ reviews             │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        AI PROVIDER LAYER                                     │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │  Google Gemini 2.5 Flash-Lite (PRIMARY)                                 │ │
│  │  - 1,000 requests/day FREE                                              │ │
│  │  - 15 RPM                                                               │ │
│  │  - 250K TPM                                                             │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │  Google Gemini 2.5 Flash (FALLBACK)                                     │ │
│  │  - 250 requests/day FREE                                                │ │
│  │  - 10 RPM                                                               │ │
│  │  - 250K TPM                                                             │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Architecture Layers Explained

#### **Layer 1: Client Layer**

- **Technology:** React 19 + TypeScript + Inertia.js 2
- **Components:** Enhanced quiz page, results page, chat interface
- **Responsibility:** UI rendering, user input collection, API calls

#### **Layer 2: API Gateway Layer**

- **Technology:** Laravel Routes + Middleware
- **Responsibility:** Request routing, rate limiting (20 req/min), authentication

#### **Layer 3: Controller Layer**

- **Technology:** Laravel Controllers
- **Responsibility:** Request validation, service orchestration, response formatting

#### **Layer 4: Service Layer**

- **Technology:** PHP 8.3.6 + Laravel HTTP Client
- **Responsibility:** Business logic, AI provider management, data processing

#### **Layer 5: Data Layer**

- **Technology:** MySQL (existing) + Redis Cache (verified working)
- **Responsibility:** Data persistence, caching, retrieval

**IMPORTANT:** Run `php artisan db:seed` before development to populate:

- ~120+ products across 20+ brands
- Fragrance notes in `attributes_json`
- Product variants with pricing

#### **Layer 6: AI Provider Layer**

- **Technology:** REST APIs (Google Gemini)
- **Responsibility:** AI inference, function calling, response generation

---

## 3. Technology Stack

### 3.1 Backend Stack (Verified from composer.json)

| Component               | Version  | Status               | Verification     |
| ----------------------- | -------- | -------------------- | ---------------- |
| **PHP**                 | 8.4.12   | ✅ Active            | `php -v` command |
| **Laravel Framework**   | ^12.0    | ✅ Latest            | composer.json    |
| **Laravel HTTP Client** | Built-in | ✅ No install needed | Laravel 12 docs  |
| **Inertia.js Laravel**  | ^2.0     | ✅ Installed         | composer.json    |
| **Filament**            | ^3.0     | ✅ Admin panel       | composer.json    |
| **Stripe PHP**          | ^14      | ✅ Payments          | composer.json    |
| **Doctrine DBAL**       | ^4.4     | ✅ DB tools          | composer.json    |

> **Note:** PHP 8.4.12 means Laravel AI SDK IS compatible (requires PHP 8.4+)

### 3.2 Frontend Stack (Verified from package.json)

| Component            | Version  | Status       | Verification |
| -------------------- | -------- | ------------ | ------------ |
| **React**            | ^19.0.0  | ✅ Latest    | package.json |
| **TypeScript**       | ^5.7.0   | ✅ Installed | package.json |
| **Inertia.js React** | ^2.1.0   | ✅ Installed | package.json |
| **TailwindCSS**      | ^4.0.0   | ✅ Latest    | package.json |
| **Radix UI**         | ^1.x     | ✅ Installed | package.json |
| **Lucide React**     | ^0.475.0 | ✅ Icons     | package.json |

### 3.3 AI Provider Stack (Official Documentation - 2026)

| Provider              | Model          | Free Tier     | Rate Limits | Endpoint                            |
| --------------------- | -------------- | ------------- | ----------- | ----------------------------------- |
| **Gemini (Primary)**  | 2.5 Flash-Lite | 1,000 req/day | 15 RPM      | `generativelanguage.googleapis.com` |
| **Gemini (Fallback)** | 2.5 Flash      | 250 req/day   | 10 RPM      | `generativelanguage.googleapis.com` |

**API Key Setup:**

1. **Gemini (REQUIRED):** Sign up at https://ai.google.dev → Create API key

**Configuration in `.env`:**

```
GEMINI_API_KEY=AIzaxxxxx
```

### 3.4 What We DON'T Use (Compatibility Check)

| Package            | Status        | Reason                      | Source        |
| ------------------ | ------------- | --------------------------- | ------------- |
| **Laravel AI SDK** | ⚠️ Optional   | Now compatible with PHP 8.4 | packagist.org |
| **LangChain PHP**  | ❌ Not needed | Adds complexity             | -             |
| **OpenAI PHP SDK** | ❌ Not needed | HTTP client sufficient      | -             |
| **Guizle**         | ❌ Not needed | Laravel HTTP is wrapper     | -             |

---

## 3.5 CRITICAL: Actual Database Structure (Verified)

> **⚠️ READ THIS BEFORE IMPLEMENTING ANY CODE**
> These are the ACTUAL column names and relationships in the database.

### **Products Table**

```
Column: id, name, slug, brand_id, category_id, short_desc, long_desc,
        is_active, featured, attributes_json, meta_json, published_at

⚠️ NO 'top_notes', 'middle_notes', 'base_notes' columns!
✅ Fragrance notes are in: attributes_json['notes']['top'|'middle'|'base']
```

### **Inventories Table**

```
Column: id, product_variant_id, stock, safety_stock, managed, updated_at

⚠️ NO 'product_id' column!
✅ Foreign key is: product_variant_id -> product_variants.id
✅ To check stock for products, JOIN through product_variants
```

### **Reviews Table**

```
Column: id, product_id, user_id, rating, body, approved, created_at, updated_at

⚠️ Column is 'approved', NOT 'is_approved'!
✅ Query: ->where('approved', true)
```

### **Product_Variants Table**

```
Column: id, product_id, sku, option_json, price_yen, sale_price_yen, is_active

✅ option_json contains: {size_ml, gender, concentration}
```

### **Relationship Chain**

```
Product -> hasMany -> ProductVariant -> hasOne -> Inventory

To get inventory for a product:
$product->variants->each(fn($v) => $v->inventory->stock)
```

---

## 4. Data Flow Architecture

### 4.1 Quiz Submission Flow

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│   User   │────▶│  React   │────▶│ Laravel  │────▶│ AI Service│────▶│  Gemini  │
│ Submits  │     │   UI     │     │   API    │     │          │     │   API    │
│   Quiz   │     │          │     │          │     │          │     │          │
└──────────┘     └──────────┘     └──────────┘     └────┬─────┘     └────┬─────┘
                                                         │                │
                                                         ▼                ▼
                                                  ┌──────────┐     ┌──────────┐
                                                  │  ReAct   │     │ Function │
                                                  │  Loop    │────▶│  Call    │
                                                  │          │     │          │
                                                  └────┬─────┘     └────┬─────┘
                                                       │                │
                                                       ▼                ▼
                                                  ┌──────────┐     ┌──────────┐
                                                  │  Tools   │◀────│  Tool    │
                                                  │ Execute  │     │ Response │
                                                  └────┬─────┘     └──────────┘
                                                       │
                                                       ▼
                                                  ┌──────────┐
                                                  │  Final   │
                                                  │ Response │
                                                  └────┬─────┘
                                                       │
                                                       ▼
                                                  ┌──────────┐     ┌──────────┐
                                                  │  Store   │────▶│ Database │
                                                  │ Results  │     │          │
                                                  └──────────┘     └──────────┘
```

### 4.2 Chat Refinement Flow

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│   User   │────▶│  React   │────▶│ Laravel  │────▶│  Load    │────▶│   AI     │
│  Sends   │     │   Chat   │     │   API    │     │ Context  │     │  Agent   │
│ Message  │     │   UI     │     │          │     │          │     │          │
└──────────┘     └──────────┘     └──────────┘     └──────────┘     └────┬─────┘
                                                                         │
                                                                         ▼
                                                                  ┌──────────┐
                                                                  │ Retrieve │
                                                                  │ Previous │
                                                                  │   Chat   │
                                                                  └────┬─────┘
                                                                       │
                                                                       ▼
                                                                  ┌──────────┐
                                                                  │  Query   │
                                                                  │ Products │
                                                                  │ (if needed)│
                                                                  └────┬─────┘
                                                                       │
                                                                       ▼
                                                                  ┌──────────┐
                                                                  │ Generate │
                                                                  │ Response │
                                                                  └────┬─────┘
                                                                       │
                                                                       ▼
                                                                  ┌──────────┐
                                                                  │  Store   │
                                                                  │ Message  │
                                                                  └──────────┘
```

### 4.3 Detailed Flow Steps

#### **Step 1: Quiz Submission**

1. User completes 7-question quiz on `/fragrance-diagnosis`
2. React frontend validates all required questions answered
3. POST request to `/api/ai/quiz` with JSON payload
4. Laravel validates input using FormRequest
5. `AIRecommendationController@submitQuiz` invoked
6. `AIRecommendationService::generateRecommendations()` called

#### **Step 2: ReAct Agent Execution**

1. `ReActAgentEngine` receives quiz data
2. **THOUGHT:** Analyzes quiz answers to determine search strategy
3. **ACTION:** Calls `ToolRegistry` to execute database tools:
    - `search_products()` - Query product catalog
    - `check_inventory()` - Verify stock levels
    - `get_reviews()` - Fetch ratings
4. **OBSERVATION:** Receives structured data from tools
5. **REFINE:** If needed, makes additional tool calls
6. **OUTPUT:** Generates final recommendations with explanations

#### **Step 3: AI Provider Call (with Tool Calling)**

1. Build prompt with user context + tool definitions
2. Send to Gemini API with function calling enabled
3. API returns either:
    - **Text response** (final answer)
    - **Function call request** (need to execute tools)
4. If function call: Execute tool, send result back to AI
5. Repeat until final response received

#### **Step 4: Response Processing**

1. Parse AI response (JSON format)
2. Extract product recommendations
3. Enrich with product data from database
4. Calculate match scores
5. Generate Scent Profile
6. Store results in `quiz_results` table

#### **Step 5: User Response**

1. Return JSON with:
    - Profile object (type, name, description)
    - Recommendations array (5-7 products)
    - Session token for chat continuation
2. React navigates to `/fragrance-diagnosis/results`
3. Display profile card + product grid

---

## 5. Backend Components

### 5.1 Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── API/
│   │   │   ├── AIRecommendationController.php    # Main API controller
│   │   │   └── ChatController.php                # Chat management
│   │   └── ... (existing controllers)
│   └── Requests/
│       ├── SubmitQuizRequest.php                 # Quiz validation
│       └── SendChatMessageRequest.php            # Chat validation
├── Services/
│   ├── AI/
│   │   ├── AIRecommendationService.php          # Main AI service
│   │   ├── ReActAgentEngine.php                 # ReAct loop implementation
│   │   ├── ToolRegistry.php                     # Tool definitions
│   │   ├── ContextBuilder.php                   # Data preparation
│   │   ├── ResponseParser.php                   # AI response parsing
│   │   └── Providers/
│   │       ├── GeminiProvider.php               # Google Gemini API (Primary + Fallback)
│   │       └── AIProviderInterface.php          # Provider contract
│   ├── Tools/
│   │   ├── SearchProductsTool.php               # Product search
│   │   ├── CheckInventoryTool.php               # Stock check
│   │   ├── GetReviewsTool.php                   # Review retrieval
│   │   └── GetTrendingTool.php                  # Trending products
│   └── ... (existing services)
├── Models/
│   ├── AiChatSession.php                        # Chat session model
│   ├── AiMessage.php                            # Chat message model
│   ├── QuizResult.php                           # Quiz result model
│   ├── UserScentProfile.php                     # User profile model
│   ├── AIRecommendationCache.php                # Cache model
│   └── ... (existing models)
└── ...
```

### 5.2 Controllers

#### **AIRecommendationController**

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitQuizRequest;
use App\Services\AI\AIRecommendationService;
use App\Services\AI\ContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AIRecommendationController extends Controller
{
    protected AIRecommendationService $aiService;
    protected ContextBuilder $contextBuilder;

    public function __construct(
        AIRecommendationService $aiService,
        ContextBuilder $contextBuilder
    ) {
        $this->aiService = $aiService;
        $this->contextBuilder = $contextBuilder;
    }

    /**
     * Submit quiz and get AI recommendations
     *
     * @param SubmitQuizRequest $request
     * @return JsonResponse
     */
    public function submitQuiz(SubmitQuizRequest $request): JsonResponse
    {
        $quizData = $request->validated();

        // Build context from database
        $context = $this->contextBuilder->build($quizData);

        // Generate recommendations using AI
        $result = $this->aiService->generateRecommendations($context);

        // Store quiz result
        $quizResult = QuizResult::create([
            'user_id' => auth()->id(),
            'session_token' => Str::random(32),
            'answers_json' => $quizData,
            'profile_type' => $result['profile']['type'],
            'profile_data_json' => $result['profile'],
            'recommended_product_ids' => collect($result['recommendations'])
                ->pluck('product_id'),
        ]);

        return response()->json([
            'success' => true,
            'profile' => $result['profile'],
            'recommendations' => $result['recommendations'],
            'session_id' => $quizResult->session_token,
        ]);
    }

    /**
     * Get recommendations with filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $filters = $request->only(['price_range', 'in_stock', 'brands', 'notes']);

        // Apply filters to existing recommendations
        $recommendations = $this->aiService->filterRecommendations($filters);

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
        ]);
    }
}
```

#### **ChatController**

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendChatMessageRequest;
use App\Models\AiChatSession;
use App\Models\AiMessage;
use App\Services\AI\AIRecommendationService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    protected AIRecommendationService $aiService;

    public function __construct(AIRecommendationService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Send chat message and get AI response
     *
     * @param SendChatMessageRequest $request
     * @return JsonResponse
     */
    public function sendMessage(SendChatMessageRequest $request): JsonResponse
    {
        $session = AiChatSession::where('session_token', $request->session_id)
            ->firstOrFail();

        // Store user message
        AiMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $request->message,
        ]);

        // Get chat history
        $history = AiMessage::where('session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        // Get AI response
        $response = $this->aiService->chat($request->message, $history, $session);

        // Store AI message
        AiMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $response['message'],
            'metadata_json' => ['products' => $response['products'] ?? []],
        ]);

        return response()->json([
            'success' => true,
            'message' => $response['message'],
            'products' => $response['products'] ?? [],
        ]);
    }
}
```

### 5.3 Services

#### **AIRecommendationService** (Main Service)

```php
<?php

namespace App\Services\AI;

use App\Services\AI\Providers\AIProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIRecommendationService
{
    protected GeminiProvider $provider;
    protected ReActAgentEngine $agentEngine;
    protected ContextBuilder $contextBuilder;

    public function __construct(
        GeminiProvider $provider,
        ReActAgentEngine $agentEngine,
        ContextBuilder $contextBuilder
    ) {
        $this->provider = $provider;
        $this->agentEngine = $agentEngine;
        $this->contextBuilder = $contextBuilder;
    }

    /**
     * Generate recommendations using ReAct agent
     *
     * @param array $context
     * @return array
     */
    public function generateRecommendations(array $context): array
    {
        $cacheKey = $this->getCacheKey($context);

        return Cache::remember($cacheKey, 3600, function () use ($context) {
            try {
                // Try primary model (gemini-2.5-flash-lite)
                return $this->agentEngine->execute($context, $this->provider);
            } catch (\Exception $e) {
                Log::warning('Primary Gemini model failed, using fallback', [
                    'error' => $e->getMessage(),
                ]);

                // Fallback to gemini-2.5-flash
                try {
                    return $this->agentEngine->execute($context, $this->provider, useFallback: true);
                } catch (\Exception $e2) {
                    Log::error('Fallback Gemini model also failed', [
                        'error' => $e2->getMessage(),
                    ]);

                    // Return cached or empty response
                    return $this->getFallbackResponse($context);
                }
            }
        });
    }

    /**
     * Handle chat messages
     *
     * @param string $message
     * @param Collection $history
     * @param AiChatSession $session
     * @return array
     */
    public function chat(string $message, $history, $session): array
    {
        $context = $this->contextBuilder->buildForChat($session, $history);

        try {
            $response = $this->provider->chat($message, $context);
        } catch (\Exception $e) {
            Log::warning('Primary chat failed, using fallback model', [
                'error' => $e->getMessage(),
            ]);
            $response = $this->provider->chat($message, $context, useFallback: true);
        }

        return [
            'message' => $response['message'],
            'products' => $response['products'] ?? [],
        ];
    }

    /**
     * Generate cache key for recommendations
     */
    protected function getCacheKey(array $context): string
    {
        return 'ai_recommendations_' . md5(json_encode($context));
    }
}
```

#### **ReActAgentEngine** (ReAct Pattern Implementation)

````php
<?php

namespace App\Services\AI;

use App\Services\AI\Providers\AIProviderInterface;
use App\Services\AI\ToolRegistry;

/**
 * ReAct Agent Engine
 *
 * Implements the ReAct pattern (Reasoning + Acting) from:
 * "ReAct: Synergizing Reasoning and Acting in Language Models"
 * Yao et al., ICLR 2023
 *
 * Pattern: Thought -> Action -> Observation -> (repeat)
 */
class ReActAgentEngine
{
    protected ToolRegistry $toolRegistry;
    protected int $maxIterations = 5;

    public function __construct(ToolRegistry $toolRegistry)
    {
        $this->toolRegistry = $toolRegistry;
    }

    /**
     * Execute ReAct loop
     *
     * @param array $context User context and quiz data
     * @param AIProviderInterface $provider AI provider (Gemini)
     * @return array Final recommendations
     */
    public function execute(array $context, AIProviderInterface $provider): array
    {
        $iteration = 0;
        $conversation = [];

        // Initial prompt with context
        $systemPrompt = $this->buildSystemPrompt($context);
        $conversation[] = ['role' => 'system', 'content' => $systemPrompt];

        while ($iteration < $this->maxIterations) {
            $iteration++;

            // Step 1: Get AI response (Thought or Action)
            $response = $provider->generateWithTools($conversation, $this->toolRegistry->getDefinitions());

            // Check if AI provided final answer
            if ($response['type'] === 'final') {
                return $this->parseFinalResponse($response['content']);
            }

            // Check if AI wants to call a tool
            if ($response['type'] === 'tool_call') {
                $conversation[] = [
                    'role' => 'assistant',
                    'content' => $response['thought'],
                    'tool_calls' => [$response['tool_call']],
                ];

                // Step 2: Execute tool (Action)
                $toolResult = $this->executeTool($response['tool_call']);

                // Step 3: Add observation to conversation
                $conversation[] = [
                    'role' => 'tool',
                    'tool_call_id' => $response['tool_call']['id'],
                    'content' => json_encode($toolResult),
                ];

                continue;
            }

            // Unexpected response type
            throw new \RuntimeException('Unexpected AI response type: ' . $response['type']);
        }

        throw new \RuntimeException('Max iterations reached without final answer');
    }

    /**
     * Build system prompt with ReAct instructions
     */
    protected function buildSystemPrompt(array $context): string
    {
        return <<<PROMPT
You are an expert fragrance consultant helping users find their perfect perfume.

USER CONTEXT:
- Personality: {$context['personality']}
- Preferred Vibe: {$context['vibe']}
- Budget: Under ¥{$context['budget']}

Follow the ReAct pattern:
1. THINK: Analyze what information you need
2. ACT: Call appropriate tools to gather data
3. OBSERVE: Review tool results
4. REPEAT: Until you have enough information
5. FINAL: Provide recommendations in JSON format

You have access to these tools:
- search_products: Search product catalog
- check_inventory: Check stock levels
- get_reviews: Get product ratings

Return your final answer as JSON with this structure:
{
  "profile": {
    "type": "string",
    "name": "string",
    "description": "string"
  },
  "recommendations": [
    {
      "product_id": number,
      "match_score": number,
      "explanation": "string"
    }
  ]
}
PROMPT;
    }

    /**
     * Execute tool by name
     */
    protected function executeTool(array $toolCall): array
    {
        $toolName = $toolCall['function']['name'];
        $arguments = json_decode($toolCall['function']['arguments'], true);

        return $this->toolRegistry->execute($toolName, $arguments);
    }

    /**
     * Parse final AI response
     */
    protected function parseFinalResponse(string $content): array
    {
        // Extract JSON from response
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in AI response: ' . json_last_error_msg());
        }

        return $data;
    }
}
````

#### **ToolRegistry** (Tool Definitions)

```php
<?php

namespace App\Services\AI;

use App\Models\Product;
use App\Models\Inventory;
use App\Models\Review;

/**
 * Tool Registry
 *
 * Defines all tools available to the AI agent
 * These tools allow the AI to interact with the database
 */
class ToolRegistry
{
    protected array $tools = [];

    public function __construct()
    {
        $this->registerDefaultTools();
    }

    /**
     * Get all tool definitions for AI
     */
    public function getDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products',
                    'description' => 'Search fragrance products by category, price range, and notes. Returns matching products from catalog.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'description' => 'Product category (e.g., floral, woody, fresh)',
                            ],
                            'max_price' => [
                                'type' => 'number',
                                'description' => 'Maximum price in yen',
                            ],
                            'notes' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Fragrance notes to search for',
                            ],
                        ],
                        'required' => ['category'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'check_inventory',
                    'description' => 'Check stock levels for specific product IDs',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_ids' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                                'description' => 'Array of product IDs to check',
                            ],
                        ],
                        'required' => ['product_ids'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_product_reviews',
                    'description' => 'Get reviews and ratings for products',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_ids' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                                'description' => 'Array of product IDs',
                            ],
                        ],
                        'required' => ['product_ids'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Execute tool by name
     */
    public function execute(string $toolName, array $arguments): array
    {
        return match($toolName) {
            'search_products' => $this->searchProducts($arguments),
            'check_inventory' => $this->checkInventory($arguments),
            'get_product_reviews' => $this->getProductReviews($arguments),
            default => throw new \InvalidArgumentException("Unknown tool: {$toolName}"),
        };
    }

    /**
     * Search products tool
     */
    protected function searchProducts(array $args): array
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with(['variants.inventory', 'images', 'reviews']);

        if (isset($args['category'])) {
            $query->whereHas('category', function($q) use ($args) {
                $q->where('name', 'like', "%{$args['category']}%");
            });
        }

        if (isset($args['max_price'])) {
            $query->whereHas('variants', function($q) use ($args) {
                $q->where('price_yen', '<=', $args['max_price']);
            });
        }

        $products = $query->limit(20)->get();

        return [
            'count' => $products->count(),
            'products' => $products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand->name,
                'category' => $p->category->name,
                'notes' => $p->attributes_json['notes'] ?? [],
                'gender' => $p->attributes_json['gender'] ?? 'unisex',
                'price' => $p->variants->min('price_yen'),
                'rating' => $p->reviews->avg('rating'),
            ]),
        ];
    }

    /**
     * Check inventory tool
     *
     * Note: Inventory is linked via product_variant_id, not product_id
     * To check stock for products, we need to join through variants
     */
    protected function checkInventory(array $args): array
    {
        // Get product variant IDs for the requested products
        $variantIds = \App\Models\ProductVariant::whereIn('product_id', $args['product_ids'])
            ->pluck('id');

        $inventory = Inventory::whereIn('product_variant_id', $variantIds)
            ->with('variant.product')
            ->get()
            ->mapWithKeys(fn($inv) => [
                $inv->variant->product_id => [
                    'in_stock' => $inv->stock > $inv->safety_stock,
                    'stock' => $inv->stock,
                    'low_stock' => $inv->stock <= $inv->safety_stock,
                ]
            ]);

        return ['inventory' => $inventory];
    }

    /**
     * Get product reviews tool
     *
     * Note: Reviews table uses 'approved' column (not 'is_approved')
     */
    protected function getProductReviews(array $args): array
    {
        $reviews = Review::whereIn('product_id', $args['product_ids'])
            ->where('approved', true)
            ->selectRaw('product_id, AVG(rating) as avg_rating, COUNT(*) as review_count')
            ->groupBy('product_id')
            ->get()
            ->mapWithKeys(fn($r) => [
                $r->product_id => [
                    'avg_rating' => round($r->avg_rating, 2),
                    'review_count' => $r->review_count,
                ]
            ]);

        return ['reviews' => $reviews];
    }
}
```

#### **ContextBuilder** (Data Preparation)

```php
<?php

namespace App\Services\AI;

use App\Models\Product;
use App\Models\RankingSnapshot;
use App\Models\AiChatSession;

/**
 * Context Builder
 *
 * Prepares database context for AI consumption
 * Aggregates data from multiple tables
 */
class ContextBuilder
{
    /**
     * Build context for quiz recommendations
     */
    public function build(array $quizData): array
    {
        return [
            'user_profile' => [
                'personality' => $quizData['personality'],
                'vibe' => $quizData['vibe'],
                'occasion' => $quizData['occasion'],
                'style' => $quizData['style'],
                'budget' => $quizData['budget'],
                'experience' => $quizData['experience'],
                'season' => $quizData['season'] ?? null,
            ],
            'available_products' => $this->getAvailableProducts($quizData),
            'trending_products' => $this->getTrendingProducts(),
            'top_rated_products' => $this->getTopRatedProducts(),
            'categories' => $this->getCategories(),
            'brands' => $this->getBrands(),
        ];
    }

    /**
     * Build context for chat refinement
     */
    public function buildForChat(AiChatSession $session, $history): array
    {
        $quizResult = $session->quizResult;

        return [
            'quiz_context' => $quizResult->answers_json,
            'profile_type' => $quizResult->profile_type,
            'previous_recommendations' => $quizResult->recommended_product_ids,
            'chat_history' => $history->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ]),
            'budget' => $quizResult->answers_json['budget'] ?? 5000,
        ];
    }

    /**
     * Get available products based on budget
     */
    protected function getAvailableProducts(array $quizData): array
    {
        $maxPrice = $quizData['budget'] ?? 5000;

        return Product::with(['variants', 'brand', 'category'])
            ->where('is_active', true)
            ->whereHas('variants', function($q) use ($maxPrice) {
                $q->where('price_yen', '<=', $maxPrice);
            })
            ->limit(50)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand->name,
                'category' => $p->category->name,
                'notes' => $p->attributes_json['notes'] ?? [],
                'gender' => $p->attributes_json['gender'] ?? 'unisex',
                'min_price' => $p->variants->min('price_yen'),
                'max_price' => $p->variants->max('price_yen'),
            ])
            ->toArray();
    }

    /**
     * Get trending products
     */
    protected function getTrendingProducts(): array
    {
        return RankingSnapshot::with('product')
            ->where('snapshot_date', '>=', now()->subDays(7))
            ->orderBy('view_count', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($r) => [
                'id' => $r->product->id,
                'name' => $r->product->name,
                'view_count' => $r->view_count,
            ])
            ->toArray();
    }

    /**
     * Get top rated products
     *
     * Note: Reviews table uses 'approved' column (not 'is_approved')
     */
    protected function getTopRatedProducts(): array
    {
        return Product::with('reviews')
            ->where('is_active', true)
            ->select('products.*')
            ->selectRaw('AVG(reviews.rating) as avg_rating')
            ->join('reviews', 'products.id', '=', 'reviews.product_id')
            ->where('reviews.approved', true)
            ->groupBy('products.id')
            ->orderByDesc('avg_rating')
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'avg_rating' => round($p->avg_rating, 2),
            ])
            ->toArray();
    }

    /**
     * Get all categories
     */
    protected function getCategories(): array
    {
        return \App\Models\Category::all()
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
    }

    /**
     * Get all brands
     */
    protected function getBrands(): array
    {
        return \App\Models\Brand::all()
            ->map(fn($b) => ['id' => $b->id, 'name' => $b->name])
            ->toArray();
    }
}
```

### 5.4 AI Providers

#### **AIProviderInterface** (Contract)

```php
<?php

namespace App\Services\AI\Providers;

/**
 * AI Provider Interface
 *
 * Contract for all AI provider implementations
 * Ensures consistent API across Gemini models
 */
interface AIProviderInterface
{
    /**
     * Generate response with tool calling support
     *
     * @param array $conversation Conversation history
     * @param array $tools Available tool definitions
     * @return array Response with type (final|tool_call) and content
     */
    public function generateWithTools(array $conversation, array $tools): array;

    /**
     * Simple chat completion
     *
     * @param string $message User message
     * @param array $context Context data
     * @return array Response with message and optional products
     */
    public function chat(string $message, array $context): array;
}
```

#### **GeminiProvider** (Fallback Provider)

```php
<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini Provider (FALLBACK)
 *
 * Fallback AI provider using Google Gemini API
 * - 1M token context window
 * - Excellent Japanese support
 * - 15 RPM, 250 requests/day FREE
 *
 * Get API key: https://ai.google.dev
 */
class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $model = 'gemini-2.0-flash';
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Generate with tool calling
     */
    public function generateWithTools(array $conversation, array $tools): array
    {
        // Convert conversation to Gemini format
        $contents = $this->formatConversation($conversation);

        // Build request payload
        $payload = [
            'contents' => $contents,
            'tools' => [
                [
                    'function_declarations' => $this->formatTools($tools),
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2000,
            ],
        ];

        // Make API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
            $payload
        );

        if (!$response->successful()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        return $this->parseResponse($response->json());
    }

    /**
     * Simple chat
     */
    public function chat(string $message, array $context): array
    {
        $prompt = $this->buildChatPrompt($message, $context);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1000,
                ],
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Extract product mentions if any
        $products = $this->extractProductMentions($text);

        return [
            'message' => $text,
            'products' => $products,
        ];
    }

    /**
     * Format conversation for Gemini API
     */
    protected function formatConversation(array $conversation): array
    {
        return collect($conversation)->map(function ($msg) {
            $role = match($msg['role']) {
                'system', 'user' => 'user',
                'assistant' => 'model',
                'tool' => 'user',
                default => 'user',
            };

            $parts = [];

            if (isset($msg['content'])) {
                $parts[] = ['text' => $msg['content']];
            }

            // Handle tool calls
            if (isset($msg['tool_calls'])) {
                foreach ($msg['tool_calls'] as $toolCall) {
                    $parts[] = [
                        'function_call' => [
                            'name' => $toolCall['function']['name'],
                            'args' => json_decode($toolCall['function']['arguments'], true),
                        ],
                    ];
                }
            }

            // Handle tool results
            if (isset($msg['tool_call_id'])) {
                $parts[] = [
                    'function_response' => [
                        'name' => 'tool_result',
                        'response' => json_decode($msg['content'], true),
                    ],
                ];
            }

            return [
                'role' => $role,
                'parts' => $parts,
            ];
        })->toArray();
    }

    /**
     * Format tools for Gemini API
     */
    protected function formatTools(array $tools): array
    {
        return collect($tools)->map(function ($tool) {
            return [
                'name' => $tool['function']['name'],
                'description' => $tool['function']['description'],
                'parameters' => $tool['function']['parameters'],
            ];
        })->toArray();
    }

    /**
     * Parse Gemini API response
     */
    protected function parseResponse(array $data): array
    {
        $candidate = $data['candidates'][0] ?? null;

        if (!$candidate) {
            throw new \RuntimeException('No candidates in Gemini response');
        }

        $parts = $candidate['content']['parts'] ?? [];

        // Check for function call
        foreach ($parts as $part) {
            if (isset($part['function_call'])) {
                return [
                    'type' => 'tool_call',
                    'thought' => $this->extractThought($parts),
                    'tool_call' => [
                        'id' => uniqid('call_'),
                        'function' => [
                            'name' => $part['function_call']['name'],
                            'arguments' => json_encode($part['function_call']['args']),
                        ],
                    ],
                ];
            }
        }

        // Check for text response
        $text = $parts[0]['text'] ?? '';

        return [
            'type' => 'final',
            'content' => $text,
        ];
    }

    /**
     * Extract reasoning thought from parts
     */
    protected function extractThought(array $parts): string
    {
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                return $part['text'];
            }
        }
        return '';
    }

    /**
     * Build chat prompt
     */
    protected function buildChatPrompt(string $message, array $context): string
    {
        $profile = $context['profile_type'] ?? 'unknown';
        $budget = $context['budget'] ?? 5000;

        return <<<PROMPT
You are a helpful fragrance consultant chatting with a Japanese user.

USER PROFILE: {$profile}
BUDGET: Under ¥{$budget}

USER MESSAGE: {$message}

Respond in Japanese, be friendly and helpful. If you mention specific products,
include their IDs in your response so we can display product cards.
PROMPT;
    }

    /**
     * Extract product mentions from text
     */
    protected function extractProductMentions(string $text): array
    {
        // Extract product IDs using regex pattern [Product ID: 123]
        preg_match_all('/Product ID:\s*(\d+)/', $text, $matches);

        if (empty($matches[1])) {
            return [];
        }

        // Fetch product details
        return \App\Models\Product::whereIn('id', $matches[1])
            ->with(['variants', 'images'])
            ->get()
            ->toArray();
    }
}
```

#### **GeminiProvider** (Primary + Fallback Models)

```php
<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini Provider
 *
 * Primary AI provider using Google Gemini API
 * - Primary: gemini-2.5-flash-lite (15 RPM, 1,000 RPD)
 * - Fallback: gemini-2.5-flash (10 RPM, 250 RPD)
 *
 * Get API key: https://ai.google.dev
 */
class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $primaryModel = 'gemini-2.5-flash-lite';
    protected string $fallbackModel = 'gemini-2.5-flash';
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Generate with tool calling (function calling in Gemini)
     */
    public function generateWithTools(array $conversation, array $tools, bool $useFallback = false): array
    {
        $model = $useFallback ? $this->fallbackModel : $this->primaryModel;

        $contents = $this->formatConversation($conversation);
        $functionDeclarations = $this->formatTools($tools);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-goog-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/models/{$model}:generateContent", [
            'contents' => $contents,
            'tools' => [
                'functionDeclarations' => $functionDeclarations,
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2000,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Gemini API error', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        return $this->parseResponse($response->json());
    }

    /**
     * Simple chat
     */
    public function chat(string $message, array $context, bool $useFallback = false): array
    {
        $model = $useFallback ? $this->fallbackModel : $this->primaryModel;
        $systemPrompt = $this->buildSystemPrompt($context);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-goog-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/models/{$model}:generateContent", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nUser: " . $message],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return [
            'message' => $text,
            'products' => [],
        ];
    }

    /**
     * Format conversation for Gemini API
     */
    protected function formatConversation(array $conversation): array
    {
        $contents = [];

        foreach ($conversation as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';

            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $msg['content'] ?? ''],
                ],
            ];
        }

        return $contents;
    }

    /**
     * Format tools for Gemini function calling
     */
    protected function formatTools(array $tools): array
    {
        return collect($tools)->map(function ($tool) {
            return [
                'name' => $tool['function']['name'],
                'description' => $tool['function']['description'],
                'parameters' => $tool['function']['parameters'],
            ];
        })->toArray();
    }

    /**
     * Parse Gemini API response
     */
    protected function parseResponse(array $data): array
    {
        $candidate = $data['candidates'][0] ?? null;

        if (!$candidate) {
            throw new \RuntimeException('No candidate in Gemini response');
        }

        $content = $candidate['content']['parts'][0] ?? null;

        // Check for function call
        if (isset($content['functionCall'])) {
            return [
                'type' => 'tool_call',
                'thought' => '',
                'tool_call' => [
                    'id' => uniqid(),
                    'function' => [
                        'name' => $content['functionCall']['name'],
                        'arguments' => json_encode($content['functionCall']['args'] ?? []),
                    ],
                ],
            ];
        }

        // Final text response
        return [
            'type' => 'final',
            'content' => $content['text'] ?? '',
        ];
    }

    /**
     * Build system prompt
     */
    protected function buildSystemPrompt(array $context): string
    {
        $profile = $context['profile_type'] ?? 'unknown';

        return <<<PROMPT
You are a helpful fragrance consultant. The user has a {$profile} scent profile.
Respond in Japanese in a friendly, helpful manner.
PROMPT;
    }
}
```

### 5.5 Form Requests (Validation)

#### **SubmitQuizRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'personality' => 'required|string|in:romantic,energetic,cool,natural',
            'vibe' => 'required|string|in:floral,citrus,vanilla,woody,ocean',
            'occasion' => 'required|array|min:1',
            'occasion.*' => 'string|in:daily,date,special,work,casual',
            'style' => 'required|string|in:feminine,casual,chic,natural',
            'budget' => 'required|integer|min:1000|max:50000',
            'experience' => 'required|string|in:beginner,intermediate,advanced',
            'season' => 'nullable|string|in:spring_summer,fall_winter,all_year',
        ];
    }

    public function messages(): array
    {
        return [
            'personality.required' => '性格を選択してください',
            'vibe.required' => '好みの香りを選択してください',
            'occasion.required' => '使用シーンを選択してください',
            'style.required' => 'スタイルを選択してください',
            'budget.required' => '予算を設定してください',
            'experience.required' => '香水経験を選択してください',
        ];
    }
}
```

#### **SendChatMessageRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => 'required|string|exists:ai_chat_sessions,session_token',
            'message' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => 'セッションIDが必要です',
            'session_id.exists' => '無効なセッションです',
            'message.required' => 'メッセージを入力してください',
            'message.max' => 'メッセージは1000文字以内で入力してください',
        ];
    }
}
```

---

## 6. Frontend Components

### 6.1 Directory Structure

```
resources/js/
├── pages/
│   ├── FragranceDiagnosis.tsx           # Enhanced quiz page
│   ├── FragranceDiagnosisResults.tsx    # Results page
│   └── ... (existing pages)
├── components/
│   └── AIChat/
│       ├── ChatContainer.tsx           # Main chat component
│       ├── MessageBubble.tsx           # Individual message
│       ├── ProductCard.tsx             # Product in chat
│       ├── QuickReplies.tsx            # Suggested responses
│       ├── TypingIndicator.tsx         # AI typing animation
│       └── ChatInput.tsx               # User input
├── hooks/
│   └── useAIChat.ts                    # Chat state management
└── types/
    └── ai.ts                           # TypeScript types
```

### 6.2 Key Components

#### **FragranceDiagnosis.tsx** (Enhanced Quiz Page)

```typescript
// resources/js/pages/FragranceDiagnosis.tsx
import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';

interface QuizState {
  step: number;
  answers: {
    personality?: string;
    vibe?: string;
    occasion?: string[];
    style?: string;
    budget?: number;
    experience?: string;
    season?: string;
  };
  isSubmitting: boolean;
}

const QUESTIONS = [
  {
    id: 'personality',
    question: 'あなたの普段の印象は？',
    type: 'single',
    options: [
      { id: 'romantic', label: 'ロマンチック', image: '/images/quiz/romantic.jpg' },
      { id: 'energetic', label: '元気いっぱい', image: '/images/quiz/energetic.jpg' },
      { id: 'cool', label: 'クール', image: '/images/quiz/cool.jpg' },
      { id: 'natural', label: 'ナチュラル', image: '/images/quiz/natural.jpg' },
    ],
  },
  // ... more questions
];

export default function FragranceDiagnosis() {
  const [state, setState] = useState<QuizState>({
    step: 1,
    answers: {},
    isSubmitting: false,
  });

  const currentQuestion = QUESTIONS[state.step - 1];
  const progress = (state.step / QUESTIONS.length) * 100;

  const handleAnswer = (value: any) => {
    setState(prev => ({
      ...prev,
      answers: { ...prev.answers, [currentQuestion.id]: value },
      step: prev.step + 1,
    }));
  };

  const submitQuiz = async () => {
    setState(prev => ({ ...prev, isSubmitting: true }));

    try {
      const response = await fetch('/api/ai/quiz', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(state.answers),
      });

      const data = await response.json();

      router.visit('/fragrance-diagnosis/results', {
        data: {
          profile: data.profile,
          recommendations: data.recommendations,
          sessionId: data.session_id,
        },
      });
    } catch (error) {
      console.error('Quiz submission failed:', error);
      alert('エラーが発生しました。もう一度お試しください。');
    }
  };

  return (
    <div className="min-h-screen bg-white py-8">
      <div className="max-w-4xl mx-auto px-4">
        {/* Progress Bar */}
        <div className="mb-8">
          <Progress value={progress} className="h-2" />
          <p className="text-sm text-gray-600 mt-2 text-center">
            {state.step} / {QUESTIONS.length}
          </p>
        </div>

        {/* Question */}
        <div className="mb-8">
          <h2 className="text-2xl font-bold text-center mb-8">
            {currentQuestion.question}
          </h2>

          <div className="grid grid-cols-2 gap-4">
            {currentQuestion.options.map((option) => (
              <button
                key={option.id}
                onClick={() => handleAnswer(option.id)}
                className="p-6 border-2 border-gray-200 rounded-lg hover:border-black transition-colors"
              >
                <img
                  src={option.image}
                  alt={option.label}
                  className="w-full h-32 object-cover rounded mb-4"
                />
                <span className="font-medium">{option.label}</span>
              </button>
            ))}
          </div>
        </div>

        {/* Navigation */}
        <div className="flex justify-between">
          {state.step > 1 && (
            <Button
              variant="outline"
              onClick={() => setState(prev => ({ ...prev, step: prev.step - 1 }))}
            >
              戻る
            </Button>
          )}

          {state.step === QUESTIONS.length && (
            <Button
              onClick={submitQuiz}
              disabled={state.isSubmitting}
              className="ml-auto"
            >
              {state.isSubmitting ? '送信中...' : '結果を見る'}
            </Button>
          )}
        </div>
      </div>
    </div>
  );
}
```

#### **ChatContainer.tsx** (Chat Component)

```typescript
// resources/js/components/AIChat/ChatContainer.tsx
import React, { useState, useRef, useEffect } from 'react';
import { Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import MessageBubble from './MessageBubble';
import TypingIndicator from './TypingIndicator';
import QuickReplies from './QuickReplies';

interface Message {
  id: string;
  role: 'user' | 'assistant';
  content: string;
  products?: Product[];
  timestamp: Date;
}

interface ChatContainerProps {
  sessionId: string;
}

export default function ChatContainer({ sessionId }: ChatContainerProps) {
  const [messages, setMessages] = useState<Message[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [isTyping, setIsTyping] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const sendMessage = async (content: string) => {
    // Add user message
    const userMessage: Message = {
      id: Date.now().toString(),
      role: 'user',
      content,
      timestamp: new Date(),
    };
    setMessages(prev => [...prev, userMessage]);
    setInputValue('');
    setIsTyping(true);

    try {
      const response = await fetch('/api/ai/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: sessionId,
          message: content,
        }),
      });

      const data = await response.json();

      // Add AI message
      const aiMessage: Message = {
        id: (Date.now() + 1).toString(),
        role: 'assistant',
        content: data.message,
        products: data.products,
        timestamp: new Date(),
      };

      setMessages(prev => [...prev, aiMessage]);
    } catch (error) {
      console.error('Chat error:', error);
    } finally {
      setIsTyping(false);
    }
  };

  // Auto-scroll to bottom
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, isTyping]);

  return (
    <div className="flex flex-col h-full bg-white rounded-lg shadow-lg">
      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {messages.map(message => (
          <MessageBubble key={message.id} message={message} />
        ))}
        {isTyping && <TypingIndicator />}
        <div ref={messagesEndRef} />
      </div>

      {/* Quick Replies */}
      <QuickReplies
        options={[
          'もっと甘い香りは？',
          '価格が安い順で',
          '在庫があるものだけ',
          'この香りの特徴を教えて',
        ]}
        onSelect={sendMessage}
      />

      {/* Input */}
      <div className="p-4 border-t">
        <div className="flex gap-2">
          <Input
            value={inputValue}
            onChange={(e) => setInputValue(e.target.value)}
            placeholder="メッセージを入力..."
            onKeyPress={(e) => e.key === 'Enter' && sendMessage(inputValue)}
          />
          <Button onClick={() => sendMessage(inputValue)} disabled={!inputValue.trim()}>
            <Send className="w-4 h-4" />
          </Button>
        </div>
      </div>
    </div>
  );
}
```

---

## 7. Database Schema

### 7.1 Migration Files

#### **Create AI Chat Sessions Table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_token', 64)->unique();
            $table->foreignId('quiz_result_id')->nullable();
            $table->json('context_json')->nullable();
            $table->timestamps();

            $table->index('session_token');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_sessions');
    }
};
```

#### **Create AI Messages Table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_chat_sessions')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
```

#### **Create Quiz Results Table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_token', 64);
            $table->json('answers_json');
            $table->string('profile_type');
            $table->json('profile_data_json');
            $table->json('recommended_product_ids');
            $table->timestamps();

            $table->index('session_token');
            $table->index('user_id');
            $table->index('profile_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
    }
};
```

#### **Create User Scent Profiles Table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_scent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('profile_type');
            $table->json('profile_data_json');
            $table->json('preferences_json');
            $table->timestamps();

            $table->unique('user_id');
            $table->index('profile_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_scent_profiles');
    }
};
```

#### **Create AI Recommendation Cache Table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_recommendation_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 255)->unique();
            $table->string('context_hash', 64);
            $table->json('product_ids_json');
            $table->text('explanation')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('cache_key');
            $table->index('expires_at');
            $table->index('context_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendation_cache');
    }
};
```

### 7.2 Eloquent Models

#### **AiChatSession Model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'quiz_result_id',
        'context_json',
    ];

    protected $casts = [
        'context_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'session_id');
    }

    public function quizResult(): HasOne
    {
        return $this->hasOne(QuizResult::class, 'id', 'quiz_result_id');
    }
}
```

#### **AiMessage Model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'metadata_json',
    ];

    protected $casts = [
        'metadata_json' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }
}
```

#### **QuizResult Model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'answers_json',
        'profile_type',
        'profile_data_json',
        'recommended_product_ids',
    ];

    protected $casts = [
        'answers_json' => 'array',
        'profile_data_json' => 'array',
        'recommended_product_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## 8. AI Integration (ReAct Pattern)

### 8.1 ReAct Pattern Implementation

**Based on:** "ReAct: Synergizing Reasoning and Acting in Language Models" (Yao et al., ICLR 2023)

#### **The ReAct Loop**

```
┌─────────────────────────────────────────────────────────────────┐
│                        REACT LOOP                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. THOUGHT                                                     │
│     AI analyzes the current state and determines what           │
│     information it needs to answer the query                    │
│     Example: "User wants floral scents under ¥5,000.             │
│              I need to search the product catalog."             │
│                            │                                    │
│                            ▼                                    │
│  2. ACTION                                                      │
│     AI calls a tool (function) to gather information            │
│     Example: search_products(category: 'floral',                │
│                               max_price: 5000)                  │
│                            │                                    │
│                            ▼                                    │
│  3. OBSERVATION                                                 │
│     AI receives the tool execution results                      │
│     Example: [Product A, Product B, Product C]                  │
│     with prices, ratings, stock levels                          │
│                            │                                    │
│                            ▼                                    │
│  4. DECISION                                                    │
│     AI decides:                                                 │
│     - If more info needed: Go back to THOUGHT                   │
│     - If sufficient: Generate FINAL answer                      │
│                            │                                    │
│                            ▼                                    │
│  5. FINAL ANSWER                                                │
│     AI generates structured recommendations                     │
│     Example: JSON with product_ids, match_scores,               │
│     explanations, and scent profile                             │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 8.2 Tool Calling API (Official Google)

**Source:** https://ai.google.dev/gemini-api/docs/function-calling

#### **How Tool Calling Works**

1. **Define Tools:** Provide JSON schema describing available functions
2. **AI Decides:** Model determines which tool to call and with what parameters
3. **Execute:** Your code executes the function
4. **Return:** Send result back to AI
5. **Final Response:** AI generates answer using tool results

#### **Example Flow**

```
┌─────────────┐
│   User      │ "Find me floral perfumes under ¥5,000"
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Gemini    │ Receives prompt + tool definitions
│     API     │
└──────┬──────┘
       │
       │ Returns function call request:
       │ {
       │   "name": "search_products",
       │   "args": {
       │     "category": "floral",
       │     "max_price": 5000
       │   }
       │ }
       ▼
┌─────────────┐
│  Laravel    │ Executes search_products()
│   Code      │ Queries database
└──────┬──────┘
       │
       │ Returns results:
       │ [Product A, Product B, ...]
       ▼
┌─────────────┐
│   Gemini    │ Receives tool results
│     API     │ Generates final recommendations
└──────┬──────┘
       │
       │ Returns:
       │ "Here are 3 floral perfumes under ¥5,000: ..."
       ▼
┌─────────────┐
│   User      │ Sees recommendations
└─────────────┘
```

### 8.3 Implementation Details

See Section 5.3 (ReActAgentEngine, ToolRegistry) for complete PHP implementation.

---

## 9. API Endpoints

### 9.1 Route Definitions

```php
<?php

// routes/api.php

use App\Http\Controllers\API\AIRecommendationController;
use App\Http\Controllers\API\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:ai-api'])->group(function () {
    // Quiz endpoints
    Route::post('/ai/quiz', [AIRecommendationController::class, 'submitQuiz']);
    Route::get('/ai/recommendations', [AIRecommendationController::class, 'getRecommendations']);

    // Chat endpoints
    Route::post('/ai/chat', [ChatController::class, 'sendMessage']);
});

// Public routes (rate limited separately)
Route::middleware(['throttle:20,1'])->group(function () {
    Route::post('/ai/quiz/guest', [AIRecommendationController::class, 'submitQuizGuest']);
});
```

### 9.2 Rate Limiting Configuration

```php
<?php

// app/Providers/RouteServiceProvider.php

protected function configureRateLimiting(): void
{
    RateLimiter::for('ai-api', function (Request $request) {
        return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
    });
}
```

### 9.3 API Documentation

#### **POST /api/ai/quiz**

Submit quiz answers and get AI recommendations.

**Request:**

```json
{
    "personality": "romantic",
    "vibe": "floral",
    "occasion": ["daily", "date"],
    "style": "feminine",
    "budget": 5000,
    "experience": "beginner",
    "season": "spring_summer"
}
```

**Response:**

```json
{
    "success": true,
    "profile": {
        "type": "romantic_floral",
        "name": "ロマンチック・ブルーム",
        "description": "優しく夢見がちなあなたにぴったりの華やかな香り"
    },
    "recommendations": [
        {
            "product_id": 123,
            "match_score": 95,
            "explanation": "ピオニーとローズの優雅な香りが、ロマンチックなあなたの魅力を引き立てます",
            "product": {
                "id": 123,
                "name": "Chanel Chance Eau Tendre",
                "brand": "Chanel",
                "price_yen": 4800,
                "image_url": "/images/products/123.jpg"
            }
        }
    ],
    "session_id": "abc123xyz789"
}
```

#### **POST /api/ai/chat**

Send chat message and get AI response.

**Request:**

```json
{
    "session_id": "abc123xyz789",
    "message": "もっと甘い香りはありますか？"
}
```

**Response:**

```json
{
    "success": true,
    "message": "もちろん！甘いバニラノートの香水を3つご提案します。",
    "products": [
        {
            "id": 456,
            "name": "Dior Addict",
            "price_yen": 5200
        }
    ]
}
```

---

## 10. Security Architecture

### 10.1 Security Measures

| Layer                | Measure               | Implementation             |
| -------------------- | --------------------- | -------------------------- |
| **API Keys**         | Environment variables | `.env` file, never in code |
| **Rate Limiting**    | 20 req/min per user   | Laravel Throttle           |
| **Input Validation** | FormRequest classes   | Strict validation rules    |
| **SQL Injection**    | Eloquent ORM          | Parameterized queries      |
| **XSS**              | Output encoding       | Blade `{{ }}` syntax       |
| **CSRF**             | Token validation      | Laravel default            |

### 10.2 API Key Protection

```php
<?php

// .env
GEMINI_API_KEY=your_gemini_api_key_here

// config/services.php
'gemini' => [
    'api_key' => env('GEMINI_API_KEY'),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    'primary_model' => env('GEMINI_PRIMARY_MODEL', 'gemini-2.5-flash-lite'),
    'fallback_model' => env('GEMINI_FALLBACK_MODEL', 'gemini-2.5-flash'),
    'rate_limit_per_minute' => 15,
    'rate_limit_per_day' => 1250,
],
```

### 10.3 Input Sanitization

```php
<?php

// Never trust user input
$message = strip_tags($request->input('message'));
$message = Str::limit($message, 1000);

// Validate strictly
$validated = $request->validate([
    'session_id' => 'required|string|size:32',
    'message' => 'required|string|max:1000',
]);
```

---

## 11. Performance & Scalability

### 11.1 Caching Strategy

#### **Multi-Layer Caching**

```php
<?php

// Layer 1: Application Cache (Laravel)
$recommendations = Cache::remember($cacheKey, 3600, function () {
    return $this->generateRecommendations();
});

// Layer 2: Database Cache
// ai_recommendation_cache table stores expensive AI calls

// Layer 3: Client-Side
// localStorage for quiz progress
```

### 11.2 Performance Targets

| Metric            | Target | Max | Measurement   |
| ----------------- | ------ | --- | ------------- |
| Quiz API Response | < 2s   | 5s  | AI + DB query |
| Chat API Response | < 3s   | 5s  | AI inference  |
| Page Load         | < 1s   | 2s  | Frontend      |
| Cache Hit Rate    | > 80%  | -   | Monitoring    |

### 11.3 Optimization Techniques

1. **Database Indexing**

    ```php
    $table->index(['category_id', 'is_active']);
    $table->index(['product_id', 'created_at']);
    ```

2. **Eager Loading**

    ```php
    Product::with(['variants', 'brand', 'reviews'])->get();
    ```

3. **Query Caching**
    ```php
    $products = Product::remember(3600)->get();
    ```

---

## 12. Integration Points

### 12.1 Existing System Integration

| System              | Integration     | File                           |
| ------------------- | --------------- | ------------------------------ |
| **Authentication**  | Laravel Auth    | Uses existing auth             |
| **Product Catalog** | Product Model   | `app/Models/Product.php`       |
| **Inventory**       | Inventory Model | `app/Models/Inventory.php`     |
| **Reviews**         | Review Model    | `app/Models/Review.php`        |
| **Cart**            | CartService     | `app/Services/CartService.php` |
| **Payments**        | Stripe          | Via existing checkout          |
| **Admin Panel**     | Filament        | New resources added            |

### 12.2 Cart Integration

```php
<?php

// Add to cart from recommendations
$cartService = app(CartService::class);
$cartService->addItem($productId, $variantId, $quantity);
```

### 12.3 Event Tracking

```php
<?php

// Track AI interactions for analytics
Event::create([
    'event_type' => 'ai_quiz_completed',
    'user_id' => $userId,
    'value' => count($recommendations),
    'meta_json' => [
        'profile_type' => $profileType,
        'quiz_duration' => $duration,
    ],
]);
```

---

## 13. Error Handling & Fallbacks

### 13.1 Error Handling Strategy

```php
<?php

class AIRecommendationController
{
    public function submitQuiz(SubmitQuizRequest $request)
    {
        try {
            return $this->getAIRecommendations($request);
        } catch (AIProviderException $e) {
            Log::error('AI Provider failed', ['error' => $e->getMessage()]);

            // Fallback 1: Try fallback model
            return $this->getAIRecommendations($request, useFallback: true);

            // Fallback 2: Use rule-based recommendations
            return $this->getRuleBasedRecommendations($request);

            // Fallback 3: Return trending products
            return $this->getTrendingRecommendations();
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'エラーが発生しました。もう一度お試しください。',
            ], 500);
        }
    }
}
```

### 13.2 Fallback Chain

```
1. Primary: Google Gemini API
   ↓ (if fails)
2. Fallback: Groq API
   ↓ (if fails)
3. Rule-Based: Predefined logic based on quiz answers
   ↓ (if fails)
4. Static: Return trending products
```

---

## 14. Deployment Architecture

### 14.1 Environment Configuration

```
Production Environment:
├── PHP 8.3.6
├── Laravel 12.x
├── SQLite / PostgreSQL
├── Redis (optional, for caching)
└── Web Server (Nginx/Apache)
```

### 14.2 Deployment Checklist

- [ ] Set environment variables (API keys)
- [ ] Run migrations
- [ ] Configure rate limiting
- [ ] Set up caching (Redis recommended)
- [ ] Configure logging
- [ ] Test API endpoints
- [ ] Monitor error rates
- [ ] Set up alerts

### 14.3 Monitoring

```php
<?php

// Log AI API latency
$start = microtime(true);
$response = $this->aiProvider->generate($messages);
$latency = microtime(true) - $start;

Log::info('AI API Latency', [
    'provider' => $this->provider,
    'latency_ms' => $latency * 1000,
    'tokens_used' => $response['usage']['total_tokens'] ?? 0,
]);
```

---

## Appendix A: File Summary

### New Files to Create

#### **Backend (PHP)**

1. `app/Http/Controllers/API/AIRecommendationController.php`
2. `app/Http/Controllers/API/ChatController.php`
3. `app/Http/Requests/SubmitQuizRequest.php`
4. `app/Http/Requests/SendChatMessageRequest.php`
5. `app/Services/AI/AIRecommendationService.php`
6. `app/Services/AI/ReActAgentEngine.php`
7. `app/Services/AI/ToolRegistry.php`
8. `app/Services/AI/ContextBuilder.php`
9. `app/Services/AI/Providers/AIProviderInterface.php`
10. `app/Services/AI/Providers/GeminiProvider.php`
11. `app/Models/AiChatSession.php`
12. `app/Models/AiMessage.php`
13. `app/Models/QuizResult.php`
14. `app/Models/UserScentProfile.php`

#### **Database**

1. `database/migrations/2026_02_18_000001_create_ai_chat_sessions_table.php`
2. `database/migrations/2026_02_18_000002_create_ai_messages_table.php`
3. `database/migrations/2026_02_18_000003_create_quiz_results_table.php`
4. `database/migrations/2026_02_18_000004_create_user_scent_profiles_table.php`
5. `database/migrations/2026_02_18_000005_create_ai_recommendation_cache_table.php`

#### **Frontend (React/TypeScript)**

1. `resources/js/pages/FragranceDiagnosisResults.tsx`
2. `resources/js/components/AIChat/ChatContainer.tsx`
3. `resources/js/components/AIChat/MessageBubble.tsx`
4. `resources/js/components/AIChat/ProductCard.tsx`
5. `resources/js/components/AIChat/QuickReplies.tsx`
6. `resources/js/components/AIChat/TypingIndicator.tsx`
7. `resources/js/components/AIChat/ChatInput.tsx`
8. `resources/js/hooks/useAIChat.ts`
9. `resources/js/types/ai.ts`

#### **Modified Files**

1. `resources/js/pages/FragranceDiagnosis.tsx` - Enhanced with API integration
2. `routes/web.php` - Add results route
3. `routes/api.php` - Add API routes
4. `config/services.php` - Add AI provider config

---

## 15. Testing Architecture (100% Real Data)

### 15.1 Testing Infrastructure Overview

**MANDATORY PRINCIPLES:**

- ✅ **NO mocks, NO stubs, NO fake data** - 100% real implementations
- ✅ **Production Database** - All tests use real products, inventory, reviews
- ✅ **Real AI APIs** - Live calls to Google Gemini
- ✅ **100% Pass Rate** - All tests must pass before deployment
- ✅ **Read-Only Safety** - Production data protected by transactions

### 15.2 Testing Technology Stack

**Primary Framework:** PestPHP (already installed)

```json
"pestphp/pest": "^4.0",
"pestphp/pest-plugin-laravel": "^4.0"
```

**Browser Testing:** Laravel Dusk (to install)

```bash
composer require --dev laravel/dusk
php artisan dusk:install
```

**Supporting Tools:**

- `mockery/mockery: ^1.6` - For isolated unit tests (when necessary)
- `fakerphp/faker: ^1.24` - Factory-based test data
- `laravel/tinker: ^2.10.1` - Interactive debugging

### 15.3 Test Architecture Layers

```
┌─────────────────────────────────────────────────────────────────┐
│                    TESTING ARCHITECTURE                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Layer 1: UNIT TESTS (PestPHP)                                  │
│  ├── ToolRegistryTest.php                                       │
│  ├── ContextBuilderTest.php                                     │
│  ├── ResponseParserTest.php                                     │
│  └── ScoreCalculatorTest.php                                    │
│  Database: SQLite (isolated)                                    │
│  Coverage: 90%+                                                 │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Layer 2: FEATURE TESTS (PestPHP + Production DB)               │
│  ├── QuizSubmissionTest.php                                     │
│  ├── ChatRefinementTest.php                                     │
│  ├── RateLimitingTest.php                                       │
│  └── ProviderFallbackTest.php                                   │
│  Database: Production MySQL (read-only transactions)            │
│  Coverage: 100% API endpoints                                   │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Layer 3: INTEGRATION TESTS (Live AI APIs)                      │
│  ├── GeminiLiveTest.php                                         │
│  ├── ToolCallingTest.php                                        │
│  └── ModelFallbackTest.php                                      │
│  APIs: Real Gemini (uses free tier quotas)                      │
│  Coverage: All provider methods                                 │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Layer 4: BROWSER TESTS (Laravel Dusk)                          │
│  ├── FragranceDiagnosisTest.php                                 │
│  ├── ChatInteractionTest.php                                    │
│  └── AddToCartFlowTest.php                                      │
│  Browser: Chrome/Chromium (real browser automation)             │
│  Coverage: Critical user flows                                  │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Layer 5: TINKER SCRIPTS (Interactive)                          │
│  ├── ai-test.php                                                │
│  ├── quiz-debug.php                                             │
│  ├── verify-products.php                                        │
│  └── provider-check.php                                         │
│  Usage: Manual testing and debugging                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 15.4 Unit Test Implementation

**File:** `tests/Unit/Services/AI/ToolRegistryTest.php`

```php
<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\ToolRegistry;
use App\Models\Product;
use App\Models\Inventory;
use PHPUnit\Framework\TestCase;

class ToolRegistryTest extends TestCase
{
    protected ToolRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ToolRegistry();
    }

    public function test_search_products_returns_real_data()
    {
        // Execute tool with real database (SQLite test DB)
        $result = $this->registry->execute('search_products', [
            'category' => 'floral',
            'max_price' => 5000,
        ]);

        // Assert structure
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('products', $result);
        $this->assertIsArray($result['products']);

        // Assert real data properties
        if ($result['count'] > 0) {
            $product = $result['products'][0];
            $this->assertArrayHasKey('id', $product);
            $this->assertArrayHasKey('name', $product);
            $this->assertArrayHasKey('price', $product);
            $this->assertIsInt($product['id']);
            $this->assertIsString($product['name']);
        }
    }

    public function test_check_inventory_returns_stock_data()
    {
        // Use real product IDs from database
        $productIds = Product::limit(5)->pluck('id')->toArray();

        $result = $this->registry->execute('check_inventory', [
            'product_ids' => $productIds,
        ]);

        $this->assertArrayHasKey('inventory', $result);

        foreach ($productIds as $id) {
            $this->assertArrayHasKey($id, $result['inventory']);
            $this->assertArrayHasKey('in_stock', $result['inventory'][$id]);
            $this->assertArrayHasKey('quantity', $result['inventory'][$id]);
        }
    }

    public function test_unknown_tool_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->registry->execute('unknown_tool', []);
    }
}
```

### 15.5 Feature Test Implementation (Production Database)

**File:** `tests/Feature/AI/QuizSubmissionTest.php`

```php
<?php

namespace Tests\Feature\AI;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizSubmissionTest extends TestCase
{
    // Note: Not using RefreshDatabase - using production with transactions

    protected function setUp(): void
    {
        parent::setUp();

        // Verify production database connection
        $dbName = \DB::connection()->getDatabaseName();
        $this->assertEquals('laravel', $dbName, 'Must use production database');

        // Verify real products exist
        $productCount = Product::count();
        $this->assertGreaterThan(0, $productCount, 'No products in database');

        echo "\nTesting with {$productCount} real products\n";
    }

    public function test_quiz_submission_uses_real_production_products()
    {
        $user = User::factory()->create();

        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily', 'date'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/ai/quiz', $quizData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'profile' => ['type', 'name', 'description'],
                'recommendations' => [
                    '*' => ['product_id', 'match_score', 'explanation']
                ],
                'session_id',
            ]);

        $recommendations = $response->json('recommendations');
        $this->assertGreaterThanOrEqual(5, count($recommendations));
        $this->assertLessThanOrEqual(7, count($recommendations));

        // Verify each recommendation references a real product
        foreach ($recommendations as $rec) {
            $product = Product::find($rec['product_id']);
            $this->assertNotNull($product, "Product ID {$rec['product_id']} not found");
            $this->assertTrue($product->is_active, "Product {$rec['product_id']} is not active");
            $this->assertIsInt($rec['match_score']);
            $this->assertGreaterThan(0, $rec['match_score']);
            $this->assertLessThanOrEqual(100, $rec['match_score']);
        }
    }

    public function test_chat_refinement_after_quiz()
    {
        // First complete a quiz
        $session = $this->createQuizSession();

        $response = $this->postJson('/api/ai/chat', [
            'session_id' => $session->session_token,
            'message' => 'もっと甘い香りはありますか？',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'message',
                'products',
            ]);
    }

    public function test_rate_limiting_blocks_excessive_requests()
    {
        $user = User::factory()->create();

        // Make 21 requests (limit is 20/min)
        for ($i = 0; $i < 21; $i++) {
            $response = $this->actingAs($user)
                ->postJson('/api/ai/quiz', [
                    'personality' => 'romantic',
                    'vibe' => 'floral',
                    'occasion' => ['daily'],
                    'style' => 'feminine',
                    'budget' => 5000,
                    'experience' => 'beginner',
                ]);

            if ($i < 20) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429); // Too Many Requests
            }
        }
    }
}
```

### 15.6 Integration Test Implementation (Live AI APIs)

**File:** `tests/Integration/AIProviders/GeminiLiveTest.php`

```php
<?php

namespace Tests\Integration\AIProviders;

use App\Services\AI\Providers\GeminiProvider;
use Tests\TestCase;

/**
 * LIVE API TESTS
 * These tests call real Google Gemini API
 * Each test uses 1 API request from your free quota (1,500/day)
 */
class GeminiLiveTest extends TestCase
{
    protected GeminiProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new GeminiProvider();

        // Verify API key is configured
        $apiKey = config('services.gemini.api_key');
        $this->assertNotNull($apiKey, 'Gemini API key not configured');
        $this->assertNotEmpty($apiKey, 'Gemini API key is empty');
    }

    /**
     * @group live-api
     * @group gemini
     * @group expensive
     */
    public function test_gemini_returns_real_response()
    {
        $response = $this->provider->chat(
            'Recommend a floral perfume under 5000 yen for a romantic person',
            ['budget' => 5000]
        );

        $this->assertArrayHasKey('message', $response);
        $this->assertIsString($response['message']);
        $this->assertNotEmpty($response['message']);

        // Log response for debugging
        echo "\nGemini Response:\n" . $response['message'] . "\n";
    }

    /**
     * @group live-api
     * @group gemini
     * @group tools
     */
    public function test_gemini_tool_calling_with_real_api()
    {
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products',
                    'description' => 'Search fragrance products by category',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => ['type' => 'string'],
                            'max_price' => ['type' => 'number'],
                        ],
                        'required' => ['category'],
                    ],
                ],
            ],
        ];

        $conversation = [
            ['role' => 'user', 'content' => 'Find floral perfumes under 5000 yen'],
        ];

        $response = $this->provider->generateWithTools($conversation, $tools);

        $this->assertArrayHasKey('type', $response);
        $this->assertContains($response['type'], ['tool_call', 'final']);

        if ($response['type'] === 'tool_call') {
            $this->assertArrayHasKey('tool_call', $response);
            $this->assertArrayHasKey('function', $response['tool_call']);
            $this->assertEquals('search_products', $response['tool_call']['function']['name']);
        }
    }

    /**
     * @group live-api
     * @group gemini
     */
    public function test_gemini_japanese_language_support()
    {
        $response = $this->provider->chat(
            'ロマンチックな人におすすめの香水を教えてください',
            ['budget' => 5000]
        );

        $this->assertArrayHasKey('message', $response);
        $this->assertIsString($response['message']);

        // Verify Japanese text (contains Japanese characters)
        $this->assertMatchesRegularExpression('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $response['message']);
    }
}
```

### 15.7 Laravel Dusk Browser Tests

**File:** `tests/Browser/FragranceDiagnosisTest.php`

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FragranceDiagnosisTest extends DuskTestCase
{
    public function test_complete_quiz_and_get_recommendations()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/fragrance-diagnosis')
                ->assertSee('あなたの普段の印象は？')

                // Question 1: Personality
                ->click('@option-romantic')
                ->waitFor('@question-2')

                // Question 2: Vibe
                ->click('@option-floral')
                ->waitFor('@question-3')

                // Question 3: Occasion
                ->click('@option-daily')
                ->click('@option-date')
                ->waitFor('@question-4')

                // Question 4: Style
                ->click('@option-feminine')
                ->waitFor('@question-5')

                // Question 5: Budget
                ->type('@budget-input', '5000')
                ->waitFor('@question-6')

                // Question 6: Experience
                ->click('@option-beginner')
                ->waitFor('@question-7')

                // Question 7: Season
                ->click('@option-spring-summer')
                ->waitFor('@submit-button')

                // Submit quiz
                ->click('@submit-button')
                ->waitFor('@results-container', 10) // Wait up to 10 seconds for AI

                // Verify results page
                ->assertSee('あなたにおすすめの香水')
                ->assertPresent('@scent-profile-card')
                ->assertPresent('@recommendation-grid')

                // Verify at least 5 recommendations
                ->assertPresent('@product-card-1')
                ->assertPresent('@product-card-5');
        });
    }

    public function test_chat_refinement_from_results()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/fragrance-diagnosis/results')
                ->waitFor('@results-container')

                // Click chat button
                ->click('@chat-button')
                ->waitFor('@chat-container')

                // Type message
                ->type('@chat-input', 'もっと甘い香りはありますか？')
                ->click('@chat-send')
                ->waitFor('@ai-message', 5)

                // Verify AI responded
                ->assertPresent('@ai-message')
                ->assertSeeIn('@ai-message', '甘い');
        });
    }

    public function test_add_to_cart_from_recommendation()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/fragrance-diagnosis/results')
                ->waitFor('@results-container')

                // Click add to cart on first recommendation
                ->click('@add-to-cart-1')
                ->waitFor('@cart-drawer')

                // Verify product added
                ->assertSeeIn('@cart-drawer', '1点')

                // Go to cart
                ->click('@view-cart')
                ->assertPathIs('/cart');
        });
    }
}
```

### 15.8 Tinker Testing Scripts

**File:** `tests/Tinker/verify-products.php`

```php
<?php
/**
 * Production Database Verification Script
 * Run: php artisan tinker < tests/Tinker/verify-products.php
 */

echo "========================================\n";
echo "PRODUCTION DATABASE VERIFICATION\n";
echo "========================================\n\n";

// Verify connection
$dbName = DB::connection()->getDatabaseName();
echo "✓ Connected to database: {$dbName}\n";

// Count products
$productCount = App\Models\Product::count();
echo "✓ Total products: {$productCount}\n";

$activeProducts = App\Models\Product::where('is_active', true)->count();
echo "✓ Active products: {$activeProducts}\n";

$withInventory = App\Models\Product::has('inventory')->count();
echo "✓ Products with inventory: {$withInventory}\n";

$withReviews = App\Models\Product::has('reviews')->count();
echo "✓ Products with reviews: {$withReviews}\n";

// Show sample products
echo "\n========================================\n";
echo "SAMPLE PRODUCTS (First 3)\n";
echo "========================================\n";

$products = App\Models\Product::with(['variants', 'brand', 'category', 'inventory'])
    ->where('is_active', true)
    ->limit(3)
    ->get();

foreach ($products as $i => $product) {
    echo "\n[Product " . ($i + 1) . "]\n";
    echo "- ID: {$product->id}\n";
    echo "- Name: {$product->name}\n";
    echo "- Brand: {$product->brand->name}\n";
    echo "- Category: {$product->category->name}\n";
    echo "- Price: ¥{$product->variants->min('price_yen')} - ¥{$product->variants->max('price_yen')}\n";
    echo "- Stock: {$product->inventory->quantity} units\n";
    echo "- Reviews: {$product->reviews->count()} reviews\n";
    echo "- Avg Rating: " . round($product->reviews->avg('rating'), 2) . "/5\n";
}

echo "\n========================================\n";
echo "CATEGORY BREAKDOWN\n";
echo "========================================\n";

$categories = App\Models\Category::withCount('products')->get();
foreach ($categories as $category) {
    echo "- {$category->name}: {$category->products_count} products\n";
}

echo "\n✓ Verification complete!\n";
echo "Database is ready for AI recommendation testing.\n";
```

**File:** `tests/Tinker/ai-test.php`

```php
<?php
/**
 * AI Provider Testing Script
 * Run: php artisan tinker < tests/Tinker/ai-test.php
 */

echo "========================================\n";
echo "AI PROVIDER TESTING\n";
echo "========================================\n\n";

// Test Gemini
echo "Testing Google Gemini API...\n";
$gemini = new App\Services\AI\Providers\GeminiProvider();

try {
    $response = $gemini->chat(
        'Recommend a perfume for a romantic person who likes floral scents',
        ['budget' => 5000]
    );

    echo "✓ Gemini API is working\n";
    echo "Response:\n" . $response['message'] . "\n\n";
} catch (Exception $e) {
    echo "✗ Gemini API failed: " . $e->getMessage() . "\n\n";
}

// Test fallback model
echo "Testing Gemini fallback model...\n";
try {
    $response = $gemini->chat(
        'Recommend a perfume for a romantic person who likes floral scents',
        ['budget' => 5000],
        useFallback: true
    );

    echo "✓ Gemini fallback model is working\n";
    echo "Response:\n" . $response['message'] . "\n\n";
} catch (Exception $e) {
    echo "✗ Gemini fallback failed: " . $e->getMessage() . "\n\n";
}

// Test full service
echo "========================================\n";
echo "FULL SERVICE TEST\n";
echo "========================================\n\n";

$service = app(App\Services\AI\AIRecommendationService::class);

$context = [
    'personality' => 'romantic',
    'vibe' => 'floral',
    'occasion' => ['daily', 'date'],
    'style' => 'feminine',
    'budget' => 5000,
    'experience' => 'beginner',
    'available_products' => App\Models\Product::limit(10)->get()->toArray(),
];

try {
    $start = microtime(true);
    $result = $service->generateRecommendations($context);
    $duration = round((microtime(true) - $start) * 1000, 2);

    echo "✓ Service working ({$duration}ms)\n";
    echo "Profile: {$result['profile']['name']}\n";
    echo "Recommendations: " . count($result['recommendations']) . " products\n";

    foreach ($result['recommendations'] as $i => $rec) {
        $product = App\Models\Product::find($rec['product_id']);
        echo "- " . ($i + 1) . ". {$product->name} (Match: {$rec['match_score']}%)\n";
    }
} catch (Exception $e) {
    echo "✗ Service failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n========================================\n";
echo "Test complete!\n";
echo "========================================\n";
```

### 15.9 Test Configuration

**File:** `phpunit.xml` (Production Testing Mode)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Browser">
            <directory>tests/Browser</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>

    <php>
        <!-- Production Database Configuration -->
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="mysql"/>
        <env name="DB_DATABASE" value="laravel"/> <!-- Production DB -->
        <env name="DB_TRANSACTION_ROLLBACK" value="true"/>

        <!-- Testing Configuration -->
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>

        <!-- Disable non-essential services -->
        <env name="PULSE_ENABLED" value="false"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>

    <coverage>
        <report>
            <html outputDirectory="coverage-report"/>
            <text outputFile="php://stdout"/>
        </report>
    </coverage>
</phpunit>
```

### 15.10 Test Execution Commands

```bash
# Run all tests (production database, real APIs)
./vendor/bin/pest

# Run specific test suites
./vendor/bin/pest --group=unit              # Fast unit tests
./vendor/bin/pest --group=feature           # Feature tests with production DB
./vendor/bin/pest --group=live-api          # Live API tests (costs quota)
./vendor/bin/pest --group=gemini            # Gemini-specific tests

# Run with coverage
./vendor/bin/pest --coverage --min=80

# Dusk browser tests
php artisan dusk

# Tinker verification
php artisan tinker < tests/Tinker/verify-products.php
php artisan tinker < tests/Tinker/ai-test.php

# Parallel testing (faster)
./vendor/bin/pest --parallel

# Stop on first failure
./vendor/bin/pest --stop-on-failure

# Verbose output
./vendor/bin/pest -v
./vendor/bin/pest -vvv  # Very verbose
```

### 15.11 Test Manifest (Complete List)

**New Test Files (20+ files):**

```
tests/
├── Unit/
│   └── Services/
│       └── AI/
│           ├── ToolRegistryTest.php              [NEW]
│           ├── ContextBuilderTest.php            [NEW]
│           ├── ResponseParserTest.php            [NEW]
│           ├── ScoreCalculatorTest.php           [NEW]
│           └── CacheManagerTest.php              [NEW]
│
├── Feature/
│   └── AI/
│       ├── QuizSubmissionTest.php                [NEW]
│       ├── ChatRefinementTest.php                [NEW]
│       ├── RateLimitingTest.php                  [NEW]
│       ├── ProviderFallbackTest.php              [NEW]
│       ├── CacheInvalidationTest.php             [NEW]
│       └── APIAuthenticationTest.php             [NEW]
│
├── Integration/
│   └── AIProviders/
│       ├── GeminiLiveTest.php                    [NEW]
│       ├── ToolCallingTest.php                   [NEW]
│       ├── ModelFallbackTest.php                 [NEW]
│       └── ErrorHandlingTest.php                 [NEW]
│
├── Browser/
│   ├── FragranceDiagnosisTest.php                [NEW]
│   ├── ChatInteractionTest.php                   [NEW]
│   ├── AddToCartFlowTest.php                     [NEW]
│   └── MobileResponsiveTest.php                  [NEW]
│
├── Tinker/
│   ├── ai-test.php                               [NEW]
│   ├── quiz-debug.php                            [NEW]
│   ├── verify-products.php                       [NEW]
│   ├── provider-check.php                        [NEW]
│   └── load-test.php                             [NEW]
│
└── CreatesApplication.php                        [EXISTING]
```

### 15.12 Test Success Criteria

**MANDATORY PASSING REQUIREMENTS:**

- [ ] **Unit Tests:** 90%+ code coverage, 100% pass rate
- [ ] **Feature Tests:** 100% API endpoint coverage, 100% pass rate
- [ ] **Integration Tests:** All live API calls successful
- [ ] **Dusk Tests:** All critical user flows pass
- [ ] **Production Verification:** Tests confirm real data integrity
- [ ] **No Skipped Tests:** Unless physically impossible
- [ ] **All Code Review Issues:** HIGH, MEDIUM, LOW - all fixed
- [ ] **Performance:** All tests complete within timeout limits

---

## Appendix B: Official Documentation References

1. **ReAct Pattern** - Yao et al., ICLR 2023
    - https://arxiv.org/abs/2210.03629

2. **Google Gemini API**
    - https://ai.google.dev/gemini-api/docs

3. **Google Gemini Function Calling**
    - https://ai.google.dev/gemini-api/docs/function-calling

4. **Laravel HTTP Client**
    - https://laravel.com/docs/12.x/http-client

5. **PestPHP Testing**
    - https://pestphp.com/docs/installation

6. **Laravel Dusk**
    - https://laravel.com/docs/12.x/dusk

---

**Document Version:** 1.3  
**Last Updated:** 2026-02-18

**Document History:**

- v1.0 (2026-02-18): Initial draft
- v1.1 (2026-02-18): Added provider details
- v1.2 (2026-02-18):
    - Added `attributes_json` structure access
    - Added Redis confirmation (working in Sail)
    - Added database seeding prerequisite
    - Added API key setup instructions
    - Fixed PHP version: 8.3.6 → 8.4.12
    - Fixed inventory column: `quantity` → `stock`
    - Added Laravel AI SDK compatibility note (now compatible with PHP 8.4)
- v1.2.1 (2026-02-18): **CRITICAL FIXES**
    - Fixed Inventory FK: `product_id` → `product_variant_id` (table structure)
    - Fixed Product notes access: `$p->top_notes` → `$p->attributes_json['notes']`
    - Fixed Reviews column: `is_approved` → `approved` (actual column name)
    - Updated checkInventory to join via ProductVariant
    - Added detailed comments explaining actual database structure
- v1.3 (2026-02-18): **PROVIDER UPDATE**
    - Removed Groq provider (not working)
    - Updated to Gemini-only strategy
    - Primary: gemini-2.5-flash-lite (15 RPM, 1,000 RPD)
    - Fallback: gemini-2.5-flash (10 RPM, 250 RPD)
    - Updated GeminiProvider to support both primary and fallback models
      **Status:** Production-Ready  
       **Total New Files:** 27 files  
       **Total Test Files:** 20+ files  
       **Total Lines of Code:** ~2,500 lines  
       **Total Lines of Tests:** ~1,500 lines  
       **Estimated Implementation Time:** 3-4 weeks  
       **Test Coverage Target:** 90%+ code coverage

**END OF ARCHITECTURE DOCUMENT**
