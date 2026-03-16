import { Loader2, Send } from 'lucide-react';
import { KeyboardEvent, useState } from 'react';

type Props = {
    onSend: (message: string) => void;
    disabled?: boolean;
    placeholder?: string;
};

export default function ChatInput({ onSend, disabled = false, placeholder = 'メッセージを入力...' }: Props) {
    const [message, setMessage] = useState('');

    const handleSend = () => {
        const trimmed = message.trim();
        if (trimmed && !disabled) {
            onSend(trimmed);
            setMessage('');
        }
    };

    const handleKeyDown = (e: KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    return (
        <div className="flex items-end gap-2 rounded-2xl border border-[#888888] bg-white p-2">
            <textarea
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                onKeyDown={handleKeyDown}
                disabled={disabled}
                placeholder={placeholder}
                rows={1}
                className="max-h-32 min-h-[40px] flex-1 resize-none bg-transparent px-2 py-2 text-sm text-[#0D0D0D] placeholder:text-gray-400 focus:outline-none disabled:opacity-50"
            />
            <button
                onClick={handleSend}
                disabled={disabled || !message.trim()}
                className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-[#0D0D0D] text-white transition-colors hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {disabled ? <Loader2 className="h-5 w-5 animate-spin" /> : <Send className="h-5 w-5" />}
            </button>
        </div>
    );
}
