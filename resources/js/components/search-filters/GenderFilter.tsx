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
      <h3 className="mb-3 text-lg font-semibold text-black">性別</h3>
      <div className="flex flex-wrap gap-5">
        {genders.map((gender) => (
          <button
            key={gender.value}
            onClick={() => handleGenderClick(gender.value)}
            className={`h-12 flex items-center justify-center ${
              (currentFilters.gender || 'all') === gender.value
                ? 'border border-gray-400 text-black'
                : 'border border-[#AAB4C3] text-black'
            }`}
            style={{
              width: gender.value === 'all' ? '130px' : 
                     gender.value === 'men' ? '130px' : 
                     gender.value === 'women' ? '118px' : '106px'
            }}
          >
            <span className="text-xs text-black">{gender.label}</span>
          </button>
        ))}
      </div>
    </div>
  );
}