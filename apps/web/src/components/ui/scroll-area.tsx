import * as React from 'react';

import { cn } from '@/lib/utils';

export type ScrollAreaProps = React.HTMLAttributes<HTMLDivElement>;

export const ScrollArea = React.forwardRef<HTMLDivElement, ScrollAreaProps>(({ className, ...props }, ref) => {
  return (
    <div
      ref={ref}
      className={cn('relative overflow-auto', className)}
      {...props}
    />
  );
});
ScrollArea.displayName = 'ScrollArea';

export default ScrollArea;
