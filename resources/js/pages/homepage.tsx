import { HomeNavigation } from '@/components/homeNavigation';
import RecommendedSection from '@/Components/RecommendedSection';
import Footer from '../Components/Footer';
import Hero from '../components/Hero/Hero';
import NewsletterSection from '../Components/NewsletterSection';

import RankingSection from '../Components/RankingSection';

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
            <Footer />
        </div>
    );
}
