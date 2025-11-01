import { HomeNavigation } from '@/components/homeNavigation';
import Hero from '@/Components/Hero/Hero';
import RecommendedSection from '@/Components/RecommendedSection';

export default function Homepage() {
  return (
    <div className="min-h-screen bg-gray-50">
      <HomeNavigation />
      <Hero />
      <main>
        <RecommendedSection className="mt-15" />
      </main>
    </div>
  );
}