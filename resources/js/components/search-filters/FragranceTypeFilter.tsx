type FragranceTypeFilterProps = {
  currentFilters: {
    fragranceType?: string[];
  };
  onFilterChange: (key: string, value: any) => void;
};

export default function FragranceTypeFilter({ currentFilters, onFilterChange }: FragranceTypeFilterProps) {
  const fragranceTypes = [
    { value: 'floral', label: 'フローラル' },
    { value: 'fresh', label: 'フレッシュ' },
    { value: 'fruity', label: 'フルーティ' },
    { value: 'herbal', label: 'ハーバル' },
    { value: 'sweet', label: 'スイート' },
    { value: 'citrus', label: 'シトラス' },
    { value: 'exotic', label: 'エキゾチック' },
    { value: 'woody', label: 'ウッディ' },
  ];

  const toggleFragranceType = (type: string) => {
    const currentTypes = currentFilters.fragranceType || [];
    const newTypes = currentTypes.includes(type)
      ? currentTypes.filter(t => t !== type)
      : [...currentTypes, type];
    
    onFilterChange('fragranceType', newTypes.length > 0 ? newTypes : undefined);
  };

  const isActive = (type: string) => {
    return (currentFilters.fragranceType || []).includes(type);
  };

  return (
    <div className="mb-6">
      <h3 className="mb-3 text-lg font-semibold text-black">香りタイプ</h3>
      <div className="space-y-1">
        {fragranceTypes.map((fragrance) => (
          <div key={fragrance.value} className="flex items-center gap-2">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={isActive(fragrance.value)}
                onChange={() => toggleFragranceType(fragrance.value)}
                className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm text-black">{fragrance.label}</span>
            </label>
          </div>
        ))}
      </div>
    </div>
  );
}