import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { Link, router, usePage } from '@inertiajs/react';
import { CircleUserRound, Heart, LogOut, Menu, Search, ShoppingBag } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

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
    const [isProfileDropdownOpen, setIsProfileDropdownOpen] = useState(false);
    const [isMobileProfileDropdownOpen, setIsMobileProfileDropdownOpen] = useState(false);
    const [isMobileMenuProfileDropdownOpen, setIsMobileMenuProfileDropdownOpen] = useState(false);
    const [cartCount, setCartCount] = useState(0);
    const [isLoading, setIsLoading] = useState(true);
    const page = usePage();
    const { auth } = page.props;
    const isAuthenticated = !!auth?.user;
    const [searchQuery, setSearchQuery] = useState('');
    const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [loadingSuggestions, setLoadingSuggestions] = useState(false);
    const hasFetchedInitialCart = useRef(false);
    const isUpdatingFromStorage = useRef(false);
    const searchTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const cartCacheRef = useRef<{ data: Cart | null; timestamp: number } | null>(null);
    const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes in milliseconds

    // Fetch cart data and update count
    useEffect(() => {
        const fetchCartCount = async () => {
            if (hasFetchedInitialCart.current) return; // Prevent multiple fetch attempts

            setIsLoading(true);
            hasFetchedInitialCart.current = true; // Mark as fetching to prevent re-entrancy

            try {
                // Check if we have cached data that's still valid
                if (cartCacheRef.current) {
                    const { data, timestamp } = cartCacheRef.current;
                    const now = Date.now();

                    if (now - timestamp < CACHE_DURATION) {
                        // Use cached data
                        const totalItems = data.lines.reduce((sum, line) => sum + line.qty, 0);
                        setCartCount(totalItems);
                        setCartToStorage(data);
                        setIsLoading(false);
                        return;
                    }
                }

                // Create a timeout promise to prevent hanging requests
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('Cart fetch timeout')), 5000); // 5 second timeout
                });

                const fetchPromise = fetch('/cart', {
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                // Race between the fetch and the timeout
                const response = await Promise.race([fetchPromise, timeoutPromise]);

                if (response.ok) {
                    const cartData: Cart = await response.json();

                    // Cache the successful response
                    cartCacheRef.current = {
                        data: cartData,
                        timestamp: Date.now(),
                    };

                    const totalItems = cartData.lines.reduce((sum, line) => sum + line.qty, 0);
                    setCartCount(totalItems);

                    // Update localStorage to notify other tabs/windows
                    setCartToStorage(cartData);

                    // Dispatch cart update to other components
                    if ((window as any).dispatchCartUpdate) {
                        (window as any).dispatchCartUpdate(cartData);
                    }
                } else {
                    // If cart API fails, try to use localStorage as fallback
                    const storedCart = getCartFromStorage();
                    if (storedCart) {
                        const totalItems = storedCart.lines.reduce((sum, line) => sum + line.qty, 0);
                        setCartCount(totalItems);
                    } else {
                        setCartCount(0); // Ensure cart count is set to 0 if no data available
                    }
                }
            } catch (error) {
                console.error('Failed to fetch cart count:', error);
                // Fallback to localStorage if API fails
                const storedCart = getCartFromStorage();
                if (storedCart) {
                    const totalItems = storedCart.lines.reduce((sum, line) => sum + line.qty, 0);
                    setCartCount(totalItems);
                } else {
                    setCartCount(0); // Ensure cart count is set to 0 if no fallback available
                }
            } finally {
                setIsLoading(false);
            }
        };

        // Only fetch cart count if document is available (client-side) and not already fetched
        if (typeof window !== 'undefined' && !hasFetchedInitialCart.current) {
            fetchCartCount();
        }
    }, []);

    // Listen for storage events to sync cart across tabs/windows
    useEffect(() => {
        const handleStorageChange = (e: StorageEvent) => {
            if (e.key === 'cart-state' && e.newValue) {
                try {
                    // This executes in OTHER tabs, not the one that made the change
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

    // Function to update cart count and broadcast changes
    const updateCartCountAndBroadcast = (cartData: Cart | null) => {
        if (cartData && Array.isArray(cartData.lines)) {
            const totalItems = cartData.lines.reduce((sum, line) => sum + line.qty, 0);
            setCartCount(totalItems);
            // Also update localStorage for cross-tab sync
            setCartToStorage(cartData);
        } else {
            setCartCount(0);
            setCartToStorage(null);
        }
    };

    // Listen for storage events to sync cart across tabs/windows
    useEffect(() => {
        const handleStorageChange = (e: StorageEvent) => {
            if (e.key === 'cart-state' && e.newValue) {
                try {
                    // This executes in OTHER tabs, not the one that made the change
                    const cartData: Cart = JSON.parse(e.newValue);
                    updateCartCountAndBroadcast(cartData);
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

    // Listen for custom cart update events from other components in the same tab
    useEffect(() => {
        const handleCustomCartUpdate = (e: CustomEvent) => {
            if (e.detail?.cart) {
                updateCartCountAndBroadcast(e.detail.cart);
            }
        };

        window.addEventListener('cart-updated', handleCustomCartUpdate as EventListener);

        return () => {
            window.removeEventListener('cart-updated', handleCustomCartUpdate as EventListener);
        };
    }, []);

    // Cleanup timeout on unmount
    useEffect(() => {
        return () => {
            // Clear the timeout on unmount to prevent memory leaks
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
                searchTimeoutRef.current = null; // Clean up reference
            }
        };
    }, []);

    // Function to force refresh cart count from server
    const refreshCartCount = async () => {
        if (isLoading) return; // Prevent concurrent requests

        setIsLoading(true);

        try {
            // Check if we have cached data that's still valid
            if (cartCacheRef.current) {
                const { data, timestamp } = cartCacheRef.current;
                const now = Date.now();

                if (now - timestamp < CACHE_DURATION) {
                    // Use cached data
                    const totalItems = data.lines.reduce((sum, line) => sum + line.qty, 0);
                    setCartCount(totalItems);
                    setCartToStorage(data);

                    // Dispatch cart update to other components
                    if ((window as any).dispatchCartUpdate) {
                        (window as any).dispatchCartUpdate(data);
                    }

                    return;
                }
            }

            // Create a timeout promise to prevent hanging requests
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Cart fetch timeout')), 5000); // 5 second timeout
            });

            const fetchPromise = fetch('/cart', {
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });

            // Race between the fetch and the timeout
            const response = await Promise.race([fetchPromise, timeoutPromise]);

            if (response.ok) {
                const cartData: Cart = await response.json();

                // Cache the successful response
                cartCacheRef.current = {
                    data: cartData,
                    timestamp: Date.now(),
                };

                const totalItems = cartData.lines.reduce((sum, line) => sum + line.qty, 0);
                setCartCount(totalItems);

                // Update localStorage to notify other tabs/windows
                setCartToStorage(cartData);

                // Dispatch cart update to other components
                if ((window as any).dispatchCartUpdate) {
                    (window as any).dispatchCartUpdate(cartData);
                }
            } else {
                // If cart API fails, try to use localStorage as fallback
                const storedCart = getCartFromStorage();
                if (storedCart) {
                    const totalItems = storedCart.lines.reduce((sum, line) => sum + line.qty, 0);
                    setCartCount(totalItems);
                } else {
                    setCartCount(0); // Ensure cart count is set to 0 if no data available
                }
            }
        } catch (error) {
            console.error('Failed to fetch cart count:', error);
            // Fallback to localStorage if API fails
            const storedCart = getCartFromStorage();
            if (storedCart) {
                const totalItems = storedCart.lines.reduce((sum, line) => sum + line.qty, 0);
                setCartCount(totalItems);
            } else {
                setCartCount(0); // Ensure cart count is set to 0 if no fallback available
            }
        } finally {
            setIsLoading(false);
        }
    };

    // Function to handle cart click and navigate to cart page
    const handleCartClick = async () => {
        // Refresh cart count before navigating to ensure latest data
        await refreshCartCount();
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
                    Accept: 'application/json',
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

        // Clear previous timeout IMMEDIATELY when new input occurs
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
            searchTimeoutRef.current = null; // Reset reference
        }

        // Set new timeout for debouncing only if there's a query
        if (value.trim()) {
            searchTimeoutRef.current = setTimeout(() => {
                fetchSuggestions(value);
            }, 300); // 300ms debounce
        } else {
            // If query is empty, clear suggestions immediately
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
        <div className="w-full bg-white py-2">
            {/* Desktop Layout */}
            <div className="mx-auto hidden max-w-[1440px] flex-row items-center px-4 lg:flex">
                {/* Logo Area */}
                <div
                    className="flex h-24 w-48 flex-shrink-0 cursor-pointer items-center justify-center gap-2.5 border border-gray-200 bg-[#FCFCF7] p-8"
                    onClick={() => router.get('/')}
                >
                    <img src="/logo/F5RA—Logo.svg" alt="F5RA Logo" className="max-h-full max-w-full object-contain" />
                </div>

                {/* Navigation Section */}
                <div className="flex flex-grow flex-col">
                    {/* Top Section - Search and User Options */}
                    <div className="flex flex-row items-center justify-between">
                        {/* Search Bar */}
                        <div className="relative flex-grow">
                            <div className="flex h-12 w-full flex-row items-center gap-1.75 border border-t border-gray-200 bg-[#FCFCF7] p-3">
                                <Search className="h-5.5 w-5.5 text-gray-500" />
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={handleSearchChange}
                                    onKeyDown={handleKeyDown}
                                    onFocus={handleInputFocus}
                                    onBlur={handleInputBlur}
                                    placeholder="検索"
                                    className="w-full border-none bg-transparent text-base text-[#0D0D0D] focus:outline-none"
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
                                        className="flex h-6 w-6 items-center justify-center rounded-full hover:bg-gray-200"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            className="h-4 w-4 text-gray-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                )}
                            </div>

                            {/* Autocomplete Dropdown */}
                            {showSuggestions && suggestions.length > 0 && (
                                <div className="absolute z-50 mt-1 max-h-96 w-full overflow-y-auto border border-gray-200 bg-white shadow-lg">
                                    <ul>
                                        {suggestions.map((suggestion, index) => (
                                            <li
                                                key={suggestion.id}
                                                onClick={() => handleSuggestionClick(suggestion)}
                                                className="cursor-pointer border-b border-gray-200 px-4 py-3 transition-colors duration-150 last:border-b-0 hover:bg-gray-50"
                                            >
                                                <div className="flex items-start justify-between">
                                                    <div className="flex min-w-0 flex-1 items-start gap-3">
                                                        {suggestion.image && (
                                                            <div className="h-12 w-12 flex-shrink-0">
                                                                <img
                                                                    src={suggestion.image}
                                                                    alt={suggestion.name}
                                                                    className="h-full w-full object-cover"
                                                                    loading="lazy"
                                                                />
                                                            </div>
                                                        )}
                                                        <div className="min-w-0 flex-1">
                                                            <div className="truncate text-sm font-semibold text-[#0D0D0D]">{suggestion.name}</div>
                                                            <div className="truncate text-xs text-[#444444]">
                                                                {suggestion.brand?.name} • {suggestion.category?.name}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="ml-4 flex flex-shrink-0 flex-col items-end justify-start">
                                                        {suggestion.price && (
                                                            <div className="mb-1 text-xs font-bold text-[#0D0D0D]">
                                                                ¥{suggestion.price.toLocaleString()}
                                                            </div>
                                                        )}
                                                        {suggestion.availability_status && (
                                                            <div
                                                                className={`mb-1 text-xs ${
                                                                    suggestion.availability_status === 'In Stock'
                                                                        ? 'text-[#0D0D0D]'
                                                                        : suggestion.availability_status === 'Low Stock'
                                                                          ? 'text-[#886600]'
                                                                          : 'text-[#880000]'
                                                                }`}
                                                            >
                                                                {suggestion.availability_status}
                                                            </div>
                                                        )}
                                                        {(suggestion.discount_percentage || suggestion.is_bestseller || suggestion.is_top_rated) && (
                                                            <div className="flex flex-wrap justify-end gap-1">
                                                                {suggestion.discount_percentage && (
                                                                    <span className="inline-flex items-center border border-red-200 bg-red-50 px-1.5 py-0.5 text-xs font-medium text-red-700">
                                                                        {suggestion.discount_percentage}% 割引
                                                                    </span>
                                                                )}
                                                                {suggestion.is_bestseller && (
                                                                    <span className="inline-flex items-center border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-xs font-medium text-blue-700">
                                                                        人気商品
                                                                    </span>
                                                                )}
                                                                {suggestion.is_top_rated && (
                                                                    <span className="inline-flex items-center border border-purple-200 bg-purple-50 px-1.5 py-0.5 text-xs font-medium text-purple-700">
                                                                        高評価
                                                                    </span>
                                                                )}
                                                            </div>
                                                        )}
                                                        {suggestion.rating !== undefined && suggestion.rating !== null && (
                                                            <div className="mt-1 flex items-center text-xs text-[#444444]">
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
                                <div className="absolute z-50 mt-1 flex w-full justify-center border border-gray-200 bg-white py-4 shadow-lg">
                                    <div className="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-black" />
                                </div>
                            )}
                        </div>

                        {/* Top Navigation Bar */}
                        <div className="relative flex h-12 w-48 flex-shrink-0 flex-row items-center justify-center gap-3 border border-t border-l border-gray-200 bg-[#FCFCF7] p-3">
                            {/* User Profile Button - Conditionally renders based on auth state */}
                            {isAuthenticated ? (
                                // If user is logged in, show profile dropdown with logout
                                <>
                                    <div
                                        className="flex cursor-pointer flex-col items-center justify-center"
                                        onClick={() => setIsProfileDropdownOpen(!isProfileDropdownOpen)}
                                    >
                                        <CircleUserRound className="h-4.5 w-4.5 text-gray-500" />
                                        <span className="mt-1 text-xs font-medium text-[#444444]">プロフィール</span>
                                    </div>
                                </>
                            ) : (
                                // If user is not logged in, show login/register buttons
                                <div
                                    className="flex cursor-pointer flex-col items-center justify-center"
                                    onClick={() =>
                                        router.visit('/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search))
                                    }
                                >
                                    <CircleUserRound className="h-4.5 w-4.5 text-gray-500" />
                                    <span className="mt-1 text-xs font-medium text-[#444444]">ログイン</span>
                                </div>
                            )}

                            {/* Favourite Button */}
                            <div
                                className="flex cursor-pointer flex-col items-center justify-center"
                                onClick={() => {
                                    setSearchQuery('');
                                    router.visit('/wishlist');
                                }}
                            >
                                <Heart className="h-4.5 w-4.5 text-gray-500" />
                                <span className="mt-1 text-xs font-medium text-[#444444]">お気に入り</span>
                            </div>

                            {/* Profile Dropdown */}
                            {isProfileDropdownOpen && (
                                <div className="absolute top-full left-0 z-50 mt-2 w-full border border-gray-200 bg-white shadow-lg">
                                    <button
                                        className="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                        onClick={() => {
                                            router.visit('/dashboard');
                                            setIsProfileDropdownOpen(false);
                                        }}
                                    >
                                        ダッシュボード
                                    </button>
                                    <button
                                        className="block flex w-full items-center px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100"
                                        onClick={() => {
                                            router.post(logout().url);
                                            setIsProfileDropdownOpen(false);
                                        }}
                                    >
                                        <LogOut className="mr-2 h-4 w-4" />
                                        ログアウト
                                    </button>
                                </div>
                            )}

                            {/* Click outside to close dropdown */}
                            {isProfileDropdownOpen && <div className="fixed inset-0 z-40" onClick={() => setIsProfileDropdownOpen(false)}></div>}
                        </div>
                    </div>

                    {/* Bottom Section - Main Navigation Menu */}
                    <div className="flex flex-col items-center gap-2.5 py-1">
                        <div className="flex w-full flex-row items-center justify-center gap-8">
                            <CustomNavLink href="/products" className="whitespace-nowrap" onClick={() => setSearchQuery('')}>
                                商品一覧
                            </CustomNavLink>
                            <CustomNavLink href="/fragrance-diagnosis" className="whitespace-nowrap" onClick={() => setSearchQuery('')}>
                                香り診断
                            </CustomNavLink>
                            <CustomNavLink href="/brand-introduction" className="whitespace-nowrap" onClick={() => setSearchQuery('')}>
                                ブランド紹介
                            </CustomNavLink>
                            <CustomNavLink href="/contact" className="whitespace-nowrap" onClick={() => setSearchQuery('')}>
                                お問い合わせ
                            </CustomNavLink>
                        </div>
                    </div>
                </div>

                {/* Cart Section - Updated to show dynamic count and be clickable */}
                <button
                    className="flex h-24 w-20 flex-shrink-0 cursor-pointer flex-row items-center justify-center gap-1.25 border border-gray-200 bg-[#FCFCF7] p-5"
                    onClick={handleCartClick}
                >
                    <ShoppingBag className="h-3.75 w-3.75 text-gray-500" />
                    <span className="text-xs font-semibold whitespace-nowrap text-black">({isLoading ? '...' : cartCount})</span>
                </button>
            </div>

            {/* Mobile/Tablet Layout */}
            <div className="flex flex-col lg:hidden">
                {/* Top Bar - Logo, Menu Button, Login, Wishlist, and Cart */}
                <div className="flex items-center justify-between px-4 py-3">
                    {/* Left side - Logo */}
                    <div className="flex cursor-pointer items-center justify-center border border-gray-200 p-2" onClick={() => router.get('/')}>
                        <div className="flex h-10 w-12 items-center justify-center rounded bg-gray-100">
                            <span className="text-xs font-bold">LOGO</span>
                        </div>
                    </div>

                    {/* Right side - Menu, User Profile, Wishlist, and Cart */}
                    <div className="flex items-center space-x-3">
                        {/* User Profile Button - Conditionally renders based on auth state */}
                        {isAuthenticated ? (
                            // If user is logged in, show profile dropdown with logout
                            <div className="relative">
                                <button
                                    className="flex cursor-pointer flex-col items-center"
                                    onClick={() => setIsMobileProfileDropdownOpen(!isMobileProfileDropdownOpen)}
                                >
                                    <CircleUserRound className="h-5 w-5 text-gray-500" />
                                    <span className="mt-1 text-xs text-[#444444]">プロフィール</span>
                                </button>

                                {/* Mobile Profile Dropdown */}
                                {isMobileProfileDropdownOpen && (
                                    <div className="absolute left-1/2 z-50 w-40 -translate-x-1/2 transform border border-gray-200 bg-white shadow-lg">
                                        <button
                                            className="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                            onClick={() => {
                                                router.visit('/dashboard');
                                                setIsMobileProfileDropdownOpen(false);
                                            }}
                                        >
                                            ダッシュボード
                                        </button>
                                        <button
                                            className="block flex w-full items-center px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100"
                                            onClick={() => {
                                                router.post(logout().url);
                                                setIsMobileProfileDropdownOpen(false);
                                            }}
                                        >
                                            <LogOut className="mr-2 h-4 w-4" />
                                            ログアウト
                                        </button>
                                    </div>
                                )}

                                {/* Click outside to close mobile dropdown */}
                                {isMobileProfileDropdownOpen && (
                                    <div className="fixed inset-0 z-40" onClick={() => setIsMobileProfileDropdownOpen(false)}></div>
                                )}
                            </div>
                        ) : (
                            // If user is not logged in, show login button
                            <button
                                className="flex cursor-pointer flex-col items-center"
                                onClick={() =>
                                    router.visit('/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search))
                                }
                            >
                                <CircleUserRound className="h-5 w-5 text-gray-700" />
                                <span className="mt-1 text-xs text-[#444444]">ログイン</span>
                            </button>
                        )}

                        {/* Wishlist Button */}
                        <button
                            className="flex cursor-pointer flex-col items-center"
                            onClick={() => {
                                setSearchQuery('');
                                router.visit('/wishlist');
                            }}
                        >
                            <Heart className="h-5 w-5 text-gray-500" />
                            <span className="mt-1 text-xs text-[#444444]">お気に入り</span>
                        </button>

                        {/* Cart - Updated to show dynamic count and be clickable */}
                        <button
                            className="flex cursor-pointer flex-col items-center"
                            onClick={() => {
                                setSearchQuery('');
                                handleCartClick();
                            }}
                        >
                            <ShoppingBag className="h-5 w-5 text-gray-500" />
                            <span className="mt-1 text-xs text-gray-500">({isLoading ? '...' : cartCount})</span>
                        </button>

                        {/* Hamburger Menu Button */}
                        <button
                            onClick={() => {
                                setSearchQuery('');
                                setIsMobileMenuOpen(!isMobileMenuOpen);
                            }}
                            className="ml-2 cursor-pointer p-1"
                        >
                            <Menu className="h-6 w-6 text-gray-700" />
                        </button>
                    </div>
                </div>

                {/* Mobile Menu - Only visible when menu is open */}
                {isMobileMenuOpen && (
                    <div className="bg-opacity-50 fixed inset-0 z-50 flex bg-black">
                        <div className="ml-auto flex h-full w-4/5 max-w-sm flex-col bg-white p-4" onClick={(e) => e.stopPropagation()}>
                            {/* Menu Header */}
                            <div className="mb-6 flex items-center justify-between p-2">
                                <div
                                    className="flex cursor-pointer items-center justify-center border border-gray-200 p-2"
                                    onClick={() => {
                                        router.get('/');
                                        setIsMobileMenuOpen(false);
                                    }}
                                >
                                    <div className="flex h-10 w-12 items-center justify-center rounded bg-gray-100">
                                        <span className="text-xs font-bold">LOGO</span>
                                    </div>
                                </div>
                                <button
                                    onClick={() => {
                                        setSearchQuery('');
                                        setIsMobileMenuOpen(false);
                                    }}
                                    className="cursor-pointer p-2"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        className="h-6 w-6 text-gray-700"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            {/* Search Bar in Menu */}
                            <div className="relative w-full">
                                <div className="mb-4 flex w-full flex-row items-center gap-2 border border-gray-200 bg-[#FCFCF7] p-3">
                                    <Search className="h-4 w-4 text-[#0D0D0D] opacity-50" />
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
                                        className="w-full border-none bg-transparent text-sm text-[#0D0D0D] focus:outline-none"
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
                                            className="flex h-5 w-5 items-center justify-center rounded-full hover:bg-gray-200"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                className="h-3 w-3 text-gray-500"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    )}
                                </div>

                                {/* Mobile Autocomplete Dropdown */}
                                {showSuggestions && suggestions.length > 0 && (
                                    <div className="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto border border-gray-200 bg-white shadow-lg">
                                        <ul>
                                            {suggestions.map((suggestion, index) => (
                                                <li
                                                    key={suggestion.id}
                                                    onClick={() => {
                                                        handleSuggestionClick(suggestion);
                                                        setIsMobileMenuOpen(false);
                                                    }}
                                                    className="cursor-pointer border-b border-gray-200 px-4 py-3 transition-colors duration-150 last:border-b-0 hover:bg-gray-50"
                                                >
                                                    <div className="flex items-start justify-between">
                                                        <div className="flex min-w-0 flex-1 items-start gap-2">
                                                            {suggestion.image && (
                                                                <div className="h-10 w-10 flex-shrink-0">
                                                                    <img
                                                                        src={suggestion.image}
                                                                        alt={suggestion.name}
                                                                        className="h-full w-full object-cover"
                                                                        loading="lazy"
                                                                    />
                                                                </div>
                                                            )}
                                                            <div className="min-w-0 flex-1">
                                                                <div className="truncate text-xs font-semibold text-[#0D0D0D]">{suggestion.name}</div>
                                                                <div className="truncate text-xs text-[#444444]">
                                                                    {suggestion.brand?.name} • {suggestion.category?.name}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="ml-2 flex flex-shrink-0 flex-col items-end justify-start">
                                                            {suggestion.price && (
                                                                <div className="mb-1 text-xs font-bold text-[#0D0D0D]">
                                                                    ¥{suggestion.price.toLocaleString()}
                                                                </div>
                                                            )}
                                                            {suggestion.availability_status && (
                                                                <div
                                                                    className={`mb-1 text-xs ${
                                                                        suggestion.availability_status === 'In Stock'
                                                                            ? 'text-[#0D0D0D]'
                                                                            : suggestion.availability_status === 'Low Stock'
                                                                              ? 'text-[#886600]'
                                                                              : 'text-[#880000]'
                                                                    }`}
                                                                >
                                                                    {suggestion.availability_status}
                                                                </div>
                                                            )}
                                                            {(suggestion.discount_percentage ||
                                                                suggestion.is_bestseller ||
                                                                suggestion.is_top_rated) && (
                                                                <div className="flex flex-wrap justify-end gap-1">
                                                                    {suggestion.discount_percentage && (
                                                                        <span className="inline-flex items-center border border-red-200 bg-red-50 px-1 py-0.5 text-xs font-medium text-red-700">
                                                                            {suggestion.discount_percentage}% 割引
                                                                        </span>
                                                                    )}
                                                                    {suggestion.is_bestseller && (
                                                                        <span className="inline-flex items-center border border-blue-200 bg-blue-50 px-1 py-0.5 text-xs font-medium text-blue-700">
                                                                            人気商品
                                                                        </span>
                                                                    )}
                                                                    {suggestion.is_top_rated && (
                                                                        <span className="inline-flex items-center border border-purple-200 bg-purple-50 px-1 py-0.5 text-xs font-medium text-purple-700">
                                                                            高評価
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            )}
                                                            {suggestion.rating !== undefined && suggestion.rating !== null && (
                                                                <div className="mt-1 flex items-center text-xs text-[#444444]">
                                                                    <span className="mr-1">⭐</span>
                                                                    <span className="font-medium">{suggestion.rating}</span>
                                                                    {suggestion.review_count && (
                                                                        <span className="ml-1">({suggestion.review_count})</span>
                                                                    )}
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
                                    <div className="absolute z-50 mt-1 flex w-full justify-center border border-gray-200 bg-white py-4 shadow-lg">
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-black" />
                                    </div>
                                )}
                            </div>

                            {/* Main Navigation Menu */}
                            <div className="flex flex-1 flex-col items-center gap-4 py-4">
                                <CustomNavLink
                                    href="/products"
                                    className="w-full text-center whitespace-nowrap"
                                    onClick={() => {
                                        setSearchQuery('');
                                        setIsMobileMenuOpen(false);
                                    }}
                                >
                                    商品一覧
                                </CustomNavLink>
                                <CustomNavLink
                                    href="/fragrance-diagnosis"
                                    className="w-full text-center whitespace-nowrap"
                                    onClick={() => {
                                        setSearchQuery('');
                                        setIsMobileMenuOpen(false);
                                    }}
                                >
                                    香り診断
                                </CustomNavLink>
                                <CustomNavLink
                                    href="/brand-introduction"
                                    className="w-full text-center whitespace-nowrap"
                                    onClick={() => {
                                        setSearchQuery('');
                                        setIsMobileMenuOpen(false);
                                    }}
                                >
                                    ブランド紹介
                                </CustomNavLink>
                                <CustomNavLink
                                    href="/contact"
                                    className="w-full text-center whitespace-nowrap"
                                    onClick={() => {
                                        setSearchQuery('');
                                        setIsMobileMenuOpen(false);
                                    }}
                                >
                                    お問い合わせ
                                </CustomNavLink>
                            </div>

                            {/* Additional Menu Options */}
                            <div className="border-t border-gray-200 pt-4">
                                <div className="flex justify-around">
                                    {isAuthenticated ? (
                                        // If user is logged in, show profile dropdown with logout
                                        <div className="relative">
                                            <button
                                                className="flex cursor-pointer flex-col items-center"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    setIsMobileMenuProfileDropdownOpen(!isMobileMenuProfileDropdownOpen);
                                                }}
                                            >
                                                <CircleUserRound className="h-6 w-6 text-gray-500" />
                                                <span className="mt-1 text-xs text-[#444444]">プロフィール</span>
                                            </button>

                                            {/* Mobile Menu Profile Dropdown */}
                                            {isMobileMenuProfileDropdownOpen && (
                                                <div className="absolute bottom-full left-0 z-50 mb-2 w-40 border border-gray-200 bg-white shadow-lg">
                                                    <button
                                                        className="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                                        onClick={() => {
                                                            router.visit('/dashboard');
                                                            setIsMobileMenuOpen(false);
                                                            setIsMobileMenuProfileDropdownOpen(false);
                                                        }}
                                                    >
                                                        ダッシュボード
                                                    </button>
                                                    <button
                                                        className="block flex w-full items-center px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100"
                                                        onClick={() => {
                                                            router.post(logout().url);
                                                            setIsMobileMenuOpen(false);
                                                            setIsMobileMenuProfileDropdownOpen(false);
                                                        }}
                                                    >
                                                        <LogOut className="mr-2 h-4 w-4" />
                                                        ログアウト
                                                    </button>
                                                </div>
                                            )}

                                            {/* Click outside to close mobile menu dropdown */}
                                            {isMobileMenuProfileDropdownOpen && (
                                                <div
                                                    className="fixed inset-0 z-40"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setIsMobileMenuProfileDropdownOpen(false);
                                                    }}
                                                ></div>
                                            )}
                                        </div>
                                    ) : (
                                        // If user is not logged in, show login button
                                        <button
                                            className="flex cursor-pointer flex-col items-center"
                                            onClick={() => {
                                                router.visit(
                                                    '/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search),
                                                );
                                                setIsMobileMenuOpen(false);
                                            }}
                                        >
                                            <CircleUserRound className="h-6 w-6 text-gray-500" />
                                            <span className="mt-1 text-xs text-[#444444]">ログイン</span>
                                        </button>
                                    )}
                                    <button
                                        className="flex cursor-pointer flex-col items-center"
                                        onClick={() => {
                                            setSearchQuery('');
                                            router.visit('/wishlist');
                                            setIsMobileMenuOpen(false);
                                        }}
                                    >
                                        <Heart className="h-6 w-6 text-gray-500" />
                                        <span className="mt-1 text-xs text-[#444444]">お気に入り</span>
                                    </button>
                                    {/* Cart in mobile menu - Updated to show dynamic count and be clickable */}
                                    <button
                                        className="flex cursor-pointer flex-col items-center"
                                        onClick={() => {
                                            setSearchQuery('');
                                            handleCartClick();
                                            setIsMobileMenuOpen(false);
                                        }}
                                    >
                                        <ShoppingBag className="h-6 w-6 text-gray-500" />
                                        <span className="mt-1 text-xs text-gray-500">({isLoading ? '...' : cartCount})</span>
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

interface CustomNavLinkProps {
    children: React.ReactNode;
    className?: string;
    href: string;
    onClick?: () => void;
}

function CustomNavLink({ children, className, href, onClick }: CustomNavLinkProps) {
    return (
        <Link
            href={href}
            onClick={onClick}
            className={cn(
                'flex h-10 cursor-pointer flex-row items-center justify-center gap-2 border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700',
                className,
            )}
        >
            {children}
        </Link>
    );
}

// Export types for other components to use
export { CartLine as CartLineType, Cart as CartType };

// Ensure the HomeNavigation component is properly exported
// export function HomeNavigation() { ... } is already present above
