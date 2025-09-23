# E-Commerce Site Enhancement Project

## Project Overview

Enhance the existing Laravel/React e-commerce perfume site with advanced product reviews and search functionality.

## Feature Requirements

### 1. Product Reviews and Ratings System

- Add Review model with rating, comment, and user association
- Display average ratings on product cards
- Allow users to submit reviews after purchase

### 2. Search Suggestions/Autocomplete (Navigation Search Bar Only)

- Real-time suggestions as users type in search box
- Popular searches and trending products
- Visual preview of suggested products

### 3. Enhanced Faceted Search

- More filter options (brand, type, ml, attributes, ratings, sex (all, men, female, unisex))
- Hierarchical category filtering
- Range sliders for price and other numeric attributes
- Filter persistence across sessions

### 4. Search Synonyms and Query Expansion

- Define synonym sets for product names
- Handle common misspellings
- Expand queries with related terms
- Support for product name variations

### 5. Advanced Search Filters

- Boolean search operators (AND, OR, NOT)
- Exact phrase matching
- Wildcard and fuzzy search support
- Filter by availability and stock status

## Implementation Plan

### Phase 1: Product Reviews and Ratings System

#### Backend Implementation

1. Create Review model with fields:
    - product_id (foreign key to products table)
    - user_id (foreign key to users table)
    - rating (integer, 1-5)
    - comment (text)
    - approved (boolean, default false)
    - timestamps

2. Create reviews table migration with:
    - id (primary key)
    - product_id (unsignedBigInteger, indexed)
    - user_id (unsignedBigInteger, indexed)
    - rating (tinyInteger, 1-5)
    - comment (text)
    - approved (boolean, default false)
    - timestamps

3. Add relationships:
    - Product model: hasMany reviews
    - User model: hasMany reviews
    - Review model: belongsTo Product, belongsTo User

4. Create Review factory for testing with:
    - Random ratings (1-5)
    - Sample comments
    - Approved status variation

5. Add Review controller with API endpoints:
    - GET /products/{product}/reviews - List reviews for a product
    - POST /products/{product}/reviews - Submit a new review
    - PUT/PATCH /reviews/{review} - Update review (admin/user)
    - DELETE /reviews/{review} - Delete review (admin/user)

6. Add validation rules:
    - Rating: required, integer, between 1-5
    - Comment: nullable, max 1000 characters
    - User must be authenticated to submit review
    - User can only submit one review per product

7. Add business logic:
    - Calculate average rating for products
    - Only allow reviews from users who purchased the product
    - Admin approval system for reviews

#### Frontend Implementation

1. Create ReviewList component to display product reviews:
    - Star rating display
    - Review comments
    - User information
    - Review date

2. Create ReviewForm component for submitting reviews:
    - Star rating selector (1-5)
    - Comment textarea
    - Submit button

3. Create RatingStars component for displaying star ratings:
    - Visual star display (filled/empty)
    - Average rating with count

4. Integrate reviews with product pages:
    - Display average rating on product detail page
    - Show review count
    - List individual reviews
    - Add review submission form for authenticated users

5. Update product cards to show average ratings:
    - Add star rating display to ProductCard component
    - Show average rating and review count

### Phase 2: Search Suggestions/Autocomplete

#### Backend Implementation

1. Create search suggestions API endpoint:
    - GET /api/search/suggestions?q={query}
    - Return top 10 matching products
    - Include product name, brand, price, and image

2. Implement search logic:
    - Search by product name and brand
    - Order by relevance/popularity
    - Limit results to 10

3. Add popular searches tracking:
    - Store search terms with frequency
    - Return top 5 popular searches when query is empty

#### Frontend Implementation

1. Create SearchSuggestions component:
    - Dropdown display below search input
    - Product previews with image, name, brand, price
    - Popular searches section
    - Trending products section

2. Integrate with navigation search bar:
    - Debounced API calls as user types
    - Keyboard navigation support
    - Click handling for suggestions

3. Add visual preview elements:
    - Product images
    - Pricing information
    - Brand information

4. Enhanced Faceted Search
    - More filter options (brand,type, ml, attributes,
      ratings, sex ( all, men, female, unisex ))
    - Hierarchical category filtering
    - Range sliders for price and other numeric attributes
    - Filter persistence across sessions

### Phase 3: Enhanced Faceted Search

#### Backend Implementation

1. Add new filter options to ProductController:
    - Filter by product type
    - Filter by ml (milliliters)
    - Filter by attributes (fragrance notes, etc.)
    - Filter by average rating
    - Filter by gender (men, women, unisex)

2. Enhance buildFacets method:
    - Add type facets
    - Add ml facets
    - Add rating facets (1-5 stars)
    - Add gender facets

3. Implement hierarchical category filtering:
    - Support for parent/child categories
    - Filter by category tree

4. Add range slider support:
    - Price range filtering
    - ML range filtering
    - Rating range filtering

5. Implement filter persistence:
    - Store active filters in session
    - Restore filters on return visits

#### Frontend Implementation

1. Update product listing page:
    - Add new filter sections (type, ml, rating, gender)
    - Implement range sliders for price/ml
    - Add hierarchical category display
    - Show active filters with clear option

2. Create filter components:
    - RangeSlider for numeric attributes
    - Checkbox groups for categorical filters
    - Star rating filter component
    - Hierarchical category selector

3. Add filter persistence:
    - Store active filters in localStorage
    - Restore filters on page load

### Phase 4: Search Synonyms and Query Expansion

#### Backend Implementation

1. Create synonyms configuration system:
    - Store synonym sets in database
    - Admin interface for managing synonyms
    - Examples: "cologne" -> "perfume", "scent" -> "fragrance"

2. Modify search logic:
    - Expand queries with synonyms before searching
    - Handle multiple synonym sets
    - Weight original terms higher than synonyms

3. Add misspelling handling:
    - Levenshtein distance for fuzzy matching
    - Common typo corrections
    - Phonetic matching (metaphone/soundex)

4. Implement query expansion:
    - Expand "men cologne" to "men perfume cologne fragrance"
    - Add related terms based on product attributes
    - Context-aware expansion

#### Frontend Implementation

1. Update search results page:
    - Show "Did you mean?" suggestions for misspellings
    - Display search query modifications
    - Highlight matched terms in results

### Phase 5: Advanced Search Filters

#### Backend Implementation

1. Implement boolean search operators:
    - Parse queries for AND, OR, NOT operators
    - Support quoted phrases for exact matching
    - Parenthetical grouping support

2. Add exact phrase matching:
    - Handle quoted search terms
    - Match exact sequences in product names/descriptions

3. Implement wildcard and fuzzy search:
    - Support \* and ? wildcards
    - Fuzzy matching with configurable threshold
    - Trigram indexing for better fuzzy performance

4. Add availability filtering:
    - Filter by in-stock products
    - Filter by low stock items
    - Filter by "coming soon" products

#### Frontend Implementation

1. Update search interface:
    - Add advanced search toggle
    - Show search syntax help
    - Display active filters

2. Create advanced search form:
    - Boolean operator selection
    - Exact phrase input
    - Wildcard support
    - Availability filters

## Technical Considerations

### Performance

- Implement caching for search results and suggestions
- Use database indexing for frequently queried fields
- Optimize Meilisearch configuration for better relevance
- Add pagination for large result sets

### Security

- Validate all user input for search queries
- Implement rate limiting for search API endpoints
- Sanitize review content to prevent XSS
- Protect review submission with authentication

### Testing

- Unit tests for review business logic
- Feature tests for search functionality
- API tests for suggestions endpoint
- UI tests for review components

### Database Changes

- New reviews table
- Possible new tables for synonyms and search analytics
- Indexes on frequently queried columns
- Foreign key constraints for data integrity

## Dependencies

- Laravel Scout with Meilisearch (already configured)
- Existing product, user, and authentication systems
- React components for frontend implementation
- Tailwind CSS for styling

## Timeline

1. Product Reviews System: 3-4 days
2. Search Suggestions: 2-3 days
3. Enhanced Faceted Search: 4-5 days
4. Search Synonyms and Expansion: 3-4 days
5. Advanced Search Filters: 3-4 days

## Success Metrics

- Increased user engagement with review system
- Improved search conversion rates
- Reduced bounce rate on search results pages
- Higher average order value through better product discovery
