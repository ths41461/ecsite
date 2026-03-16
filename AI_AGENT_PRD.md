# AI Fragrance Recommendation Agent - Product Requirements Document (PRD)

**Version:** 1.3  
**Date:** February 18, 2026  
**Status:** Production-Ready  
**Author:** AI Research Team

---

## 1. Executive Summary

### 1.1 Vision

Build an intelligent AI-powered fragrance recommendation system that helps young Japanese women (19-25) discover their perfect perfume through a personalized diagnostic quiz and conversational refinement, leveraging 100% free AI APIs and the existing e-commerce database.

### 1.2 Scope & Existing Assets

**This PRD covers the enhancement of the existing 香り診断 (Fragrance Diagnosis) feature:**

**Current Implementation:**

- **Page:** `/fragrance-diagnosis` (exists at `resources/js/pages/FragranceDiagnosis.tsx`)
- **Route:** Already defined in `routes/web.php` as `fragrance.diagnosis`
- **Navigation:** "香り診断" appears in Hero, Footer, and homeNavigation
- **Current State:** Basic 5-question quiz with text buttons → ends with alert popup (non-functional)

**What This PRD Delivers:**

- ✅ Complete reset of the quiz with 7 visual questions
- ✅ AI-powered recommendation engine integration
- ✅ New results page with product recommendations
- ✅ Chat refinement interface
- ✅ Real database integration (products, reviews, inventory)
- ✅ 100% production-ready implementation

**Files to be Modified/Created:**

1. `resources/js/pages/FragranceDiagnosis.tsx` - Enhanced quiz (reset)
2. `resources/js/pages/FragranceDiagnosisResults.tsx` - New results page
3. `resources/js/components/AIChat/` - New chat components
4. `routes/web.php` - Add results route
5. Backend API controllers and services (new)

### 1.3 Target Persona

**Primary Persona: 中村 彩花 (Nakamura Ayaka)**

- **Age:** 19 years old
- **Occupation:** Female college freshman (1st year)
- **Location:** Osaka Prefecture, Japan
- **Interests:** Instagram posts, K-POP, shopping
- **Tech Savvy:** High - uses SNS daily, trend-conscious

**Pain Points:**

- Wants to try fragrances but afraid of expensive mistakes
- Doesn't understand fragrance notes and terminology
- Physical stores feel intimidating for beginners
- Limited budget (student-friendly prices)

**Needs:**

- Cute, Instagram-worthy perfume designs
- Try-before-buy options (discovery sets)
- Personalized recommendations without store visits
- Trending fragrance discovery
- Price filtering for student budgets

**Quote:**  
_「トレンド✖️わたしらしさ。香水初心者でも自分にピッタリが見つかる。」_  
(Trend × My Style. Even perfume beginners can find their perfect match.)

### 1.4 Success Metrics

| Metric                                     | Target |
| ------------------------------------------ | ------ |
| Quiz Completion Rate                       | >70%   |
| Recommendation Conversion Rate             | >15%   |
| Chat Engagement Rate                       | >40%   |
| Average Order Value (vs. regular browsing) | +20%   |
| User Return Rate (within 30 days)          | >25%   |
| SNS Profile Sharing Rate                   | >10%   |

---

## 2. User Stories

### 2.1 Primary User Flows

#### Story 1: First-Time Discovery

**As a** perfume beginner browsing during lunch break  
**I want to** complete a quick visual quiz about my preferences  
**So that** I can get personalized perfume recommendations without reading product descriptions

**Acceptance Criteria:**

- [ ] Quiz takes less than 2 minutes to complete
- [ ] Questions use visual cards (not just text)
- [ ] I receive at least 5 recommendations immediately
- [ ] Each recommendation explains why it matches me

#### Story 2: Refinement Through Chat

**As a** user who received quiz results but wants more options  
**I want to** chat with an AI consultant to refine my preferences  
**So that** I can explore alternatives and understand fragrance notes better

**Acceptance Criteria:**

- [ ] Chat interface is accessible from results page
- [ ] AI responds within 3 seconds
- [ ] I can ask questions in natural Japanese
- [ ] AI suggests products based on my feedback
- [ ] Product cards appear in chat with add-to-cart button

#### Story 3: Budget-Conscious Shopping

**As a** student with limited budget (under ¥5,000)  
**I want to** filter recommendations by price range  
**So that** I only see perfumes I can actually afford

**Acceptance Criteria:**

- [ ] Price filter available on results page
- [ ] Filter options: Under ¥3,000 / ¥3,000-5,000 / ¥5,000-8,000 / ¥8,000+
- [ ] Filter updates results instantly
- [ ] Out-of-stock items excluded by default

#### Story 4: Social Sharing

**As a** trend-conscious user who loves Instagram  
**I want to** share my "Scent Profile" on social media  
**So that** I can show friends my personalized fragrance personality

**Acceptance Criteria:**

- [ ] Visual "Scent Profile Card" generated after quiz
- [ ] Card is Instagram-ready (1080x1080px)
- [ ] Share buttons for Instagram, Twitter, LINE
- [ ] Card includes my profile type and top notes

#### Story 5: Stock-Aware Shopping

**As a** user ready to purchase immediately  
**I want to** see only in-stock items  
**So that** I don't get disappointed by unavailable recommendations

**Acceptance Criteria:**

- [ ] Real-time inventory check on all recommendations
- [ ] "In Stock" / "Low Stock" / "Out of Stock" badges
- [ ] "Notify me when back in stock" option for out-of-stock items

---

## 3. Features & Requirements

### 3.1 Core Features

#### Feature 1: Smart Fragrance Quiz (Quick Start)

**Overview:**  
A 5-7 question visual quiz that captures user's personality, preferences, and needs in under 2 minutes.

**Existing Implementation Note:**  
This feature will replace/enhance the existing **香り診断** page at `/fragrance-diagnosis`:

- **Current State:** Basic 5-question quiz with text buttons, no AI integration, alert popup for results
- **New Implementation:** Full AI-powered quiz with visual cards, real recommendations, chat refinement
- **Route:** Keep `/fragrance-diagnosis` (already exists in routes/web.php)
- **Navigation:** Keep "香り診断" link in homeNavigation, Hero, Footer
- **Page File:** Update `resources/js/pages/FragranceDiagnosis.tsx`

**Reset Requirements:**

- ✅ Remove existing static quiz logic
- ✅ Replace with new 7-question visual quiz (as specified below)
- ✅ Add real backend API integration
- ✅ Add AI-powered results page
- ✅ Add chat refinement interface
- ✅ Keep consistent styling with existing site (borders, colors, typography)

**Questions (Replacing Existing 5 Questions):**

**Current → New Mapping:**

- Q1 (印象) → Enhanced Q1 (Personality Type with visuals)
- Q2 (香りタイプ) → Enhanced Q2 (Preferred Vibe with mood boards)
- Q3 (シーン) → Enhanced Q3 (Occasion with visuals)
- Q4 (香りの強さ) → **REMOVED** (not in PRD)
- Q5 (重視要素) → **REMOVED** (replaced by separate questions)
- **NEW Q4** (Style Preference)
- **NEW Q5** (Budget Range)
- **NEW Q6** (Experience Level)
- **NEW Q7** (Seasonal Preference)

1. **Personality Type** (Visual Cards - Single Select)
    - Romantic / Dreamy (ロマンチック)
    - Energetic / Active (元気いっぱい)
    - Cool / Confident (クール)
    - Natural / Calm (ナチュラル)

2. **Preferred Vibe** (Mood Board - Single Select)
    - Floral Garden (フラワーガーデン) - Pink palette
    - Fresh Citrus (フレッシュシトラス) - Yellow palette
    - Sweet Vanilla (スイートバニラ) - Cream palette
    - Woody Forest (ウッディフォレスト) - Brown palette
    - Ocean Breeze (オーシャンブリーズ) - Blue palette

3. **Occasion** (Multi-Select with Visuals)
    - Daily use (デイリー使い)
    - Date nights (デート)
    - Special events (特別な日)
    - School/Work (学校・お仕事)
    - Casual outings (カジュアルなお出かけ)

4. **Style Preference** (Visual Cards - Single Select)
    - Feminine & Elegant (フェミニン・エレガント)
    - Casual & Cute (カジュアル・キュート)
    - Chic & Modern (シック・モダン)
    - Natural & Simple (ナチュラル・シンプル)

5. **Budget Range** (Slider with Preset Buttons)
    - Under ¥3,000 (学生向け)
    - ¥3,000-5,000
    - ¥5,000-8,000
    - ¥8,000+ (高級ライン)

6. **Experience Level** (Single Select)
    - Complete beginner (初心者)
    - Tried a few (いくつか試したことがある)
    - Know what I like (好みが分かってきた)

7. **Seasonal Preference** (Optional - Single Select)
    - Spring/Summer (春夏向け)
    - Fall/Winter (秋冬向け)
    - All year round (オールシーズン)

**Technical Requirements:**

- **Existing Progress Bar:** Keep current progress bar component (lines 68-79 in FragranceDiagnosis.tsx) but enhance styling
- **Existing Navigation:** Keep current "戻る/次へ" button pattern but update styling to match visual cards
- **State Management:** Replace `useState` with proper form state, add API integration
- **Animation:** Add Framer Motion for smooth transitions between questions
- **Progress Bar:** Keep showing completion percentage (current: 5 questions, new: 7 questions)
- **Skip Option:** Add skip button for optional questions
- **Back Button:** Keep existing back functionality but enhance UX
- **Auto-save:** Save progress to localStorage (quiz*answers*{session_id})
- **Mobile-first:** Already responsive, maintain breakpoints

**Design System Consistency:**

```css
/* Keep existing design tokens from FragranceDiagnosis.tsx */
- Background: bg-white (white background)
- Container: max-w-4xl mx-auto px-4 py-8
- Headings: text-[#0D0D0D] (black text)
- Body text: text-[#444444] (dark gray)
- Borders: border-[#888888] (gray borders)
- Accent: bg-[#0D0D0D] text-white (black/white buttons)
- Font: Default Tailwind sans-serif (consistent with existing site)

/* Enhance with */
- Visual cards with images
- Smooth animations
- Improved hover states
- Better mobile touch targets
```

**Implementation Details:**

```typescript
// Current (to be removed):
const [step, setStep] = useState(1);
const [answers, setAnswers] = useState<Record<string, string>>({});

// New Implementation:
const [step, setStep] = useState(1);
const [answers, setAnswers] = useState<QuizAnswers>({
    personality: '',
    vibe: '',
    occasion: [],
    style: '',
    budget: 0,
    experience: '',
    season: '',
});
const [isSubmitting, setIsSubmitting] = useState(false);
```

**Success Criteria:**

- Average completion time < 2 minutes
- Completion rate > 70%
- Mobile completion rate > 60%

**Post-Quiz Flow (Replacing Current Alert):**

**Current:** Line 128 `alert('診断完了！あなたに合う香りを提案します。')`

**New Implementation:**

1. Submit answers to API: `POST /api/ai/quiz/submit`
2. Show loading state while AI generates recommendations
3. Navigate to results page: `/fragrance-diagnosis/results`
4. Display:
    - Personalized Scent Profile card (shareable)
    - 5-7 AI-recommended products from your catalog
    - Filter options (price, stock, brand)
    - "Chat for More Options" button

**API Integration:**

```typescript
// On "結果を見る" button click:
const submitQuiz = async () => {
    setIsSubmitting(true);

    const response = await fetch('/api/ai/quiz', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(answers),
    });

    const data = await response.json();

    // Navigate to results with data
    router.visit('/fragrance-diagnosis/results', {
        data: {
            profile: data.profile,
            recommendations: data.recommendations,
            sessionId: data.session_id,
        },
    });
};
```

---

#### Feature 2: AI Chat Refinement

**Overview:**  
Conversational interface to refine recommendations based on quiz results, answer questions, and provide personalized guidance.

**Capabilities:**

- Answer questions about recommended products
- Suggest alternatives based on user feedback ("sweeter", "more floral", "longer lasting")
- Explain fragrance notes in simple, beginner-friendly terms
- Compare products side-by-side
- Adjust recommendations based on new criteria
- Provide "Why this matches you" explanations

**Example Conversations:**

**Example 1 - Style Refinement:**

```
User: "I like the first one but is there something sweeter?"
AI: "Of course! Based on your romantic personality, here are 3 similar perfumes with sweeter notes.

🌸 [Product Card: Chanel Chance Eau Tendre]
   Notes: Grapefruit, Quince, Jasmine
   Match: 95%

🌸 [Product Card: Dior Miss Dior Blooming Bouquet]
   Notes: Sicilian Mandarin, Peony, White Musk
   Match: 93%

Would you like to see more options or learn about any of these?"
```

**Example 2 - Educational:**

```
User: "What's the difference between EDP and EDT?"
AI: "Great question! Here's the simple breakdown:

🌟 EDP (Eau de Parfum):
   - Fragrance concentration: 15-20%
   - Lasts: 4-6 hours
   - Best for: Evening, special occasions
   - Price: Usually higher

💧 EDT (Eau de Toilette):
   - Fragrance concentration: 5-15%
   - Lasts: 2-4 hours
   - Best for: Daily use, work, casual
   - Price: More affordable

For a college student like you, EDT is often perfect for daily use, while EDP is great for dates or special events!"
```

**Integration with Results Page:**

**New Component:** Add chat interface to `/fragrance-diagnosis/results` page

- **Desktop:** Sidebar chat panel (right side)
- **Mobile:** Bottom sheet modal (swipe up)
- **Toggle Button:** Floating "AIと相談" button on results page

**Technical Requirements:**

- WhatsApp/Line-style chat bubbles
- Typing indicator ("AI is thinking...")
- Product cards with image, price, and key notes
- Quick reply buttons for common questions
- Message history persistence (for logged-in users)
- Auto-scroll to latest message
- "Add to Cart" buttons in product cards
- Session ID from quiz passed to chat component

\*\*Success Criteria:

- Average response time < 3 seconds
- Chat engagement rate > 40% (users who chat after quiz)
- Average chat session length > 3 messages

---

#### Feature 3: Comprehensive Recommendation Engine

**Overview:**  
AI agent that leverages ALL database information to generate intelligent, personalized recommendations.

**Data Sources to Utilize:**

1. **Product Catalog** (Primary)
    - Product names, descriptions, attributes
    - Fragrance notes (top, middle, base)
    - Brands, categories, variants
    - Prices, sizes
    - Product images

2. **Inventory Data** (Real-time)
    - Current stock levels
    - Safety stock thresholds
    - Variant availability
    - "Only X left" indicators

3. **Review Data** (Sentiment Analysis)
    - Average ratings per product
    - Review sentiment (positive/negative keywords)
    - "Users like you loved..." insights
    - Popular products among similar demographics

4. **User Behavior** (Analytics)
    - Most viewed products
    - Wishlist additions
    - Purchase patterns
    - Event tracking (views, add-to-carts, purchases)

5. **Ranking Data** (Trending)
    - Overall trending products
    - Category-specific rankings
    - Seasonal popularity
    - New arrivals

**Recommendation Algorithm:**

```
Match Score =
  (Quiz Match × 0.30) +
  (Review Rating × 0.20) +
  (Stock Availability × 0.15) +
  (Price Match × 0.15) +
  (Trending Score × 0.10) +
  (Similar User Preference × 0.10)
```

**Output Format:**

- Top 5-7 recommended products
- Match score (0-100%) for each
- Personalized explanation
- Alternative options

**Success Criteria:**

- Recommendation generation time < 2 seconds
- User satisfaction rating > 4.0/5.0
- Click-through rate on recommendations > 25%

---

#### Feature 4: Personalized Scent Profile

**Overview:**  
Create a unique "fragrance personality" for each user based on quiz answers, displayed as a shareable visual card.

**Profile Elements:**

1. **Profile Name** (Auto-generated based on answers)
    - Examples:
        - "フレッシュ&ガーリー" (Fresh & Girly)
        - "シック&モダン" (Chic & Modern)
        - "ナチュラル&ロマンチック" (Natural & Romantic)
        - "クール&エレガント" (Cool & Elegant)

2. **Character Description** (2-3 sentences)
    - Example: "明るく元気なあなたにぴったりのフレッシュな香り。フルーティーで軽やかなノートが、あなたの魅力を引き立てます。"

3. **Recommended Note Families** (Visual Chart)
    - Top Notes: Citrus, Floral, Fruity
    - Middle Notes: Floral, Spicy, Green
    - Base Notes: Woody, Musk, Vanilla

4. **Perfect Occasions** (Icons)
    - Daily, Date, Special Events, Work

5. **Seasonal Recommendations**
    - Spring/Summer or Fall/Winter suggestions

**Visual Design:**

- Instagram-ready card (1080x1080px)
- Color scheme based on profile type
- Profile illustration/avatar
- Share buttons (Instagram, Twitter, LINE)

**Technical Implementation:**

Server-side image generation using Intervention Image:

```bash
composer require intervention/image
```

```php
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ScentProfileCardGenerator
{
    public function generate(array $profile): string
    {
        $manager = new ImageManager(new Driver());

        $image = $manager->create(1080, 1080)
            ->fill($profile['color'] ?? '#f5f5f5');

        // Add profile type text
        $image->text($profile['name'], 540, 200, function($font) {
            $font->size(48);
            $font->color('#0d0d0d');
            $font->align('center');
        });

        // Add description
        $image->text($profile['description'], 540, 400, function($font) {
            $font->size(24);
            $font->color('#444444');
            $font->align('center');
        });

        // Add note families visualization
        // Add occasion icons
        // Add brand logo

        return $image->toPng()->toString();
    }
}
```

**Free Visual Asset Sources:**

| Asset Type         | Source                                                    | License              |
| ------------------ | --------------------------------------------------------- | -------------------- |
| Perfume bottles    | [Unsplash Perfume](https://unsplash.com/s/photos/perfume) | Free for commercial  |
| Floral backgrounds | [Pexels Flowers](https://www.pexels.com/search/flower/)   | Free for commercial  |
| Illustrations      | [unDraw](https://undraw.co/)                              | MIT (no attribution) |
| Icons              | [Lucide](https://lucide.dev/)                             | ISC License          |

**Success Criteria:**

- SNS sharing rate > 10%
- Users report "This is so me!" sentiment

---

#### Feature 5: Smart Filtering

**Overview:**  
Dynamic filters that work with AI recommendations to help users narrow down options.

**Filter Options:**

1. **Price Range** (Preset Buttons)
    - Under ¥3,000
    - ¥3,000-5,000
    - ¥5,000-8,000
    - ¥8,000+

2. **In Stock Only** (Toggle)
    - ON: Show only in-stock items
    - OFF: Show all (with availability status)

3. **Brand Preferences** (Multi-select)
    - Popular brands from database
    - Searchable brand list

4. **Size Options** (Multi-select)
    - 10ml / 30ml / 50ml / 100ml+

5. **Note Preferences** (Visual Tags)
    - Floral (花の香り)
    - Woody (ウッディ)
    - Fresh (フレッシュ)
    - Oriental (オリエンタル)
    - Citrus (シトラス)
    - Fruity (フルーティー)

**Technical Requirements:**

- Instant filter updates (no page reload)
- URL sync for shareable filtered views
- Filter persistence across sessions

---

### 3.2 Technical Requirements

#### Performance Requirements

| Metric                    | Target  | Maximum |
| ------------------------- | ------- | ------- |
| Quiz Load Time            | < 1s    | 2s      |
| Quiz Completion Time      | < 2 min | 3 min   |
| AI Response Time          | < 3s    | 5s      |
| Recommendation Generation | < 2s    | 4s      |
| Page Load (Results)       | < 2s    | 3s      |
| Concurrent Users          | 100+    | 500     |

#### Integration Requirements

**Must Integrate With Existing Systems:**

- ✅ Authentication (Laravel Auth)
- ✅ Product Catalog (Product model)
- ✅ Inventory Management (Inventory model)
- ✅ Shopping Cart (CartService)
- ✅ Reviews System (Review model)
- ✅ Payment System (Stripe)
- ✅ Analytics (Event tracking)
- ✅ Admin Panel (Filament)

**API Requirements:**

- RESTful API endpoints
- Rate limiting: 20 requests/minute per user
- Caching: 80% hit rate target
- JSON responses with consistent schema

#### Mobile-First Design

**Breakpoints:**

- Mobile: 320px - 768px (Primary)
- Tablet: 769px - 1024px
- Desktop: 1025px+

**Mobile-Specific Features:**

- Touch-friendly quiz interface
- Bottom sheet for chat (mobile)
- Horizontal scroll for product cards
- Pull-to-refresh on results page
- Native share API integration

---

## 4. AI Agent Architecture

### 4.1 AI Provider Strategy (100% FREE)

> **IMPORTANT:** Sign up for API key BEFORE development:
>
> - **Google AI Studio:** https://ai.google.dev (FREE, no credit card required)

**Primary Provider:** Google Gemini 2.5 Flash-Lite

- **Why:** Highest free tier daily quota (1,000 RPD), excellent Japanese support, fast responses
- **Free Tier Limits:** 15 RPM, 1,000 requests/day, 250K TPM
- **Model:** `gemini-2.5-flash-lite`
- **Endpoint:** `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent`
- **Use for:** Main recommendation generation, chat responses

**Fallback Provider:** Google Gemini 2.5 Flash

- **Why:** More capable model for complex queries, still generous free tier
- **Free Tier Limits:** 10 RPM, 250 requests/day, 250K TPM
- **Model:** `gemini-2.5-flash`
- **Endpoint:** `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent`
- **Use for:** When primary model hits rate limits, complex reasoning tasks

**Total Free Capacity:** ~1,250 requests/day (primary + fallback)
**Effective Capacity (with caching):** ~8,000 requests/day

### 4.1.1 Provider Selection Logic

```
Request → Try Gemini 2.5 Flash-Lite (Primary)
            ↓ (429 error)
          Try Gemini 2.5 Flash (Fallback)
            ↓ (all fail)
          Return cached response or graceful error
```

### 4.1.2 API Key Configuration

Add to `.env`:

```
GEMINI_API_KEY=AIzaxxxxx  # Get from ai.google.dev
```

Add to `config/services.php`:

```php
'gemini' => [
    'api_key' => env('GEMINI_API_KEY'),
    'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
    'primary_model' => 'gemini-2.5-flash-lite',
    'fallback_model' => 'gemini-2.5-flash',
],
```

### 4.2 Agent Pattern: ReAct (Reason + Act)

**Architecture:**

```
Input (Quiz Answers + Context)
    ↓
Think (Analyze preferences, determine strategy)
    ↓
Act (Query database, retrieve products)
    ↓
Observe (Process results, evaluate matches)
    ↓
Think (Refine selections based on scoring)
    ↓
Output (Final recommendations with explanations)
```

**Tools Available to Agent:**

1. `search_products()` - Query product catalog
2. `check_inventory()` - Verify stock levels
3. `analyze_reviews()` - Get sentiment data
4. `get_trending()` - Retrieve popular items
5. `filter_by_price()` - Apply budget constraints

### 4.3 Memory Architecture

**Short-Term Memory (Session):**

- Current quiz answers
- Active chat history
- Current recommendations
- User preferences (this session)

**Long-Term Memory (Database):**

- User scent profile
- Past quiz results
- Chat history (logged-in users)
- Purchase history
- Favorite notes/styles

**Storage:**

- Session: Redis/Cache (TTL: 24 hours)
- Long-term: PostgreSQL via Eloquent

---

## 5. Data Requirements

### 5.1 New Database Tables

**ai_chat_sessions**

```sql
- id: bigint (PK)
- user_id: bigint (FK, nullable)
- session_token: varchar(64), unique
- quiz_result_id: bigint (FK, nullable)
- context_json: json
- created_at: timestamp
- updated_at: timestamp
```

**ai_messages**

```sql
- id: bigint (PK)
- session_id: bigint (FK)
- role: enum('user', 'assistant', 'system')
- content: text
- metadata_json: json (nullable)
- created_at: timestamp
```

**quiz_results**

```sql
- id: bigint (PK)
- user_id: bigint (FK, nullable)
- session_token: varchar(64)
- answers_json: json
- profile_type: varchar(50)
- profile_data_json: json
- recommended_product_ids: json
- created_at: timestamp
```

**user_scent_profiles**

```sql
- id: bigint (PK)
- user_id: bigint (FK, unique)
- profile_type: varchar(50)
- profile_data_json: json
- preferences_json: json
- created_at: timestamp
- updated_at: timestamp
```

**ai_recommendation_cache**

```sql
- id: bigint (PK)
- cache_key: varchar(255), unique
- context_hash: varchar(64)
- product_ids_json: json
- explanation: text (nullable)
- expires_at: timestamp
- created_at: timestamp
```

### 5.2 Integration with Existing Tables

**Must Query:**

- `products` - Catalog data
- `product_variants` - Size/price options
- `inventories` - Stock levels (column: `stock`, `safety_stock`, `managed`)
- `reviews` - Ratings and feedback (column: `rating`, `body`, `approved`)
- `categories` - Product categorization
- `brands` - Brand information
- `orders` / `order_items` - Purchase history
- `events` - User behavior analytics
- `ranking_snapshots` - Trending data

### 5.3 Existing Product Data Structure

**CRITICAL:** Database must be seeded before development!

```bash
php artisan db:seed
```

**Products Table `attributes_json` Structure:**

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

**Product Variants `option_json` Structure:**

```json
{
  "size_ml": 50,
  "gender": "women",
  "concentration": "EDP" | "EDT" | "EDC" | "Parfum" | "Mist" | "Oil"
}
```

**Available Product Data (from seeders):**

- ~120+ products across 20+ brands
- Brands: SHIRO, COSME DECORTE, Issey Miyake, Shiseido, Chanel, Dior, Tom Ford, etc.
- Categories: Floral EDP, Fresh EDT, Woody EDP, Oriental EDP, Citrus EDT, etc.
- Price range: ¥1,100 - ¥58,850

---

## 6. User Interface Requirements

### 6.1 Quiz Interface

**Design:**

- Full-screen or modal overlay
- Progress indicator (step X of Y)
- Large visual cards for options
- Smooth transitions (slide/fade)
- Skip button (optional)
- Back button
- "Start Over" option

**Visual Elements:**

- Hero image/illustration per question
- Color-coded mood boards
- Animated transitions
- Loading state between questions

### 6.2 Results Page

**Layout:**

```
┌─────────────────────────────────────┐
│  Scent Profile Card (Visual)        │
│  [Share Button]                     │
├─────────────────────────────────────┤
│  Filter Bar                         │
│  [Price] [Stock] [Brand] [Notes]    │
├─────────────────────────────────────┤
│  Recommendations (5-7 products)     │
│  [Horizontal scroll on mobile]      │
│  ┌────────┐ ┌────────┐ ┌────────┐  │
│  │Product │ │Product │ │Product │  │
│  │Image   │ │Image   │ │Image   │  │
│  │Match%  │ │Match%  │ │Match%  │  │
│  │Price   │ │Price   │ │Price   │  │
│  │[Add]   │ │[Add]   │ │[Add]   │  │
│  └────────┘ └────────┘ └────────┘  │
├─────────────────────────────────────┤
│  "Chat for More Options" Button     │
└─────────────────────────────────────┘
```

### 6.3 Chat Interface

**Design:**

- WhatsApp/Line-style bubbles
- AI messages on left (white/gray)
- User messages on right (brand color)
- Product cards embedded in chat
- Quick reply buttons above input
- Typing indicator
- "Clear Chat" option
- Expandable on desktop (sidebar)

---

## 7. Security & Privacy Requirements

### 7.1 Security

**Must Implement:**

- [ ] Input sanitization on all user inputs
- [ ] Rate limiting on AI endpoints (20 req/min)
- [ ] API key protection (never exposed to frontend)
- [ ] HTTPS only
- [ ] CORS configuration
- [ ] SQL injection prevention (use Eloquent)
- [ ] XSS protection

### 7.2 Privacy

**Requirements:**

- [ ] No sensitive user data in AI prompts (use anonymized IDs)
- [ ] Explicit consent for data usage (GDPR/CCPA ready)
- [ ] Option to delete chat history
- [ ] Data retention policy (90 days for anonymous, indefinite for logged-in)
- [ ] No PII in logs

### 7.3 Guardrails

**AI Safety:**

- [ ] Output validation (JSON schema validation)
- [ ] Max token limits on responses
- [ ] Content filtering (inappropriate content)
- [ ] Tool permission restrictions (read-only where possible)
- [ ] Circuit breaker pattern (fail gracefully)

---

## 8. Non-Functional Requirements

### 8.1 Accessibility

**WCAG 2.1 AA Compliance:**

- [ ] Keyboard navigation support
- [ ] Screen reader compatibility
- [ ] Alt text for all images
- [ ] Color contrast ratio > 4.5:1
- [ ] Focus indicators
- [ ] ARIA labels

### 8.2 Localization

**Full Japanese Language Support:**

- [ ] All UI text in Japanese
- [ ] Right-to-left not needed
- [ ] Cultural appropriateness review
- [ ] Local date/number formats
- [ ] Japanese yen currency

### 8.3 Browser Support

**Target Browsers:**

- Chrome 90+ (Primary)
- Safari 14+ (iOS critical)
- Firefox 88+
- Edge 90+

---

## 9. Future Enhancements (Phase 2+)

### 9.1 Advanced Features

- [ ] Image-based fragrance matching (upload outfit photo)
- [ ] Voice-based chat interaction
- [ ] AR try-on integration
- [ ] Subscription box recommendations
- [ ] Gift recommendation mode (for others)
- [ ] Seasonal trend predictions
- [ ] Personalized email campaigns

### 9.2 AI Improvements

- [ ] Review sentiment analysis
- [ ] Predictive stock management
- [ ] Dynamic pricing suggestions
- [ ] A/B testing framework for recommendations

---

## 10. Resolved Questions

| Question                   | Resolution                                                                                                    |
| -------------------------- | ------------------------------------------------------------------------------------------------------------- |
| **Quiz Question Count**    | ✅ 7 questions is the right balance - provides enough data for personalization without overwhelming users     |
| **Chat Persistence**       | ✅ **Anonymous users: NO chat history saved** (session only). Logged-in users: chat history saved to database |
| **Profile Sharing**        | ✅ No legal concerns - users opt-in to share their own personality profile                                    |
| **Fallback Strategy**      | ✅ 2-tier fallback: Gemini Flash-Lite → Gemini Flash → Cached response → Graceful error                       |
| **A/B Testing**            | ✅ Use feature flags in `meta_json` column, track via `events` table                                          |
| **Social Card Generation** | ✅ Server-side using Intervention Image (install: `composer require intervention/image`)                      |
| **Quiz Visual Assets**     | ✅ Free sources: Unsplash, Pexels, unDraw (MIT/CC0 licensed)                                                  |

### 10.1 Remaining Questions for Phase 2

1. Should we add voice input for chat?
2. Should we support multiple languages (English)?
3. Should we add "gift mode" for recommending perfumes for others?

---

## 11. Appendix

### A. Prompt Templates

**System Prompt (Recommendation Generation):**

```
You are an expert fragrance consultant specializing in helping young Japanese women (ages 19-25) find their perfect perfume. You have access to the user's quiz results and a database of fragrance products.

USER PROFILE:
- Personality: {{personality}}
- Preferred Vibe: {{vibe}}
- Occasion: {{occasion}}
- Style: {{style}}
- Budget: Under ¥{{budget}}
- Experience Level: {{experience}}

AVAILABLE PRODUCTS (In Stock, Under Budget):
{{products_json}}

TRENDING PRODUCTS:
{{trending_json}}

TOP REVIEWED:
{{top_rated_json}}

Based on this information, recommend 5-7 specific perfumes. For each recommendation:
1. Provide product ID
2. Calculate match score (0-100)
3. Write personalized explanation (2-3 sentences in Japanese)
4. Suggest best occasions

Return ONLY valid JSON in this exact format:
{
  "recommendations": [
    {
      "product_id": number,
      "match_score": number,
      "explanation": "string in Japanese",
      "best_occasions": ["string"]
    }
  ],
  "profile": {
    "type": "string",
    "name": "string in Japanese",
    "description": "string in Japanese"
  }
}
```

**System Prompt (Chat):**

```
You are a helpful, friendly fragrance consultant chatting with a young Japanese woman about perfumes. Be encouraging, use casual but polite Japanese, and explain fragrance concepts in simple terms.

CONTEXT:
- User's Scent Profile: {{profile_name}}
- Current Recommendations: {{current_products}}
- Budget: Under ¥{{budget}}

CONVERSATION HISTORY:
{{chat_history}}

Be helpful, answer questions clearly, and suggest products from the catalog when appropriate. If you mention specific products, include their IDs so we can display product cards.
```

### B. Routes & Pages

**Existing (Keep):**

- `GET /fragrance-diagnosis` → FragranceDiagnosis.tsx (quiz page - enhanced)

**New (Add):**

- `GET /fragrance-diagnosis/results` → FragranceDiagnosisResults.tsx (results page)
- Component: ChatModal.tsx (chat interface for results page)

**Route Configuration (routes/web.php):**

```php
// Update existing route to use enhanced component
Route::get('/fragrance-diagnosis', function () {
    return Inertia::render('FragranceDiagnosis');
})->name('fragrance.diagnosis');

// Add new results route
Route::get('/fragrance-diagnosis/results', function () {
    return Inertia::render('FragranceDiagnosisResults');
})->name('fragrance.diagnosis.results');
```

### C. API Endpoints

**POST /api/ai/quiz/submit**

- Submit quiz answers
- Returns: recommendations + profile + session_id

**POST /api/ai/chat/message**

- Send chat message
- Body: { session_id, message }
- Returns: { message, products }

**GET /api/ai/recommendations**

- Get recommendations with filters
- Query: { price_range, in_stock, brands, notes }
- Returns: filtered recommendations

**POST /api/ai/profile/share**

- Generate shareable profile card
- Returns: { image_url, share_text }

---

## 11. Testing Requirements (100% Real Data)

### 11.0 Prerequisites (MUST DO FIRST)

**Before ANY development:**

```bash
# 1. Seed the database with products
php artisan db:seed

# 2. Verify products exist
php artisan tinker --execute="echo 'Products: ' . App\Models\Product::count();"

# 3. Get API keys
# Gemini: https://ai.google.dev (sign up, create API key)

# 4. Add keys to .env
echo "GEMINI_API_KEY=your_key_here" >> .env
```

### 11.1 Testing Philosophy

**MANDATORY REQUIREMENTS:**

- ✅ **NO mocks, NO fake data, NO stubs** - All tests use real implementations
- ✅ **100% production database** - Tests run against real products, real inventory, real reviews
- ✅ **Real AI APIs** - Tests call actual Google Gemini and Groq APIs (uses free tier quotas)
- ✅ **All tests MUST PASS** before deployment
- ✅ **Read-only production tests** - Never modify production data

### 11.2 Test Categories

#### **A. Unit Tests (PestPHP)**

**Scope:** Individual service classes, no external dependencies
**Database:** SQLite (isolated, fast)
**Coverage Target:** 90%

**Test Files:**

- `ToolRegistryTest.php` - Tool execution logic
- `ContextBuilderTest.php` - Data preparation
- `ResponseParserTest.php` - AI response parsing
- `ScoreCalculatorTest.php` - Recommendation scoring

**Example:**

```php
it('parses Gemini API response correctly', function () {
    $parser = new ResponseParser();
    $rawResponse = getRealGeminiResponse(); // Real API call
    $result = $parser->parse($rawResponse);
    expect($result)->toHaveKey('recommendations');
});
```

#### **B. Feature Tests (Production Database)**

**Scope:** API endpoints with real database
**Database:** Production MySQL (read-only transactions)
**Coverage Target:** 100% of API endpoints

**Test Files:**

- `QuizSubmissionTest.php` - Full quiz → recommendations flow
- `ChatRefinementTest.php` - Chat interface functionality
- `RateLimitingTest.php` - API rate limiting
- `ProviderFallbackTest.php` - Gemini → Groq fallback

**Critical Requirement:**

```php
beforeEach(function () {
    // Verify production database connection
    $dbName = DB::connection()->getDatabaseName();
    expect($dbName)->toBe('laravel');

    // Verify real products exist
    expect(Product::count())->toBeGreaterThan(100);
});

it('uses real production products', function () {
    $product = Product::with('inventory')->first();
    expect($product->name)->toBeString();
    expect($product->inventory)->not->toBeNull();
});
```

#### **C. Integration Tests (Live AI APIs)**

**Scope:** Real AI provider calls
**APIs:** Google Gemini (1,250 req/day free combined)
**Coverage:** All provider methods

**Test Files:**

- `GeminiLiveTest.php` - Real Gemini API calls
- `ToolCallingTest.php` - Function calling with real APIs

**Test Markers:**

```php
it('calls real Gemini API', function () {
    $provider = new GeminiProvider();
    $response = $provider->chat('Test message', ['budget' => 5000]);
    expect($response['message'])->toBeString();
})->group('live-api', 'gemini'); // Costs 1 API request
```

#### **D. Laravel Dusk (Browser E2E)**

**Scope:** Full user journey in real browser
**Browser:** Chrome/Chromium
**Coverage:** Critical user flows

**Test Files:**

- `FragranceDiagnosisTest.php` - Complete quiz flow
- `ChatInteractionTest.php` - Chat refinement
- `AddToCartFlowTest.php` - Recommendations → cart

**Example:**

```php
public function test_complete_quiz_and_get_recommendations()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/fragrance-diagnosis')
            ->click('@option-romantic')
            ->click('@option-floral')
            ->click('@option-daily')
            ->press('結果を見る')
            ->waitFor('@results-container')
            ->assertSee('あなたにおすすめの香水');
    });
}
```

### 11.3 Testing Tools

**Primary Framework:** PestPHP (already installed: `pestphp/pest: ^4.0`)
**Browser Testing:** Laravel Dusk (to be installed)
**Tinker Scripts:** Interactive debugging and verification

**Installed Testing Dependencies (verified):**

- `pestphp/pest: ^4.0` - Primary testing framework
- `pestphp/pest-plugin-laravel: ^4.0` - Laravel integration
- `mockery/mockery: ^1.6` - For when mocking is absolutely necessary
- `fakerphp/faker: ^1.24` - Test data generation (not fake data - uses factories)
- `laravel/tinker: ^2.10.1` - Interactive testing

### 11.4 Tinker Testing Scripts

**Location:** `tests/Tinker/`

**Scripts:**

1. **ai-test.php** - Manual AI provider testing
2. **quiz-debug.php** - Quiz flow debugging
3. **verify-products.php** - Production data verification
4. **provider-check.php** - API connectivity check

**Example Tinker Script:**

```php
<?php
// tests/Tinker/verify-products.php
// Run: php artisan tinker < tests/Tinker/verify-products.php

echo "Verifying production database...\n";
$count = Product::count();
echo "✓ Found {$count} products\n";

$active = Product::where('is_active', true)->count();
echo "✓ {$active} active products\n";

$withInventory = Product::has('inventory')->count();
echo "✓ {$withInventory} products with inventory\n";

$product = Product::with(['variants', 'brand'])->first();
echo "\nSample product:\n";
echo "- Name: {$product->name}\n";
echo "- Brand: {$product->brand->name}\n";
echo "- Price: ¥{$product->variants->min('price_yen')}\n";
```

### 11.5 Test Database Strategy

**CRITICAL:** Tests use **production database** with **read-only transactions**

**Configuration:**

```xml
<!-- phpunit.xml -->
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="mysql"/>
    <env name="DB_DATABASE" value="laravel"/> <!-- Production DB -->
    <env name="DB_TRANSACTION_ROLLBACK" value="true"/>
</php>
```

**Safety Mechanisms:**

1. All tests wrap in transactions (auto-rollback)
2. Write operations blocked at application level
3. Separate API keys for testing (if needed)
4. Test markers for expensive operations (live-api group)

### 11.6 Running Tests

**All Tests:**

```bash
./vendor/bin/pest
```

**Unit Tests Only (fast):**

```bash
./vendor/bin/pest --group=unit
```

**Feature Tests (production DB):**

```bash
./vendor/bin/pest --group=feature
```

**Live API Tests (costs quota):**

```bash
./vendor/bin/pest --group=live-api
```

**Dusk Tests:**

```bash
php artisan dusk
```

**Tinker Verification:**

```bash
php artisan tinker < tests/Tinker/verify-products.php
```

### 11.7 Test Success Criteria

**MANDATORY:**

- [ ] **Unit Tests:** 90%+ code coverage, all pass
- [ ] **Feature Tests:** 100% API endpoint coverage, all pass
- [ ] **Integration Tests:** All live API calls successful
- [ ] **Dusk Tests:** All critical user flows pass
- [ ] **Production Verification:** Tests confirm real data integrity
- [ ] **No Skipped Tests:** Unless physically impossible
- [ ] **Auto-fix:** All code review issues (HIGH, MEDIUM, LOW) fixed

### 11.8 Test File Manifest

**New Test Files (20+ files):**

```
tests/
├── Unit/
│   └── Services/
│       └── AI/
│           ├── ToolRegistryTest.php
│           ├── ContextBuilderTest.php
│           ├── ResponseParserTest.php
│           └── ScoreCalculatorTest.php
│
├── Feature/
│   └── AI/
│       ├── QuizSubmissionTest.php
│       ├── ChatRefinementTest.php
│       ├── RateLimitingTest.php
│       ├── ProviderFallbackTest.php
│       └── CacheInvalidationTest.php
│
├── Integration/
│   └── AIProviders/
│       ├── GeminiLiveTest.php
│       └── ToolCallingTest.php
│
├── Browser/
│   ├── FragranceDiagnosisTest.php
│   ├── ChatInteractionTest.php
│   └── AddToCartFlowTest.php
│
└── Tinker/
    ├── ai-test.php
    ├── quiz-debug.php
    ├── verify-products.php
    └── provider-check.php
```

---

**End of PRD**

**Document History:**

- v1.0 (2026-02-18): Initial draft
- v1.1 (2026-02-18): Added comprehensive testing requirements (Section 11)
- v1.2 (2026-02-18):
    - Added correct API rate limits
    - Added `attributes_json` structure documentation
    - Added database seeding prerequisite
    - Added visual asset sources (Unsplash, Pexels, unDraw)
    - Added Intervention Image for social card generation
    - Resolved all open questions
    - Confirmed: Anonymous users NO chat history saved
    - Fixed PHP version reference (actual: 8.4.12)
- v1.3 (2026-02-18):
    - Removed Groq provider (not working)
    - Updated to Gemini-only strategy
    - Primary: gemini-2.5-flash-lite (15 RPM, 1,000 RPD)
    - Fallback: gemini-2.5-flash (10 RPM, 250 RPD)
