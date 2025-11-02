import React, { useState } from 'react';

const NewsletterSection: React.FC = () => {
    const [email, setEmail] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitSuccess, setSubmitSuccess] = useState(false);
    const [error, setError] = useState('');

    const validateEmail = (email: string) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        
        if (!email) {
            setError('メールアドレスを入力してください');
            return;
        }
        
        if (!validateEmail(email)) {
            setError('有効なメールアドレスを入力してください');
            return;
        }
        
        setIsSubmitting(true);
        
        // Simulate API call
        setTimeout(() => {
            setIsSubmitting(false);
            setSubmitSuccess(true);
            setEmail('');
            // Reset success message after 5 seconds
            setTimeout(() => setSubmitSuccess(false), 5000);
        }, 1000);
    };

    return (
        <section className="w-full border-t border-b border-[#888888] bg-[#FCFCF7] py-12">
            <div className="container mx-auto flex flex-col items-center px-4">
                <h2 className="font-['Hiragino_Mincho_ProN'] text-3xl font-bold text-gray-900">登録で10%OFFクーポン</h2>
                <p className="mt-2 text-center font-['Hiragino_Mincho_ProN'] text-base text-gray-700">
                    学生限定のお得なセールや、新作香水の情報をいち早くお届けします。
                </p>
                
                <form onSubmit={handleSubmit} className="mt-6 flex w-full max-w-2xl items-center justify-center gap-4">
                    <div className="relative w-full max-w-md">
                        <input
                            type="email"
                            id="newsletter-email"
                            name="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="メールアドレスを入力"
                            required
                            disabled={isSubmitting}
                            className={`h-12 w-full border ${
                                error ? 'border-red-500' : 'border-gray-300'
                            } bg-white px-4 py-3 text-base text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400 ${
                                isSubmitting ? 'opacity-75 cursor-not-allowed' : ''
                            }`}
                            aria-label="メールアドレスを入力"
                            aria-describedby="newsletter-help"
                        />
                    </div>
                    <button 
                        type="submit" 
                        disabled={isSubmitting}
                        className={`h-12 min-w-[140px] bg-gray-700 px-6 py-3 font-['Hiragino_Mincho_ProN'] text-base font-medium text-white ${
                            isSubmitting ? 'opacity-75 cursor-not-allowed' : 'hover:bg-gray-800'
                        }`}
                    >
                        {isSubmitting ? '送信中...' : '登録する'}
                    </button>
                </form>
                
                {error && (
                    <p className="mt-3 text-sm text-red-600 font-medium" role="alert">
                        {error}
                    </p>
                )}
                
                {submitSuccess && (
                    <p className="mt-3 text-sm text-green-700 font-medium" role="status">
                        メールアドレスの登録が完了しました！10%OFFクーポンを送信しました。
                    </p>
                )}
            </div>
        </section>
    );
};

export default NewsletterSection;
