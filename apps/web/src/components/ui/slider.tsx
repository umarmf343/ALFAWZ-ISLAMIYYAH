import * as React from 'react';

import { cn } from '@/lib/utils';

export interface SliderProps
  extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'value' | 'defaultValue' | 'onChange'> {
  value?: number[];
  defaultValue?: number[];
  onValueChange?: (value: number[]) => void;
}

export const Slider = React.forwardRef<HTMLInputElement, SliderProps>(
  ({ className, value, defaultValue, onValueChange, min = 0, max = 100, step = 1, disabled, ...props }, ref) => {
    const [internalValue, setInternalValue] = React.useState<number>(
      () => value?.[0] ?? defaultValue?.[0] ?? (typeof min === 'number' ? Number(min) : 0),
    );

    React.useEffect(() => {
      if (value !== undefined && typeof value[0] === 'number') {
        setInternalValue(value[0]);
      }
    }, [value]);

    const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
      const nextValue = Number(event.target.value);
      if (value === undefined) {
        setInternalValue(nextValue);
      }
      onValueChange?.([nextValue]);
    };

    return (
      <input
        ref={ref}
        type="range"
        min={min}
        max={max}
        step={step}
        disabled={disabled}
        value={value?.[0] ?? internalValue}
        onChange={handleChange}
        className={cn(
          'h-2 w-full cursor-pointer appearance-none rounded-full bg-gray-200 accent-emerald-600 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-700',
          className,
        )}
        {...props}
      />
    );
  },
);
Slider.displayName = 'Slider';

export default Slider;
