import React from 'react';

const NewsletterSection: React.FC = () => {
    return (
        <section className="w-full border-t border-b border-[#888888] bg-[#FCFCF7] py-24">
            <div className="container mx-auto px-4 flex flex-col items-center">
                <h2 className="font-['Noto_Sans_JP'] text-4xl font-bold text-[#444444]">登録で10%OFFクーポン</h2>
                <p className="mt-2.5 text-center font-['Noto_Sans_JP'] text-xl text-[#444444]">学生限定のお得なセールや、新作香水の情報をいち早くお届けします。</p>
                <form className="mt-10 flex w-full max-w-2xl flex-wrap items-center justify-center gap-4">
                    <input
                        type="email"
                        placeholder="メールアドレスを入力"
                        className="h-15 flex-grow border-none bg-[#EEDDD4] px-4 py-4 text-xl text-black placeholder-black/50 focus:outline-none"
                        aria-label="Email address"
                    />
                    <button
                        type="submit"
                        className="h-15 shrink-0 rounded-full bg-[#444444] px-6 py-2.5 font-['Lato'] text-sm font-medium text-white"
                    >
                        登録する
                    </button>
                </form>
            </div>
        </section>
    );
};

export default NewsletterSection;
