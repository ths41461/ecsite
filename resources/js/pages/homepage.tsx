import { HomeNavigation } from '@/components/homeNavigation';
import RecommendedSection from '@/Components/RecommendedSection';
import Footer from '../Components/Footer';
import Hero from '../components/Hero/Hero';
import NewsletterSection from '../Components/NewsletterSection';

import RankingSection from '../Components/RankingSection';

interface ProductData {
    id: number;
    productImageSrc: string;
    category: string;
    productName: string;
    price: string;
    slug: string;
    rank?: number;
    score?: number;
}

interface HomepageProps {
    recommendedProducts?: ProductData[];
}

export default function Homepage({ recommendedProducts = [] }: HomepageProps) {
    return (
        <div className="min-h-screen bg-gray-50">
            <HomeNavigation />
            <Hero />
            <main>
                <RecommendedSection products={recommendedProducts} className="mt-15" />
                <RankingSection products={recommendedProducts} />
                <NewsletterSection />
            </main>
            <Footer />
        </div>
    );
}