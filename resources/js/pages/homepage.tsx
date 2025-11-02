import { HomeNavigation } from '@/components/homeNavigation';
import RecommendedSection from '@/Components/RecommendedSection';
import Hero from '../components/Hero/Hero';
import RankingSection from '../Components/RankingSection';
import NewsletterSection from '../Components/NewsletterSection';

export default function Homepage() {
    return (
        <div className="min-h-screen bg-gray-50">
            <HomeNavigation />
            <Hero />
            <main>
                <RecommendedSection className="mt-15" />
                <RankingSection />
                <NewsletterSection />
            </main>
        </div>
    );
}
