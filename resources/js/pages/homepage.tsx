import { HomeNavigation } from '@/components/homeNavigation';
import RecommendedSection from '@/Components/RecommendedSection';
import Footer from '../Components/Footer';
import Hero from '../components/Hero/Hero';
import NewsletterSection from '../Components/NewsletterSection';
import ProductCard from '../components/ProductCard';
import RankingSection from '../Components/RankingSection';

export default function Homepage() {
    const dummyProduct = {
        id: 1,
        slug: 'sample-product',
        name: 'Sample Perfume',
        brand: 'Designer Brand',
        price: 12000,
        salePrice: 9800,
        imageUrl: '/perfume-images/perfume-1.png',
        imageAlt: 'A bottle of sample perfume',
        averageRating: 4.5,
        reviewCount: 82,
        genders: ['unisex'],
        sizes: [50, 100],
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <HomeNavigation />
            <Hero />
            <main>
                <RecommendedSection className="mt-15" />
                <RankingSection />
                <NewsletterSection />
                <div className="flex justify-center py-12">
                    <ProductCard product={dummyProduct} />
                </div>
            </main>
            <Footer />
        </div>
    );
}
