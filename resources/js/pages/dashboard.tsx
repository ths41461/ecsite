import { Head, usePage, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type DashboardData } from '@/types';
import Heading from '@/components/heading';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Transition } from '@headlessui/react';
import { useRef, useState, useEffect } from 'react';
import { send } from '@/routes/verification';
import { Link } from '@inertiajs/react';
import { 
  BookOpen, 
  Folder, 
  LayoutGrid, 
  User, 
  Package, 
  MapPin, 
  Heart, 
  MessageCircle,
  PackageCheck,
  Trash2,
  Edit3
} from 'lucide-react';

// Define TypeScript interfaces for dashboard data

// Helper function to get cookie value
function getCookie(name: string) {
    const parts = document.cookie.split('; ').map((c) => c.split('='));
    const found = parts.find(([k]) => k === name);
    return found ? decodeURIComponent(found[1] ?? '') : null;
}

function xsrfHeaders(): HeadersInit {
    const xsrf = getCookie('XSRF-TOKEN');
    return {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'ダッシュボード',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    const { profile, orders, addresses, wishlistItems, reviews } = usePage<DashboardData>().props;
    const page = usePage();
    const mustVerifyEmail = profile.email_verified_at === null;
    const status = page.props.status as string | undefined;
    
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);
    
    // Extract tab from URL parameters
    const [activeTab, setActiveTab] = useState<string>('overview');
    
    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            setActiveTab(tab);
        } else {
            setActiveTab('overview');
        }
    }, [page.url]);

    // Format date helper
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Get order status color
    const getOrderStatusColor = (status: string) => {
        switch (status) {
            case 'pending': return 'bg-yellow-100 text-yellow-800';
            case 'paid': return 'bg-green-100 text-green-800';
            case 'shipped': return 'bg-blue-100 text-blue-800';
            case 'delivered': return 'bg-purple-100 text-purple-800';
            case 'cancelled': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    // Format price helper
    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('ja-JP', {
            style: 'currency',
            currency: 'JPY',
            maximumFractionDigits: 0
        }).format(price);
    };

    // Function to refresh dashboard data
    const refreshDashboardData = () => {
        // Get the current URL and preserve any tab parameters
        const currentUrl = window.location.href;
        // Update the page by fetching fresh data from the server
        router.reload({ only: ['profile', 'orders', 'addresses', 'wishlistItems', 'reviews'] });
    };
    
    // State for managing review editing (must be at component level due to React hooks rules)
    const [editingReview, setEditingReview] = useState<{id: number, rating: number, body: string} | null>(null);
    
    // Function to render only active tab content
    const renderActiveTab = () => {
        switch (activeTab) {
            case 'profile':
                return (
                    <>
                        {/* Profile Header */}
                        <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                            <div className="flex items-center space-x-4">
                                <div className="bg-gray-200 dark:bg-gray-700 border-2 border-dashed rounded-xl w-16 h-16" />
                                <div>
                                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{profile.name}</h1>
                                    <p className="text-gray-600 dark:text-gray-400">{profile.email}</p>
                                </div>
                            </div>
                        </div>

                        {/* Profile and Password Forms - Responsive Grid */}
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Profile Information Card */}
                            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                                <div className="mb-6">
                                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">プロフィール情報</h2>
                                    <p className="text-gray-600 dark:text-gray-400 mt-1">お名前とメールアドレスを更新してください</p>
                                </div>
                                
                                <form 
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const formData = new FormData(e.currentTarget as HTMLFormElement);
                                        ProfileController.update({
                                            name: formData.get('name') as string,
                                            email: formData.get('email') as string,
                                        });
                                    }}
                                >
                                    <div className="space-y-4">
                                        <div>
                                            <Label htmlFor="name" className="text-gray-700 dark:text-gray-300">お名前</Label>
                                            <Input
                                                id="name"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                                defaultValue={profile.name}
                                                name="name"
                                                required
                                                autoComplete="name"
                                                placeholder="氏名"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="email" className="text-gray-700 dark:text-gray-300">メールアドレス</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                                defaultValue={profile.email}
                                                name="email"
                                                required
                                                autoComplete="username"
                                                placeholder="メールアドレス"
                                            />
                                        </div>

                                        {mustVerifyEmail && (
                                            <div className="rounded-md bg-yellow-50 dark:bg-yellow-900/20 p-4">
                                                <div className="flex">
                                                    <div className="flex-shrink-0">
                                                        <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <div className="ml-3">
                                                        <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">メールアドレスの確認が必要です</h3>
                                                        <div className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                                            <p>
                                                                メールアドレスが未確認です。{' '}
                                                                <Link
                                                                    href={send()}
                                                                    as="button"
                                                                    className="font-medium text-yellow-800 dark:text-yellow-200 underline hover:text-yellow-900 dark:hover:text-yellow-100"
                                                                >
                                                                    こちらをクリックして確認メールを再送信してください。
                                                                </Link>
                                                            </p>
                                                        </div>
                                                        {status === 'verification-link-sent' && (
                                                            <div className="mt-2 text-sm text-green-600 dark:text-green-400">
                                                                新しい確認リンクがメールアドレスに送信されました。
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        <div className="flex items-center justify-end">
                                            <Button type="submit" className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                                                変更を保存
                                            </Button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            {/* Password Update Card */}
                            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                                <div className="mb-6">
                                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">パスワードの更新</h2>
                                    <p className="text-gray-600 dark:text-gray-400 mt-1">アカウントの安全性を保つため、長くてランダムなパスワードを使用してください</p>
                                </div>
                                
                                <form 
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const formData = new FormData(e.currentTarget as HTMLFormElement);
                                        PasswordController.update({
                                            current_password: formData.get('current_password') as string,
                                            password: formData.get('password') as string,
                                            password_confirmation: formData.get('password_confirmation') as string,
                                        });
                                    }}
                                >
                                    <div className="space-y-4">
                                        <div>
                                            <Label htmlFor="current_password" className="text-gray-700 dark:text-gray-300">現在のパスワード</Label>
                                            <Input
                                                id="current_password"
                                                name="current_password"
                                                type="password"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                                autoComplete="current-password"
                                                placeholder="現在のパスワード"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="password" className="text-gray-700 dark:text-gray-300">新しいパスワード</Label>
                                            <Input
                                                id="password"
                                                name="password"
                                                type="password"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                                autoComplete="new-password"
                                                placeholder="新しいパスワード"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="password_confirmation" className="text-gray-700 dark:text-gray-300">パスワードの確認</Label>
                                            <Input
                                                id="password_confirmation"
                                                name="password_confirmation"
                                                type="password"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                                autoComplete="new-password"
                                                placeholder="パスワードの確認"
                                            />
                                        </div>

                                        <div className="flex items-center justify-end">
                                            <Button type="submit" className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                                                パスワードを更新
                                            </Button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {/* Account Deletion Card */}
                        <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-red-200 dark:border-red-900/50 shadow-sm">
                            <div className="mb-6">
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-white">プロフィール情報の削除</h2>
                                <p className="text-gray-600 dark:text-gray-400 mt-1">プロフィール情報を削除すると、アカウントから個人情報が削除されます。この操作は元に戻せません。</p>
                            </div>
                            <div className="flex justify-end">
                                <Button 
                                    type="button" 
                                    onClick={() => {
                                        if (window.confirm('本当にプロフィール情報を削除してもよろしいですか？この操作は元に戻せません。')) {
                                            // Reset profile info to empty values using the existing ProfileController
                                            ProfileController.update({
                                                name: '',
                                                email: profile.email // Keep email for account identification
                                            });
                                        }
                                    }}
                                    className="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out"
                                >
                                    プロフィール情報を削除
                                </Button>
                            </div>
                        </div>
                        
                        {/* Account Deletion Card */}
                        <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-red-200 dark:border-red-900/50 shadow-sm">
                            <div className="mb-6">
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-white">アカウントの削除</h2>
                                <p className="text-gray-600 dark:text-gray-400 mt-1">アカウントを削除すると、すべてのデータが完全に削除されます。この操作は元に戻せません。</p>
                            </div>
                            <DeleteUser />
                        </div>
                    </>
                );
            case 'orders':
                return (
                    <section className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <div className="mb-6">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">注文履歴</h2>
                            <p className="text-gray-600 dark:text-gray-400 mt-1">過去の注文履歴を確認します</p>
                        </div>
                        
                        <div className="mt-2">
                            {orders.length > 0 ? (
                                <div className="space-y-4">
                                    {orders.map((order) => (
                                        <div key={order.id} className="p-5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                            <div className="flex justify-between items-center mb-4">
                                                <h3 className="font-medium text-gray-900 dark:text-white">注文番号: {order.order_number}</h3>
                                                <span className={`px-3 py-1 rounded-full text-xs font-medium ${getOrderStatusColor(order.status)}`}>
                                                    {order.status}
                                                </span>
                                            </div>
                                            
                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                                <div>
                                                    <p className="text-gray-500 dark:text-gray-400">合計金額</p>
                                                    <p className="font-medium text-gray-900 dark:text-white">{formatPrice(order.total_yen)}</p>
                                                </div>
                                                <div>
                                                    <p className="text-gray-500 dark:text-gray-400">商品数</p>
                                                    <p className="font-medium text-gray-900 dark:text-white">{order.items_count} 点</p>
                                                </div>
                                                <div>
                                                    <p className="text-gray-500 dark:text-gray-400">注文日</p>
                                                    <p className="font-medium text-gray-900 dark:text-white">{formatDate(order.created_at)}</p>
                                                </div>
                                            </div>
                                            
                                            <div className="mt-4">
                                                <p className="text-gray-500 dark:text-gray-400 mb-2">注文商品</p>
                                                <div className="space-y-2">
                                                    {order.items.map((item) => (
                                                        <div key={item.id} className="flex justify-between text-sm py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                                            <span className="text-gray-700 dark:text-gray-300">{item.product_name} × {item.quantity}</span>
                                                            <span className="font-medium text-gray-900 dark:text-white">{formatPrice(item.price_yen)}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <Package className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">注文がありません</h3>
                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        注文履歴がまだありません。
                                    </p>
                                </div>
                            )}
                        </div>
                    </section>
                );
            case 'addresses':
                return (
                    <section className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <div className="mb-6">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">住所</h2>
                            <p className="text-gray-600 dark:text-gray-400 mt-1">配送先住所を管理します</p>
                        </div>
                        
                        <div className="mt-2">
                            {addresses.length > 0 ? (
                                <div className="space-y-4">
                                    {addresses.map((address) => (
                                        <div 
                                            key={address.id} 
                                            className={`p-5 rounded-lg border ${
                                                address.is_default 
                                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' 
                                                    : 'border-gray-200 dark:border-gray-700'
                                            } hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150`}
                                        >
                                            <div className="flex justify-between items-start">
                                                <div className="flex-1">
                                                    <div className="flex items-start justify-between">
                                                        <div>
                                                            <h3 className="font-medium text-gray-900 dark:text-white">{address.name}</h3>
                                                            <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                                {address.phone}
                                                            </p>
                                                        </div>
                                                        {address.is_default && (
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">
                                                                既定の住所
                                                            </span>
                                                        )}
                                                    </div>
                                                    
                                                    <div className="mt-4">
                                                        <p className="text-gray-900 dark:text-white">
                                                            {address.address_line1} {address.address_line2}
                                                        </p>
                                                        <p className="text-gray-900 dark:text-white">
                                                            {address.city}, {address.state} {address.zip}
                                                        </p>
                                                        <p className="text-gray-900 dark:text-white">
                                                            {address.country}
                                                        </p>
                                                    </div>
                                                    
                                                    <div className="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                                        登録日: {formatDate(address.created_at)}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <MapPin className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">住所がありません</h3>
                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        まだ住所が登録されていません。
                                    </p>
                                </div>
                            )}
                        </div>
                    </section>
                );
            case 'wishlist':
                const removeFromWishlist = (productId: number) => {
                    if (window.confirm('この商品をお気に入りから削除してもよろしいですか？')) {
                        // Use the same endpoint as the wishlist page: DELETE /wishlist/{product_id}
                        fetch(`/wishlist/${productId}`, {
                            method: 'DELETE',
                            headers: xsrfHeaders(),
                        })
                        .then(response => {
                            if (response.ok) {
                                // Refresh the dashboard data to update the wishlist
                                refreshDashboardData();
                            } else {
                                alert('お気に入りからの削除に失敗しました');
                            }
                        })
                        .catch(error => {
                            console.error('Error removing from wishlist:', error);
                            alert('お気に入りからの削除中にエラーが発生しました');
                        });
                    }
                };
                
                return (
                    <section className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <div className="mb-6">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">お気に入り</h2>
                            <p className="text-gray-600 dark:text-gray-400 mt-1">お気に入りに登録した商品を確認・削除します</p>
                        </div>
                        
                        <div className="mt-2">
                            {wishlistItems.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {wishlistItems.map((item) => (
                                        <div 
                                            key={item.id} 
                                            className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex flex-col hover:shadow-md transition-shadow duration-200"
                                        >
                                            <div className="relative">
                                                <button
                                                    onClick={() => removeFromWishlist(item.product_id)}
                                                    className="absolute top-2 right-2 p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 z-10"
                                                    title="お気に入りから削除"
                                                >
                                                    <Trash2 size={16} />
                                                </button>
                                                
                                                <div className="flex-1">
                                                    {item.product_image ? (
                                                        <img 
                                                            src={item.product_image} 
                                                            alt={item.product_name} 
                                                            className="w-full h-32 object-cover rounded mb-3"
                                                        />
                                                    ) : (
                                                        <div className="bg-gray-200 dark:bg-gray-700 border-2 border-dashed rounded-xl w-full h-32 mb-3" />
                                                    )}
                                                    <h3 className="font-medium text-gray-900 dark:text-white line-clamp-2">{item.product_name}</h3>
                                                    <p className="text-sm text-gray-600 dark:text-gray-300 mt-2">
                                                        {formatPrice(item.product_price)}
                                                    </p>
                                                </div>
                                                <div className="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                                    追加日: {formatDate(item.created_at)}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <Heart className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">お気に入りがありません</h3>
                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        まだお気に入りに登録された商品がありません。
                                    </p>
                                </div>
                            )}
                        </div>
                    </section>
                );
            case 'reviews':
                const startEditingReview = (review: Review) => {
                    setEditingReview({
                        id: review.id,
                        rating: review.rating,
                        body: review.body
                    });
                };
                
                const cancelEditingReview = () => {
                    setEditingReview(null);
                };
                
                const updateReview = (e: React.FormEvent, reviewId: number) => {
                    e.preventDefault();
                    if (!editingReview) return;
                    
                    // Update the review using the existing Review API
                    fetch(`/reviews/${reviewId}`, {
                        method: 'PUT',
                        headers: xsrfHeaders(),
                        body: JSON.stringify({
                            rating: editingReview.rating,
                            body: editingReview.body
                        })
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        } else if (response.status === 401 || response.status === 403) {
                            throw new Error('Unauthorized');
                        } else {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                    })
                    .then(data => {
                        // Refresh the dashboard data to update the reviews list
                        setEditingReview(null);
                        refreshDashboardData();
                    })
                    .catch(error => {
                        if (error.message === 'Unauthorized') {
                            alert('レビューの更新に失敗しました: 認証エラー');
                        } else {
                            console.error('Error updating review:', error);
                            alert('レビューの更新中にエラーが発生しました');
                        }
                    });
                };
                
                const deleteReview = (reviewId: number) => {
                    if (window.confirm('このレビューを削除してもよろしいですか？')) {
                        fetch(`/reviews/${reviewId}`, {
                            method: 'DELETE',
                            headers: xsrfHeaders(),
                        })
                        .then(response => {
                            if (response.ok) {
                                // Refresh the dashboard data to update the reviews list
                                refreshDashboardData();
                            } else if (response.status === 401 || response.status === 403) {
                                alert('レビューの削除に失敗しました: 認証エラー');
                            } else {
                                alert('レビューの削除に失敗しました');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting review:', error);
                            alert('レビューの削除中にエラーが発生しました');
                        });
                    }
                };
                
                return (
                    <section className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <div className="mb-6">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">レビュー</h2>
                            <p className="text-gray-600 dark:text-gray-400 mt-1">商品について投稿したレビューを確認・編集・削除します</p>
                        </div>
                        
                        <div className="mt-2">
                            {reviews.length > 0 ? (
                                <div className="space-y-4">
                                    {reviews.map((review) => (
                                        <div 
                                            key={review.id} 
                                            className="p-5 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150"
                                        >
                                            {editingReview && editingReview.id === review.id ? (
                                                // Edit mode
                                                <form onSubmit={(e) => updateReview(e, review.id)}>
                                                    <div className="mb-4">
                                                        <h3 className="font-medium text-gray-900 dark:text-white">{review.product_name}</h3>
                                                        <div className="flex items-center mt-2">
                                                            {[1, 2, 3, 4, 5].map((star) => (
                                                                <button
                                                                    key={star}
                                                                    type="button"
                                                                    onClick={() => setEditingReview({
                                                                        ...editingReview,
                                                                        rating: star
                                                                    })}
                                                                    className="focus:outline-none"
                                                                    key={star}
                                                                >
                                                                    <svg 
                                                                        className={`w-5 h-5 ${star <= editingReview.rating ? 'text-yellow-400' : 'text-gray-300'}`} 
                                                                        fill="currentColor" 
                                                                        viewBox="0 0 20 20"
                                                                    >
                                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                    </svg>
                                                                </button>
                                                            ))}
                                                        </div>
                                                    </div>
                                                    <div className="mb-4">
                                                        <textarea
                                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                                            value={editingReview.body}
                                                            onChange={(e) => setEditingReview({
                                                                ...editingReview,
                                                                body: e.target.value
                                                            })}
                                                            rows={3}
                                                        />
                                                    </div>
                                                    <div className="flex space-x-2">
                                                        <button
                                                            type="submit"
                                                            className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-1 px-3 rounded-md transition duration-150 ease-in-out"
                                                        >
                                                            更新
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={cancelEditingReview}
                                                            className="bg-gray-500 hover:bg-gray-600 text-white font-medium py-1 px-3 rounded-md transition duration-150 ease-in-out"
                                                        >
                                                            キャンセル
                                                        </button>
                                                    </div>
                                                </form>
                                            ) : (
                                                // View mode
                                                <>
                                                    <div className="flex justify-between items-start">
                                                        <div className="flex-1">
                                                            <h3 className="font-medium text-gray-900 dark:text-white">{review.product_name}</h3>
                                                            <div className="flex items-center mt-2">
                                                                {[...Array(5)].map((_, i) => (
                                                                    <svg 
                                                                        key={i} 
                                                                        className={`w-5 h-5 ${i < review.rating ? 'text-yellow-400' : 'text-gray-300'}`} 
                                                                        fill="currentColor" 
                                                                        viewBox="0 0 20 20"
                                                                    >
                                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                    </svg>
                                                                ))}
                                                            </div>
                                                            <p className="mt-3 text-gray-700 dark:text-gray-300">{review.body}</p>
                                                        </div>
                                                        <div className="ml-4 text-right">
                                                            <span className={`inline-block px-3 py-1 text-xs rounded-full ${
                                                                review.approved 
                                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
                                                                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                            }`}>
                                                                {review.approved ? '承認済み' : '未承認'}
                                                            </span>
                                                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                                投稿日: {formatDate(review.created_at)}
                                                            </p>
                                                            <div className="flex space-x-2 mt-2">
                                                                <button
                                                                    onClick={() => startEditingReview(review)}
                                                                    className="mt-2 p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                                    title="レビューを編集"
                                                                >
                                                                    <Edit3 size={16} />
                                                                </button>
                                                                <button
                                                                    onClick={() => deleteReview(review.id)}
                                                                    className="mt-2 p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                                    title="レビューを削除"
                                                                >
                                                                    <Trash2 size={16} />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <MessageCircle className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">レビューがありません</h3>
                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        まだ商品に対するレビューを投稿していません。
                                    </p>
                                </div>
                            )}
                        </div>
                    </section>
                );
            case 'overview':
            default:
                return (
                    <>
                        {/* Welcome Section */}
                        <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                {profile.name} さん、こんにちは
                            </h1>
                            <p className="text-gray-600 dark:text-gray-300 mt-2">
                                あなたのアカウントダッシュボードへようこそ
                            </p>
                        </div>

                        {/* Summary Cards */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-lg bg-blue-100 dark:bg-blue-900/50">
                                        <Package className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">注文数</h3>
                                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{orders.length}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-lg bg-green-100 dark:bg-green-900/50">
                                        <MapPin className="h-6 w-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">住所数</h3>
                                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{addresses.length}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-lg bg-pink-100 dark:bg-pink-900/50">
                                        <Heart className="h-6 w-6 text-pink-600 dark:text-pink-400" />
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">お気に入り</h3>
                                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{wishlistItems.length}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-lg bg-yellow-100 dark:bg-yellow-900/50">
                                        <MessageCircle className="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">レビュー</h3>
                                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{reviews.length}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Recent Orders */}
                        <section className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                            <div className="mb-6">
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-white">最近の注文</h2>
                                <p className="text-gray-600 dark:text-gray-400 mt-1">最新の注文履歴</p>
                            </div>
                            
                            <div className="mt-2">
                                {orders.length > 0 ? (
                                    <div className="space-y-4">
                                        {orders.slice(0, 3).map((order) => (
                                            <div key={order.id} className="p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                                <div className="flex justify-between items-center">
                                                    <div>
                                                        <h3 className="font-medium text-gray-900 dark:text-white">注文番号: {order.order_number}</h3>
                                                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                            {formatDate(order.created_at)}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center space-x-4">
                                                        <div className="text-right">
                                                            <p className="font-medium text-gray-900 dark:text-white">{formatPrice(order.total_yen)}</p>
                                                            <p className="text-sm text-gray-500 dark:text-gray-400">{order.items_count} 点</p>
                                                        </div>
                                                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${getOrderStatusColor(order.status)}`}>
                                                            {order.status}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <Package className="mx-auto h-12 w-12 text-gray-400" />
                                        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">注文がありません</h3>
                                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            注文履歴がまだありません。
                                        </p>
                                    </div>
                                )}
                            </div>
                        </section>
                    </>
                );
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="ダッシュボード" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto p-4 max-w-7xl mx-auto w-full">
                {renderActiveTab()}
            </div>
        </AppLayout>
    );
}