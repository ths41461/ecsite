const Hero = () => {
    return (
        <section className="mx-auto flex min-h-screen max-w-[1440px] flex-col items-center border-b border-black bg-[#FCFCF7] px-4">
            <div
                className="flex flex-grow flex-col justify-end self-stretch bg-cover bg-center py-25"
                style={{ backgroundImage: 'url(/perfume-images/hero-background.png)' }}
            >
                <div className="flex max-w-3xl flex-col justify-center gap-6 pl-6">
                    <div className="flex max-w-2xl flex-row items-center justify-start gap-2.5 border border-white/40 p-6">
                        <h1 className="font-['Hiragino_Mincho_ProN'] text-3xl leading-tight font-light tracking-[-0.02em] text-white md:text-4xl">
                            あなたが選ぶ香りを、一期一会に。
                        </h1>
                    </div>
                    <div className="flex max-w-xl flex-row items-center gap-2.5">
                        <p className="font-['Noto_Sans_JP'] text-lg leading-relaxed font-normal text-white md:text-xl">
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
                </div>{' '}
            </div>
            <div className="flex flex-row items-center justify-center gap-30 py-10">
                <div className="flex h-25 w-25 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Logo Placeholder"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="flex h-25 w-25 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Logo Placeholder"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="flex h-25 w-25 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Logo Placeholder"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="flex h-25 w-25 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Logo Placeholder"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Logo Placeholder"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
                <div className="flex h-25 w-25 items-center justify-center rounded-full bg-gray-200">
                    <img
                        src="https://dummyimage.com/50x50/ccc/000.png&text=Logo"
                        alt="Logo Placeholder"
                        className="h-full w-full rounded-full object-cover"
                    />
                </div>
            </div>
        </section>
    );
};

export default Hero;
