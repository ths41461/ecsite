import React from 'react';

const Hero = () => {
  return (
    <section className="flex flex-col items-center gap-10 px-4 py-2 max-w-[1440px] mx-auto h-[740px] bg-[#FCFCF7] border-b border-black">
      <div className="flex flex-col justify-end self-stretch gap-2.5 px-[62px] py-[42px] h-[550px] bg-cover bg-center" style={{ backgroundImage: 'url(/perfume-images/hero-background.png)' }}>
        <div className="flex flex-col justify-center gap-4 px-4 py-2 w-[974px]">
          <div className="flex flex-row justify-center items-center gap-2.5 px-2 py-5 border border-white/40 rounded-[10px]">
            <p className="font-['Hiragino_Mincho_ProN'] font-light text-4xl leading-tight tracking-[-0.02em] text-white">
              あなたが選ぶ香りを、一期一会に。
            </p>
          </div>
          <div className="flex flex-row items-center gap-2.5 w-[530px]">
            <p className="font-['Noto_Sans_JP'] font-normal text-xl leading-relaxed text-white">
              気になる香水を、名前やブランドですぐに検索。<br />質問に答えるだけで、あなたに合う香りも見つかります。
            </p>
          </div>
          <div className="flex flex-row justify-center items-center gap-3 w-[246px]">
            <button className="flex flex-row justify-center items-center gap-2 px-4 py-2.5 h-10 bg-[#EAB308] border border-[#EEDDD4] shadow-sm font-['Noto_Sans_JP'] font-normal text-sm leading-normal text-[#444444]">
              香りを探す
            </button>
            <button className="flex flex-row justify-center items-center gap-2 px-4 py-2.5 h-10 bg-[#EAB308] border border-[#EEDDD4] shadow-sm font-['Lato'] font-medium text-sm leading-normal text-[#444444]">
              診断をはじめる
            </button>
          </div>
        </div>
      </div>
      <div className="flex flex-row justify-center items-center gap-[130px] px-2">
        <div className="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
          <img src="https://dummyimage.com/50x50/ccc/000.png&text=Logo" alt="Logo Placeholder" className="w-full h-full object-cover rounded-full" />
        </div>
        <div className="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
          <img src="https://dummyimage.com/50x50/ccc/000.png&text=Logo" alt="Logo Placeholder" className="w-full h-full object-cover rounded-full" />
        </div>
        <div className="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
          <img src="https://dummyimage.com/50x50/ccc/000.png&text=Logo" alt="Logo Placeholder" className="w-full h-full object-cover rounded-full" />
        </div>
        <div className="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
          <img src="https://dummyimage.com/50x50/ccc/000.png&text=Logo" alt="Logo Placeholder" className="w-full h-full object-cover rounded-full" />
        </div>
        <div className="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
          <img src="https://dummyimage.com/50x50/ccc/000.png&text=Logo" alt="Logo Placeholder" className="w-full h-full object-cover rounded-full" />
        </div>
        <div className="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
          <img src="https://dummyimage.com/50x50/ccc/000.png&text=Logo" alt="Logo Placeholder" className="w-full h-full object-cover rounded-full" />
        </div>
      </div>
    </section>
  );
};

export default Hero;
