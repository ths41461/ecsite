import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { User, Mail, Lock, LoaderCircle, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { HomeNavigation } from '@/components/homeNavigation';

const calculatePasswordStrength = (password: string): { score: number; label: string; color: string } => {
    if (!password) return { score: 0, label: '', color: 'bg-gray-200' };
    
    let score = 0;
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    const strengthMap = [
        { label: '弱い', color: 'bg-red-500' },
        { label: '弱い', color: 'bg-red-500' },
        { label: '普通', color: 'bg-yellow-500' },
        { label: '強い', color: 'bg-green-500' },
        { label: '非常に強い', color: 'bg-green-700' },
    ];

    const strength = strengthMap[Math.min(score, 4)];
    return { 
        score: score * 20, // Convert to percentage
        label: strength.label, 
        color: strength.color 
    };
};

export default function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');

    const passwordStrength = calculatePasswordStrength(password);
    const passwordsMatch = password === confirmPassword && password !== '';

    return (
        <>
            <HomeNavigation />
            <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12">
                <main className="w-full max-w-md mx-auto px-4">
                    <div className="text-center mb-8">
                        <h1 className="font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-gray-800">アカウントを作成</h1>
                        <p className="mt-2 text-gray-600">以下に詳細情報を入力してアカウントを作成してください</p>
                    </div>

                    <Head title="新規登録" />
                    <Form
                        {...RegisteredUserController.store.form()}
                        resetOnSuccess={['password', 'password_confirmation']}
                        disableWhileProcessing
                        className="bg-[#FCFCF7] border border-gray-200 p-8"
                    >
                        {({ processing, errors, data }) => (
                            <>
                                <div className="space-y-6">
                                    <div>
                                        <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">お名前</label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <User className="h-5 w-5 text-gray-400" />
                                            </div>
                                            <input
                                                id="name"
                                                type="text"
                                                required
                                                autoFocus
                                                tabIndex={1}
                                                autoComplete="name"
                                                name="name"
                                                placeholder="氏名"
                                                className="block w-full pl-10 pr-3 py-3 border border-gray-300 shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 bg-white text-gray-900"
                                            />
                                        </div>
                                        <InputError message={errors.name} className="mt-2" />
                                    </div>

                                    <div>
                                        <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <Mail className="h-5 w-5 text-gray-400" />
                                            </div>
                                            <input
                                                id="email"
                                                type="email"
                                                required
                                                tabIndex={2}
                                                autoComplete="email"
                                                name="email"
                                                placeholder="email@example.com"
                                                className="block w-full pl-10 pr-3 py-3 border border-gray-300 shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 bg-white text-gray-900"
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
                                                required
                                                tabIndex={3}
                                                autoComplete="new-password"
                                                name="password"
                                                value={password}
                                                onChange={(e) => setPassword(e.target.value)}
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
                                        
                                        {password && (
                                            <div className="mt-2">
                                                <div className="flex items-center justify-between text-xs text-gray-600 mb-1">
                                                    <span>パスワード強度: {passwordStrength.label}</span>
                                                    <span>{passwordStrength.score}%</span>
                                                </div>
                                                <div className="w-full bg-gray-200 h-1.5">
                                                    <div 
                                                        className={`h-1.5 ${passwordStrength.color}`} 
                                                        style={{ width: `${passwordStrength.score}%` }}
                                                    ></div>
                                                </div>
                                            </div>
                                        )}
                                        
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
                                                required
                                                tabIndex={4}
                                                autoComplete="new-password"
                                                name="password_confirmation"
                                                value={confirmPassword}
                                                onChange={(e) => setConfirmPassword(e.target.value)}
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
                                        {password && confirmPassword && (
                                            <div className="mt-2 flex items-center text-xs">
                                                {passwordsMatch ? (
                                                    <span className="text-green-600 flex items-center">
                                                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        パスワードが一致しています
                                                    </span>
                                                ) : (
                                                    <span className="text-red-600 flex items-center">
                                                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        パスワードが一致していません
                                                    </span>
                                                )}
                                            </div>
                                        )}
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
                                            "アカウントを作成"
                                        )}
                                    </button>
                                </div>
                            </>
                        )}
                    </Form>

                    <div className="mt-6 text-center text-sm text-gray-600">
                        既にアカウントをお持ちですか？{' '}
                        <a 
                            href={login().url + window.location.search} 
                            className="font-medium text-gray-800 hover:text-gray-900 underline"
                            tabIndex={6}
                        >
                            ログイン
                        </a>
                    </div>
                </main>
            </div>
        </>
    );
}