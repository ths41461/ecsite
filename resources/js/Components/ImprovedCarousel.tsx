import React, { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface CarouselProps {
    children: React.ReactNode[];
    itemsToShow?: number;
    slideOffset?: number;
}

const ImprovedCarousel: React.FC<CarouselProps> = ({ 
    children, 
    itemsToShow = 4, 
    slideOffset = 1
}) => {
    const [currentIndex, setCurrentIndex] = useState(0);
    const totalItems = children.length;
    const maxIndex = Math.max(0, totalItems - itemsToShow);
    
    const handleNext = () => {
        if (currentIndex < maxIndex) {
            setCurrentIndex(prev => Math.min(prev + slideOffset, maxIndex));
        }
    };

    const handlePrev = () => {
        if (currentIndex > 0) {
            setCurrentIndex(prev => Math.max(prev - slideOffset, 0));
        }
    };

    // Reset index if it goes beyond maxIndex
    useEffect(() => {
        if (currentIndex > maxIndex) {
            setCurrentIndex(maxIndex);
        }
    }, [currentIndex, maxIndex]);

    return (
        <div className="relative flex items-center justify-center">
            <button 
                className="absolute left-0 z-10 -translate-x-8 transform"
                onClick={handlePrev}
                disabled={currentIndex === 0}
                aria-label="Previous items"
            >
                <ChevronLeft 
                    className={`h-8 w-8 ${currentIndex === 0 ? 'text-gray-300' : 'text-gray-600'}`} 
                />
            </button>
            
            <div 
                className="overflow-hidden"
                style={{ 
                    width: `calc(18rem * ${itemsToShow} + 1.5rem * ${itemsToShow - 1})` 
                }}
            >
                <div 
                    className="flex"
                    style={{ 
                        transform: `translateX(calc(-${currentIndex} * (18rem + 1.5rem)))`,
                        transition: 'transform 0.3s ease',
                    }}
                >
                    {React.Children.map(children, (child, index) => (
                        <div 
                            key={index} 
                            className="w-72 flex-shrink-0"
                            style={{ 
                                width: '18rem',
                                marginRight: index < children.length - 1 ? '1.5rem' : '0'
                            }}
                        >
                            {child}
                        </div>
                    ))}
                </div>
            </div>
            
            <button 
                className="absolute right-0 z-10 translate-x-8 transform"
                onClick={handleNext}
                disabled={currentIndex >= maxIndex}
                aria-label="Next items"
            >
                <ChevronRight 
                    className={`h-8 w-8 ${currentIndex >= maxIndex ? 'text-gray-300' : 'text-gray-600'}`} 
                />
            </button>
        </div>
    );
};

export default ImprovedCarousel;