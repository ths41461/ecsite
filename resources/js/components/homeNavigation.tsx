import { Search, User, Heart, ShoppingCart, Menu } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useState, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

// Define cart related types
type CartLine = {
  line_id: string;
  variant_id: number;
  sku: string;
  product: { id: number; name: string; slug: string };
  price_cents: number;
  compare_at_cents: number | null;
  qty: number;
  managed: boolean;
  available_qty: number | null;
  line_total_cents: number;
  savings_cents: number;
  stock_badge: string;
  notice?: {
    code: string;
    requested: number;
    available: number;
  };
};

// Define search suggestion type
type SearchSuggestion = {
  id: number;
  name: string;
  slug: string;
  brand: { name?: string | null };
  category: { name?: string | null };
  price?: number | null;
  image?: string | null;
  availability_status?: string | null;
  discount_percentage?: number | null;
  is_bestseller?: boolean;
  is_top_rated?: boolean;
  rating?: number | null;
  review_count?: number | null;
};

type Cart = {
  lines: CartLine[];
  subtotal_cents: number;
  savings_cents: number;
  tax_cents?: number;
  total_cents: number;
  currency: string;
  coupon_code?: string | null;
  coupon_discount_cents?: number;
  coupon_summary?: string;
  coupon_line_ids?: string[];
  coupon_line_names?: string[];
};

// Safe localStorage utility functions
const setCartToStorage = (cart: Cart | null) => {
  try {
    if (typeof window !== 'undefined' && window.localStorage) {
      localStorage.setItem('cart-state', JSON.stringify(cart));
    }
  } catch (error) {
    console.error('Error saving cart to localStorage:', error);
  }
};

const getCartFromStorage = (): Cart | null => {
  try {
    if (typeof window !== 'undefined' && window.localStorage) {
      const stored = localStorage.getItem('cart-state');
      return stored ? JSON.parse(stored) : null;
    }
    return null;
  } catch (error) {
    console.error('Error reading cart from localStorage:', error);
    return null;
  }
};

export function HomeNavigation() {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [cartCount, setCartCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [loadingSuggestions, setLoadingSuggestions] = useState(false);
  const hasFetchedInitialCart = useRef(false);
  const isUpdatingFromStorage = useRef(false);
  const searchTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  // Fetch cart data and update count
  useEffect(() => {
    const fetchCartCount = async () => {
      setIsLoading(true);
      try {
        const response = await fetch('/cart', {
          headers: { 
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
          credentials: 'same-origin',
        });
        
        if (response.ok) {
          const cartData: Cart = await response.json();
          const totalItems = cartData.lines.reduce((sum, line) => sum + line.qty, 0);
          setCartCount(totalItems);
          
          // Update localStorage to notify other tabs/windows
          setCartToStorage(cartData);
        }
      } catch (error) {
        console.error('Failed to fetch cart count:', error);
      } finally {
        setIsLoading(false);
        hasFetchedInitialCart.current = true;
      }
    };

    // Only fetch cart count if document is available (client-side)
    if (typeof window !== 'undefined' && !hasFetchedInitialCart.current) {
      fetchCartCount();
    }
  }, []);

  // Listen for storage events to sync cart across tabs/windows
  useEffect(() => {
    const handleStorageChange = (e: StorageEvent) => {
      if (e.key === 'cart-state' && e.newValue) {
        try {
          // This only executes in OTHER tabs, not the one that made the change
          const cartData: Cart = JSON.parse(e.newValue);
          const totalItems = cartData.lines.reduce((sum, line) => sum + line.qty, 0);
          setCartCount(totalItems);
        } catch (error) {
          console.error('Failed to parse cart data from storage event:', error);
        }
      }
    };

    window.addEventListener('storage', handleStorageChange);
    
    return () => {
      window.removeEventListener('storage', handleStorageChange);
    };
  }, []);

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current);
      }
    };
  }, []);

  // Function to handle cart click and navigate to cart page
  const handleCartClick = () => {
    router.get('/cart');
  };

  // Fetch search suggestions
  const fetchSuggestions = async (query: string) => {
    if (!query.trim()) {
      setSuggestions([]);
      setShowSuggestions(false);
      return;
    }

    setLoadingSuggestions(true);
    try {
      const response = await fetch(`/api/search/autocomplete?q=${encodeURIComponent(query)}`, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setSuggestions(data.suggestions || []);
        setShowSuggestions(true);
      } else {
        setSuggestions([]);
        setShowSuggestions(false);
      }
    } catch (error) {
      console.error('Error fetching search suggestions:', error);
      setSuggestions([]);
      setShowSuggestions(false);
    } finally {
      setLoadingSuggestions(false);
    }
  };

  // Handle search input change with debounce
  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setSearchQuery(value);

    // Clear previous timeout
    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
    }

    // Set new timeout for debouncing
    if (value.trim()) {
      searchTimeoutRef.current = setTimeout(() => {
        fetchSuggestions(value);
      }, 300); // 300ms debounce
    } else {
      setSuggestions([]);
      setShowSuggestions(false);
    }
  };

  // Handle suggestion click
  const handleSuggestionClick = (suggestion: SearchSuggestion) => {
    // Navigate directly to the specific product page using the slug
    router.get(`/products/${suggestion.slug}`);
    // Clear search and hide suggestions
    setSearchQuery('');
    setSuggestions([]);
    setShowSuggestions(false);
  };

  // Handle Enter key press
  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' && searchQuery.trim()) {
      if (suggestions.length > 0 && showSuggestions) {
        // If suggestions are visible, use the first one
        handleSuggestionClick(suggestions[0]);
      } else {
        // Otherwise, just navigate to search results
        router.get(`/products?q=${encodeURIComponent(searchQuery.trim())}`);
        setSuggestions([]);
        setShowSuggestions(false);
      }
    }
  };

  // Handle input blur to hide suggestions after a delay
  const handleInputBlur = () => {
    setTimeout(() => {
      setShowSuggestions(false);
    }, 200); // Small delay to allow for clicking on suggestions
  };

  // Handle input focus to show suggestions if there are any
  const handleInputFocus = () => {
    if (searchQuery && suggestions.length > 0) {
      setShowSuggestions(true);
    }
  };

  return (
    <div className="w-full bg-white px-4 py-2">
      {/* Desktop Layout */}
      <div className="hidden lg:flex flex-row items-center max-w-[1440px] mx-auto">
        {/* Logo Area */}
        <div className="flex items-center justify-center gap-2.5 p-8 w-[200px] h-[100px] border border-[#888888] cursor-pointer" onClick={() => router.get('/')}>
          <div className="w-[200px] h-[65.19px] bg-gray-100 rounded flex items-center justify-center">
            <span className="text-lg font-bold">LOGO</span>
          </div>
        </div>

        {/* Navigation Section */}
        <div className="flex flex-col w-[1128px]">
          {/* Top Section - Search and User Options */}
          <div className="flex flex-row items-center justify-between">
            {/* Search Bar */}
            <div className="relative">
              <div className="flex flex-row items-center gap-1.75 p-3 w-[934px] h-[50px] bg-[#FCFCF7] border border-l border-r border-t border-[#888888]">
                <Search className="w-5.5 h-5.5 text-[#0D0D0D]" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={handleSearchChange}
                  onKeyDown={handleKeyDown}
                  onFocus={handleInputFocus}
                  onBlur={handleInputBlur}
                  placeholder="検索"
                  className="w-full bg-transparent border-none focus:outline-none text-[#0D0D0D] text-base"
                />
                {searchQuery && (
                  <button 
                    type="button" 
                    onClick={(e) => {
                      e.stopPropagation();
                      setSearchQuery('');
                      setSuggestions([]);
                      setShowSuggestions(false);
                    }}
                    className="flex items-center justify-center w-6 h-6 rounded-full hover:bg-gray-200"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                )}
              </div>
              
              {/* Autocomplete Dropdown */}
              {showSuggestions && suggestions.length > 0 && (
                <div className="absolute z-50 mt-1 w-[934px] bg-white border border-[#888888] shadow-lg max-h-96 overflow-y-auto">
                  <ul>
                    {suggestions.map((suggestion, index) => (
                      <li 
                        key={suggestion.id}
                        onClick={() => handleSuggestionClick(suggestion)}
                        className="border-b border-gray-200 last:border-b-0 cursor-pointer px-4 py-3 hover:bg-gray-50 transition-colors duration-150"
                      >
                        <div className="flex items-start justify-between">
                          <div className="flex items-start gap-3 flex-1 min-w-0">
                            {suggestion.image && (
                              <div className="flex-shrink-0 w-12 h-12">
                                <img 
                                  src={suggestion.image} 
                                  alt={suggestion.name} 
                                  className="w-full h-full object-cover"
                                  loading="lazy"
                                />
                              </div>
                            )}
                            <div className="flex-1 min-w-0">
                              <div className="font-semibold text-[#0D0D0D] text-sm truncate">{suggestion.name}</div>
                              <div className="text-xs text-[#444444] truncate">
                                {suggestion.brand?.name} • {suggestion.category?.name}
                              </div>
                            </div>
                          </div>
                          <div className="ml-4 flex flex-col items-end justify-start flex-shrink-0">
                            {suggestion.price && (
                              <div className="text-xs font-bold text-[#0D0D0D] mb-1">
                                ¥{suggestion.price.toLocaleString()}
                              </div>
                            )}
                            {suggestion.availability_status && (
                              <div className={`text-xs mb-1 ${
                                suggestion.availability_status === 'In Stock' ? 'text-[#0D0D0D]' : 
                                suggestion.availability_status === 'Low Stock' ? 'text-[#886600]' : 
                                'text-[#880000]'
                              }`}>
                                {suggestion.availability_status}
                              </div>
                            )}
                            {(suggestion.discount_percentage || suggestion.is_bestseller || suggestion.is_top_rated) && (
                              <div className="flex gap-1 flex-wrap justify-end">
                                {suggestion.discount_percentage && (
                                  <span className="inline-flex items-center px-1.5 py-0.5 text-[0.7rem] font-medium bg-red-50 text-red-700 border border-red-200">
                                    {suggestion.discount_percentage}% 割引
                                  </span>
                                )}
                                {suggestion.is_bestseller && (
                                  <span className="inline-flex items-center px-1.5 py-0.5 text-[0.7rem] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                    人気商品
                                  </span>
                                )}
                                {suggestion.is_top_rated && (
                                  <span className="inline-flex items-center px-1.5 py-0.5 text-[0.7rem] font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                    高評価
                                  </span>
                                )}
                              </div>
                            )}
                            {suggestion.rating !== undefined && suggestion.rating !== null && (
                              <div className="flex items-center text-[0.7rem] text-[#444444] mt-1">
                                <span className="mr-1">⭐</span>
                                <span className="font-medium">{suggestion.rating}</span>
                                {suggestion.review_count && <span className="ml-1">({suggestion.review_count})</span>}
                              </div>
                            )}
                          </div>
                        </div>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
              
              {/* Loading indicator */}
              {loadingSuggestions && (
                <div className="absolute z-50 mt-1 w-[934px] bg-white border border-[#888888] shadow-lg py-4 flex justify-center">
                  <div className="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-black" />
                </div>
              )}
            </div>

            {/* Top Navigation Bar */}
            <div className="flex flex-row items-center justify-center gap-3 p-3 w-[194px] h-[50px] bg-[#FCFCF7] border border-l border-r border-t border-[#888888]">
              {/* Login Button */}
              <div className="flex flex-row items-center justify-center gap-2 cursor-pointer" onClick={() => setSearchQuery('')}>
                <User className="w-4.5 h-4.5 text-gray-700" />
                <span className="text-xs font-medium text-[#444444] whitespace-nowrap">ログイン</span>
              </div>

              {/* Favourite Button */}
              <div className="flex flex-row items-center justify-center gap-2 cursor-pointer" onClick={() => setSearchQuery('')}>
                <Heart className="w-4.5 h-4.5 text-gray-700" />
                <span className="text-xs font-medium text-[#444444] whitespace-nowrap">お気に入り</span>
              </div>
            </div>
          </div>

          {/* Bottom Section - Main Navigation Menu */}
          <div className="flex flex-col items-center gap-2.5 px-[312px] py-[5px]">
            <div className="flex flex-row items-center justify-center gap-8 w-full">
              <CustomNavButton className="whitespace-nowrap" onClick={() => { setSearchQuery(''); router.get('/products'); }}>商品一覧</CustomNavButton>
              <CustomNavButton className="whitespace-nowrap" onClick={() => { setSearchQuery(''); router.get('/fragrance-diagnosis'); }}>香り診断</CustomNavButton>
              <CustomNavButton className="whitespace-nowrap" onClick={() => { setSearchQuery(''); router.get('/brand-introduction'); }}>ブランド紹介</CustomNavButton>
              <CustomNavButton className="whitespace-nowrap" onClick={() => { setSearchQuery(''); router.get('/contact'); }}>お問い合わせ</CustomNavButton>
            </div>
          </div>
        </div>

        {/* Cart Section - Updated to show dynamic count and be clickable */}
        <button 
          className="flex flex-row items-center justify-center gap-1.25 p-5 w-[80px] h-[100px] bg-[#FCFCF7] border border-[#888888] cursor-pointer"
          onClick={handleCartClick}
        >
          <ShoppingCart className="w-3.75 h-3.75 text-gray-700" />
          <span className="text-xs font-semibold text-black whitespace-nowrap">
            ({isLoading ? '...' : cartCount})
          </span>
        </button>
      </div>

      {/* Mobile/Tablet Layout */}
      <div className="lg:hidden flex flex-col">
        {/* Top Bar - Logo, Menu Button, Login, Wishlist, and Cart */}
        <div className="flex items-center justify-between py-3 px-4">
          {/* Left side - Logo */}
          <div className="flex items-center justify-center p-2 border border-[#888888] cursor-pointer" onClick={() => router.get('/')}>
            <div className="w-12 h-10 bg-gray-100 rounded flex items-center justify-center">
              <span className="text-xs font-bold">LOGO</span>
            </div>
          </div>

          {/* Right side - Menu, Login, Wishlist, and Cart */}
          <div className="flex items-center space-x-3">
            {/* Login Button */}
            <button className="flex flex-col items-center cursor-pointer" onClick={() => setSearchQuery('')}>
              <User className="w-5 h-5 text-gray-700" />
              <span className="text-[0.6rem] text-[#444444] mt-1">ログイン</span>
            </button>

            {/* Wishlist Button */}
            <button className="flex flex-col items-center cursor-pointer" onClick={() => setSearchQuery('')}>
              <Heart className="w-5 h-5 text-gray-700" />
              <span className="text-[0.6rem] text-[#444444] mt-1">お気に入り</span>
            </button>

            {/* Cart - Updated to show dynamic count and be clickable */}
            <button 
              className="flex flex-col items-center cursor-pointer"
              onClick={() => {
                setSearchQuery('');
                handleCartClick();
              }}
            >
              <ShoppingCart className="w-5 h-5 text-gray-700" />
              <span className="text-[0.6rem] text-black mt-1">
                ({isLoading ? '...' : cartCount})
              </span>
            </button>

            {/* Hamburger Menu Button */}
            <button 
              onClick={() => {
                setSearchQuery('');
                setIsMobileMenuOpen(!isMobileMenuOpen);
              }}
              className="ml-2 p-1 cursor-pointer"
            >
              <Menu className="w-6 h-6 text-gray-700" />
            </button>
          </div>
        </div>

        {/* Mobile Menu - Only visible when menu is open */}
        {isMobileMenuOpen && (
          <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex">
            <div 
              className="bg-white w-4/5 max-w-sm ml-auto h-full p-4 flex flex-col"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Menu Header */}
              <div className="flex justify-between items-center mb-6 p-2">
                <div className="flex items-center justify-center p-2 border border-[#888888] cursor-pointer" onClick={() => {
                  router.get('/');
                  setIsMobileMenuOpen(false);
                }}>
                  <div className="w-12 h-10 bg-gray-100 rounded flex items-center justify-center">
                    <span className="text-xs font-bold">LOGO</span>
                  </div>
                </div>
                <button 
                  onClick={() => {
                    setSearchQuery('');
                    setIsMobileMenuOpen(false);
                  }}
                  className="p-2 cursor-pointer"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              {/* Search Bar in Menu */}
              <div className="relative w-full">
                <div className="flex flex-row items-center gap-2 p-3 mb-4 bg-[#FCFCF7] border border-[#888888] w-full">
                  <Search className="w-4 h-4 text-[#0D0D0D] opacity-50" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={handleSearchChange}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' && searchQuery.trim()) {
                        if (suggestions.length > 0 && showSuggestions) {
                          handleSuggestionClick(suggestions[0]);
                          setIsMobileMenuOpen(false);
                        } else {
                          router.get(`/products?q=${encodeURIComponent(searchQuery.trim())}`);
                          setIsMobileMenuOpen(false);
                        }
                      }
                    }}
                    onFocus={handleInputFocus}
                    onBlur={handleInputBlur}
                    placeholder="検索"
                    className="w-full bg-transparent border-none focus:outline-none text-[#0D0D0D] text-sm"
                  />
                  {searchQuery && (
                    <button 
                      type="button" 
                      onClick={(e) => {
                        e.stopPropagation();
                        setSearchQuery('');
                        setSuggestions([]);
                        setShowSuggestions(false);
                      }}
                      className="flex items-center justify-center w-5 h-5 rounded-full hover:bg-gray-200"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  )}
                </div>
                
                {/* Mobile Autocomplete Dropdown */}
                {showSuggestions && suggestions.length > 0 && (
                  <div className="absolute z-50 mt-1 w-full bg-white border border-[#888888] shadow-lg max-h-64 overflow-y-auto">
                    <ul>
                      {suggestions.map((suggestion, index) => (
                        <li 
                          key={suggestion.id}
                          onClick={() => {
                            handleSuggestionClick(suggestion);
                            setIsMobileMenuOpen(false);
                          }}
                          className="border-b border-gray-200 last:border-b-0 cursor-pointer px-4 py-3 hover:bg-gray-50 transition-colors duration-150"
                        >
                          <div className="flex items-start justify-between">
                            <div className="flex items-start gap-2 flex-1 min-w-0">
                              {suggestion.image && (
                                <div className="flex-shrink-0 w-10 h-10">
                                  <img 
                                    src={suggestion.image} 
                                    alt={suggestion.name} 
                                    className="w-full h-full object-cover"
                                    loading="lazy"
                                  />
                                </div>
                              )}
                              <div className="flex-1 min-w-0">
                                <div className="font-semibold text-[#0D0D0D] text-xs truncate">{suggestion.name}</div>
                                <div className="text-[0.6rem] text-[#444444] truncate">
                                  {suggestion.brand?.name} • {suggestion.category?.name}
                                </div>
                              </div>
                            </div>
                            <div className="ml-2 flex flex-col items-end justify-start flex-shrink-0">
                              {suggestion.price && (
                                <div className="text-[0.6rem] font-bold text-[#0D0D0D] mb-1">
                                  ¥{suggestion.price.toLocaleString()}
                                </div>
                              )}
                              {suggestion.availability_status && (
                                <div className={`text-[0.6rem] mb-1 ${
                                  suggestion.availability_status === 'In Stock' ? 'text-[#0D0D0D]' : 
                                  suggestion.availability_status === 'Low Stock' ? 'text-[#886600]' : 
                                  'text-[#880000]'
                                }`}>
                                  {suggestion.availability_status}
                                </div>
                              )}
                              {(suggestion.discount_percentage || suggestion.is_bestseller || suggestion.is_top_rated) && (
                                <div className="flex gap-1 flex-wrap justify-end">
                                  {suggestion.discount_percentage && (
                                    <span className="inline-flex items-center px-1 py-0.5 text-[0.6rem] font-medium bg-red-50 text-red-700 border border-red-200">
                                      {suggestion.discount_percentage}% 割引
                                    </span>
                                  )}
                                  {suggestion.is_bestseller && (
                                    <span className="inline-flex items-center px-1 py-0.5 text-[0.6rem] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                      人気商品
                                    </span>
                                  )}
                                  {suggestion.is_top_rated && (
                                    <span className="inline-flex items-center px-1 py-0.5 text-[0.6rem] font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                      高評価
                                    </span>
                                  )}
                                </div>
                              )}
                              {suggestion.rating !== undefined && suggestion.rating !== null && (
                                <div className="flex items-center text-[0.6rem] text-[#444444] mt-1">
                                  <span className="mr-1">⭐</span>
                                  <span className="font-medium">{suggestion.rating}</span>
                                  {suggestion.review_count && <span className="ml-1">({suggestion.review_count})</span>}
                                </div>
                              )}
                            </div>
                          </div>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
                
                {/* Mobile Loading indicator */}
                {loadingSuggestions && (
                  <div className="absolute z-50 mt-1 w-full bg-white border border-[#888888] shadow-lg py-4 flex justify-center">
                    <div className="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-black" />
                  </div>
                )}
              </div>

              {/* Main Navigation Menu */}
              <div className="flex flex-col items-center gap-4 py-4 flex-1">
                <CustomNavButton className="w-full text-center whitespace-nowrap" onClick={() => { setSearchQuery(''); router.get('/products'); setIsMobileMenuOpen(false); }}>商品一覧</CustomNavButton>
                <CustomNavButton className="w-full text-center whitespace-nowrap" onClick={() => { setSearchQuery(''); router.get('/fragrance-diagnosis'); setIsMobileMenuOpen(false); }}>香り診断</CustomNavButton>
                <CustomNavButton className="w-full text-center whitespace-nowrap" onClick={() => { setSearchQuery(''); router.get('/brand-introduction'); setIsMobileMenuOpen(false); }}>ブランド紹介</CustomNavButton>
                <CustomNavButton className="w-full text-center whitespace-nowrap" onClick={() => { setSearchQuery(''); router.get('/contact'); setIsMobileMenuOpen(false); }}>お問い合わせ</CustomNavButton>
              </div>

              {/* Additional Menu Options */}
              <div className="pt-4 border-t border-gray-200">
                <div className="flex justify-around">
                  <button className="flex flex-col items-center cursor-pointer" onClick={() => {
                    setSearchQuery('');
                    setIsMobileMenuOpen(false);
                  }}>
                    <User className="w-6 h-6 text-gray-700" />
                    <span className="text-xs text-[#444444] mt-1">ログイン</span>
                  </button>
                  <button className="flex flex-col items-center cursor-pointer" onClick={() => {
                    setSearchQuery('');
                    setIsMobileMenuOpen(false);
                  }}>
                    <Heart className="w-6 h-6 text-gray-700" />
                    <span className="text-xs text-[#444444] mt-1">お気に入り</span>
                  </button>
                  {/* Cart in mobile menu - Updated to show dynamic count and be clickable */}
                  <button 
                    className="flex flex-col items-center cursor-pointer"
                    onClick={() => {
                      setSearchQuery('');
                      handleCartClick();
                      setIsMobileMenuOpen(false);
                    }}
                  >
                    <ShoppingCart className="w-6 h-6 text-gray-700" />
                    <span className="text-xs text-black mt-1">
                      ({isLoading ? '...' : cartCount})
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

interface CustomNavButtonProps {
  children: React.ReactNode;
  className?: string;
}

function CustomNavButton({ children, className, onClick }: CustomNavButtonProps & { onClick?: () => void }) {
  return (
    <button
      className={cn(
        "flex flex-row items-center justify-center gap-2 h-10 border-2 border-[#888888] text-sm font-medium text-[#444444] px-4 py-2.5 cursor-pointer",
        className
      )}
      onClick={onClick}
    >
      {children}
    </button>
  );
}

// Export types for other components to use
export { Cart as CartType, CartLine as CartLineType };