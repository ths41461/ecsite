import { Head } from '@inertiajs/react';
import { HomeNavigation } from '@/components/homeNavigation';

export default function BrandIntroduction() {
  const brands = [
    {
      id: 1,
      name: 'Sakura Botanica',
      description: '日本の桜をモチーフにした自然派ブランドです。伝統的な和の香りと現代のテクノロジーを融合させています。',
      image: '/images/brand-sakura.jpg',
      category: 'ナチュラル系'
    },
    {
      id: 2,
      name: 'Tokyo Modern',
      description: '東京の都会的なライフスタイルに合わせた洗練された香りを提案するブランドです。',
      image: '/images/brand-tokyo.jpg',
      category: 'モダン系'
    },
    {
      id: 3,
      name: 'Kyoto Heritage',
      description: '京都の伝統文化に根ざしたクラシックでエレガントな香りを提供するブランドです。',
      image: '/images/brand-kyoto.jpg',
      category: 'クラシック系'
    },
    {
      id: 4,
      name: 'Osaka Lifestyle',
      description: '大阪のエネルギッシュな文化からインスパイアされた活力ある香りブランドです。',
      image: '/images/brand-osaka.jpg',
      category: 'エナジッシュ系'
    }
  ];

  return (
    <div className="min-h-screen bg-white">
      <Head title="ブランド紹介" />
      
      {/* Global Navigation */}
      <HomeNavigation />
      
      <div className="max-w-6xl mx-auto px-4 py-8">
        {/* Header */}
        <div className="text-center mb-12">
          <h1 className="text-3xl font-bold text-[#0D0D0D] mb-4">ブランド紹介</h1>
          <p className="text-[#444444] max-w-2xl mx-auto">
            当サイトで取り扱うブランドをご紹介します。
            あなたに合った香りのブランドを見つけましょう。
          </p>
        </div>

        {/* Brands Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8">
          {brands.map((brand) => (
            <div 
              key={brand.id}
              className="border border-[#888888] bg-[#FCFCF7] overflow-hidden transition-all duration-300 hover:shadow-md"
            >
              <div className="p-6">
                <div className="flex items-start gap-4">
                  <div className="flex-shrink-0 w-16 h-16 bg-gray-200 rounded-sm flex items-center justify-center">
                    <span className="text-[#0D0D0D] font-medium">B{brand.id}</span>
                  </div>
                  <div className="flex-1">
                    <h2 className="text-xl font-medium text-[#0D0D0D] mb-1">{brand.name}</h2>
                    <span className="text-xs text-[#444444] border border-[#888888] px-2 py-1">
                      {brand.category}
                    </span>
                  </div>
                </div>
                
                <p className="mt-4 text-[#444444] leading-relaxed">
                  {brand.description}
                </p>
                
                <div className="mt-6">
                  <button className="text-sm text-[#0D0D0D] border border-[#888888] px-4 py-2 hover:bg-gray-50 transition-colors duration-200">
                    ブランド詳細を見る
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Additional Info */}
        <div className="mt-12 bg-[#FCFCF7] border border-[#888888] p-6">
          <h3 className="text-lg font-medium text-[#0D0D0D] mb-3">ブランド選びのヒント</h3>
          <p className="text-[#444444] mb-4">
            ブランドごとに特徴や香調が異なります。自分のライフスタイルや好みの香りに合ったブランドを選ぶことが大切です。
          </p>
          <ul className="text-[#444444] space-y-2">
            <li className="flex items-start">
              <span className="mr-2">・</span>
              <span>普段のスタイルに合ったブランド選びをしましょう</span>
            </li>
            <li className="flex items-start">
              <span className="mr-2">・</span>
              <span>使用シーンに応じて香りを選ぶのもポイントです</span>
            </li>
            <li className="flex items-start">
              <span className="mr-2">・</span>
              <span>サンプルからお気に入りを見つけるのもおすすめです</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  );
}