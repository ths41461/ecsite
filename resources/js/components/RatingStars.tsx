import { Star } from 'lucide-react';

interface RatingStarsProps {
  rating: number;
  size?: 'sm' | 'md' | 'lg';
  showLabel?: boolean;
}

export default function RatingStars({ rating, size = 'md', showLabel = false }: RatingStarsProps) {
  const sizeClasses = {
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6',
  };

  const stars = [];
  const fullStars = Math.floor(rating);
  const hasHalfStar = rating % 1 >= 0.5;

  for (let i = 1; i <= 5; i++) {
    if (i <= fullStars) {
      stars.push(
        <Star
          key={i}
          className={`${sizeClasses[size]} fill-amber-400 text-amber-400`}
        />
      );
    } else if (i === fullStars + 1 && hasHalfStar) {
      stars.push(
        <div key={i} className="relative">
          <Star className={`${sizeClasses[size]} fill-gray-200 text-gray-200`} />
          <div className="absolute inset-0 overflow-hidden" style={{ width: '50%' }}>
            <Star className={`${sizeClasses[size]} fill-amber-400 text-amber-400`} />
          </div>
        </div>
      );
    } else {
      stars.push(
        <Star
          key={i}
          className={`${sizeClasses[size]} fill-gray-200 text-gray-200`}
        />
      );
    }
  }

  return (
    <div className="flex items-center">
      <div className="flex">{stars}</div>
      {showLabel && (
        <span className="ml-2 text-sm text-gray-600">
          {rating.toFixed(1)}
        </span>
      )}
    </div>
  );
}