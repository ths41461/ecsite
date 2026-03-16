import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

// Dashboard-specific interfaces
export interface OrderItem {
    id: number;
    product_name: string;
    quantity: number;
    price_yen: number;
}

export interface Order {
    id: number;
    order_number: string;
    total_yen: number;
    status: string;
    created_at: string;
    items_count: number;
    items: OrderItem[];
}

export interface Address {
    id: number;
    name: string;
    phone: string | null;
    address_line1: string;
    address_line2: string | null;
    city: string;
    state: string | null;
    zip: string;
    country: string;
    is_default: boolean;
    created_at: string;
}

export interface WishlistItem {
    id: number;
    product_id: number;
    product_name: string;
    product_price: number;
    product_image: string | null;
    created_at: string;
}

export interface Review {
    id: number;
    product_id: number;
    product_name: string;
    rating: number;
    body: string;
    approved: boolean;
    created_at: string;
}

export interface DashboardData extends SharedData {
    profile: {
        name: string;
        email: string;
        email_verified_at: string | null;
    };
    orders: Order[];
    addresses: Address[];
    wishlistItems: WishlistItem[];
    reviews: Review[];
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
