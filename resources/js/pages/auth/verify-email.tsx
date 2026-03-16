// Components
import EmailVerificationNotificationController from '@/actions/App/Http/Controllers/Auth/EmailVerificationNotificationController';
import { logout } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle, Mail } from 'lucide-react';

import { HomeNavigation } from '@/components/homeNavigation';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <HomeNavigation />
            <div className="min-h-screen bg-gray-50 flex items-center justify-center py-12">
                <main className="w-full max-w-md mx-auto px-4">
                    <div className="text-center mb-8">
                        <h1 className="font-['Hiragino_Mincho_ProN'] text-2xl font-semibold text-gray-800">メールアドレスの確認</h1>
                        <p className="mt-2 text-gray-600">ご登録時にご提供いただいたメールアドレスに送信したリンクをクリックして、メールアドレスをご確認ください。</p>
                    </div>

                    <Head title="メールアドレスの確認" />

                    {status === 'verification-link-sent' && (
                        <div className="mb-4 text-center text-sm font-medium text-green-600">
                            新しい確認リンクが、ご登録時にご提供いただいたメールアドレスに送信されました。
                        </div>
                    )}

                    <Form 
                        {...EmailVerificationNotificationController.store.form()} 
                        className="bg-[#FCFCF7] border border-gray-200 p-8 text-center"
                    >
                        {({ processing }) => (
                            <div className="space-y-6">
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
                                        "確認メールを再送信"
                                    )}
                                </button>

                                <a 
                                    href={logout().url} 
                                    className="mx-auto block text-sm text-gray-600 hover:text-gray-900 underline"
                                >
                                    ログアウト
                                </a>
                            </div>
                        )}
                    </Form>
                </main>
            </div>
        </>
    );
}