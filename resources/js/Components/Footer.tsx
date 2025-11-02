import React from 'react';

const Footer: React.FC = () => {
    return (
        <footer className="mt-16 w-full border-t border-b border-[#888888] bg-[#FCFCF7] pt-16 pb-0">
            <div className="mx-auto max-w-7xl px-4">
                {/* Top part: Logo, Nav Links, Social Icons */}
                <div className="flex flex-col items-center justify-between gap-8 py-6 md:flex-row">
                    {/* Logo */}
                    <div>
                        <a href="/" className="flex h-12 items-center justify-center bg-red-500">
                            <span className="font-['Hiragino_Mincho_ProN'] text-xl font-bold text-white">LOGO</span>
                        </a>
                    </div>

                    {/* Navigation Links */}
                    <nav aria-label="footer navigation">
                        <ul className="flex flex-wrap items-center justify-center gap-4 font-['Hiragino_Mincho_ProN'] text-base text-gray-700 md:justify-normal">
                            <li>
                                <a href="#" className="transition-colors hover:text-gray-900">
                                    ホーム
                                </a>
                            </li>
                            <li className="hidden md:block">
                                <span className="text-gray-400">|</span>
                            </li>
                            <li>
                                <a href="#" className="transition-colors hover:text-gray-900">
                                    商品一覧
                                </a>
                            </li>
                            <li className="hidden md:block">
                                <span className="text-gray-400">|</span>
                            </li>
                            <li>
                                <a href="#" className="transition-colors hover:text-gray-900">
                                    香り診断
                                </a>
                            </li>
                            <li className="hidden md:block">
                                <span className="text-gray-400">|</span>
                            </li>
                            <li>
                                <a href="#" className="transition-colors hover:text-gray-900">
                                    ブランド紹介
                                </a>
                            </li>
                            <li className="hidden md:block">
                                <span className="text-gray-400">|</span>
                            </li>
                            <li>
                                <a href="#" className="transition-colors hover:text-gray-900">
                                    お問い合わせ
                                </a>
                            </li>
                        </ul>
                    </nav>

                    {/* Social Media Icons */}
                    <div className="flex items-center gap-4">
                        <a href="#" className="transition-opacity hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-gray-400 rounded" aria-label="Visit us on Facebook">
                            <img src="/icons/facebook.svg" alt="" />
                        </a>
                        <a href="#" className="transition-opacity hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-gray-400 rounded" aria-label="Follow us on Instagram">
                            <img src="/icons/instagram.svg" alt="" />
                        </a>
                        <a href="#" className="transition-opacity hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-gray-400 rounded" aria-label="Follow us on X">
                            <img src="/icons/x.svg" alt="" />
                        </a>
                        <a href="#" className="transition-opacity hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-gray-400 rounded" aria-label="Subscribe to our YouTube channel">
                            <img src="/icons/youtube.svg" alt="" />
                        </a>
                    </div>
                </div>

                {/* Bottom part: Copyright, Payment Methods */}
                <div className="mt-12 flex flex-col items-center justify-between gap-6 border-t border-gray-300 py-6 md:flex-row">
                    {/* Copyright */}
                    <div className="font-['Hiragino_Mincho_ProN'] text-base text-gray-700">© 2025 Copyright. All rights reserved.</div>

                    {/* Payment Methods */}
                    <div>
                        <img src="/icons/footer-payment-methods.svg" alt="We accept various payment methods" />
                    </div>
                </div>
            </div>
        </footer>
    );
};

export default Footer;
