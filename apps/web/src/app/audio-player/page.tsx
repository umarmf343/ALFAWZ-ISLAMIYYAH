'use client';

import { ChangeEvent, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import Link from 'next/link';
import {
  AudioProgressRecord,
  AudioSurah,
  getAudioProgress,
  getAudioProgressList,
  getAudioSurahs,
  saveAudioProgress,
} from '@/lib/api';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';
import {
  Clock,
  Headphones,
  Loader2,
  Music4,
  Pause,
  Play,
  RotateCcw,
  SkipBack,
  SkipForward,
  Volume2,
  VolumeX,
} from 'lucide-react';

const PLAYBACK_RATES = [0.75, 1, 1.25, 1.5, 2];

const formatTime = (value: number) => {
  if (!Number.isFinite(value) || value < 0) {
    return '0:00';
  }

  const minutes = Math.floor(value / 60);
  const seconds = Math.floor(value % 60);
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
};

const calculateProgress = (position: number, total?: number | null) => {
  if (!total || total <= 0) {
    return 0;
  }
  return Math.min(100, (position / total) * 100);
};

export default function AudioPlayerPage() {
  const audioRef = useRef<HTMLAudioElement | null>(null);
  const activeSurahIdRef = useRef<number | null>(null);
  const pendingSeekRef = useRef<number | null>(null);
  const suppressAutoSaveRef = useRef(false);
  const lastSavedRef = useRef(0);
  const progressMapRef = useRef<Record<number, number>>({});

  const [surahs, setSurahs] = useState<AudioSurah[]>([]);
  const [currentSurahIndex, setCurrentSurahIndex] = useState(0);
  const [progressMap, setProgressMap] = useState<Record<number, number>>({});
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [volume, setVolume] = useState(0.85);
  const [playbackRate, setPlaybackRate] = useState(1);

  const currentSurah = surahs[currentSurahIndex] ?? null;

  const setProgressValue = useCallback((surahId: number, position: number) => {
    setProgressMap((prev) => {
      if (prev[surahId] === position) {
        progressMapRef.current = prev;
        return prev;
      }

      const next = { ...prev, [surahId]: position };
      progressMapRef.current = next;
      return next;
    });
  }, []);

  const persistProgress = useCallback(
    async (surahId: number, position: number, durationOverride?: number | null) => {
      if (!surahId) {
        return;
      }

      const resolvedDuration =
        durationOverride ??
        audioRef.current?.duration ??
        (activeSurahIdRef.current === surahId ? duration : undefined);

      try {
        const response = await saveAudioProgress({
          surah_id: surahId,
          position_seconds: position,
          duration_seconds: resolvedDuration ?? undefined,
        });

        const savedPosition = response.data?.position_seconds ?? position;
        setProgressValue(surahId, savedPosition);
      } catch (saveError) {
        console.error('Unable to save audio progress', saveError);
      }
    },
    [duration, setProgressValue]
  );

  const loadInitialData = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const [surahResponse, progressResponse] = await Promise.all([
        getAudioSurahs(),
        getAudioProgressList(),
      ]);

      const loadedSurahs = surahResponse.data?.surahs ?? [];
      const progressEntries = progressResponse.data?.progress ?? [];

      setSurahs(loadedSurahs);

      if (progressEntries.length > 0) {
        const mapped: Record<number, number> = {};
        progressEntries.forEach((entry: AudioProgressRecord) => {
          mapped[entry.surah_id] = entry.position_seconds;
        });
        progressMapRef.current = mapped;
        setProgressMap(mapped);

        const lastListenedId = progressEntries[0]?.surah_id;
        const resumeIndex = loadedSurahs.findIndex((surah) => surah.id === lastListenedId);
        if (resumeIndex >= 0) {
          setCurrentSurahIndex(resumeIndex);
        }
      } else {
        progressMapRef.current = {};
        setProgressMap({});
      }
    } catch (loadError) {
      console.error('Failed to load audio player data', loadError);
      setError('We could not load the audio lessons right now. Please try again shortly.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadInitialData();
  }, [loadInitialData]);

  useEffect(() => {
    const audio = audioRef.current;

    if (!audio) {
      return;
    }

    suppressAutoSaveRef.current = true;
    audio.pause();

    if (!currentSurah) {
      audio.removeAttribute('src');
      audio.load();
      activeSurahIdRef.current = null;
      setCurrentTime(0);
      setDuration(0);
      suppressAutoSaveRef.current = false;
      return;
    }

    audio.src = currentSurah.audio_url;
    audio.load();

    activeSurahIdRef.current = currentSurah.id;
    const savedPosition = progressMapRef.current[currentSurah.id] ?? 0;
    pendingSeekRef.current = savedPosition;
    lastSavedRef.current = savedPosition;
    setCurrentTime(savedPosition);
    setIsPlaying(false);
    setDuration(0);

    let cancelled = false;

    const fetchProgress = async () => {
      try {
        const response = await getAudioProgress(currentSurah.id);
        if (cancelled) {
          return;
        }
        const entry = response.data;
        const position = entry?.position_seconds ?? savedPosition;
        pendingSeekRef.current = position;
        lastSavedRef.current = position;
        setProgressValue(currentSurah.id, position);
        setCurrentTime(position);

        if (audio.readyState >= 1 && pendingSeekRef.current !== null) {
          try {
            audio.currentTime = pendingSeekRef.current;
            pendingSeekRef.current = null;
          } catch (seekError) {
            console.warn('Unable to set audio current time immediately', seekError);
          }
        }
      } catch (progressError) {
        console.error('Unable to fetch saved audio progress', progressError);
      } finally {
        if (!cancelled) {
          suppressAutoSaveRef.current = false;
        }
      }
    };

    fetchProgress();

    return () => {
      cancelled = true;
    };
  }, [currentSurah, setProgressValue]);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) {
      return;
    }
    audio.volume = volume;
  }, [volume]);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) {
      return;
    }
    audio.playbackRate = playbackRate;
  }, [playbackRate, currentSurah?.id]);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return undefined;
    }

    const handleBeforeUnload = () => {
      const element = audioRef.current;
      const surahId = activeSurahIdRef.current;
      if (element && surahId) {
        persistProgress(surahId, element.currentTime);
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
      handleBeforeUnload();
    };
  }, [persistProgress]);

  const handlePlayPause = async () => {
    const audio = audioRef.current;
    if (!audio || !currentSurah) {
      return;
    }

    try {
      if (isPlaying) {
        audio.pause();
      } else {
        await audio.play();
        setIsPlaying(true);
      }
    } catch (playError) {
      console.error('Audio playback failed', playError);
    }
  };

  const handleTimeUpdate = () => {
    const audio = audioRef.current;
    const surahId = activeSurahIdRef.current;
    if (!audio || !surahId) {
      return;
    }

    const time = audio.currentTime;
    setCurrentTime(time);
    setProgressValue(surahId, time);

    if (Math.abs(time - lastSavedRef.current) >= 15) {
      persistProgress(surahId, time);
      lastSavedRef.current = time;
    }
  };

  const handleSeek = (event: ChangeEvent<HTMLInputElement>) => {
    const audio = audioRef.current;
    if (!audio || !activeSurahIdRef.current) {
      return;
    }

    const value = Number(event.target.value);
    try {
      audio.currentTime = value;
      setCurrentTime(value);
      setProgressValue(activeSurahIdRef.current as number, value);
    } catch (seekError) {
      console.error('Unable to seek audio', seekError);
    }
  };

  const handleLoadedMetadata = () => {
    const audio = audioRef.current;
    if (!audio) {
      return;
    }

    const durationValue = Number.isFinite(audio.duration) ? audio.duration : 0;
    setDuration(durationValue);

    if (pendingSeekRef.current !== null) {
      try {
        audio.currentTime = pendingSeekRef.current;
        setCurrentTime(pendingSeekRef.current);
      } catch (seekError) {
        console.warn('Unable to seek after metadata load', seekError);
      } finally {
        pendingSeekRef.current = null;
      }
    }
  };

  const handlePause = () => {
    const audio = audioRef.current;
    const surahId = activeSurahIdRef.current;
    if (!audio || !surahId) {
      setIsPlaying(false);
      return;
    }

    setIsPlaying(false);

    if (suppressAutoSaveRef.current) {
      suppressAutoSaveRef.current = false;
      return;
    }

    const position = audio.currentTime;
    persistProgress(surahId, position);
    lastSavedRef.current = position;
  };

  const handlePlay = () => {
    setIsPlaying(true);
  };

  const handleEnded = () => {
    const audio = audioRef.current;
    const surahId = activeSurahIdRef.current;
    if (!audio || !surahId) {
      setIsPlaying(false);
      return;
    }

    const totalDuration = audio.duration || duration;
    persistProgress(surahId, totalDuration, totalDuration);
    lastSavedRef.current = totalDuration;
    setIsPlaying(false);

    if (surahs.length > currentSurahIndex + 1) {
      handleSelectSurah(currentSurahIndex + 1, { skipSave: true });
    }
  };

  const handleVolumeChange = (event: ChangeEvent<HTMLInputElement>) => {
    const value = Number(event.target.value);
    setVolume(value);
  };

  const toggleMute = () => {
    setVolume((prev) => (prev === 0 ? 0.85 : 0));
  };

  const handleRateChange = (rate: number) => {
    setPlaybackRate(rate);
  };

  const handleSelectSurah = (index: number, options: { skipSave?: boolean } = {}) => {
    if (index === currentSurahIndex || !surahs[index]) {
      return;
    }

    const audio = audioRef.current;
    const previousSurahId = activeSurahIdRef.current;

    if (!options.skipSave && audio && previousSurahId) {
      persistProgress(previousSurahId, audio.currentTime);
      lastSavedRef.current = audio.currentTime;
    }

    setCurrentSurahIndex(index);
  };

  const handleResetProgress = () => {
    const audio = audioRef.current;
    const surahId = activeSurahIdRef.current;
    if (!audio || !surahId) {
      return;
    }

    suppressAutoSaveRef.current = true;
    audio.currentTime = 0;
    setCurrentTime(0);
    setProgressValue(surahId, 0);
    persistProgress(surahId, 0, duration);
    lastSavedRef.current = 0;
    suppressAutoSaveRef.current = false;
  };

  const handlePrevious = () => {
    if (currentSurahIndex > 0) {
      handleSelectSurah(currentSurahIndex - 1);
    }
  };

  const handleNext = () => {
    if (currentSurahIndex < surahs.length - 1) {
      handleSelectSurah(currentSurahIndex + 1);
    }
  };

  const currentProgressPercent = useMemo(
    () => calculateProgress(currentTime, duration || currentSurah?.duration_seconds),
    [currentTime, currentSurah?.duration_seconds, duration]
  );

  const remainingTime = useMemo(() => {
    if (!duration) {
      return null;
    }
    return Math.max(duration - currentTime, 0);
  }, [currentTime, duration]);

  const seekLimit = duration || currentSurah?.duration_seconds || 0;
  const seekValue = Math.min(currentTime, seekLimit);
  const durationLabel = currentSurah?.duration_seconds
    ? formatTime(currentSurah.duration_seconds)
    : 'Length loading…';
  const remainingDisplay = remainingTime !== null
    ? `-${formatTime(remainingTime)}`
    : currentSurah?.duration_seconds
      ? `-${formatTime(Math.max((currentSurah.duration_seconds ?? 0) - currentTime, 0))}`
      : 'Loading…';

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50 px-4 py-10">
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-8">
        <div
          className={cn(
            'flex flex-col gap-4 border-b border-slate-200 pb-6',
            'md:flex-row md:items-center md:justify-between'
          )}
        >
          <div>
            <div className="flex items-center gap-3">
              <span className="rounded-full bg-indigo-100 p-3 text-indigo-600">
                <Headphones className="h-6 w-6" />
              </span>
              <div>
                <h1 className="text-3xl font-bold text-slate-900">Qur&apos;an Audio Journey</h1>
                <p className="text-slate-600">
                  Immerse yourself in recitations, track your progress,{' '}
                  and resume where you left off.
                </p>
              </div>
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            <Button
              asChild
              variant="ghost"
              className={cn('text-slate-600', 'hover:text-indigo-600')}
            >
              <Link href="/dashboard/student">Back to dashboard</Link>
            </Button>
            <Button
              variant="secondary"
              className={cn(
                'bg-indigo-500 text-white shadow-md shadow-indigo-200',
                'hover:bg-indigo-600'
              )}
              onClick={() => loadInitialData()}
            >
              Refresh list
            </Button>
          </div>
        </div>

        {error ? (
          <Card className="border-red-200 bg-red-50/60">
            <CardContent className="flex items-center gap-4 p-6">
              <Loader2 className="h-5 w-5 animate-spin text-red-500" />
              <div>
                <p className="font-semibold text-red-600">{error}</p>
                <p className="text-sm text-red-500">Try refreshing the page or come back later.</p>
              </div>
            </CardContent>
          </Card>
        ) : (
          <div className="grid gap-8 lg:grid-cols-[320px_1fr]">
            <Card className="h-fit border-indigo-100 bg-white/70 shadow-lg shadow-indigo-100">
              <CardHeader>
                <CardTitle className="flex items-center justify-between text-lg text-slate-800">
                  <span>Available Surahs</span>
                  <Badge className="bg-indigo-100 text-indigo-700">{surahs.length} tracks</Badge>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {isLoading ? (
                  <div className="flex flex-col items-center gap-3 py-10 text-slate-500">
                    <Loader2 className="h-6 w-6 animate-spin" />
                    <span>Loading your playlist…</span>
                  </div>
                ) : surahs.length === 0 ? (
                  <div className="rounded-lg bg-slate-100 p-4 text-center text-slate-600">
                    No audio lessons have been shared yet. Check back soon!
                  </div>
                ) : (
                  surahs.map((surah, index) => {
                    const listenedSeconds = progressMap[surah.id] ?? 0;
                    const estimatedTotal =
                      surah.duration_seconds ??
                      (surah.id === currentSurah?.id ? duration : undefined);
                    const percent = calculateProgress(listenedSeconds, estimatedTotal);
                    const remainingSeconds = Math.max(
                      (surah.duration_seconds ?? 0) - listenedSeconds,
                      0
                    );
                    const remainingLabel = surah.duration_seconds
                      ? `${formatTime(remainingSeconds)} remaining`
                      : 'Tap to continue';

                    return (
                        <button
                          key={surah.id}
                          type="button"
                          onClick={() => handleSelectSurah(index)}
                          className={cn(
                            'w-full rounded-xl border border-transparent bg-white p-4 text-left',
                            'shadow-sm transition-all',
                            'hover:-translate-y-[1px] hover:shadow-md',
                            index === currentSurahIndex
                              ? 'border-indigo-300 bg-indigo-50 shadow-indigo-100'
                              : 'hover:border-indigo-200'
                          )}
                        >
                        <div className="flex items-start justify-between gap-3">
                          <div>
                            <h3 className="text-base font-semibold text-slate-900">{surah.name}</h3>
                            {surah.arabic_name && (
                              <p className="text-sm text-indigo-600">{surah.arabic_name}</p>
                            )}
                          </div>
                            <Badge
                              variant="secondary"
                              className="bg-indigo-100 text-xs text-indigo-700"
                            >
                              {surah.reciter ?? 'Beautiful recitation'}
                            </Badge>
                        </div>
                        {surah.description && (
                          <p className="mt-2 text-sm text-slate-600">{surah.description}</p>
                        )}
                        <div className="mt-4 space-y-1">
                          <Progress value={percent} />
                          <div className="flex items-center justify-between text-xs text-slate-500">
                            <span>{formatTime(listenedSeconds)} listened</span>
                            <span>{remainingLabel}</span>
                          </div>
                        </div>
                      </button>
                    );
                  })
                )}
              </CardContent>
            </Card>

            <div className="space-y-6">
                <Card className="border-indigo-100 bg-white/80 shadow-xl shadow-indigo-100">
                  <CardHeader className="space-y-2">
                    <CardTitle className="text-2xl font-semibold text-slate-900">
                      {currentSurah ? currentSurah.name : 'Select a Surah'}
                    </CardTitle>
                  {currentSurah && (
                    <div className="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                      <span className="flex items-center gap-2">
                        <Music4 className="h-4 w-4 text-indigo-500" />
                        {currentSurah.reciter ?? 'Dedicated recitation'}
                      </span>
                        <span className="flex items-center gap-2">
                          <Clock className="h-4 w-4 text-indigo-500" />
                          {durationLabel}
                        </span>
                    </div>
                  )}
                </CardHeader>
                  <CardContent className="space-y-6">
                    <div
                      className={cn(
                        'rounded-2xl bg-gradient-to-r from-indigo-500 via-indigo-600 to-purple-600',
                        'p-6 text-white shadow-lg shadow-indigo-200'
                      )}
                    >
                        <div className="flex flex-wrap items-center justify-between gap-4">
                          <div className="space-y-1">
                            <p
                              className={cn(
                                'text-sm uppercase tracking-[0.2em]',
                                'text-indigo-100'
                              )}
                            >
                              Now Playing
                            </p>
                            <p className="text-xl font-semibold">
                              {currentSurah ? currentSurah.name : 'Choose a recitation'}
                            </p>
                        {currentSurah?.arabic_name && (
                          <p className="text-indigo-100">{currentSurah.arabic_name}</p>
                        )}
                      </div>
                        <div className="flex items-center gap-3">
                          <Button
                            type="button"
                            onClick={handlePrevious}
                            disabled={currentSurahIndex === 0}
                            variant="secondary"
                            className={cn(
                              'h-12 w-12 rounded-full bg-white/20 text-white',
                              'hover:bg-white/30 disabled:cursor-not-allowed disabled:opacity-40'
                            )}
                          >
                            <SkipBack className="h-5 w-5" />
                          </Button>
                          <Button
                            type="button"
                            onClick={handlePlayPause}
                            disabled={!currentSurah}
                            className={cn(
                              'h-14 w-14 rounded-full bg-white text-indigo-600',
                              'shadow-lg shadow-indigo-300',
                              'hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50'
                            )}
                          >
                            {isPlaying ? (
                              <Pause className="h-6 w-6" />
                            ) : (
                              <Play className="h-6 w-6" />
                            )}
                          </Button>
                          <Button
                            type="button"
                            onClick={handleNext}
                            disabled={currentSurahIndex >= surahs.length - 1}
                            variant="secondary"
                            className={cn(
                              'h-12 w-12 rounded-full bg-white/20 text-white',
                              'hover:bg-white/30 disabled:cursor-not-allowed disabled:opacity-40'
                            )}
                          >
                            <SkipForward className="h-5 w-5" />
                          </Button>
                        </div>
                      </div>

                      <div className="mt-6">
                        <input
                          type="range"
                          min={0}
                          max={seekLimit}
                          step={0.5}
                          value={seekValue}
                          onChange={handleSeek}
                          className="w-full accent-white"
                          disabled={!currentSurah}
                        />
                        <div
                          className={cn(
                            'mt-3 flex items-center justify-between text-xs',
                            'text-indigo-100'
                          )}
                        >
                          <span>{formatTime(currentTime)}</span>
                          <span>{remainingDisplay}</span>
                        </div>
                    </div>
                  </div>

                    <div
                      className={cn(
                        'grid gap-4 rounded-2xl border border-indigo-100 bg-white/80 p-5',
                        'shadow-inner shadow-indigo-50 md:grid-cols-3'
                      )}
                    >
                      <div className="flex flex-col gap-2">
                        <span
                          className={cn(
                            'text-xs font-semibold uppercase',
                            'tracking-widest text-slate-500'
                          )}
                        >
                          Volume
                        </span>
                        <div className="flex items-center gap-3">
                          <button
                            type="button"
                            onClick={toggleMute}
                            className={cn(
                              'rounded-full bg-indigo-50 p-2 text-indigo-500',
                              'hover:bg-indigo-100'
                            )}
                          >
                          {volume === 0 ? (
                            <VolumeX className="h-5 w-5" />
                          ) : (
                            <Volume2 className="h-5 w-5" />
                          )}
                          </button>
                        <input
                          type="range"
                          min={0}
                          max={1}
                          step={0.05}
                          value={volume}
                          onChange={handleVolumeChange}
                          className="w-full"
                        />
                      </div>
                    </div>
                      <div className="flex flex-col gap-2">
                        <span
                          className={cn(
                            'text-xs font-semibold uppercase',
                            'tracking-widest text-slate-500'
                          )}
                        >
                          Playback speed
                        </span>
                        <div className="flex flex-wrap gap-2">
                        {PLAYBACK_RATES.map((rate) => (
                          <button
                            key={rate}
                            type="button"
                            onClick={() => handleRateChange(rate)}
                            className={cn(
                              'rounded-full px-3 py-1 text-sm transition',
                              playbackRate === rate
                                ? 'bg-indigo-500 text-white shadow-md shadow-indigo-200'
                                : 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100'
                            )}
                          >
                            {rate.toFixed(2).replace(/\.00$/, '')}x
                          </button>
                        ))}
                      </div>
                    </div>
                      <div className="flex flex-col gap-2">
                        <span
                          className={cn(
                            'text-xs font-semibold uppercase',
                            'tracking-widest text-slate-500'
                          )}
                        >
                          Progress
                        </span>
                        <div className="rounded-xl bg-indigo-50 p-4 text-sm text-indigo-700">
                          <p className="font-semibold">
                            {currentProgressPercent.toFixed(0)}% complete
                          </p>
                          <p className="text-xs text-indigo-500">
                            Resume anytime — your place is saved automatically.
                          </p>
                          <Button
                            type="button"
                            variant="ghost"
                            className={cn(
                              'mt-3 flex items-center gap-2 px-0 text-indigo-600',
                              'hover:text-indigo-700'
                            )}
                            onClick={handleResetProgress}
                            disabled={!currentSurah}
                          >
                          <RotateCcw className="h-4 w-4" /> Reset position
                        </Button>
                      </div>
                    </div>
                  </div>

                  <audio
                    ref={audioRef}
                    hidden
                    onLoadedMetadata={handleLoadedMetadata}
                    onTimeUpdate={handleTimeUpdate}
                    onPlay={handlePlay}
                    onPause={handlePause}
                    onEnded={handleEnded}
                    preload="metadata"
                  />
                </CardContent>
              </Card>

              <Card className="border-slate-200 bg-white/70">
                <CardHeader>
                  <CardTitle className="text-lg text-slate-800">Listening tips</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-3 text-sm text-slate-600 md:grid-cols-3">
                    <div className="rounded-xl bg-slate-100 p-4">
                      <h3 className="font-semibold text-slate-800">Capture reflections</h3>
                      <p>
                        Pause after impactful ayat to note your reflections or vocabulary to review.
                      </p>
                    </div>
                    <div className="rounded-xl bg-slate-100 p-4">
                      <h3 className="font-semibold text-slate-800">Use speed mindfully</h3>
                      <p>
                        Slow down for tajweed revision and speed up when revising familiar sections.
                      </p>
                    </div>
                    <div className="rounded-xl bg-slate-100 p-4">
                      <h3 className="font-semibold text-slate-800">Stay consistent</h3>
                      <p>
                        Return daily to maintain your streak and strengthen memorisation.
                      </p>
                    </div>
                </CardContent>
              </Card>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
