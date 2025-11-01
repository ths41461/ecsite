import { HomeNavigation } from '@/components/homeNavigation';
import Hero from '@/Components/Hero/Hero';
import RecommendedSection from '@/Components/RecommendedSection';

export default function Homepage() {
  const dummyProducts = [
    {
      productImageSrc: "/perfume-images/perfume-1.png",
      category: "ブランド名",
      productName: "商品名 1",
      price: "￥29,00",
      showRatingIcon: true,
      showGenderIcon: true,
      showWishlistIcon: true,
    },
    {
      productImageSrc: "https://via.placeholder.com/310x263",
      category: "ブランド名",
      productName: "商品名 2",
      price: "￥35,00",
      showRatingIcon: true,
      showGenderIcon: false,
      showWishlistIcon: true,
    },
    {
      productImageSrc: "https://via.placeholder.com/310x263",
      category: "ブランド名",
      productName: "商品名 3",
      price: "￥25,00",
      showRatingIcon: false,
      showGenderIcon: true,
      showWishlistIcon: false,
    },
    {
      productImageSrc: "https://via.placeholder.com/310x263",
      category: "ブランド名",
      productName: "商品名 4",
      price: "￥40,00",
      showRatingIcon: true,
      showGenderIcon: true,
      showWishlistIcon: true,
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      <HomeNavigation />
      <Hero />
      <main>
        <RecommendedSection products={dummyProducts} className="mt-[60px]" />
      </main>
    </div>
  );
}