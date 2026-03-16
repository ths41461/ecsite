import { ChatContainer } from '@/components/AIChat';
import { HomeNavigation } from '@/components/homeNavigation';
import { Head, Link } from '@inertiajs/react';
import { Heart, MessageCircle, RefreshCw, Share2 } from 'lucide-react';
import { useState } from 'react';

type Profile = {
    type: string;
    name: string;
    description: string;
    color: string;
    notes: {
        top: string[];
        middle: string[];
        base: string[];
    };
    occasions: string[];
    season: string;
};

type Recommendation = {
    id: number;
    slug?: string;
    name: string;
    brand: string;
    category: string;
    price: number;
    imageUrl?: string;
    notes: {
        top?: string;
        middle?: string;
        base?: string;
    };
    matchScore: number;
    reason: string;
};

type QuizData = {
    personality: string;
    vibe: string;
    occasion: string[];
    style: string;
    budget: number;
    experience: string;
    season: string;
};

type Props = {
    quizData: QuizData;
    profile: Profile;
    recommendations: Recommendation[];
    sessionId: string;
};

function yen(n: number) {
    return `¥${n.toLocaleString()}`;
}

const renderMatchScore = (score: number) => {
    const percentage = Math.round(score);
    return (
        <div className="flex items-center gap-1">
            <div className="h-2 w-20 rounded-full bg-gray-200">
                <div className="h-2 rounded-full bg-[#EAB308]" style={{ width: `${percentage}%` }} />
            </div>
            <span className="text-xs text-gray-500">{percentage}%</span>
        </div>
    );
};

const renderStarRating = (score: number) => {
    const stars = Math.round(score / 20);
    return (
        <div className="flex items-center gap-0.5">
            {Array.from({ length: 5 }, (_, i) => (
                <svg
                    key={i}
                    width="14"
                    height="14"
                    viewBox="0 0 20 20"
                    fill={i < stars ? '#616161' : 'none'}
                    stroke="#616161"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path d="M10 1.667L12.667 7.5L18.333 8.333L14 12.5L15.333 18.333L10 15.833L4.667 18.333L6 12.5L1.667 8.333L7.333 7.5L10 1.667Z" />
                </svg>
            ))}
        </div>
    );
};

export default function FragranceDiagnosisResults({ quizData, profile, recommendations, sessionId }: Props) {
    const [priceFilter, setPriceFilter] = useState<string>('all');
    const [showChat, setShowChat] = useState(false);

    const priceFilters = [
        { key: 'all', label: 'すべて' },
        { key: '3000', label: '¥3,000以下' },
        { key: '5000', label: '¥3,000-5,000' },
        { key: '8000', label: '¥5,000-8,000' },
        { key: '10000', label: '¥8,000以上' },
    ];

    const filteredRecommendations = recommendations.filter((rec) => {
        if (priceFilter === 'all') return true;
        if (priceFilter === '3000') return rec.price <= 3000;
        if (priceFilter === '5000') return rec.price > 3000 && rec.price <= 5000;
        if (priceFilter === '8000') return rec.price > 5000 && rec.price <= 8000;
        if (priceFilter === '10000') return rec.price > 8000;
        return true;
    });

    return (
        <div className="min-h-screen bg-white">
            <Head title="診断結果 - 香り診断" />

            <HomeNavigation />

            <div className="mx-auto max-w-6xl px-4 py-8" data-testid="results-container">
                <div className="mb-8 text-center">
                    <h1 className="mb-2 text-3xl font-bold text-[#0D0D0D]">診断結果</h1>
                    <p className="text-[#444444]">あなたにぴったりの香りが見つかりました</p>
                </div>

                <div className="mb-8 rounded-sm border border-[#888888] p-6" data-testid="scent-profile-card">
                    <div className="mb-4 flex items-start justify-between">
                        <div>
                            <p className="mb-1 text-sm text-[#444444]">あなたの香りプロフィール</p>
                            <h2 className="text-2xl font-bold text-[#0D0D0D]" data-testid="profile-type">
                                {profile.name}
                            </h2>
                        </div>
                        <div className="flex gap-2">
                            <button className="rounded-full p-2 hover:bg-gray-100">
                                <Heart className="h-5 w-5 text-gray-400" />
                            </button>
                            <button className="rounded-full p-2 hover:bg-gray-100">
                                <Share2 className="h-5 w-5 text-gray-400" />
                            </button>
                        </div>
                    </div>

                    <p className="mb-6 text-[#444444]">{profile.description}</p>

                    <div className="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div className="rounded-sm bg-gray-50 p-3">
                            <p className="mb-1 text-xs text-gray-500">トップノート</p>
                            <p className="text-sm font-medium text-[#0D0D0D]">{profile.notes.top.join('、')}</p>
                        </div>
                        <div className="rounded-sm bg-gray-50 p-3">
                            <p className="mb-1 text-xs text-gray-500">ミドルノート</p>
                            <p className="text-sm font-medium text-[#0D0D0D]">{profile.notes.middle.join('、')}</p>
                        </div>
                        <div className="rounded-sm bg-gray-50 p-3">
                            <p className="mb-1 text-xs text-gray-500">ベースノート</p>
                            <p className="text-sm font-medium text-[#0D0D0D]">{profile.notes.base.join('、')}</p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {profile.occasions.map((occasion) => (
                            <span key={occasion} className="rounded-full border border-[#888888] px-3 py-1 text-xs text-[#444444]">
                                {occasion}
                            </span>
                        ))}
                        <span className="rounded-full border border-[#888888] px-3 py-1 text-xs text-[#444444]">{profile.season}</span>
                    </div>
                </div>

                <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <h2 className="text-xl font-bold text-[#0D0D0D]" data-testid="product-recommendations">
                        あなたにおすすめの香水
                    </h2>
                    <div className="flex gap-2" data-testid="price-filter">
                        {priceFilters.map((filter) => (
                            <button
                                key={filter.key}
                                onClick={() => setPriceFilter(filter.key)}
                                className={`rounded-full px-3 py-1 text-sm transition-colors ${
                                    priceFilter === filter.key ? 'bg-[#0D0D0D] text-white' : 'border border-[#888888] text-[#444444] hover:bg-gray-50'
                                }`}
                            >
                                {filter.label}
                            </button>
                        ))}
                    </div>
                </div>

                {filteredRecommendations.length === 0 ? (
                    <div className="rounded-sm border border-[#888888] p-8 text-center">
                        <p className="text-[#444444]">この価格帯に該当する商品がありません</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {filteredRecommendations.map((product) => (
                            <div
                                key={product.id}
                                className="flex flex-col rounded-sm border border-gray-200 bg-white shadow-sm"
                                data-testid="product-card"
                            >
                                <Link
                                    href={`/products/${product.slug || product.id}`}
                                    className="flex h-48 items-center justify-center bg-[#FAF7EF] p-4"
                                >
                                    {product.imageUrl ? (
                                        <img src={product.imageUrl} alt={product.name} className="h-full w-full object-cover" />
                                    ) : (
                                        <div className="flex h-full w-full items-center justify-center text-gray-400">
                                            <span className="text-4xl">🌸</span>
                                        </div>
                                    )}
                                </Link>

                                <div className="flex flex-1 flex-col p-4">
                                    <p className="mb-1 text-xs text-red-600">{product.brand}</p>
                                    <Link href={`/products/${product.slug || product.id}`}>
                                        <h3 className="mb-2 font-semibold text-[#0D0D0D] hover:text-gray-600">{product.name}</h3>
                                    </Link>

                                    <div className="mb-2 flex items-center justify-between">
                                        {renderMatchScore(product.matchScore)}
                                        {renderStarRating(product.matchScore)}
                                    </div>

                                    <p className="mb-3 flex-1 text-xs text-[#444444]">{product.reason}</p>

                                    <div className="flex items-center justify-between">
                                        <span className="font-bold text-[#0D0D0D]">{yen(product.price)}</span>
                                        <Link
                                            href={`/products/${product.slug || product.id}`}
                                            className="rounded bg-[#EAB308] px-4 py-2 text-sm text-white hover:bg-yellow-600"
                                        >
                                            詳細を見る
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                <div className="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <button
                        onClick={() => setShowChat(!showChat)}
                        className="flex items-center gap-2 rounded-full bg-[#0D0D0D] px-6 py-3 text-white hover:bg-gray-800"
                        data-testid="chat-button"
                    >
                        <MessageCircle className="h-5 w-5" />
                        AIと相談
                    </button>

                    <Link
                        href="/fragrance-diagnosis"
                        className="flex items-center gap-2 rounded-full border border-[#888888] px-6 py-3 text-[#0D0D0D] hover:bg-gray-50"
                        data-testid="back-to-quiz"
                    >
                        <RefreshCw className="h-5 w-5" />
                        診断をやり直す
                    </Link>
                </div>
            </div>

            <ChatContainer sessionId={sessionId} isOpen={showChat} onClose={() => setShowChat(false)} />
        </div>
    );
}
