import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { HomeNavigation } from '@/components/homeNavigation';

export default function FragranceDiagnosis() {
    const [step, setStep] = useState(1);
    const [answers, setAnswers] = useState<Record<string, string>>({});

    const handleAnswer = (questionId: string, answer: string) => {
        setAnswers((prev) => ({ ...prev, [questionId]: answer }));
    };

    const nextStep = () => {
        if (step < 5) {
            setStep(step + 1);
        }
    };

    const prevStep = () => {
        if (step > 1) {
            setStep(step - 1);
        }
    };

    const questions = [
        {
            id: 'q1',
            title: 'あなたの普段の印象は？',
            options: ['洗練された', 'カジュアル', 'エレガント', 'ナチュラル'],
        },
        {
            id: 'q2',
            title: '好む香りのタイプは？',
            options: ['フローラル', 'シトラス', 'ウッディ', 'スパイシー'],
        },
        {
            id: 'q3',
            title: '使用するシーンは？',
            options: ['デイリー', 'オフィス', 'デート', 'パーティー'],
        },
        {
            id: 'q4',
            title: '香りの強さは？',
            options: ['控えめ', '程よい', 'しっかり', '強め'],
        },
        {
            id: 'q5',
            title: '重視する要素は？',
            options: ['持続性', '価格', 'ブランド', '香りの印象'],
        },
    ];

    return (
        <div className="min-h-screen bg-white">
            <Head title="香り診断" />
            
            {/* Global Navigation */}
            <HomeNavigation />
            
            <div className="mx-auto max-w-4xl px-4 py-8">
                {/* Header */}
                <div className="mb-12 text-center">
                    <h1 className="mb-4 text-3xl font-bold text-[#0D0D0D]">香り診断</h1>
                    <p className="text-[#444444]">あなたに合う香りを見つけるための簡単な診断です</p>
                </div>

                {/* Progress */}
                <div className="mb-8">
                    <div className="mb-2 flex items-center justify-between">
                        <span className="text-sm text-[#444444]">質問 {step}/5</span>
                        <span className="text-sm text-[#444444]">{Math.round((step / 5) * 100)}%</span>
                    </div>
                    <div className="h-2 w-full rounded-full bg-gray-200">
                        <div
                            className="h-2 rounded-full bg-[#0D0D0D] transition-all duration-300"
                            style={{ width: `${(step / 5) * 100}%` }}
                        ></div>
                    </div>
                </div>

                {/* Question */}
                {step <= questions.length && (
                    <div className="mb-8 rounded-sm border border-[#888888] bg-[#FCFCF7] p-6">
                        <h2 className="mb-6 text-center text-xl font-medium text-[#0D0D0D]">{questions[step - 1].title}</h2>

                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                            {questions[step - 1].options.map((option, index) => (
                                <button
                                    key={index}
                                    onClick={() => handleAnswer(questions[step - 1].id, option)}
                                    className={`border border-[#888888] p-4 text-center transition-colors duration-200 ${
                                        answers[questions[step - 1].id] === option
                                            ? 'bg-[#0D0D0D] text-white'
                                            : 'bg-white text-[#0D0D0D] hover:bg-gray-50'
                                    }`}
                                >
                                    {option}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                {/* Navigation */}
                <div className="flex justify-between">
                    <button
                        onClick={prevStep}
                        disabled={step === 1}
                        className={`border border-[#888888] px-6 py-3 ${
                            step === 1 ? 'cursor-not-allowed text-[#888888]' : 'text-[#0D0D0D] hover:bg-gray-50'
                        }`}
                    >
                        戻る
                    </button>

                    {step < 5 ? (
                        <button
                            onClick={nextStep}
                            disabled={!answers[questions[step - 1].id]}
                            className={`border border-[#888888] px-6 py-3 ${
                                !answers[questions[step - 1].id] ? 'cursor-not-allowed text-[#888888]' : 'text-[#0D0D0D] hover:bg-gray-50'
                            }`}
                        >
                            次へ
                        </button>
                    ) : (
                        <button
                            onClick={() => alert('診断完了！あなたに合う香りを提案します。')}
                            className="border border-[#888888] px-6 py-3 text-[#0D0D0D] hover:bg-gray-50"
                        >
                            結果を見る
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
