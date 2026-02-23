import { MessageCircle, Minimize2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import ChatInput from './ChatInput';
import MessageBubble from './MessageBubble';

type Message = {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    timestamp: string;
    products?: Product[];
};

type Product = {
    id: number;
    name: string;
    brand: string;
    price: number;
    imageUrl?: string;
};

type Props = {
    sessionId: string;
    isOpen?: boolean;
    onClose?: () => void;
    initialMessages?: Message[];
};

const quickReplies = [
    { text: 'もっと甘い香りはありますか？', icon: '🍬' },
    { text: '予算内で一番おすすめは？', icon: '💰' },
    { text: 'プレゼント用に探しています', icon: '🎁' },
    { text: 'この香りの特徴は？', icon: '🌸' },
];

export default function ChatContainer({ sessionId, isOpen = false, onClose, initialMessages = [] }: Props) {
    const [messages, setMessages] = useState<Message[]>(initialMessages);
    const [isLoading, setIsLoading] = useState(false);
    const [isExpanded, setIsExpanded] = useState(isOpen);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const [error, setError] = useState<string | null>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    useEffect(() => {
        if (isOpen && messages.length === 0) {
            setMessages([
                {
                    id: 0,
                    role: 'assistant',
                    content:
                        'こんにちは！香りについてのご質問や、おすすめの香水についてお気軽にお聞きください。あなたにぴったりの香りを見つけるお手伝いをします。',
                    timestamp: new Date().toLocaleTimeString('ja-JP', {
                        hour: '2-digit',
                        minute: '2-digit',
                    }),
                },
            ]);
        }
    }, [isOpen]);

    const sendMessage = async (content: string) => {
        const userMessage: Message = {
            id: Date.now(),
            role: 'user',
            content,
            timestamp: new Date().toLocaleTimeString('ja-JP', {
                hour: '2-digit',
                minute: '2-digit',
            }),
        };

        setMessages((prev) => [...prev, userMessage]);
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/api/v1/ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    session_id: sessionId,
                    message: content,
                }),
            });

            if (!response.ok) {
                throw new Error('メッセージの送信に失敗しました');
            }

            const data = await response.json();

            const assistantMessage: Message = {
                id: Date.now() + 1,
                role: 'assistant',
                content: data.message || '申し訳ありません、エラーが発生しました。',
                timestamp: new Date().toLocaleTimeString('ja-JP', {
                    hour: '2-digit',
                    minute: '2-digit',
                }),
                products: data.products || [],
            };

            setMessages((prev) => [...prev, assistantMessage]);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'エラーが発生しました');
            const errorMessage: Message = {
                id: Date.now() + 1,
                role: 'assistant',
                content: '申し訳ありません、一時的なエラーが発生しました。もう一度お試しください。',
                timestamp: new Date().toLocaleTimeString('ja-JP', {
                    hour: '2-digit',
                    minute: '2-digit',
                }),
            };
            setMessages((prev) => [...prev, errorMessage]);
        } finally {
            setIsLoading(false);
        }
    };

    const handleQuickReply = (text: string) => {
        sendMessage(text);
    };

    if (!isOpen) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-end p-4 sm:relative sm:inset-auto">
            <div
                className={`flex flex-col rounded-2xl border border-[#888888] bg-white shadow-xl ${
                    isExpanded ? 'h-[600px] w-full sm:w-[400px]' : 'h-[500px] w-full sm:w-[360px]'
                }`}
            >
                <div className="flex items-center justify-between rounded-t-2xl border-b border-gray-100 bg-white px-4 py-3">
                    <div className="flex items-center gap-2">
                        <MessageCircle className="h-5 w-5 text-[#0D0D0D]" />
                        <h3 className="font-medium text-[#0D0D0D]">AIコンシェルジュ</h3>
                    </div>
                    <div className="flex items-center gap-1">
                        <button onClick={() => setIsExpanded(!isExpanded)} className="rounded-full p-1.5 hover:bg-gray-100">
                            <Minimize2 className="h-4 w-4 text-gray-500" />
                        </button>
                        {onClose && (
                            <button onClick={onClose} className="rounded-full p-1.5 hover:bg-gray-100">
                                <X className="h-4 w-4 text-gray-500" />
                            </button>
                        )}
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto p-4">
                    <div className="flex flex-col gap-4">
                        {messages.map((message) => (
                            <MessageBubble key={message.id} message={message} />
                        ))}
                        {isLoading && (
                            <div className="flex items-center gap-2 text-gray-400">
                                <div className="flex gap-1">
                                    <span className="h-2 w-2 animate-bounce rounded-full bg-gray-400"></span>
                                    <span className="h-2 w-2 animate-bounce rounded-full bg-gray-400" style={{ animationDelay: '0.1s' }}></span>
                                    <span className="h-2 w-2 animate-bounce rounded-full bg-gray-400" style={{ animationDelay: '0.2s' }}></span>
                                </div>
                                <span className="text-sm">入力中...</span>
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>
                </div>

                {messages.length === 1 && (
                    <div className="border-t border-gray-100 px-4 py-2">
                        <p className="mb-2 text-xs text-gray-500">よくある質問</p>
                        <div className="flex flex-wrap gap-2">
                            {quickReplies.map((reply, index) => (
                                <button
                                    key={index}
                                    onClick={() => handleQuickReply(reply.text)}
                                    disabled={isLoading}
                                    className="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs text-[#444444] transition-colors hover:border-[#0D0D0D] hover:bg-gray-50 disabled:opacity-50"
                                >
                                    {reply.icon} {reply.text}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                <div className="border-t border-gray-100 p-4">
                    <ChatInput onSend={sendMessage} disabled={isLoading} />
                    {error && <p className="mt-2 text-xs text-red-500">{error}</p>}
                </div>
            </div>
        </div>
    );
}
