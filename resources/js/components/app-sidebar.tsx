import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarGroup, SidebarGroupLabel, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem } from '@/components/ui/sidebar';
import { dashboard, orders, profile } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, User, Package, MapPin, Heart, MessageCircle } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'ダッシュボード',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

// Account sub-navigation items
const accountNavItems = [
    {
        title: '概要',
        href: `${dashboard().url}?tab=overview`,
        icon: LayoutGrid,
    },
    {
        title: 'プロフィール',
        href: `${dashboard().url}?tab=profile`,
        icon: User,
    },
    {
        title: '注文履歴',
        href: `${dashboard().url}?tab=orders`,
        icon: Package,
    },
    {
        title: '住所',
        href: `${dashboard().url}?tab=addresses`,
        icon: MapPin,
    },
    {
        title: 'お気に入り',
        href: `${dashboard().url}?tab=wishlist`,
        icon: Heart,
    },
    {
        title: 'レビュー',
        href: `${dashboard().url}?tab=reviews`,
        icon: MessageCircle,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'リポジトリ',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'ドキュメント',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const page = usePage();
    const isOnDashboard = page.url.startsWith('/dashboard');
    
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                
                {isOnDashboard && (
                    <SidebarGroup className="px-2 py-0">
                        <SidebarGroupLabel>アカウント</SidebarGroupLabel>
                        <SidebarMenu>
                            {accountNavItems.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={page.url.includes(`tab=${item.href.split('tab=')[1]}`)}
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
