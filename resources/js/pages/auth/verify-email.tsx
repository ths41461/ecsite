// Components
import EmailVerificationNotificationController from '@/actions/App/Http/Controllers/Auth/EmailVerificationNotificationController';
import { logout } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { HomeNavigation } from '@/components/homeNavigation';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <HomeNavigation />
            <AuthLayout title="メールアドレスの確認" description="ご登録時にご提供いただいたメールアドレスに送信したリンクをクリックして、メールアドレスをご確認ください。">
                <Head title="メールアドレスの確認" />

                {status === 'verification-link-sent' && (
                    <div className="mb-4 text-center text-sm font-medium text-green-600">
                        新しい確認リンクが、ご登録時にご提供いただいたメールアドレスに送信されました。
                    </div>
                )}

                <Form {...EmailVerificationNotificationController.store.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            確認メールを再送信
                        </Button>

                        <TextLink href={logout()} className="mx-auto block text-sm">
                            ログアウト
                        </TextLink>
                    </>
                )}
            </Form>
        </AuthLayout>
        </>
    );
}