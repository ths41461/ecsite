const Hero = () => {
    return (
        <section className="mx-auto flex min-h-screen max-w-[1440px] flex-col border-b border-black bg-[#FCFCF7]">
            <div
                className="flex flex-grow flex-col justify-end self-stretch bg-cover bg-center px-6 py-12 sm:px-8 md:px-16"
                style={{ backgroundImage: 'url(/perfume-images/hero-background.png)' }}
            >
                                    <div className="flex max-w-3xl flex-col justify-center gap-6">
                                        <div className="flex flex-row items-center justify-start gap-2.5 border border-white/40 p-6 max-w-2xl">
                                            <h1 className="font-['Hiragino_Mincho_ProN'] text-3xl font-light leading-tight tracking-[-0.02em] text-white md:text-4xl">
                                                あなたが選ぶ香りを、一期一会に。
                                            </h1>
                                        </div>
                                        <div className="flex max-w-xl flex-row items-center gap-2.5">
                                            <p className="font-['Noto_Sans_JP'] text-lg font-normal leading-relaxed text-white md:text-xl">
                                                気になる香水を、名前やブランドですぐに検索。
                                                <br />
                                                質問に答えるだけで、あなたに合う香りも見つかります。
                                            </p>
                                        </div>
                                        <div className="flex flex-col gap-4 sm:flex-row">
                                            <button className="flex h-12 items-center justify-center gap-2 border border-[#EEDDD4] bg-[#EAB308] px-8 py-3 font-['Noto_Sans_JP'] text-base font-medium text-[#444444] shadow-lg transition-transform hover:scale-105">
                                                香りを探す
                                            </button>
                                            <button className="flex h-12 items-center justify-center gap-2 border border-white/80 px-8 py-3 font-['Lato'] text-base font-medium text-white shadow-lg transition-colors hover:bg-white/10">
                                                診断をはじめる
                                            </button>
                                        </div>
                                    </div>            </div>
            <div className="w-full p-6">
                <h2 className="mb-6 text-center text-sm font-semibold uppercase tracking-widest text-gray-600">
                    FEATURED IN
                </h2>
                <div className="flex flex-wrap items-center justify-center gap-x-12 gap-y-6 px-4 md:gap-x-24">
                    {[...Array(6)].map((_, i) => (
                                                    <div
                                                        key={i}
                                                        className="flex h-16 w-16 items-center justify-center bg-gray-200 md:h-20 md:w-20"
                                                    >                            <img
                                src={`https://dummyimage.com/50x50/ccc/000.png&text=Logo${
                                    i + 1
                                }`}
                                alt={`Logo ${i + 1} Placeholder`}
                                className="object-cover"
                            />
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
};

export default Hero;
