import { Bot, User } from 'lucide-react';

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
    message: Message;
};

function yen(n: number) {
    return `¥${n.toLocaleString()}`;
}

export default function MessageBubble({ message }: Props) {
    const isUser = message.role === 'user';

    return (
        <div className={`flex gap-3 ${isUser ? 'flex-row-reverse' : 'flex-row'}`}>
            <div className={`flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full ${isUser ? 'bg-[#0D0D0D]' : 'bg-gray-200'}`}>
                {isUser ? <User className="h-4 w-4 text-white" /> : <Bot className="h-4 w-4 text-gray-600" />}
            </div>

            <div className={`flex max-w-[70%] flex-col ${isUser ? 'items-end' : 'items-start'}`}>
                <div
                    className={`rounded-2xl px-4 py-2 ${
                        isUser ? 'rounded-tr-none bg-[#0D0D0D] text-white' : 'rounded-tl-none bg-gray-100 text-[#0D0D0D]'
                    }`}
                >
                    <p className="text-sm whitespace-pre-wrap">{message.content}</p>
                </div>

                <span className="mt-1 text-xs text-gray-400">{message.timestamp}</span>

                {message.products && message.products.length > 0 && (
                    <div className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        {message.products.map((product) => (
                            <a
                                key={product.id}
                                href={`/products/${product.id}`}
                                className="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded bg-[#FAF7EF]">
                                    {product.imageUrl ? (
                                        <img src={product.imageUrl} alt={product.name} className="h-full w-full rounded object-cover" />
                                    ) : (
                                        <span className="text-lg">🌸</span>
                                    )}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-xs text-red-600">{product.brand}</p>
                                    <p className="truncate text-sm font-medium text-[#0D0D0D]">{product.name}</p>
                                    <p className="text-sm font-bold text-[#0D0D0D]">{yen(product.price)}</p>
                                </div>
                            </a>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
