import NewPasswordController from '@/actions/App/Http/Controllers/Auth/NewPasswordController';
import { Form, Head } from '@inertiajs/react';
import { Mail, Lock, LoaderCircle, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { HomeNavigation } from '@/components/homeNavigation';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    return (
        <>
            <HomeNavigation />
            <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12">
                <main className="w-full max-w-md mx-auto px-4">
                    <div className="text-center mb-8">
                        <h1 className="font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-gray-800">パスワードをリセット</h1>
                        <p className="mt-2 text-gray-600">以下に新しいパスワードを入力してください</p>
                    </div>

                    <Head title="パスワードリセット" />

                    <Form
                        {...NewPasswordController.store.form()}
                        transform={(data) => ({ ...data, token, email })}
                        resetOnSuccess={['password', 'password_confirmation']}
                        className="bg-[#FCFCF7] border border-gray-200 p-8"
                    >
                        {({ processing, errors }) => (
                            <div className="space-y-6">
                                <div>
                                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <Mail className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            id="email"
                                            type="email"
                                            name="email"
                                            autoComplete="email"
                                            value={email}
                                            readOnly
                                            className="block w-full pl-10 pr-3 py-3 border border-gray-300 shadow-sm bg-gray-100 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500"
                                        />
                                    </div>
                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <Lock className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            id="password"
                                            type={showPassword ? "text" : "password"}
                                            name="password"
                                            autoComplete="new-password"
                                            autoFocus
                                            placeholder="パスワード"
                                            className="block w-full pl-10 pr-10 py-3 border border-gray-300 shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 bg-white text-gray-900"
                                        />
                                        <button
                                            type="button"
                                            className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                            onClick={() => setShowPassword(!showPassword)}
                                            tabIndex={-1}
                                        >
                                            {showPassword ? <EyeOff className="h-5 w-5 text-gray-400" /> : <Eye className="h-5 w-5 text-gray-400" />}
                                        </button>
                                    </div>
                                    <InputError message={errors.password} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 mb-1">パスワード確認</label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <Lock className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            id="password_confirmation"
                                            type={showConfirmPassword ? "text" : "password"}
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                            placeholder="パスワード確認"
                                            className="block w-full pl-10 pr-10 py-3 border border-gray-300 shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 bg-white text-gray-900"
                                        />
                                        <button
                                            type="button"
                                            className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                            tabIndex={-1}
                                        >
                                            {showConfirmPassword ? <EyeOff className="h-5 w-5 text-gray-400" /> : <Eye className="h-5 w-5 text-gray-400" />}
                                        </button>
                                    </div>
                                    <InputError message={errors.password_confirmation} className="mt-2" />
                                </div>

                                <button 
                                    type="submit" 
                                    disabled={processing}
                                    className="w-full border border-[#EEDDD4] bg-[#EAB308] text-gray-800 py-3 px-4 font-medium transition-colors min-h-[44px] hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#EAB308]"
                                >
                                    {processing ? (
                                        <span className="flex items-center justify-center">
                                            <LoaderCircle className="h-4 w-4 animate-spin mr-2" />
                                            処理中...
                                        </span>
                                    ) : (
                                        "パスワードをリセット"
                                    )}
                                </button>
                            </div>
                        )}
                    </Form>
                </main>
            </div>
        </>
    );
}