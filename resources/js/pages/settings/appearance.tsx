import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { appearance } from '@/routes';
import { HomeNavigation } from '@/components/homeNavigation';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: '外観設定',
        href: appearance().url,
    },
];

export default function Appearance() {
    return (
        <>
            <HomeNavigation />
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="外観設定" />

                <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="外観設定" description="アカウントの外観設定を更新してください" />
                    <AppearanceTabs />
                </div>
            </SettingsLayout>
        </AppLayout>
        </>
    );
}