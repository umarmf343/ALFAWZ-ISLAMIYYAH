import React from 'react';
import { render, screen, waitFor, act } from '@testing-library/react';
import { describe, it, expect, vi, afterEach } from 'vitest';

import { AuthProvider, useAuth } from '../AuthContext';
import { api, type TokenMeta } from '@/lib/api';
import type { User } from '@/types/auth';

describe('AuthProvider', () => {
  const TestConsumer = () => {
    const { user, token, isAuthenticated, tokenExpiresAt, refreshExpiresAt } = useAuth();

    return (
      <>
        <span data-testid="user">{user?.email ?? 'none'}</span>
        <span data-testid="token">{token ?? 'none'}</span>
        <span data-testid="auth">{isAuthenticated ? 'true' : 'false'}</span>
        <span data-testid="token-expiry">{tokenExpiresAt ?? 'none'}</span>
        <span data-testid="refresh-expiry">{refreshExpiresAt ?? 'none'}</span>
      </>
    );
  };

  const renderWithProvider = () =>
    render(
      <AuthProvider>
        <TestConsumer />
      </AuthProvider>
    );

  const baseUser: User = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    role: 'student',
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
  };

  afterEach(() => {
    vi.restoreAllMocks();
    api.clearToken();
  });

  it('logs out when scheduled refresh fails', async () => {
    const expiresAt = new Date(Date.now() + 60_000).toISOString();
    const refreshExpiresAt = new Date(Date.now() + 3_600_000).toISOString();
    const meta: TokenMeta = { expiresAt, refreshExpiresAt };

    api.setToken('initial-token', meta);

    const getSpy = vi.spyOn(api, 'get').mockResolvedValue({ user: baseUser } as any);
    const refreshSpy = vi.spyOn(api, 'refreshAccessToken').mockResolvedValue(false);
    const clearSpy = vi.spyOn(api, 'clearToken');

    renderWithProvider();

    await waitFor(() => expect(getSpy).toHaveBeenCalled());
    await waitFor(() => expect(screen.getByTestId('user')).toHaveTextContent(baseUser.email));

    await act(async () => {
      await new Promise((resolve) => setTimeout(resolve, 0));
      await Promise.resolve();
    });

    await waitFor(() => expect(screen.getByTestId('auth')).toHaveTextContent('false'));
    expect(screen.getByTestId('user')).toHaveTextContent('none');
    expect(refreshSpy).toHaveBeenCalled();
    expect(clearSpy).toHaveBeenCalled();
  });

  it('updates auth state when token refresh event fires', async () => {
    const meta: TokenMeta = {
      expiresAt: new Date(Date.now() + 300_000).toISOString(),
      refreshExpiresAt: new Date(Date.now() + 3_600_000).toISOString(),
    };

    api.setToken('initial-token', meta);

    vi.spyOn(api, 'get').mockResolvedValue({ user: baseUser } as any);
    vi.spyOn(api, 'refreshAccessToken').mockResolvedValue(true);

    renderWithProvider();

    await waitFor(() => expect(screen.getByTestId('user')).toHaveTextContent(baseUser.email));
    expect(screen.getByTestId('token')).toHaveTextContent('initial-token');

    const refreshedUser: User = {
      ...baseUser,
      email: 'updated@example.com',
    };

    const newMeta: TokenMeta = {
      expiresAt: new Date(Date.now() + 600_000).toISOString(),
      refreshExpiresAt: new Date(Date.now() + 7_200_000).toISOString(),
    };

    // Access internal handler for testing purposes
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const internalApi = api as any;
    await act(async () => {
      internalApi.tokenRefreshedHandler?.('new-token', newMeta, refreshedUser);
    });

    expect(screen.getByTestId('token')).toHaveTextContent('new-token');
    expect(screen.getByTestId('user')).toHaveTextContent(refreshedUser.email);
    expect(screen.getByTestId('token-expiry')).toHaveTextContent(newMeta.expiresAt);
    expect(screen.getByTestId('refresh-expiry')).toHaveTextContent(newMeta.refreshExpiresAt);
  });
});
