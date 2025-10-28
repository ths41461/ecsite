import { Search, User, Heart, ShoppingCart } from 'lucide-react';
import { cn } from '@/lib/utils';

export function HomeNavigation() {
  return (
    <div className="flex flex-row items-center w-full max-w-[1440px] mx-auto bg-white px-4 py-2">
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
            <span className="text-[#0D0D0D] text-base">検索</span>
          </div>

          {/* Top Navigation Bar */}
          <div className="flex flex-row items-center justify-center gap-3 p-3 w-[194px] h-[50px] bg-[#FCFCF7] border border-l border-r border-t border-[#888888]">
            {/* Login Button */}
            <div className="flex flex-row items-center justify-center gap-2">
              <User className="w-4.5 h-4.5 text-gray-700" />
              <span className="text-xs font-medium text-[#444444]">ログイン</span>
            </div>

            {/* Favourite Button */}
            <div className="flex flex-row items-center justify-center gap-2">
              <Heart className="w-4.5 h-4.5 text-gray-700" />
              <span className="text-xs font-medium text-[#444444]">お気に入り</span>
            </div>
          </div>
        </div>

        {/* Bottom Section - Main Navigation Menu */}
        <div className="flex flex-col items-center gap-2.5 px-[312px] py-[5px]">
          <div className="flex flex-row items-center justify-center gap-8 w-full">
            <CustomNavButton>商品一覧</CustomNavButton>
            <CustomNavButton>香り診断</CustomNavButton>
            <CustomNavButton>ブランド紹介</CustomNavButton>
            <CustomNavButton>お問い合わせ</CustomNavButton>
          </div>
        </div>
      </div>

      {/* Cart Section */}
      <div className="flex flex-row items-center justify-center gap-1.25 p-5 w-[80px] h-[100px] bg-[#FCFCF7] border border-[#888888]">
        <ShoppingCart className="w-3.75 h-3.75 text-gray-700" />
        <span className="text-xs font-semibold text-black">(0)</span>
      </div>
    </div>
  );
}

interface CustomNavButtonProps {
  children: React.ReactNode;
}

function CustomNavButton({ children }: CustomNavButtonProps) {
  return (
    <button
      className={cn(
        "flex flex-row items-center justify-center gap-2 h-10 border-2 border-[#888888] text-sm font-medium text-[#444444] px-4 py-2.5"
      )}
    >
      {children}
    </button>
  );
}