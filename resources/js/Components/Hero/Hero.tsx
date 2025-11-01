const Hero = () => {
    return (
        <section className="mx-auto flex h-[740px] max-w-[1440px] flex-col items-center gap-10 border-b border-black bg-[#FCFCF7] px-4 py-2">
            <div
                className="flex h-[550px] flex-col justify-end gap-2.5 self-stretch bg-cover bg-center px-[62px] py-[42px]"
                style={{ backgroundImage: 'url(/perfume-images/hero-background.png)' }}
            >
                <div className="flex w-[650px] flex-col justify-center gap-4 px-4 py-2">
                    <div className="flex flex-row items-center justify-start gap-2.5 border border-white/40 px-2 py-5">
                        <p className="font-['Hiragino_Mincho_ProN'] text-4xl leading-tight font-light tracking-[-0.02em] text-white">
                            あなたが選ぶ香りを、一期一会に。
                        </p>
                    </div>
                    <div className="flex w-[530px] flex-row items-center gap-2.5">
                        <p className="font-['Noto_Sans_JP'] text-xl leading-relaxed font-normal text-white">
                            気になる香水を、名前やブランドですぐに検索。
                            <br />
                            質問に答えるだけで、あなたに合う香りも見つかります。
                        </p>
                    </div>
                    <div className="flex w-[300px] flex-row items-center justify-center gap-3">
                        <button className="flex h-10 flex-row items-center justify-center gap-2 border border-[#EEDDD4] bg-[#EAB308] px-4 py-2.5 font-['Noto_Sans_JP'] text-sm leading-normal font-normal text-[#444444] shadow-sm">
                            香りを探す
                        </button>
                        <button className="flex h-10 flex-row items-center justify-center gap-2 border border-[#EEDDD4] bg-[#EAB308] px-4 py-2.5 font-['Lato'] text-sm leading-normal font-medium text-[#444444] shadow-sm">
                            診断をはじめる
                        </button>
                    </div>
                </div>
            </div>
            <div className="flex flex-row items-center justify-center gap-[130px] px-2">
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200">
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
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200">
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
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200">
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
            </div>
        </section>
    );
};

export default Hero;
