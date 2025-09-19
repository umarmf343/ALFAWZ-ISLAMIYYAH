'use client';

import React, { useEffect, useMemo, useState } from 'react';
import {
  QueryClient,
  QueryClientProvider,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';
import { AnimatePresence, motion } from 'framer-motion';
import { formatDistanceToNow } from 'date-fns';
import { Mail, MailOpen, RefreshCw, Sparkles, WifiOff, CheckCircle2 } from 'lucide-react';

import { api } from '@/lib/api';
import { CACHE_KEYS, indexedDBService } from '@/lib/indexedDB';
import { cn } from '@/lib/utils';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const API_BASE = process.env.NEXT_PUBLIC_API_BASE || 'http://localhost:8000/api';

type Nullable<T> = T | null | undefined;

interface MessageSender {
  id: Nullable<number>;
  name: string;
  avatar_url?: Nullable<string>;
}

interface MessageAssignment {
  id: number;
  title: string;
  due_date?: Nullable<string>;
}

interface MessageSchedule {
  id: number;
  title: string;
  scheduled_for?: Nullable<string>;
}

export interface StudentMessage {
  id: number;
  title: string;
  subtitle?: Nullable<string>;
  body: string;
  sender: MessageSender;
  created_at?: Nullable<string>;
  read_at?: Nullable<string>;
  assignment?: Nullable<MessageAssignment>;
  schedule?: Nullable<MessageSchedule>;
  metadata?: Record<string, unknown> | null;
}

const MESSAGE_CACHE_TTL_MINUTES = 120;
const QUERY_KEY = ['student-messages'];

interface RawStudentMessage {
  id?: unknown;
  message_id?: unknown;
  title?: Nullable<string>;
  subtitle?: Nullable<string>;
  subject?: Nullable<string>;
  body?: Nullable<string>;
  content?: Nullable<string>;
  created_at?: Nullable<string>;
  sent_at?: Nullable<string>;
  read_at?: Nullable<string>;
  is_read?: boolean;
  updated_at?: Nullable<string>;
  metadata?: Record<string, unknown> | null;
  sender?: {
    id?: unknown;
    name?: Nullable<string>;
    avatar_url?: Nullable<string>;
  } | null;
  sender_id?: unknown;
  sender_name?: Nullable<string>;
  sender_avatar?: Nullable<string>;
  assignment?: {
    id?: unknown;
    title?: Nullable<string>;
    due_date?: Nullable<string>;
  } | null;
  assignment_id?: unknown;
  assignment_title?: Nullable<string>;
  assignment_due_date?: Nullable<string>;
  schedule?: {
    id?: unknown;
    title?: Nullable<string>;
    scheduled_for?: Nullable<string>;
  } | null;
  schedule_id?: unknown;
  schedule_title?: Nullable<string>;
  schedule_date?: Nullable<string>;
  data?: RawStudentMessage[];
}

const isRawMessageArray = (value: unknown): value is RawStudentMessage[] =>
  Array.isArray(value);

const isRawMessageCollection = (value: unknown): value is { data: RawStudentMessage[] } => {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const potential = value as { data?: unknown };
  return Array.isArray(potential.data);
};

function normalizeMessage(raw: RawStudentMessage): StudentMessage {
  const sender: MessageSender = {
    id: raw?.sender?.id ?? raw?.sender_id ?? null,
    name: raw?.sender?.name ?? raw?.sender_name ?? 'Teacher',
    avatar_url: raw?.sender?.avatar_url ?? raw?.sender_avatar ?? undefined,
  };

  const assignment: MessageAssignment | null = raw?.assignment || raw?.assignment_id
    ? {
        id: Number(raw?.assignment?.id ?? raw?.assignment_id ?? 0),
        title: raw?.assignment?.title ?? raw?.assignment_title ?? 'Assignment',
        due_date: raw?.assignment?.due_date ?? raw?.assignment_due_date ?? null,
      }
    : null;

  const schedule: MessageSchedule | null = raw?.schedule || raw?.schedule_id
    ? {
        id: Number(raw?.schedule?.id ?? raw?.schedule_id ?? 0),
        title: raw?.schedule?.title ?? raw?.schedule_title ?? 'Schedule',
        scheduled_for: raw?.schedule?.scheduled_for ?? raw?.schedule_date ?? null,
      }
    : null;

  return {
    id: Number(raw?.id ?? raw?.message_id ?? Date.now()),
    title: raw?.title ?? 'New Message',
    subtitle: raw?.subtitle ?? raw?.subject ?? '',
    body: raw?.body ?? raw?.content ?? '',
    sender,
    created_at: raw?.created_at ?? raw?.sent_at ?? new Date().toISOString(),
    read_at:
      raw?.read_at ??
      (raw?.is_read
        ? raw?.updated_at ?? new Date().toISOString()
        : null),
    assignment,
    schedule,
    metadata: raw?.metadata ?? null,
  };
}

async function fetchStudentMessages(): Promise<StudentMessage[]> {
  try {
    const response = await api.get<RawStudentMessage[] | { data: RawStudentMessage[] }>(
      '/student/messages',
    );
    const payload = response.data;

    const messagesArray: RawStudentMessage[] = isRawMessageArray(payload)
      ? payload
      : isRawMessageCollection(payload)
      ? payload.data
      : [];

    const normalized = messagesArray.map(normalizeMessage);
    await indexedDBService.setCache(
      CACHE_KEYS.STUDENT_MESSAGES,
      normalized,
      MESSAGE_CACHE_TTL_MINUTES,
    );
    return normalized;
  } catch (error) {
    const cached = await indexedDBService.getCache<StudentMessage[]>(
      CACHE_KEYS.STUDENT_MESSAGES,
    );
    if (cached) {
      return cached;
    }
    throw error;
  }
}

async function markStudentMessageRead(
  messageId: number,
): Promise<{ queued: boolean }> {
  const token =
    typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  try {
    const response = await fetch(
      `${API_BASE}/student/messages/${messageId}/read`,
      {
        method: 'POST',
        headers,
        body: JSON.stringify({}),
      },
    );

    if (!response.ok) {
      const message = await response.text();
      throw new Error(message || 'Failed to mark message as read');
    }

    return { queued: false };
  } catch (error) {
    if (error instanceof TypeError) {
      await indexedDBService.addToOfflineQueue(
        `${API_BASE}/student/messages/${messageId}/read`,
        'POST',
        JSON.stringify({}),
        headers,
      );
      return { queued: true };
    }
    throw error;
  }
}

function useMessageCacheWarmup() {
  const queryClient = useQueryClient();

  useEffect(() => {
    let isMounted = true;

    (async () => {
      try {
        const cached = await indexedDBService.getCache<StudentMessage[]>(
          CACHE_KEYS.STUDENT_MESSAGES,
        );
        if (cached && isMounted) {
          queryClient.setQueryData<StudentMessage[]>(
            QUERY_KEY,
            cached,
          );
        }
      } catch (error) {
        console.error('Failed to load cached messages:', error);
      }
    })();

    return () => {
      isMounted = false;
    };
  }, [queryClient]);
}

function MessageSectionContent() {
  const queryClient = useQueryClient();
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const [isOffline, setIsOffline] = useState<boolean>(
    typeof navigator !== 'undefined' ? !navigator.onLine : false,
  );

  useMessageCacheWarmup();

  const {
    data: messages = [],
    isLoading,
    isFetching,
    error,
    refetch,
  } = useQuery<StudentMessage[]>({
    queryKey: QUERY_KEY,
    queryFn: fetchStudentMessages,
    staleTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
  });

  const sortedMessages = useMemo(() => {
    return [...messages].sort((a, b) => {
      const aDate = a.created_at ? new Date(a.created_at).getTime() : 0;
      const bDate = b.created_at ? new Date(b.created_at).getTime() : 0;
      return bDate - aDate;
    });
  }, [messages]);

  useEffect(() => {
    if (!sortedMessages.length) {
      setSelectedId(null);
      return;
    }

    const hasSelected = sortedMessages.some((msg) => msg.id === selectedId);
    if (!hasSelected) {
      setSelectedId(sortedMessages[0].id);
    }
  }, [sortedMessages, selectedId]);

  useEffect(() => {
    const handleOnline = () => {
      setIsOffline(false);
      setStatusMessage('Alhamdulillah! You are back online. Refreshing messages.');
      refetch();
    };

    const handleOffline = () => {
      setIsOffline(true);
      setStatusMessage('You are offline. Showing your saved messages.');
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, [refetch]);

  useEffect(() => {
    if (!statusMessage) return;
    const timeout = window.setTimeout(() => setStatusMessage(null), 4000);
    return () => window.clearTimeout(timeout);
  }, [statusMessage]);

  const markAsRead = useMutation({
    mutationFn: markStudentMessageRead,
    onSuccess: async (result, messageId) => {
      queryClient.setQueryData<StudentMessage[]>(QUERY_KEY, (prev) => {
        if (!prev) return prev;
        const updated = prev.map((message) =>
          message.id === messageId
            ? {
                ...message,
                read_at: new Date().toISOString(),
              }
            : message,
        );
        void indexedDBService.setCache(
          CACHE_KEYS.STUDENT_MESSAGES,
          updated,
          MESSAGE_CACHE_TTL_MINUTES,
        );
        return updated;
      });

      setStatusMessage(
        result.queued
          ? 'Message acknowledged offline. We will sync your read receipt soon.'
          : 'MashaAllah! Message marked as read.',
      );
    },
    onError: (mutationError: unknown) => {
      const description =
        mutationError instanceof Error
          ? mutationError.message
          : 'Unable to mark message as read.';
      setStatusMessage(description);
    },
  });

  const unreadCount = useMemo(
    () => messages.filter((msg) => !msg.read_at).length,
    [messages],
  );

  const selectedMessage = sortedMessages.find((msg) => msg.id === selectedId) ?? null;

  const renderBody = (body: string) => {
    const paragraphs = body.split('\n').filter(Boolean);
    return paragraphs.length ? (
      <div className="space-y-3 text-sm leading-relaxed text-gray-700">
        {paragraphs.map((paragraph, index) => (
          <p key={index}>{paragraph}</p>
        ))}
      </div>
    ) : (
      <p className="text-sm text-gray-500">No additional details.</p>
    );
  };

  return (
    <Card className="h-full bg-gradient-to-br from-[#FAF7F2] via-white to-white/90 border border-gold-200 shadow-xl">
      <CardHeader className="pb-4">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <CardTitle className="flex items-center gap-2 text-2xl font-semibold text-maroon-800">
              <Sparkles className="h-6 w-6 text-gold-500" aria-hidden="true" />
              Message Center
            </CardTitle>
            <CardDescription className="text-sm text-maroon-600">
              Guidance and reminders from your teachers, beautifully curated for your Qur&apos;anic journey.
            </CardDescription>
          </div>
          <div className="flex items-center gap-2">
            <Badge
              variant="secondary"
              className={cn(
                'rounded-full bg-gold-100 px-3 py-1 text-xs font-semibold text-gold-700 shadow-sm',
                unreadCount === 0 && 'bg-emerald-100 text-emerald-700',
              )}
            >
              {unreadCount} unread
            </Badge>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => refetch()}
              className="border-gold-300 text-maroon-700 hover:bg-gold-50"
              aria-label="Refresh messages"
              disabled={isFetching}
            >
              <RefreshCw className={cn('mr-2 h-4 w-4', isFetching && 'animate-spin')} />
              Refresh
            </Button>
          </div>
        </div>

        {isOffline && (
          <motion.div
            initial={{ opacity: 0, y: -8 }}
            animate={{ opacity: 1, y: 0 }}
            className="mt-3 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"
          >
            <WifiOff className="h-4 w-4" aria-hidden="true" />
            Offline mode enabled. We will sync once you&apos;re connected again.
          </motion.div>
        )}

        <AnimatePresence>
          {statusMessage && (
            <motion.div
              key={statusMessage}
              initial={{ opacity: 0, y: -6 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -6 }}
              className="mt-3 flex items-center gap-2 rounded-xl border border-maroon-200 bg-maroon-50 px-3 py-2 text-sm text-maroon-700 shadow-sm"
            >
              <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
              {statusMessage}
            </motion.div>
          )}
        </AnimatePresence>
      </CardHeader>

      <CardContent className="pt-0">
        {isLoading ? (
          <div className="grid gap-6 md:grid-cols-[minmax(0,260px)_1fr]">
            <div className="space-y-3">
              {[0, 1, 2].map((item) => (
                <div
                  key={item}
                  className="h-20 animate-pulse rounded-2xl bg-gradient-to-r from-maroon-100/40 via-white to-gold-100/40"
                />
              ))}
            </div>
            <div className="h-64 animate-pulse rounded-3xl bg-gradient-to-br from-white via-maroon-50/60 to-gold-50" />
          </div>
        ) : error ? (
          <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
            Unable to load your messages at this time. Please try refreshing or check your connection.
          </div>
        ) : sortedMessages.length === 0 ? (
          <div className="flex flex-col items-center justify-center gap-4 rounded-3xl border border-dashed border-gold-300 bg-white/70 p-10 text-center text-maroon-700">
            <MailOpen className="h-10 w-10 text-gold-500" aria-hidden="true" />
            <div>
              <h3 className="text-lg font-semibold">No messages yet</h3>
              <p className="text-sm text-maroon-500">
                Your teachers will send guidance and reflections here. Keep reciting and stay tuned!
              </p>
            </div>
          </div>
        ) : (
          <div className="grid gap-6 md:grid-cols-[minmax(0,280px)_1fr]">
            <div className="max-h-[26rem] space-y-3 overflow-y-auto pr-1">
              <AnimatePresence>
                {sortedMessages.map((message) => {
                  const isSelected = message.id === selectedId;
                  const isUnread = !message.read_at;
                  return (
                    <motion.button
                      key={message.id}
                      layout
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, x: -10 }}
                      whileHover={{ scale: 1.01 }}
                      onClick={() => setSelectedId(message.id)}
                      className={cn(
                        'w-full rounded-2xl border p-4 text-left transition-all focus:outline-none focus:ring-2 focus:ring-maroon-400',
                        isSelected
                          ? 'border-transparent bg-gradient-to-r from-maroon-600 via-maroon-500 to-gold-500 text-white shadow-lg'
                          : isUnread
                          ? 'border-gold-200 bg-gradient-to-r from-gold-50 via-white to-gold-100 text-maroon-800 shadow-sm'
                          : 'border-maroon-100 bg-white/80 text-maroon-700 hover:border-maroon-300',
                      )}
                    >
                      <div className="flex items-start justify-between gap-3">
                        <div className="space-y-1">
                          <p className="text-sm font-semibold">
                            {message.title}
                          </p>
                          {message.subtitle && (
                            <p
                              className={cn(
                                'text-xs',
                                isSelected ? 'text-gold-100/90' : 'text-maroon-500',
                              )}
                            >
                              {message.subtitle}
                            </p>
                          )}
                        </div>
                        {isUnread ? (
                          <Badge className="flex items-center gap-1 rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-white">
                            <Mail className="h-3 w-3" aria-hidden="true" />
                            New
                          </Badge>
                        ) : (
                          <Badge className="rounded-full bg-white/20 px-2 py-0.5 text-[11px] text-white/80">
                            Read
                          </Badge>
                        )}
                      </div>
                      <p
                        className={cn(
                          'mt-3 line-clamp-2 text-xs',
                          isSelected ? 'text-white/90' : 'text-maroon-600/80',
                        )}
                      >
                        {message.body}
                      </p>
                      <p
                        className={cn(
                          'mt-4 text-[11px] font-medium',
                          isSelected ? 'text-white/70' : 'text-maroon-400',
                        )}
                      >
                        {formatDistanceToNow(
                          message.created_at ? new Date(message.created_at) : new Date(),
                          { addSuffix: true },
                        )}
                      </p>
                    </motion.button>
                  );
                })}
              </AnimatePresence>
            </div>

            <div className="rounded-3xl border border-maroon-100 bg-white/90 p-6 shadow-inner">
              {selectedMessage && (
                <div className="flex flex-col gap-4">
                  <div className="flex flex-wrap items-center gap-2">
                    <Badge className="rounded-full bg-maroon-600/90 px-3 py-1 text-xs font-semibold text-white shadow">
                      From {selectedMessage.sender.name}
                    </Badge>
                    {selectedMessage.assignment && (
                      <Badge className="rounded-full bg-gold-500/90 px-3 py-1 text-xs font-semibold text-white shadow">
                        Assignment: {selectedMessage.assignment.title}
                      </Badge>
                    )}
                    {selectedMessage.schedule && (
                      <Badge className="rounded-full bg-emerald-500/90 px-3 py-1 text-xs font-semibold text-white shadow">
                        Schedule: {selectedMessage.schedule.title}
                      </Badge>
                    )}
                  </div>

                  <div className="space-y-3">
                    <h3 className="text-xl font-semibold text-maroon-800">
                      {selectedMessage.title}
                    </h3>
                    {selectedMessage.subtitle && (
                      <p className="text-sm font-medium text-gold-700">
                        {selectedMessage.subtitle}
                      </p>
                    )}
                    <div className="rounded-2xl border border-gold-200/80 bg-white/70 p-5 shadow-sm">
                      {renderBody(selectedMessage.body)}
                    </div>
                  </div>

                  <div className="flex flex-wrap items-center gap-3 text-xs text-maroon-500">
                    <span>
                      Sent{' '}
                      {formatDistanceToNow(
                        selectedMessage.created_at
                          ? new Date(selectedMessage.created_at)
                          : new Date(),
                        { addSuffix: true },
                      )}
                    </span>
                    {selectedMessage.assignment?.due_date && (
                      <span>
                        Due by {new Date(selectedMessage.assignment.due_date).toLocaleDateString()}
                      </span>
                    )}
                    {selectedMessage.schedule?.scheduled_for && (
                      <span>
                        Scheduled for{' '}
                        {new Date(selectedMessage.schedule.scheduled_for).toLocaleString()}
                      </span>
                    )}
                  </div>

                  <div className="flex flex-wrap items-center gap-3">
                    <Button
                      type="button"
                      variant="outline"
                      className="border-maroon-300 bg-maroon-600/10 text-maroon-700 hover:bg-maroon-600/20"
                      onClick={() => markAsRead.mutate(selectedMessage.id)}
                      disabled={Boolean(selectedMessage.read_at) || markAsRead.isPending}
                    >
                      <MailOpen className="mr-2 h-4 w-4" aria-hidden="true" />
                      {selectedMessage.read_at ? 'Already read' : 'Mark as read'}
                    </Button>

                    <div className="text-xs text-maroon-500">
                      {selectedMessage.read_at
                        ? `Read ${formatDistanceToNow(new Date(selectedMessage.read_at), {
                            addSuffix: true,
                          })}`
                        : 'Gain barakah by acknowledging your teacher\'s reminder.'}
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export default function MessageSection() {
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            retry: 1,
            refetchOnReconnect: true,
          },
        },
      }),
  );

  return (
    <QueryClientProvider client={queryClient}>
      <MessageSectionContent />
    </QueryClientProvider>
  );
}
