import * as React from 'react';

import { cn } from '@/lib/utils';

export interface ProgressProps extends React.HTMLAttributes<HTMLDivElement> {
  value?: number;
  max?: number;
}

export const Progress = React.forwardRef<HTMLDivElement, ProgressProps>(
  ({ className, value = 0, max = 100, ...props }, ref) => {
    const safeMax = max <= 0 ? 100 : max;
    const clampedValue = Math.min(Math.max(value, 0), safeMax);
    const percentage = Math.min(Math.max((clampedValue / safeMax) * 100, 0), 100);

    return (
      <div
        ref={ref}
        role="progressbar"
        aria-valuemin={0}
        aria-valuemax={safeMax}
        aria-valuenow={clampedValue}
        className={cn('relative h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800', className)}
        {...props}
      >
        <div
          className="h-full bg-emerald-500 transition-all dark:bg-emerald-400"
          style={{ width: `${percentage}%` }}
        />
      </div>
    );
  },
);
Progress.displayName = 'Progress';

export default Progress;
