import React from 'react';

const Footer: React.FC = () => {
    return (
        <footer className="mt-8 w-full border-t border-b border-[#888888] bg-[#FCFCF7] pt-8 pb-0 sm:mt-12 sm:pt-12">
            <div className="mx-auto max-w-7xl px-4">
                {/* Top part: Logo, Nav Links, Social Icons */}
                <div className="flex flex-col items-center justify-between gap-4 py-3 sm:gap-6 sm:py-4 md:flex-row">
                    {/* Logo */}
                    <div>
                        <a href="/" className="flex items-center justify-center">
                            <img src="/logo/F5RA—Logo.svg" alt="F5RA Logo" className="h-8 w-auto" />
                        </a>
                    </div>

                    {/* Navigation Links */}
                    <nav aria-label="footer navigation">
                        <ul className="flex flex-wrap items-center justify-center gap-2 font-['Hiragino_Mincho_ProN'] text-sm text-gray-700 sm:gap-4 sm:text-base md:justify-normal">
                            <li>
                                <a href="#" className="font-medium transition-colors hover:text-gray-900">
                                    ホーム
                                </a>
                            </li>
                            <li className="hidden md:block">
                                <span className="px-1 text-gray-400">|</span>
                            </li>
                            <li>
                                <a href="#" className="font-medium transition-colors hover:text-gray-900">
                                    商品一覧
                                </a>
                            </li>
                            <li className="hidden md:block">
                                <span className="px-1 text-gray-400">|</span>
                            </li>
                            <li>
                                <a href="#" className="font-medium transition-colors hover:text-gray-900">
                                    香り診断
                                </a>
                            </li>
                            <li className="hidden md:block">
                                <span className="px-1 text-gray-400">|</span>
                            </li>
                            <li>
                                <a href="#" className="font-medium transition-colors hover:text-gray-900">
                                    ブランド紹介
                                </a>
                            </li>
                            <li className="hidden md:block">
                                <span className="px-1 text-gray-400">|</span>
                            </li>
                            <li>
                                <a href="#" className="font-medium transition-colors hover:text-gray-900">
                                    お問い合わせ
                                </a>
                            </li>
                        </ul>
                    </nav>

                    {/* Social Media Icons */}
                    <div className="flex items-center gap-4">
                        <a
                            href="#"
                            className="rounded transition-opacity hover:opacity-75 focus:ring-2 focus:ring-gray-400 focus:outline-none"
                            aria-label="Visit us on Facebook"
                        >
                            <img src="/icons/facebook.svg" alt="" />
                        </a>
                        <a
                            href="#"
                            className="rounded transition-opacity hover:opacity-75 focus:ring-2 focus:ring-gray-400 focus:outline-none"
                            aria-label="Follow us on Instagram"
                        >
                            <img src="/icons/instagram.svg" alt="" />
                        </a>
                        <a
                            href="#"
                            className="rounded transition-opacity hover:opacity-75 focus:ring-2 focus:ring-gray-400 focus:outline-none"
                            aria-label="Follow us on X"
                        >
                            <img src="/icons/x.svg" alt="" />
                        </a>
                        <a
                            href="#"
                            className="rounded transition-opacity hover:opacity-75 focus:ring-2 focus:ring-gray-400 focus:outline-none"
                            aria-label="Subscribe to our YouTube channel"
                        >
                            <img src="/icons/youtube.svg" alt="" />
                        </a>
                    </div>
                </div>

                {/* Bottom part: Copyright, Payment Methods */}
                <div className="mt-8 mb-4 flex flex-col items-center justify-between gap-3 border-t border-gray-300 pt-5 sm:gap-4 sm:pt-6 md:flex-row">
                    {/* Payment Methods */}
                    <div>
                        <img src="/icons/footer-payment-methods.svg" alt="We accept various payment methods" />
                    </div>

                    {/* Copyright */}
                    <div className="font-['Hiragino_Mincho_ProN'] text-sm font-medium text-gray-600 sm:text-base">
                        © 2025 Copyright. All rights reserved.
                    </div>
                </div>
            </div>
        </footer>
    );
};

export default Footer;
