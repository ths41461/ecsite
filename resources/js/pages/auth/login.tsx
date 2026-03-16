import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/input-error';
import { register, login } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';
import { Mail, Lock, LoaderCircle, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import { HomeNavigation } from '@/components/homeNavigation';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const [showPassword, setShowPassword] = useState(false);

    return (
        <>
            <HomeNavigation />
            <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12">
                <main className="w-full max-w-md mx-auto px-4">
                    <div className="text-center mb-8">
                        <h1 className="font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-gray-800">アカウントにログイン</h1>
                        <p className="mt-2 text-gray-600">以下にメールアドレスとパスワードを入力してログインしてください</p>
                    </div>

                    <Head title="ログイン" />
                    <Form 
                        {...AuthenticatedSessionController.store.form()} 
                        resetOnSuccess={['password']} 
                        className="bg-[#FCFCF7] border border-gray-200 p-8"
                    >
                        {({ processing, errors }) => (
                            <>
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
                                                required
                                                autoFocus
                                                tabIndex={1}
                                                autoComplete="email"
                                                placeholder="email@example.com"
                                                className="block w-full pl-10 pr-3 py-3 border border-gray-300 shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 bg-white text-gray-900"
                                            />
                                        </div>
                                        <InputError message={errors.email} className="mt-2" />
                                    </div>

                                    <div>
                                        <div className="flex items-center justify-between mb-1">
                                            <label htmlFor="password" className="block text-sm font-medium text-gray-700">パスワード</label>
                                            {canResetPassword && (
                                                <a 
                                                    href={request().url} 
                                                    className="text-sm text-gray-600 hover:text-gray-900 underline"
                                                    tabIndex={5}
                                                >
                                                    パスワードをお忘れですか？
                                                </a>
                                            )}
                                        </div>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <Lock className="h-5 w-5 text-gray-400" />
                                            </div>
                                            <input
                                                id="password"
                                                type={showPassword ? "text" : "password"}
                                                name="password"
                                                required
                                                tabIndex={2}
                                                autoComplete="current-password"
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

                                    <div className="flex items-center">
                                        <input
                                            id="remember"
                                            name="remember"
                                            type="checkbox"
                                            tabIndex={3}
                                            className="h-4 w-4 text-gray-800 focus:ring-gray-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="remember" className="ml-2 block text-sm text-gray-700">
                                            ログイン状態を保持する
                                        </label>
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
                                            "ログイン"
                                        )}
                                    </button>
                                </div>
                            </>
                        )}
                    </Form>

                    <div className="mt-6 text-center text-sm text-gray-600">
                        アカウントをお持ちではありませんか？{' '}
                        <a 
                            href={register().url + window.location.search} 
                            className="font-medium text-gray-800 hover:text-gray-900 underline"
                            tabIndex={6}
                        >
                            新規登録
                        </a>
                    </div>

                    {status && <div className="mt-4 mb-4 text-center text-sm font-medium text-green-600">{status}</div>}
                </main>
            </div>
        </>
    );
}