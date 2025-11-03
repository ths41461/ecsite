const Hero = () => {
    return (
        <section className="mx-auto flex min-h-screen max-w-[1440px] flex-col items-center border-b border-black bg-[#FCFCF7] px-4">
            <div
                className="flex flex-grow flex-col justify-center self-stretch bg-cover bg-center py-24 sm:py-32"
                style={{ backgroundImage: 'url(/perfume-images/hero-background.png)' }}
            >
                <div className="flex max-w-3xl flex-col justify-center gap-6 pl-4 sm:pl-6">
                    <div className="flex flex-col items-start justify-start gap-3 border border-white/40 p-4 sm:p-6">
                        <h1 className="font-['Hiragino_Mincho_ProN'] text-2xl leading-tight font-light tracking-[-0.02em] text-white sm:text-3xl md:text-4xl">
                            あなたが選ぶ香りを、一期一会に。
                        </h1>
                    </div>
                    <div className="flex flex-col items-start gap-3">
                        <p className="font-['Hiragino_Mincho_ProN'] text-base leading-relaxed font-normal text-white sm:text-lg md:text-xl">
                            気なる香水を、名前やブランドですぐに検索。
                            <br />
                            質問に答えるだけで、あなたに合う香りも見つかります。
                        </p>
                    </div>
                    <div className="flex w-full flex-col gap-4">
                        <button className="mx-auto flex h-12 w-full max-w-xs items-center justify-center gap-2 border border-[#EEDDD4] bg-[#EAB308] px-6 py-3 font-['Hiragino_Mincho_ProN'] text-sm font-medium text-gray-800 shadow-lg transition-transform hover:scale-105 focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 focus:ring-offset-[#EAB308] focus:outline-none sm:mx-0 sm:px-8 sm:py-3 sm:text-base">
                            香りを探す
                        </button>
                        <button className="mx-auto flex h-12 w-full max-w-xs items-center justify-center gap-2 border border-white/80 px-6 py-3 font-['Hiragino_Mincho_ProN'] text-sm font-medium text-white shadow-lg transition-colors hover:bg-white/10 focus:ring-2 focus:ring-white focus:outline-none sm:mx-0 sm:px-8 sm:py-3 sm:text-base">
                            診断をはじめる
                        </button>
                    </div>
                </div>{' '}
            </div>
            <div className="flex flex-wrap items-center justify-center gap-20 py-6">
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Trusted Brand Logo"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Trusted Brand Logo"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Trusted Brand Logo"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Trusted Brand Logo"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="hidden h-16 w-16 items-center justify-center rounded-full bg-gray-200 sm:flex">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Trusted Brand Logo"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="hidden h-16 w-16 items-center justify-center rounded-full bg-gray-200 sm:flex">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Trusted Brand Logo"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
            </div>
        </section>
    );
};

export default Hero;
