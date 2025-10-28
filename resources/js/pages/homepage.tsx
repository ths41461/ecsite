import { HomeNavigation } from '@/components/homeNavigation';

export default function Homepage() {
  return (
    <div className="min-h-screen bg-gray-50">
      <HomeNavigation />
      <main className="container mx-auto py-8">
        <div className="text-center">
          <h1 className="text-3xl font-bold text-gray-800 mb-4">Welcome to Our Homepage</h1>
          <p className="text-gray-600">This is the main content area of your homepage.</p>
        </div>
      </main>
    </div>
  );
}