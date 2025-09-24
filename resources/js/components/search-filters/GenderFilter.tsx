type GenderFilterProps = {
  currentFilters: {
    gender?: string;
  };
  onFilterChange: (key: string, value: any) => void;
};

export default function GenderFilter({ currentFilters, onFilterChange }: GenderFilterProps) {
  const genders = [
    { value: 'all', label: 'すべて' },
    { value: 'men', label: 'メンズ' },
    { value: 'women', label: 'レディース' },
    { value: 'unisex', label: 'ユニセックス' },
  ];

  const handleGenderClick = (gender: string) => {
    if (gender === 'all') {
      onFilterChange('gender', undefined);
    } else {
      onFilterChange('gender', gender);
    }
  };

  return (
    <div className="mb-6">
      <h3 className="mb-3 text-lg font-semibold">性別</h3>
      <div className="flex flex-wrap gap-2">
        {genders.map((gender) => (
          <button
            key={gender.value}
            onClick={() => handleGenderClick(gender.value)}
            className={`rounded-full px-3 py-1 text-sm transition-colors ${
              (currentFilters.gender || 'all') === gender.value
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
            }`}
          >
            {gender.label}
          </button>
        ))}
      </div>
    </div>
  );
}