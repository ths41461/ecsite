import { Head, usePage, router } from '@inertiajs/react';
import { type BreadcrumbItem, type DashboardData } from '@/types';
import { HomeNavigation } from '@/components/homeNavigation';
import { 
  Package, 
  MapPin, 
  Heart, 
  MessageCircle,
  PackageCheck,
  Trash2,
  Edit3,
  User,
  Mail,
  Lock,
  X
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { send } from '@/routes/verification';
import { Link } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';

// Define TypeScript interfaces for dashboard data
interface Review {
    id: number;
    product_name: string;
    rating: number;
    body: string;
    approved: boolean;
    created_at: string;
}

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
        href: '/dashboard',
    },
];

export default function Dashboard() {
    const { profile, orders, addresses, wishlistItems, reviews } = usePage<DashboardData>().props;
    const page = usePage();
    const mustVerifyEmail = profile.email_verified_at === null;
    const status = page.props.status as string | undefined;
    
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
                    <div className="max-w-4xl mx-auto">
                        {/* Profile Header */}
                        <div className="flex items-center border-b border-gray-200 pb-6 mb-8">
                            <div className="bg-gray-100 border-2 border-dashed rounded-xl w-16 h-16 flex items-center justify-center" />
                            <div className="ml-4">
                                <h1 className="font-['Hiragino_Mincho_ProN'] text-xl font-semibold text-gray-800">{profile.name}</h1>
                                <p className="text-gray-600">{profile.email}</p>
                            </div>
                        </div>

                        {/* Profile and Password Forms - Responsive Grid */}
                        <div className="grid grid-cols-1 gap-8">
                            {/* Profile Information Card */}
                            <div className="border border-gray-200 bg-[#FCFCF7] p-6">
                                <h2 className="font-['Hiragino_Mincho_ProN'] text-lg font-semibold text-gray-800 mb-4">プロフィール情報</h2>
                                <p className="text-gray-600 mb-6">お名前とメールアドレスを更新してください</p>
                                
                                <form 
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const formData = new FormData(e.currentTarget as HTMLFormElement);
                                        ProfileController.update({
                                            name: formData.get('name') as string,
                                            email: formData.get('email') as string,
                                        });
                                    }}
                                    className="space-y-4"
                                >
                                    <div className="flex flex-col sm:flex-row gap-4">
                                        <div className="flex-1">
                                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">お名前</label>
                                            <div className="relative">
                                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <User className="h-5 w-5 text-gray-400" />
                                                </div>
                                                <input
                                                    id="name"
                                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-gray-500 focus:border-gray-500"
                                                    defaultValue={profile.name}
                                                    name="name"
                                                    required
                                                    autoComplete="name"
                                                />
                                            </div>
                                        </div>

                                        <div className="flex-1">
                                            <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
                                            <div className="relative">
                                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <Mail className="h-5 w-5 text-gray-400" />
                                                </div>
                                                <input
                                                    id="email"
                                                    type="email"
                                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-gray-500 focus:border-gray-500"
                                                    defaultValue={profile.email}
                                                    name="email"
                                                    required
                                                    autoComplete="username"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    {mustVerifyEmail && (
                                        <div className="rounded-md bg-yellow-50 p-4 border border-yellow-200">
                                            <div className="flex">
                                                <div className="flex-shrink-0">
                                                    <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div className="ml-3">
                                                    <h3 className="text-sm font-medium text-yellow-800">メールアドレスの確認が必要です</h3>
                                                    <div className="mt-2 text-sm text-yellow-700">
                                                        <p>
                                                            メールアドレスが未確認です。{' '}
                                                            <button
                                                                onClick={() => router.post(send().url)}
                                                                className="font-medium text-yellow-800 underline hover:text-yellow-900"
                                                            >
                                                                こちらをクリックして確認メールを再送信してください。
                                                            </button>
                                                        </p>
                                                    </div>
                                                    {status === 'verification-link-sent' && (
                                                        <div className="mt-2 text-sm text-green-600">
                                                            新しい確認リンクがメールアドレスに送信されました。
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex justify-end">
                                        <button type="submit" className="bg-gray-800 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-900 transition-colors min-h-[44px]">
                                            変更を保存
                                        </button>
                                    </div>
                                </form>
                            </div>

                            {/* Password Update Card */}
                            <div className="border border-gray-200 bg-[#FCFCF7] p-6">
                                <h2 className="font-['Hiragino_Mincho_ProN'] text-lg font-semibold text-gray-800 mb-4">パスワードの更新</h2>
                                <p className="text-gray-600 mb-6">アカウントの安全性を保つため、長くてランダムなパスワードを使用してください</p>
                                
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
                                    className="space-y-4"
                                >
                                    <div className="flex flex-col sm:flex-row gap-4">
                                        <div className="flex-1">
                                            <label htmlFor="current_password" className="block text-sm font-medium text-gray-700 mb-1">現在のパスワード</label>
                                            <div className="relative">
                                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <Lock className="h-5 w-5 text-gray-400" />
                                                </div>
                                                <input
                                                    id="current_password"
                                                    name="current_password"
                                                    type="password"
                                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-gray-500 focus:border-gray-500"
                                                    autoComplete="current-password"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex flex-col sm:flex-row gap-4">
                                        <div className="flex-1">
                                            <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">新しいパスワード</label>
                                            <div className="relative">
                                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <Lock className="h-5 w-5 text-gray-400" />
                                                </div>
                                                <input
                                                    id="password"
                                                    name="password"
                                                    type="password"
                                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-gray-500 focus:border-gray-500"
                                                    autoComplete="new-password"
                                                />
                                            </div>
                                        </div>

                                        <div className="flex-1">
                                            <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 mb-1">パスワードの確認</label>
                                            <div className="relative">
                                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <Lock className="h-5 w-5 text-gray-400" />
                                                </div>
                                                <input
                                                    id="password_confirmation"
                                                    name="password_confirmation"
                                                    type="password"
                                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-gray-500 focus:border-gray-500"
                                                    autoComplete="new-password"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex justify-end">
                                        <button type="submit" className="bg-gray-800 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-900 transition-colors min-h-[44px]">
                                            パスワードを更新
                                        </button>
                                    </div>
                                </form>
                            </div>

                            {/* Account Deletion Card */}
                            <div className="border border-red-200 bg-[#FCFCF7] p-6">
                                <h2 className="font-['Hiragino_Mincho_ProN'] text-lg font-semibold text-gray-800 mb-4">アカウントの削除</h2>
                                <p className="text-gray-600 mb-6">アカウントを削除すると、すべてのデータが完全に削除されます。この操作は元に戻せません。</p>
                                <div className="flex justify-end">
                                    <button 
                                        onClick={() => {
                                            if (window.confirm('アカウントを削除してもよろしいですか？この操作は元に戻せません。')) {
                                                // Use the existing DeleteUser action if available, or call the delete endpoint directly
                                                fetch('/user', {
                                                    method: 'DELETE',
                                                    headers: xsrfHeaders(),
                                                }).then(response => {
                                                    if (response.ok) {
                                                        window.location.href = '/';
                                                    }
                                                });
                                            }
                                        }}
                                        className="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition-colors min-h-[44px]"
                                    >
                                        アカウントを削除
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                );
            case 'orders':
                return (
                    <div className="max-w-4xl mx-auto">
                        <h2 className="font-['Hiragino_Mincho_ProN'] text-lg font-semibold text-gray-800 mb-6">注文履歴</h2>
                        
                        <div className="mt-2">
                            {orders.length > 0 ? (
                                <div className="space-y-6">
                                    {orders.map((order) => (
                                        <div key={order.id} className="border border-gray-200 bg-[#FCFCF7] p-6">
                                            <div className="flex justify-between items-center mb-4">
                                                <h3 className="font-medium text-gray-900">注文番号: {order.order_number}</h3>
                                                <span className={`px-3 py-1 rounded-full text-xs font-medium ${getOrderStatusColor(order.status)}`}>
                                                    {order.status}
                                                </span>
                                            </div>
                                            
                                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm mb-6">
                                                <div>
                                                    <p className="text-gray-500">合計金額</p>
                                                    <p className="font-medium text-gray-900">{formatPrice(order.total_yen)}</p>
                                                </div>
                                                <div>
                                                    <p className="text-gray-500">商品数</p>
                                                    <p className="font-medium text-gray-900">{order.items_count} 点</p>
                                                </div>
                                                {order.coupon_code && order.coupon_discount_yen > 0 && (
                                                    <div>
                                                        <p className="text-gray-500">クーポン割引</p>
                                                        <p className="font-medium text-emerald-600">-{formatPrice(order.coupon_discount_yen)}</p>
                                                    </div>
                                                )}
                                                <div>
                                                    <p className="text-gray-500">注文日</p>
                                                    <p className="font-medium text-gray-900">{formatDate(order.created_at)}</p>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <p className="text-gray-500 mb-3">注文商品</p>
                                                <div className="space-y-3">
                                                    {order.items.map((item) => (
                                                        <div key={item.id} className="flex justify-between border-b border-gray-200 pb-3 last:border-0 last:pb-0">
                                                            <div>
                                                                <span className="text-gray-700">{item.product_name}</span>
                                                                <p className="text-xs text-gray-500">SKU: {item.product_sku} × {item.quantity}</p>
                                                            </div>
                                                            <div className="text-right">
                                                                <p className="font-medium text-gray-900">{formatPrice(item.price_yen)}</p>
                                                                <p className="text-xs text-gray-500">小計: {formatPrice(item.line_total_yen)}</p>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 border border-gray-200 bg-[#FCFCF7]">
                                    <Package className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">注文がありません</h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        注文履歴がまだありません。
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                );
            case 'addresses':
                return (
                    <div className="max-w-4xl mx-auto">
                        <h2 className="font-['Hiragino_Mincho_ProN'] text-lg font-semibold text-gray-800 mb-6">住所</h2>
                        
                        <div className="mt-2">
                            {addresses.length > 0 ? (
                                <div className="space-y-6">
                                    {addresses.map((address) => (
                                        <div 
                                            key={address.id} 
                                            className={`border ${
                                                address.is_default 
                                                    ? 'border-gray-800 bg-[#FCFCF7]' 
                                                    : 'border-gray-200 bg-[#FCFCF7]'
                                            } p-6`}
                                        >
                                            <div className="flex justify-between items-center mb-3">
                                                <h3 className="font-medium text-gray-900">{address.name}</h3>
                                                {address.is_default && (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        既定の住所
                                                    </span>
                                                )}
                                            </div>
                                            
                                            <div className="text-gray-900 mb-4">
                                                <p>{address.phone}</p>
                                                <p className="mt-1">
                                                    {address.address_line1} {address.address_line2}
                                                </p>
                                                <p>
                                                    {address.city}, {address.state} {address.zip}
                                                </p>
                                                <p>
                                                    {address.country}
                                                </p>
                                            </div>
                                            
                                            <div className="text-xs text-gray-500">
                                                登録日: {formatDate(address.created_at)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 border border-gray-200 bg-[#FCFCF7]">
                                    <MapPin className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">住所がありません</h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        まだ住所が登録されていません。
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
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
                    <div className="max-w-4xl mx-auto">
                        <h2 className="font-['Hiragino_Mincho_ProN'] text-lg font-semibold text-gray-800 mb-6">お気に入り</h2>
                        
                        <div className="mt-2">
                            {wishlistItems.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {wishlistItems.map((item) => (
                                        <div 
                                            key={item.id} 
                                            className="border border-gray-200 bg-[#FCFCF7] p-4 flex flex-col"
                                        >
                                            <div className="relative">
                                                <button
                                                    onClick={() => removeFromWishlist(item.product_id)}
                                                    className="absolute top-2 right-2 p-1 text-red-600 hover:text-red-800 z-10"
                                                    title="お気に入りから削除"
                                                >
                                                    <X size={16} />
                                                </button>
                                                
                                                <div className="flex-1 flex flex-col items-center text-center">
                                                    {item.product_image ? (
                                                        <img 
                                                            src={item.product_image} 
                                                            alt={item.product_name} 
                                                            className="w-full h-32 object-contain mb-3"
                                                        />
                                                    ) : (
                                                        <div className="bg-gray-100 border-2 border-dashed rounded-xl w-full h-32 mb-3 flex items-center justify-center">
                                                            <span className="text-gray-400 text-sm">画像なし</span>
                                                        </div>
                                                    )}
                                                    <h3 className="font-medium text-gray-900 line-clamp-2 mb-2">{item.product_name}</h3>
                                                    <p className="text-sm text-gray-600 mb-4">
                                                        {formatPrice(item.product_price)}
                                                    </p>
                                                    <button 
                                                        className="mt-auto border border-[#EEDDD4] bg-[#EAB308] px-4 py-2.5 text-sm font-medium text-white"
                                                        onClick={() => router.get(`/products/${item.product_id}`)}
                                                    >
                                                        詳細を見る
                                                    </button>
                                                </div>
                                                <div className="mt-4 text-xs text-gray-500">
                                                    追加日: {formatDate(item.created_at)}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 border border-gray-200 bg-[#FCFCF7]">
                                    <Heart className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">お気に入りがありません</h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        まだお気に入りに登録された商品がありません。
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
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
                    <div className="max-w-4xl mx-auto">
                        <h2 className="font-['Hiragino_Mincho_ProN'] text-lg font-semibold text-gray-800 mb-6">レビュー</h2>
                        
                        <div className="mt-2">
                            {reviews.length > 0 ? (
                                <div className="space-y-6">
                                    {reviews.map((review) => (
                                        <div 
                                            key={review.id} 
                                            className="border border-gray-200 bg-[#FCFCF7] p-6"
                                        >
                                            {editingReview && editingReview.id === review.id ? (
                                                // Edit mode
                                                <form onSubmit={(e) => updateReview(e, review.id)} className="space-y-4">
                                                    <div>
                                                        <h3 className="font-medium text-gray-900">{review.product_name}</h3>
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
                                                    <div>
                                                        <textarea
                                                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500"
                                                            value={editingReview.body}
                                                            onChange={(e) => setEditingReview({
                                                                ...editingReview,
                                                                body: e.target.value
                                                            })}
                                                            rows={3}
                                                        />
                                                    </div>
                                                    <div className="flex space-x-2 justify-end">
                                                        <button
                                                            type="button"
                                                            onClick={cancelEditingReview}
                                                            className="border border-gray-300 text-gray-700 px-3 py-1.5 rounded-md text-sm font-medium hover:bg-gray-100"
                                                        >
                                                            キャンセル
                                                        </button>
                                                        <button
                                                            type="submit"
                                                            className="bg-gray-800 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-gray-900"
                                                        >
                                                            更新
                                                        </button>
                                                    </div>
                                                </form>
                                            ) : (
                                                // View mode
                                                <>
                                                    <div className="flex justify-between">
                                                        <div className="flex-1">
                                                            <h3 className="font-medium text-gray-900">{review.product_name}</h3>
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
                                                            <p className="mt-3 text-gray-700">{review.body}</p>
                                                        </div>
                                                        <div className="ml-4 text-right">
                                                            <span className={`inline-block px-3 py-1 text-xs rounded-full ${
                                                                review.approved 
                                                                    ? 'bg-green-100 text-green-800' 
                                                                    : 'bg-yellow-100 text-yellow-800'
                                                            }`}>
                                                                {review.approved ? '承認済み' : '未承認'}
                                                            </span>
                                                            <p className="text-xs text-gray-500 mt-2">
                                                                投稿日: {formatDate(review.created_at)}
                                                            </p>
                                                            <div className="flex space-x-2 mt-3">
                                                                <button
                                                                    onClick={() => startEditingReview(review)}
                                                                    className="p-1 text-blue-600 hover:text-blue-800"
                                                                    title="レビューを編集"
                                                                >
                                                                    <Edit3 size={16} />
                                                                </button>
                                                                <button
                                                                    onClick={() => deleteReview(review.id)}
                                                                    className="p-1 text-red-600 hover:text-red-800"
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
                                <div className="text-center py-12 border border-gray-200 bg-[#FCFCF7]">
                                    <MessageCircle className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">レビューがありません</h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        まだ商品に対するレビューを投稿していません。
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                );
            case 'overview':
            default:
                return (
                    <div className="max-w-4xl mx-auto">
                        {/* Welcome Section */}
                        <div className="border-b border-gray-200 pb-6 mb-8">
                            <h1 className="font-['Hiragino_Mincho_ProN'] text-xl font-semibold text-gray-800">
                                {profile.name} さん、こんにちは
                            </h1>
                            <p className="text-gray-600 mt-2">
                                あなたのアカウントダッシュボードへようこそ
                            </p>
                        </div>

                        {/* Summary Cards */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <div className="border border-gray-200 bg-[#FCFCF7] p-4 text-center">
                                <div className="flex justify-center mb-3">
                                    <div className="bg-gray-100 p-3 rounded-full">
                                        <Package className="h-6 w-6 text-gray-600" />
                                    </div>
                                </div>
                                <h3 className="text-sm text-gray-600">注文数</h3>
                                <p className="text-2xl font-semibold text-gray-900">{orders.length}</p>
                            </div>
                            
                            <div className="border border-gray-200 bg-[#FCFCF7] p-4 text-center">
                                <div className="flex justify-center mb-3">
                                    <div className="bg-gray-100 p-3 rounded-full">
                                        <MapPin className="h-6 w-6 text-gray-600" />
                                    </div>
                                </div>
                                <h3 className="text-sm text-gray-600">住所数</h3>
                                <p className="text-2xl font-semibold text-gray-900">{addresses.length}</p>
                            </div>
                            
                            <div className="border border-gray-200 bg-[#FCFCF7] p-4 text-center">
                                <div className="flex justify-center mb-3">
                                    <div className="bg-gray-100 p-3 rounded-full">
                                        <Heart className="h-6 w-6 text-gray-600" />
                                    </div>
                                </div>
                                <h3 className="text-sm text-gray-600">お気に入り</h3>
                                <p className="text-2xl font-semibold text-gray-900">{wishlistItems.length}</p>
                            </div>
                            
                            <div className="border border-gray-200 bg-[#FCFCF7] p-4 text-center">
                                <div className="flex justify-center mb-3">
                                    <div className="bg-gray-100 p-3 rounded-full">
                                        <MessageCircle className="h-6 w-6 text-gray-600" />
                                    </div>
                                </div>
                                <h3 className="text-sm text-gray-600">レビュー</h3>
                                <p className="text-2xl font-semibold text-gray-900">{reviews.length}</p>
                            </div>
                        </div>

                        {/* Recent Orders */}
                        <div className="border-t border-gray-200 pt-8">
                            <div className="flex justify-between items-center mb-6">
                                <h2 className="font-['Hiragino_Mincho_ProN'] text-lg font-semibold text-gray-800">最近の注文</h2>
                                <button 
                                    onClick={() => setActiveTab('orders')}
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    すべて見る →
                                </button>
                            </div>
                            
                            <div className="mt-2">
                                {orders.length > 0 ? (
                                    <div className="space-y-4">
                                        {orders.slice(0, 3).map((order) => (
                                            <div key={order.id} className="border border-gray-200 bg-[#FCFCF7] p-5">
                                                <div className="flex justify-between items-center">
                                                    <div>
                                                        <h3 className="font-medium text-gray-900">注文番号: {order.order_number}</h3>
                                                        <p className="text-sm text-gray-500 mt-1">
                                                            {formatDate(order.created_at)}
                                                        </p>
                                                        <div className="mt-2">
                                                            <p className="text-sm text-gray-500">商品: {order.items.slice(0,2).map(item => item.product_name).join(', ')}</p>
                                                            {order.items.length > 2 && <span className="text-sm text-gray-500">...他{order.items.length - 2}点</span>}
                                                        </div>
                                                        {order.coupon_code && order.coupon_discount_yen > 0 && (
                                                            <div className="mt-1">
                                                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                                                    クーポン: {order.coupon_code} (-{formatPrice(order.coupon_discount_yen)})
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center space-x-4">
                                                        <div className="text-right">
                                                            <p className="font-medium text-gray-900">{formatPrice(order.total_yen)}</p>
                                                            <p className="text-sm text-gray-500">{order.items_count} 点</p>
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
                                    <div className="text-center py-8 border border-gray-200 bg-[#FCFCF7]">
                                        <Package className="mx-auto h-12 w-12 text-gray-400" />
                                        <h3 className="mt-2 text-sm font-medium text-gray-900">注文がありません</h3>
                                        <p className="mt-1 text-sm text-gray-500">
                                            注文履歴がまだありません。
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                );
        }
    };

    return (
        <>
            <HomeNavigation />
            <div className="min-h-screen bg-gray-50">
                <main className="py-8">
                    <div className="max-w-7xl mx-auto px-4">
                        {/* Tab Navigation */}
                        <div className="border-b border-gray-200 mb-8">
                            <nav className="-mb-px flex space-x-8 overflow-x-auto">
                                {[
                                    { id: 'overview', name: '概要', icon: Package },
                                    { id: 'profile', name: 'プロフィール', icon: User },
                                    { id: 'orders', name: '注文履歴', icon: Package },
                                    { id: 'addresses', name: '住所', icon: MapPin },
                                    { id: 'wishlist', name: 'お気に入り', icon: Heart },
                                    { id: 'reviews', name: 'レビュー', icon: MessageCircle },
                                ].map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => setActiveTab(tab.id)}
                                        className={`whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm ${
                                            activeTab === tab.id
                                                ? 'border-gray-800 text-gray-800'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                    >
                                        <div className="flex items-center">
                                            <tab.icon className="mr-2 h-4 w-4" />
                                            {tab.name}
                                        </div>
                                    </button>
                                ))}
                            </nav>
                        </div>
                        
                        {renderActiveTab()}
                    </div>
                </main>
            </div>
        </>
    );
}