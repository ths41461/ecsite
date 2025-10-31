import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { HomeNavigation } from '@/components/homeNavigation';

export default function Contact() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    category: '',
    message: ''
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    
    // Simulate form submission
    setTimeout(() => {
      setIsSubmitting(false);
      setSubmitSuccess(true);
      setFormData({ name: '', email: '', phone: '', category: '', message: '' });
      
      // Reset success message after 5 seconds
      setTimeout(() => setSubmitSuccess(false), 5000);
    }, 1500);
  };

  const categories = [
    '商品について',
    '注文・配送について',
    '会員登録・ログインについて',
    'その他'
  ];

  return (
    <div className="min-h-screen bg-white">
      <Head title="お問い合わせ" />
      
      {/* Global Navigation */}
      <HomeNavigation />
      
      <div className="max-w-2xl mx-auto px-4 py-8">
        {/* Header */}
        <div className="text-center mb-10">
          <h1 className="text-3xl font-bold text-[#0D0D0D] mb-4">お問い合わせ</h1>
          <p className="text-[#444444]">
            ご質問・ご意見などございましたら、以下のお問い合わせフォームよりご連絡ください。
          </p>
        </div>

        {submitSuccess ? (
          <div className="bg-[#FCFCF7] border border-[#888888] p-6 text-center mb-8">
            <h2 className="text-xl font-medium text-[#0D0D0D] mb-2">お問い合わせを受け付けました</h2>
            <p className="text-[#444444]">
              お問い合わせいただきありがとうございます。
              通常1〜2営業日以内に返信いたします。
            </p>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label htmlFor="name" className="block text-sm font-medium text-[#0D0D0D] mb-2">
                お名前 <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="name"
                name="name"
                value={formData.name}
                onChange={handleChange}
                required
                className="w-full p-3 border border-[#888888] bg-white focus:outline-none focus:ring-1 focus:ring-[#0D0D0D]"
                placeholder="山田 太郎"
              />
            </div>

            <div>
              <label htmlFor="email" className="block text-sm font-medium text-[#0D0D0D] mb-2">
                メールアドレス <span className="text-red-500">*</span>
              </label>
              <input
                type="email"
                id="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                required
                className="w-full p-3 border border-[#888888] bg-white focus:outline-none focus:ring-1 focus:ring-[#0D0D0D]"
                placeholder="example@example.com"
              />
            </div>

            <div>
              <label htmlFor="phone" className="block text-sm font-medium text-[#0D0D0D] mb-2">
                電話番号
              </label>
              <input
                type="tel"
                id="phone"
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                className="w-full p-3 border border-[#888888] bg-white focus:outline-none focus:ring-1 focus:ring-[#0D0D0D]"
                placeholder="03-1234-5678"
              />
            </div>

            <div>
              <label htmlFor="category" className="block text-sm font-medium text-[#0D0D0D] mb-2">
                お問い合わせ種別 <span className="text-red-500">*</span>
              </label>
              <select
                id="category"
                name="category"
                value={formData.category}
                onChange={handleChange}
                required
                className="w-full p-3 border border-[#888888] bg-white focus:outline-none focus:ring-1 focus:ring-[#0D0D0D]"
              >
                <option value="">選択してください</option>
                {categories.map((cat, index) => (
                  <option key={index} value={cat}>{cat}</option>
                ))}
              </select>
            </div>

            <div>
              <label htmlFor="message" className="block text-sm font-medium text-[#0D0D0D] mb-2">
                お問い合わせ内容 <span className="text-red-500">*</span>
              </label>
              <textarea
                id="message"
                name="message"
                value={formData.message}
                onChange={handleChange}
                required
                rows={6}
                className="w-full p-3 border border-[#888888] bg-white focus:outline-none focus:ring-1 focus:ring-[#0D0D0D]"
                placeholder="お問い合わせ内容をご記入ください"
              ></textarea>
            </div>

            <div className="text-center">
              <button
                type="submit"
                disabled={isSubmitting}
                className="px-8 py-3 border border-[#888888] text-[#0D0D0D] hover:bg-gray-50 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSubmitting ? '送信中...' : '送信する'}
              </button>
            </div>
          </form>
        )}

        {/* Contact Info */}
        <div className="mt-12 border-t border-[#888888] pt-8">
          <h2 className="text-xl font-medium text-[#0D0D0D] mb-4">その他の連絡先</h2>
          
          <div className="space-y-4">
            <div>
              <h3 className="font-medium text-[#0D0D0D] mb-1">電話でのお問い合わせ</h3>
              <p className="text-[#444444]">03-0000-0000</p>
              <p className="text-sm text-[#444444]">受付時間：平日 10:00〜18:00</p>
            </div>
            
            <div>
              <h3 className="font-medium text-[#0D0D0D] mb-1">メールでのお問い合わせ</h3>
              <p className="text-[#444444]">contact@example.com</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}