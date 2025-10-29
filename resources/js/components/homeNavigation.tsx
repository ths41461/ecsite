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
  const hasFetchedInitialCart = useRef(false);
  const isUpdatingFromStorage = useRef(false);

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

  // Function to handle cart click and navigate to cart page
  const handleCartClick = () => {
    router.get('/cart');
  };

  return (
    <div className="w-full bg-white px-4 py-2">
      {/* Desktop Layout */}
      <div className="hidden lg:flex flex-row items-center max-w-[1440px] mx-auto">
        {/* Logo Area */}
        <div className="flex items-center justify-center gap-2.5 p-8 w-[200px] h-[100px] border border-[#888888]">
          <div className="w-[200px] h-[65.19px] bg-gray-100 rounded flex items-center justify-center">
            <span className="text-lg font-bold">LOGO</span>
          </div>
        </div>

        {/* Navigation Section */}
        <div className="flex flex-col w-[1128px]">
          {/* Top Section - Search and User Options */}
          <div className="flex flex-row items-center justify-between">
            {/* Search Bar */}
            <div className="flex flex-row items-center gap-1.75 p-3 w-[934px] h-[50px] bg-[#FCFCF7] border border-l border-r border-t border-[#888888]">
              <Search className="w-5.5 h-5.5 text-[#0D0D0D]" />
              <span className="text-[#0D0D0D] text-base truncate max-w-[800px]">検索</span>
            </div>

            {/* Top Navigation Bar */}
            <div className="flex flex-row items-center justify-center gap-3 p-3 w-[194px] h-[50px] bg-[#FCFCF7] border border-l border-r border-t border-[#888888]">
              {/* Login Button */}
              <div className="flex flex-row items-center justify-center gap-2">
                <User className="w-4.5 h-4.5 text-gray-700" />
                <span className="text-xs font-medium text-[#444444] whitespace-nowrap">ログイン</span>
              </div>

              {/* Favourite Button */}
              <div className="flex flex-row items-center justify-center gap-2">
                <Heart className="w-4.5 h-4.5 text-gray-700" />
                <span className="text-xs font-medium text-[#444444] whitespace-nowrap">お気に入り</span>
              </div>
            </div>
          </div>

          {/* Bottom Section - Main Navigation Menu */}
          <div className="flex flex-col items-center gap-2.5 px-[312px] py-[5px]">
            <div className="flex flex-row items-center justify-center gap-8 w-full">
              <CustomNavButton className="whitespace-nowrap">商品一覧</CustomNavButton>
              <CustomNavButton className="whitespace-nowrap">香り診断</CustomNavButton>
              <CustomNavButton className="whitespace-nowrap">ブランド紹介</CustomNavButton>
              <CustomNavButton className="whitespace-nowrap">お問い合わせ</CustomNavButton>
            </div>
          </div>
        </div>

        {/* Cart Section - Updated to show dynamic count and be clickable */}
        <button 
          className="flex flex-row items-center justify-center gap-1.25 p-5 w-[80px] h-[100px] bg-[#FCFCF7] border border-[#888888]"
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
          <div className="flex items-center justify-center p-2 border border-[#888888]">
            <div className="w-12 h-10 bg-gray-100 rounded flex items-center justify-center">
              <span className="text-xs font-bold">LOGO</span>
            </div>
          </div>

          {/* Right side - Menu, Login, Wishlist, and Cart */}
          <div className="flex items-center space-x-3">
            {/* Login Button */}
            <button className="flex flex-col items-center">
              <User className="w-5 h-5 text-gray-700" />
              <span className="text-[0.6rem] text-[#444444] mt-1">ログイン</span>
            </button>

            {/* Wishlist Button */}
            <button className="flex flex-col items-center">
              <Heart className="w-5 h-5 text-gray-700" />
              <span className="text-[0.6rem] text-[#444444] mt-1">お気に入り</span>
            </button>

            {/* Cart - Updated to show dynamic count and be clickable */}
            <button 
              className="flex flex-col items-center"
              onClick={handleCartClick}
            >
              <ShoppingCart className="w-5 h-5 text-gray-700" />
              <span className="text-[0.6rem] text-black mt-1">
                ({isLoading ? '...' : cartCount})
              </span>
            </button>

            {/* Hamburger Menu Button */}
            <button 
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="ml-2 p-1"
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
                <div className="flex items-center justify-center p-2 border border-[#888888]">
                  <div className="w-12 h-10 bg-gray-100 rounded flex items-center justify-center">
                    <span className="text-xs font-bold">LOGO</span>
                  </div>
                </div>
                <button 
                  onClick={() => setIsMobileMenuOpen(false)}
                  className="p-2"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              {/* Search Bar in Menu */}
              <div className="flex flex-row items-center gap-2 p-3 mb-4 bg-[#FCFCF7] border border-[#888888] w-full">
                <Search className="w-4 h-4 text-[#0D0D0D] opacity-50" />
                <span className="text-[#0D0D0D] text-sm whitespace-nowrap">検索</span>
              </div>

              {/* Main Navigation Menu */}
              <div className="flex flex-col items-center gap-4 py-4 flex-1">
                <CustomNavButton className="w-full text-center whitespace-nowrap">商品一覧</CustomNavButton>
                <CustomNavButton className="w-full text-center whitespace-nowrap">香り診断</CustomNavButton>
                <CustomNavButton className="w-full text-center whitespace-nowrap">ブランド紹介</CustomNavButton>
                <CustomNavButton className="w-full text-center whitespace-nowrap">お問い合わせ</CustomNavButton>
              </div>

              {/* Additional Menu Options */}
              <div className="pt-4 border-t border-gray-200">
                <div className="flex justify-around">
                  <button className="flex flex-col items-center">
                    <User className="w-6 h-6 text-gray-700" />
                    <span className="text-xs text-[#444444] mt-1">ログイン</span>
                  </button>
                  <button className="flex flex-col items-center">
                    <Heart className="w-6 h-6 text-gray-700" />
                    <span className="text-xs text-[#444444] mt-1">お気に入り</span>
                  </button>
                  {/* Cart in mobile menu - Updated to show dynamic count and be clickable */}
                  <button 
                    className="flex flex-col items-center"
                    onClick={() => {
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

function CustomNavButton({ children, className }: CustomNavButtonProps) {
  return (
    <button
      className={cn(
        "flex flex-row items-center justify-center gap-2 h-10 border-2 border-[#888888] text-sm font-medium text-[#444444] px-4 py-2.5",
        className
      )}
    >
      {children}
    </button>
  );
}

// Export types for other components to use
export { Cart as CartType, CartLine as CartLineType };