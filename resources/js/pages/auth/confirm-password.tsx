import ConfirmablePasswordController from '@/actions/App/Http/Controllers/Auth/ConfirmablePasswordController';
import InputError from '@/components/input-error';
import { Form, Head } from '@inertiajs/react';
import { Lock, LoaderCircle, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import { HomeNavigation } from '@/components/homeNavigation';

export default function ConfirmPassword() {
    const [showPassword, setShowPassword] = useState(false);

    return (
        <>
            <HomeNavigation />
            <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12">
                <main className="w-full max-w-md mx-auto px-4">
                    <div className="text-center mb-8">
                        <h1 className="font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-gray-800">パスワードの確認</h1>
                        <p className="mt-2 text-gray-600">これはアプリケーションのセキュアなエリアです。続行する前にパスワードを確認してください。</p>
                    </div>

                    <Head title="パスワード確認" />

                    <Form 
                        {...ConfirmablePasswordController.store.form()} 
                        resetOnSuccess={['password']}
                        className="bg-[#FCFCF7] border border-gray-200 p-8"
                    >
                        {({ processing, errors }) => (
                            <div className="space-y-6">
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
                                            placeholder="パスワード"
                                            autoComplete="current-password"
                                            autoFocus
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
                                        "パスワードを確認"
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