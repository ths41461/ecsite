import { useState } from 'react';
import RatingStars from '@/components/RatingStars';

interface ReviewFormProps {
  productId: number;
  onSubmit: (rating: number, comment: string) => Promise<void>;
  onCancel?: () => void;
}

export default function ReviewForm({ productId, onSubmit, onCancel }: ReviewFormProps) {
  const [rating, setRating] = useState(0);
  const [hoverRating, setHoverRating] = useState(0);
  const [comment, setComment] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (rating === 0) {
      setError('評価を選択してください。');
      return;
    }
    
    setIsSubmitting(true);
    setError(null);
    
    try {
      await onSubmit(rating, comment);
      // Reset form on successful submission
      setRating(0);
      setComment('');
    } catch (err: any) {
      setError(err.message || 'レビューの送信に失敗しました。');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleStarClick = (starRating: number) => {
    setRating(starRating);
  };

  const handleStarHover = (starRating: number) => {
    setHoverRating(starRating);
  };

  const handleStarLeave = () => {
    setHoverRating(0);
  };

  return (
    <form onSubmit={handleSubmit} className="mt-6">
      <h3 className="text-lg font-semibold text-gray-800 mb-4">レビューを書く</h3>
      
      {error && (
        <div className="mb-4 bg-[#FCFCF7] border border-gray-200 p-3 text-sm text-gray-800">
          {error}
        </div>
      )}
      
      <div className="mb-4">
        <label className="block text-sm font-medium text-gray-700 mb-2">
          評価 <span className="text-amber-600">*</span>
        </label>
        <div 
          className="flex space-x-1"
          onMouseLeave={handleStarLeave}
        >
          {[1, 2, 3, 4, 5].map((star) => (
            <button
              key={star}
              type="button"
              onClick={() => handleStarClick(star)}
              onMouseEnter={() => handleStarHover(star)}
              className="focus:outline-none"
              aria-label={`${star}つ星`}
            >
              <svg
                className={`w-8 h-8 ${
                  star <= (hoverRating || rating)
                    ? 'text-amber-400 fill-amber-400'
                    : 'text-gray-300 fill-gray-300'
                }`}
                viewBox="0 0 24 24"
              >
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
              </svg>
            </button>
          ))}
        </div>
        <p className="mt-1 text-sm text-gray-500">
          {rating > 0 ? `${rating}つ星` : '評価を選択してください'}
        </p>
      </div>
      
      <div className="mb-4">
        <label htmlFor="body" className="block text-sm font-medium text-gray-700 mb-2">
          コメント
        </label>
        <textarea
          id="body"
          rows={4}
          className="w-full border border-gray-300 px-3 py-2 bg-white text-gray-800 focus:outline-none focus:ring-gray-500 focus:border-gray-500"
          placeholder="この商品についての感想を教えてください..."
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          maxLength={1000}
        />
        <p className="mt-1 text-sm text-gray-500 text-right">
          {comment.length}/1000
        </p>
      </div>
      
      <div className="flex space-x-3">
        <button
          type="submit"
          disabled={isSubmitting || rating === 0}
          className="inline-flex border border-[#EEDDD4] bg-[#EAB308] px-4 py-2 text-sm font-medium text-gray-800 hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-[#EAB308] focus:ring-offset-2 disabled:opacity-50"
        >
          {isSubmitting ? '送信中...' : 'レビューを送信'}
        </button>
        
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="inline-flex border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
          >
            キャンセル
          </button>
        )}
      </div>
    </form>
  );
}