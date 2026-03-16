import { useState, useEffect } from 'react';

type RangeSliderProps = {
  min: number;
  max: number;
  value: [number, number];
  onChange: (value: [number, number]) => void;
  label: string;
};

export default function RangeSlider({ min, max, value, onChange, label }: RangeSliderProps) {
  const [localValue, setLocalValue] = useState<[number, number]>(value);

  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  const handleMinChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newMin = Math.min(Number(e.target.value), localValue[1] - 1);
    setLocalValue([newMin, localValue[1]]);
    onChange([newMin, localValue[1]]);
  };

  const handleMaxChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newMax = Math.max(Number(e.target.value), localValue[0] + 1);
    setLocalValue([localValue[0], newMax]);
    onChange([localValue[0], newMax]);
  };

  return (
    <div className="mb-6">
      <h3 className="mb-3 text-lg font-semibold">{label}</h3>
      <div className="mb-2 flex items-center justify-between">
        <span className="text-sm text-gray-600 dark:text-gray-400">
          {localValue[0]}ml
        </span>
        <span className="text-sm text-gray-600 dark:text-gray-400">
          {localValue[1]}ml
        </span>
      </div>
      <div className="relative mb-4">
        <input
          type="range"
          min={min}
          max={max}
          value={localValue[0]}
          onChange={handleMinChange}
          className="absolute h-1 w-full appearance-none bg-transparent p-0"
        />
        <input
          type="range"
          min={min}
          max={max}
          value={localValue[1]}
          onChange={handleMaxChange}
          className="absolute h-1 w-full appearance-none bg-transparent p-0"
        />
        <div className="relative h-1 rounded-full bg-gray-200 dark:bg-gray-700">
          <div
            className="absolute top-0 h-1 rounded-full bg-blue-600"
            style={{
              left: `${((localValue[0] - min) / (max - min)) * 100}%`,
              width: `${((localValue[1] - localValue[0]) / (max - min)) * 100}%`,
            }}
          />
        </div>
      </div>
      <div className="flex justify-between">
        <span className="text-xs text-gray-500">{min}ml</span>
        <span className="text-xs text-gray-500">{max}ml</span>
      </div>
    </div>
  );
}