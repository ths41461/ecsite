import PasswordResetLinkController from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { Mail, LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { HomeNavigation } from '@/components/homeNavigation';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <HomeNavigation />
            <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12">
                <main className="w-full max-w-md mx-auto px-4">
                    <div className="text-center mb-8">
                        <h1 className="font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-gray-800">パスワードをお忘れですか</h1>
                        <p className="mt-2 text-gray-600">パスワードリセットリンクを受け取るにはメールアドレスを入力してください</p>
                    </div>

                    <Head title="パスワードリセット" />

                    {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}

                    <Form 
                        {...PasswordResetLinkController.store.form()}
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
                                            autoComplete="off"
                                            autoFocus
                                            placeholder="email@example.com"
                                            className="block w-full pl-10 pr-3 py-3 border border-gray-300 shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 bg-white text-gray-900"
                                        />
                                    </div>
                                    <InputError message={errors.email} className="mt-2" />
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
                                        "パスワードリセットリンクを送信"
                                    )}
                                </button>
                            </div>
                        )}
                    </Form>

                    <div className="mt-6 text-center text-sm text-gray-600">
                        <span>または、</span>
                        <a 
                            href={login().url} 
                            className="font-medium text-gray-800 hover:text-gray-900 underline"
                        >
                            ログイン
                        </a>
                        <span>に戻る</span>
                    </div>
                </main>
            </div>
        </>
    );
}