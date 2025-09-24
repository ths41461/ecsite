/**
 * Utility for managing temporary review data that overrides cached values
 * This helps provide immediate feedback to users when they submit reviews
 * without waiting for server-side cache to refresh
 */

// Key for localStorage
const STORAGE_KEY = 'fresh-product-reviews';

/**
 * Store updated review data for a product
 */
export function storeFreshReviewData(productId: number, averageRating: number, reviewCount: number) {
  try {
    const now = Date.now();
    const freshData = getFreshReviewData();
    
    freshData[productId] = {
      averageRating,
      reviewCount,
      timestamp: now,
    };
    
    // Clean up expired entries (older than 10 minutes)
    const expiryTime = now - (10 * 60 * 1000); // 10 minutes in milliseconds
    Object.keys(freshData).forEach(id => {
      if (freshData[Number(id)].timestamp < expiryTime) {
        delete freshData[Number(id)];
      }
    });
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(freshData));
  } catch (error) {
    console.warn('Failed to store fresh review data:', error);
  }
}

/**
 * Get fresh review data for a product if it exists and isn't expired
 */
export function getFreshReviewDataForProduct(productId: number) {
  try {
    const freshData = getFreshReviewData();
    const productData = freshData[productId];
    
    if (!productData) {
      return null;
    }
    
    // Check if the data is still fresh (less than 10 minutes old)
    const now = Date.now();
    const expiryTime = now - (10 * 60 * 1000); // 10 minutes in milliseconds
    
    if (productData.timestamp < expiryTime) {
      // Data expired, remove it
      delete freshData[productId];
      localStorage.setItem(STORAGE_KEY, JSON.stringify(freshData));
      return null;
    }
    
    return {
      averageRating: productData.averageRating,
      reviewCount: productData.reviewCount,
    };
  } catch (error) {
    console.warn('Failed to get fresh review data:', error);
    return null;
  }
}

/**
 * Get all fresh review data
 */
export function getFreshReviewData(): Record<number, { averageRating: number; reviewCount: number; timestamp: number }> {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : {};
  } catch (error) {
    console.warn('Failed to parse fresh review data:', error);
    return {};
  }
}

/**
 * Clear all fresh review data
 */
export function clearFreshReviewData() {
  try {
    localStorage.removeItem(STORAGE_KEY);
  } catch (error) {
    console.warn('Failed to clear fresh review data:', error);
  }
}

/**
 * Update the stored review count and average when a new review is added
 */
export function updateProductReviewData(productId: number, oldAverageRating: number, oldReviewCount: number, newRating: number) {
  const newReviewCount = oldReviewCount + 1;
  const newAverageRating = oldReviewCount > 0 
    ? (oldAverageRating * oldReviewCount + newRating) / newReviewCount
    : newRating;
  
  storeFreshReviewData(productId, newAverageRating, newReviewCount);
  
  return {
    averageRating: newAverageRating,
    reviewCount: newReviewCount
  };
}