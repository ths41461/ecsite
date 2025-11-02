import React from 'react';

const Footer: React.FC = () => {
    return (
        <footer className="w-full border-t border-b border-[#888888] bg-[#FCFCF7] pt-[100px] pb-0">
            <div className="mx-auto max-w-[1360px] px-[15px]">
                {/* Top part: Logo, Nav Links, Social Icons */}
                <div className="flex items-center justify-between py-6">
                    {/* Logo */}
                    <div>
                        <div className="flex h-12 items-center justify-center bg-red-500" style={{ width: '178.49px' }}>
                            <span className="text-lg font-bold text-white font-['Plus_Jakarta_Sans']">LOGO</span>
                        </div>
                    </div>

                    {/* Navigation Links */}
                    <nav>
                        <ul className="flex items-center gap-x-8 text-base text-[#444444] font-['Noto_Sans_JP']">
                            <li><a href="#" className="hover:underline">ホーム</a></li>
                            <li><img src="/icons/footer-nav-separator.svg" alt="" /></li>
                            <li><a href="#" className="hover:underline">商品一覧</a></li>
                            <li><img src="/icons/footer-nav-separator.svg" alt="" /></li>
                            <li><a href="#" className="hover:underline">香り診断</a></li>
                            <li><img src="/icons/footer-nav-separator.svg" alt="" /></li>
                            <li><a href="#" className="hover:underline">ブランド紹介</a></li>
                            <li><img src="/icons/footer-nav-separator.svg" alt="" /></li>
                            <li><a href="#" className="hover:underline">お問い合わせ</a></li>
                        </ul>
                    </nav>

                    {/* Social Media Icons */}
                    <div>
                        <img src="/icons/footer-social-icons.svg" alt="Social media icons" />
                    </div>
                </div>

                {/* Bottom part: Copyright, Payment Methods */}
                <div className="mt-[50px] border-t border-[#D6D6D6] py-6 flex items-end justify-between">
                    {/* Copyright */}
                    <div className="text-base text-[#444444] font-['Plus_Jakarta_Sans']">
                        © 2025 Copyright. All rights reserved.
                    </div>

                    {/* Payment Methods */}
                    <div>
                        <img src="/icons/footer-payment-methods.svg" alt="Payment methods" />
                    </div>
                </div>
            </div>
        </footer>
    );
};

export default Footer;
