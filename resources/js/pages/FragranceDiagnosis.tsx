import { HomeNavigation } from '@/components/homeNavigation';
import { Head, router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useState } from 'react';

type QuizAnswers = {
    personality: string;
    vibe: string;
    occasion: string[];
    style: string;
    budget: number;
    experience: string;
    season: string;
};

type Question = {
    id: keyof QuizAnswers;
    title: string;
    subtitle?: string;
    type: 'single' | 'multi' | 'budget';
    options: {
        value: string;
        label: string;
        icon?: string;
        description?: string;
    }[];
};

const questions: Question[] = [
    {
        id: 'personality',
        title: 'あなたの印象は？',
        subtitle: '一番近いものを選んでください',
        type: 'single',
        options: [
            { value: 'romantic', label: 'ロマンチック', icon: '💕', description: '夢見がちで女性らしい' },
            { value: 'energetic', label: '元気いっぱい', icon: '✨', description: '明るく活発なタイプ' },
            { value: 'cool', label: 'クール', icon: '🌙', description: '落ち着いていて大人っぽい' },
            { value: 'natural', label: 'ナチュラル', icon: '🌿', description: '素朴で飾らない雰囲気' },
        ],
    },
    {
        id: 'vibe',
        title: '好む香りのタイプは？',
        subtitle: '直感で選んでください',
        type: 'single',
        options: [
            { value: 'floral', label: 'フローラル', icon: '🌸', description: 'お花のような優しい香り' },
            { value: 'citrus', label: 'シトラス', icon: '🍋', description: 'みずみずしく爽やかな香り' },
            { value: 'vanilla', label: 'スイート', icon: '🍬', description: '甘くて温かみのある香り' },
            { value: 'woody', label: 'ウッディ', icon: '🌲', description: '落ち着いた大人の香り' },
            { value: 'ocean', label: 'オーシャン', icon: '🌊', description: '清涼感あるフレッシュな香り' },
        ],
    },
    {
        id: 'occasion',
        title: '使用するシーンは？',
        subtitle: '複数選択可能です',
        type: 'multi',
        options: [
            { value: 'daily', label: 'デイリー', icon: '☀️', description: '日常使い' },
            { value: 'date', label: 'デート', icon: '💝', description: '特別な人との時間' },
            { value: 'special', label: '特別な日', icon: '🎉', description: 'パーティー・イベント' },
            { value: 'work', label: '学校・お仕事', icon: '💼', description: 'フォーマルな場面' },
            { value: 'casual', label: 'カジュアル', icon: '👕', description: 'お出かけ・リラックス' },
        ],
    },
    {
        id: 'style',
        title: 'あなたのスタイルは？',
        subtitle: 'ファッションや雰囲気に合わせて',
        type: 'single',
        options: [
            { value: 'feminine', label: 'フェミニン', icon: '👗', description: '女性らしくエレガント' },
            { value: 'casual', label: 'カジュアル', icon: '👟', description: 'リラックスしたスタイル' },
            { value: 'chic', label: 'シック', icon: '🖤', description: '洗練されたモダン' },
            { value: 'natural', label: 'ナチュラル', icon: '🍃', description: 'シンプルで natural' },
        ],
    },
    {
        id: 'budget',
        title: '予算はどのくらい？',
        subtitle: '予算に合わせておすすめします',
        type: 'budget',
        options: [
            { value: '3000', label: '¥3,000以下', icon: '💰', description: '学生さんにも嬉しい価格' },
            { value: '5000', label: '¥3,000-5,000', icon: '💎', description: '手頃な価格帯' },
            { value: '8000', label: '¥5,000-8,000', icon: '✨', description: '少し贅沢に' },
            { value: '10000', label: '¥8,000以上', icon: '👑', description: '高級ラインも視野に' },
        ],
    },
    {
        id: 'experience',
        title: '香水の経験は？',
        subtitle: 'あなたのレベルに合わせて解説します',
        type: 'single',
        options: [
            { value: 'beginner', label: '初心者', icon: '🌱', description: '初めてでも安心' },
            { value: 'some', label: '少し経験あり', icon: '🌿', description: 'いくつか試したことがある' },
            { value: 'experienced', label: '慣れている', icon: '🌳', description: '好みが分かってきた' },
        ],
    },
    {
        id: 'season',
        title: '季節の好みは？',
        subtitle: 'あとで変えることもできます',
        type: 'single',
        options: [
            { value: 'spring', label: '春夏向け', icon: '🌷', description: '軽やかで爽やかな香り' },
            { value: 'fall', label: '秋冬向け', icon: '🍂', description: '深みのある温かい香り' },
            { value: 'all', label: 'オールシーズン', icon: '🌸', description: '一年中使える香り' },
        ],
    },
];

export default function FragranceDiagnosis() {
    const [step, setStep] = useState(1);
    const [answers, setAnswers] = useState<QuizAnswers>({
        personality: '',
        vibe: '',
        occasion: [],
        style: '',
        budget: 5000,
        experience: '',
        season: '',
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    const currentQuestion = questions[step - 1];
    const totalQuestions = questions.length;

    const handleSingleAnswer = (questionId: keyof QuizAnswers, value: string) => {
        setAnswers((prev) => ({ ...prev, [questionId]: value }));
    };

    const handleMultiAnswer = (value: string) => {
        setAnswers((prev) => {
            const current = prev.occasion;
            if (current.includes(value)) {
                return { ...prev, occasion: current.filter((v) => v !== value) };
            }
            return { ...prev, occasion: [...current, value] };
        });
    };

    const handleBudgetAnswer = (value: string) => {
        const budgetMap: Record<string, number> = {
            '3000': 3000,
            '5000': 5000,
            '8000': 8000,
            '10000': 10000,
        };
        setAnswers((prev) => ({ ...prev, budget: budgetMap[value] || 5000 }));
    };

    const nextStep = () => {
        if (step < totalQuestions) {
            setStep(step + 1);
        }
    };

    const prevStep = () => {
        if (step > 1) {
            setStep(step - 1);
        }
    };

    const canProceed = () => {
        const q = currentQuestion;
        if (q.type === 'multi') {
            return answers.occasion.length > 0;
        }
        return answers[q.id] !== '' && answers[q.id] !== 0;
    };

    const submitQuiz = async () => {
        setIsSubmitting(true);

        const params = new URLSearchParams();
        params.append('personality', answers.personality);
        params.append('vibe', answers.vibe);
        answers.occasion.forEach((o) => params.append('occasion[]', o));
        params.append('style', answers.style);
        params.append('budget', answers.budget.toString());
        params.append('experience', answers.experience);
        params.append('season', answers.season);

        router.visit(`/fragrance-diagnosis/results?${params.toString()}`);
    };

    const isOptionSelected = (value: string) => {
        const q = currentQuestion;
        if (q.type === 'multi') {
            return answers.occasion.includes(value);
        }
        if (q.type === 'budget') {
            const budgetMap: Record<string, number> = {
                '3000': 3000,
                '5000': 5000,
                '8000': 8000,
                '10000': 10000,
            };
            return answers.budget === budgetMap[value];
        }
        return answers[q.id] === value;
    };

    const handleOptionClick = (value: string) => {
        const q = currentQuestion;
        if (q.type === 'multi') {
            handleMultiAnswer(value);
        } else if (q.type === 'budget') {
            handleBudgetAnswer(value);
        } else {
            handleSingleAnswer(q.id, value);
        }
    };

    return (
        <div className="min-h-screen bg-white">
            <Head title="香り診断" />

            <HomeNavigation />

            <div className="mx-auto max-w-4xl px-4 py-8">
                <div className="mb-12 text-center">
                    <h1 className="mb-4 text-3xl font-bold text-[#0D0D0D]">香り診断</h1>
                    <p className="text-[#444444]">あなたにぴったりの香りを見つけるための7つの質問</p>
                </div>

                <div className="mb-8">
                    <div className="mb-2 flex items-center justify-between">
                        <span className="text-sm text-[#444444]">
                            質問 {step}/{totalQuestions}
                        </span>
                        <span className="text-sm text-[#444444]">{Math.round((step / totalQuestions) * 100)}%</span>
                    </div>
                    <div className="h-2 w-full rounded-full bg-gray-200">
                        <div
                            className="h-2 rounded-full bg-[#0D0D0D] transition-all duration-300"
                            style={{ width: `${(step / totalQuestions) * 100}%` }}
                        ></div>
                    </div>
                </div>

                {currentQuestion && (
                    <div className="mb-8 rounded-sm border border-[#888888] bg-[#FCFCF7] p-6">
                        <h2 className="mb-2 text-center text-xl font-medium text-[#0D0D0D]">{currentQuestion.title}</h2>
                        {currentQuestion.subtitle && <p className="mb-6 text-center text-sm text-[#444444]">{currentQuestion.subtitle}</p>}

                        <div
                            className={`grid gap-3 ${
                                currentQuestion.options.length === 5
                                    ? 'grid-cols-1 md:grid-cols-5'
                                    : currentQuestion.options.length === 3
                                      ? 'grid-cols-1 md:grid-cols-3'
                                      : 'grid-cols-1 md:grid-cols-2'
                            }`}
                        >
                            {currentQuestion.options.map((option) => (
                                <button
                                    key={option.value}
                                    onClick={() => handleOptionClick(option.value)}
                                    className={`flex flex-col items-center rounded-lg border-2 p-4 text-center transition-all duration-200 ${
                                        isOptionSelected(option.value)
                                            ? 'border-[#0D0D0D] bg-[#0D0D0D] text-white'
                                            : 'border-[#888888] bg-white text-[#0D0D0D] hover:border-gray-400 hover:bg-gray-50'
                                    }`}
                                >
                                    <span className="mb-2 text-2xl">{option.icon}</span>
                                    <span className="mb-1 font-medium">{option.label}</span>
                                    {option.description && (
                                        <span className={`text-xs ${isOptionSelected(option.value) ? 'text-gray-200' : 'text-[#888888]'}`}>
                                            {option.description}
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                <div className="flex justify-between">
                    <button
                        onClick={prevStep}
                        disabled={step === 1}
                        className={`border border-[#888888] px-6 py-3 transition-colors ${
                            step === 1 ? 'cursor-not-allowed text-[#888888]' : 'text-[#0D0D0D] hover:bg-gray-50'
                        }`}
                    >
                        戻る
                    </button>

                    {step < totalQuestions ? (
                        <button
                            onClick={nextStep}
                            disabled={!canProceed()}
                            className={`border border-[#888888] px-6 py-3 transition-colors ${
                                !canProceed() ? 'cursor-not-allowed text-[#888888]' : 'text-[#0D0D0D] hover:bg-gray-50'
                            }`}
                        >
                            次へ
                        </button>
                    ) : (
                        <button
                            onClick={submitQuiz}
                            disabled={!canProceed() || isSubmitting}
                            className="flex items-center gap-2 rounded-full bg-[#EAB308] px-8 py-3 font-medium text-white shadow-sm transition-colors hover:bg-yellow-600 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {isSubmitting ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    診断中...
                                </>
                            ) : (
                                '結果を見る'
                            )}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
