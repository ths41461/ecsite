import RatingStars from '@/components/RatingStars';
import { useState } from 'react';

interface Review {
    id: number;
    rating: number;
    body: string | null;
    created_at: string;
    user: {
        name: string;
    } | null; // Allow user to be null
}

interface ReviewListProps {
    reviews: Review[];
    productId: number;
}

export default function ReviewList({ reviews, productId }: ReviewListProps) {
    const [currentPage, setCurrentPage] = useState(1);
    const reviewsPerPage = 10;

    // Pagination logic
    const indexOfLastReview = currentPage * reviewsPerPage;
    const indexOfFirstReview = indexOfLastReview - reviewsPerPage;
    const currentReviews = reviews.slice(indexOfFirstReview, indexOfLastReview);
    const totalPages = Math.ceil(reviews.length / reviewsPerPage);

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    return (
        <div className="mt-8">
            <h3 className="mb-4 text-lg font-semibold">レビュー</h3>

            {reviews.length === 0 ? (
                <p className="text-gray-500">この商品のレビューはまだありません。</p>
            ) : (
                <>
                    <div className="space-y-6">
                        {currentReviews.map((review) => (
                            <div key={review.id} className="border-b border-gray-200 pb-6 last:border-0 last:pb-0">
                                <div className="mb-2 flex items-center justify-between">
                                    <div className="flex items-center">
                                        <span className="font-medium">{review.user?.name || '匿名ユーザー'}</span>
                                        <span className="mx-2 text-gray-300">•</span>
                                        <RatingStars rating={review.rating} size="sm" />
                                    </div>
                                    <span className="text-sm text-gray-500">{formatDate(review.created_at)}</span>
                                </div>

                                {review.body && <p className="text-gray-700">{review.body}</p>}
                            </div>
                        ))}
                    </div>

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="mt-6 flex justify-center">
                            <nav className="flex space-x-2">
                                {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                                    <button
                                        key={page}
                                        onClick={() => setCurrentPage(page)}
                                        className={`rounded-md px-3 py-1 text-sm ${
                                            currentPage === page ? 'bg-rose-600 text-white' : 'text-gray-700 hover:bg-gray-100'
                                        }`}
                                    >
                                        {page}
                                    </button>
                                ))}
                            </nav>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
